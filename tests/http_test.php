<?php
/**
 * HTTP end-to-end test (CLI + cURL): percorre o stack real (index.php -> api.php -> controllers).
 * Executar: php .tmp_test/http_test.php
 */

$base = "http://localhost/secade-beauty-tarde";

$passed = 0; $failed = 0;
function check(string $label, bool $ok, string $extra = "") {
    global $passed, $failed;
    if ($ok) { $passed++; echo "  PASS  {$label}\n"; }
    else { $failed++; echo "  FAIL  {$label}  {$extra}\n"; }
}
function section(string $title) { echo "\n=== {$title} ===\n"; }

function jarPath(string $name): string {
    return sys_get_temp_dir() . "/sb_cookies_{$name}.txt";
}

function request(string $url, string $method = "GET", $body = null, ?string $jar = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    if ($jar !== null) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    }

    if ($method === "POST") {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        }
    }

    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headers = substr((string)$raw, 0, $headerSize);
    $content = substr((string)$raw, $headerSize);

    preg_match('/^Location:\s*(.+)$/mi', $headers, $locationMatch);

    return [
        "status"   => $status,
        "headers"  => $headers,
        "body"     => $content,
        "json"     => json_decode($content, true),
        "location" => trim($locationMatch[1] ?? "")
    ];
}

function nextWorkingDate(int $offsetDays = 1): string {
    $ts = strtotime("+{$offsetDays} day");
    while ((int)date("N", $ts) < 2 || (int)date("N", $ts) > 6) { $ts = strtotime("+1 day", $ts); }
    return date("Y-m-d", $ts);
}

foreach (["cliente", "gestor", "anon"] as $jarName) {
    @unlink(jarPath($jarName));
}

$clientJar  = jarPath("cliente");
$managerJar = jarPath("gestor");
$anonJar    = jarPath("anon");

// Limpeza dos dados de teste (torna a suite repetível — requer MySQL acessível)
try {
    $pdo = new PDO("mysql:host=localhost;dbname=secade_beauty", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("DELETE FROM agendamento WHERE cliente_id = 3");
    $pdo->exec("DELETE FROM cliente_morada WHERE cliente_id = 3 AND id > 1");
    $pdo->exec("DELETE FROM rota_ambulante");
    $pdo->exec("DELETE FROM alerta_fiscal");
    $pdo->exec("DELETE FROM obrigacao_fiscal");
    $pdo->exec("DELETE FROM config_recibo_verde WHERE id > 1");
} catch (Exception $e) {
    echo "AVISO: limpeza de dados falhou ({$e->getMessage()}). O resultado pode ser afetado.\n";
}

$bookingDate = nextWorkingDate(3);

// ---------------------------------------------------------------------------
section("1. Autenticação (HTTP)");
$login = request("{$base}/api?action=auth-login", "POST", [
    "email" => "cliente@teste.pt", "password" => "Cliente@123"
], $clientJar);
check("login do cliente devolve success", ($login["json"]["success"] ?? false) === true, json_encode($login["json"]));

$badLogin = request("{$base}/api?action=auth-login", "POST", [
    "email" => "cliente@teste.pt", "password" => "Errada@123"
], $anonJar);
check("login inválido devolve 422", $badLogin["status"] === 422, (string)$badLogin["status"]);

// ---------------------------------------------------------------------------
section("2. Rotas do site (main)");
$home = request("{$base}/");
check("home responde 200", $home["status"] === 200, (string)$home["status"]);

$catalog = request("{$base}/servicos/cabelereiro");
check("catálogo /servicos/cabelereiro responde 200", $catalog["status"] === 200, (string)$catalog["status"]);
check("catálogo contém contentor de serviços", str_contains($catalog["body"], "services-container"));
check("catálogo contém modal de detalhes", str_contains($catalog["body"], "serviceDetailsModal"));

$categories = request("{$base}/servicos");
check("/servicos responde 200", $categories["status"] === 200, (string)$categories["status"]);

$wizard = request("{$base}/agendar", "GET", null, $clientJar);
check("wizard /agendar responde 200 (cliente autenticado)", $wizard["status"] === 200, (string)$wizard["status"]);
check("wizard contém o formulário", str_contains($wizard["body"], "bookingWizardForm"));

foreach (["services", "address", "otp", "datetime", "policy", "summary"] as $step) {
    check("wizard tem passo '{$step}'", str_contains($wizard["body"], 'data-step="' . $step . '"'), "");
}

$profilePage = request("{$base}/perfil", "GET", null, $clientJar);
check("/perfil responde 200", $profilePage["status"] === 200, (string)$profilePage["status"]);
check("/perfil carrega componente JS", str_contains($profilePage["body"], "components/profile.js"));

$appointmentsPage = request("{$base}/agendamentos", "GET", null, $clientJar);
check("/agendamentos responde 200", $appointmentsPage["status"] === 200, (string)$appointmentsPage["status"]);
check("/agendamentos carrega componente JS", str_contains($appointmentsPage["body"], "components/appointments.js"));

// ---------------------------------------------------------------------------
section("3. APIs públicas/autenticadas (HTTP)");
$services = request("{$base}/api?action=booking-services", "GET", null, $clientJar);
check("booking-services devolve 35 serviços", count($services["json"]["services"] ?? []) === 35, json_encode($services["json"]["message"] ?? ""));

$cityList = request("{$base}/api?action=city-supported");
check("city-supported devolve 10 cidades", count($cityList["json"]["cities"] ?? []) === 10, (string)$cityList["status"]);

$wrongMethod = request("{$base}/api?action=booking-services", "POST", []);
check("método HTTP errado devolve 405", $wrongMethod["status"] === 405, (string)$wrongMethod["status"]);

$invalidEndpoint = request("{$base}/api?action=endpoint-inexistente");
check("endpoint inexistente devolve 404", $invalidEndpoint["status"] === 404, (string)$invalidEndpoint["status"]);

$profileApi = request("{$base}/api?action=customer-profile", "GET", null, $clientJar);
check("customer-profile devolve perfil", ($profileApi["json"]["profile"]["name"] ?? "") === "João Cliente", json_encode($profileApi["json"]));

$addressApi = request("{$base}/api?action=customer-address-list", "GET", null, $clientJar);
check("customer-address-list devolve moradas", count($addressApi["json"]["addresses"] ?? []) >= 1);

$anonProfile = request("{$base}/api?action=customer-profile", "GET", null, $anonJar);
check("API de cliente sem sessão devolve 401", $anonProfile["status"] === 401, (string)$anonProfile["status"]);

$anonAdmin = request("{$base}/api?action=admin-routes-list", "GET", null, $anonJar);
check("API de gestão sem sessão devolve 401", $anonAdmin["status"] === 401, (string)$anonAdmin["status"]);

$clientAdmin = request("{$base}/api?action=admin-routes-list", "GET", null, $clientJar);
check("API de gestão com perfil cliente devolve 403", $clientAdmin["status"] === 403, (string)$clientAdmin["status"]);

echo "\n--- Fase 1: {$passed} pass, {$failed} fail ---\n";

// ---------------------------------------------------------------------------
section("4. Fluxo completo via HTTP: carrinha + viabilidade");
$otp = request("{$base}/api?action=booking-otp-request", "POST", [], $clientJar);
check("OTP solicitado via HTTP", strlen((string)($otp["json"]["otpCode"] ?? "")) === 6, json_encode($otp["json"]));

$createAmb = request("{$base}/api?action=booking-create-amb", "POST", [
    "addressId" => 1,
    "otpCode"   => $otp["json"]["otpCode"] ?? "",
    "date"      => $bookingDate,
    "time"      => "09:30",
    "people"    => [["name" => "João Cliente", "serviceIds" => [28, 29]]]
], $clientJar);
check("agendamento ambulatório criado via HTTP", !empty($createAmb["json"]["bookingId"]), json_encode($createAmb["json"]));

$myBookings = request("{$base}/api?action=booking-my", "GET", null, $clientJar);
check("booking-my devolve o agendamento", count($myBookings["json"]["bookings"] ?? []) >= 1, "");

$managerLogin = request("{$base}/api?action=auth-login", "POST", [
    "email" => "gestor@secade.pt", "password" => "Gestor@123"
], $managerJar);
check("login do gestor", ($managerLogin["json"]["success"] ?? false) === true, json_encode($managerLogin["json"]));

$employeeJar = jarPath("funcionario");
@unlink($employeeJar);
$employeeLogin = request("{$base}/api?action=auth-login", "POST", [
    "email" => "funcionario@secade.pt", "password" => "Func@12345"
], $employeeJar);
check("login do funcionario", ($employeeLogin["json"]["success"] ?? false) === true, json_encode($employeeLogin["json"]));

$routesList = request("{$base}/api?action=admin-routes-list&date={$bookingDate}", "GET", null, $managerJar);
check("admin-routes-list devolve candidatas", count($routesList["json"]["routes"] ?? []) >= 1, json_encode($routesList["json"]));
check("rotas em modo de decisao manual", ($routesList["json"]["decisionMode"] ?? null) === "manual", json_encode($routesList["json"]["decisionMode"] ?? null));
check("indicador de referencia = 50 EUR", (float)($routesList["json"]["referenceProfitability"] ?? 0) === 50.0, json_encode($routesList["json"]["referenceProfitability"] ?? null));

$routeRow = $routesList["json"]["routes"][0] ?? [];
$decide = request("{$base}/api?action=admin-route-decide", "POST", [
    "cityId"   => $routeRow["cityId"] ?? 0,
    "date"     => $routeRow["routeDate"] ?? $bookingDate,
    "decision" => "aprovada"
], $managerJar);
check("admin-route-decide APROVA manualmente", ($decide["json"]["status"] ?? "") === "aprovada", json_encode($decide["json"]));

$employeeDecide = request("{$base}/api?action=admin-route-decide", "POST", [
    "cityId" => 1, "date" => $bookingDate, "decision" => "aprovada"
], $employeeJar);
check("funcionario NAO pode decidir rotas (403)", $employeeDecide["status"] === 403, (string)$employeeDecide["status"]);

$appointmentsList = request("{$base}/api?action=admin-appointments-list&perPage=20", "GET", null, $managerJar);
check("admin-appointments-list devolve agendamentos", ($appointmentsList["json"]["total"] ?? 0) >= 1, json_encode($appointmentsList["json"]["total"] ?? null));
check("listagem inclui nome do cliente", ($appointmentsList["json"]["bookings"][0]["customerName"] ?? "") === "João Cliente", json_encode($appointmentsList["json"]["bookings"][0] ?? []));

$anyBookingId = (int)($appointmentsList["json"]["bookings"][0]["id"] ?? 0);
$details = request("{$base}/api?action=admin-appointment-details&bookingId={$anyBookingId}", "GET", null, $managerJar);
check("admin-appointment-details devolve servicos + progresso", count($details["json"]["services"] ?? []) >= 1 && isset($details["json"]["progress"]), json_encode(array_keys($details["json"] ?? [])));

$clientDetails = request("{$base}/api?action=admin-appointment-details&bookingId={$anyBookingId}", "GET", null, $clientJar);
check("detalhe bloqueado ao cliente (403)", $clientDetails["status"] === 403, (string)$clientDetails["status"]);

$filtered = request("{$base}/api?action=admin-appointments-list&local=carrinha_ambulante", "GET", null, $managerJar);
check("filtro por local devolve resultado", ($filtered["json"]["total"] ?? 0) >= 1, json_encode($filtered["json"]["total"] ?? null));

// Agendamento em loja criado por HTTP (serve o filtro por estado e o cancelamento)
$storeBooking = request("{$base}/api?action=booking-create-store", "POST", [
    "serviceIds" => [30],
    "date"       => $bookingDate,
    "time"       => "15:00"
], $clientJar);
check("agendamento em loja criado via HTTP", !empty($storeBooking["json"]["bookingId"]), json_encode($storeBooking["json"]));

$statusFiltered = request("{$base}/api?action=admin-appointments-list&status=pendente_validacao_logistica_loja", "GET", null, $managerJar);
check("filtro por estado devolve resultado", ($statusFiltered["json"]["total"] ?? 0) >= 1, json_encode($statusFiltered["json"]["total"] ?? null));

$cancelId = (int)($storeBooking["json"]["bookingId"] ?? 0);
$cancel = request("{$base}/api?action=admin-appointment-cancel", "POST", ["bookingId" => $cancelId], $managerJar);
check("admin-appointment-cancel funciona", ($cancel["json"]["success"] ?? false) === true, json_encode($cancel["json"]));

$cancelAgain = request("{$base}/api?action=admin-appointment-cancel", "POST", ["bookingId" => $cancelId], $managerJar);
check("cancelar duas vezes devolve 409", $cancelAgain["status"] === 409, (string)$cancelAgain["status"]);

$cancelMissing = request("{$base}/api?action=admin-appointment-cancel", "POST", ["bookingId" => 999999], $managerJar);
check("cancelar id inexistente devolve 404", $cancelMissing["status"] === 404, (string)$cancelMissing["status"]);

// ---------------------------------------------------------------------------
section("5. Páginas do backoffice (HTTP)");
$boAppointments = request("{$base}/gestao/agendamentos", "GET", null, $managerJar);
check("GET /gestao/agendamentos responde 200", $boAppointments["status"] === 200, (string)$boAppointments["status"]);
check("página de agendamentos contém tabela", str_contains($boAppointments["body"], "appointmentsTableBody"));
check("página de agendamentos carrega JS", str_contains($boAppointments["body"], "components/appointments.js"));

$boRoutes = request("{$base}/gestao/rotas", "GET", null, $managerJar);
check("GET /gestao/rotas responde 200", $boRoutes["status"] === 200, (string)$boRoutes["status"]);
check("página de rotas tem decisão manual (aprovar/recusar)", str_contains($boRoutes["body"], "data-decide") || str_contains($boRoutes["body"], "Decisão manual"));
check("página de rotas carrega JS", str_contains($boRoutes["body"], "components/routes.js"));

$boAsClient = request("{$base}/gestao/agendamentos", "GET", null, $clientJar);
check("cliente é redirecionado fora do backoffice", $boAsClient["status"] === 302, (string)$boAsClient["status"] . " " . $boAsClient["location"]);

$boAnon = request("{$base}/gestao/agendamentos", "GET", null, $anonJar);
check("anónimo é redirecionado para login", $boAnon["status"] === 302 && str_contains($boAnon["location"], "login"), (string)$boAnon["status"] . " " . $boAnon["location"]);

$wizardAnon = request("{$base}/agendar", "GET", null, $anonJar);
check("anónimo é redirecionado do wizard para login", $wizardAnon["status"] === 302 && str_contains($wizardAnon["location"], "login"), (string)$wizardAnon["status"] . " " . $wizardAnon["location"]);

$successPage = request("{$base}/agendamento-sucesso?id={$createAmb["json"]["bookingId"]}&local=carrinha_ambulante", "GET", null, $clientJar);
check("página de sucesso responde 200", $successPage["status"] === 200, (string)$successPage["status"]);
check("página de sucesso menciona estado pendente", str_contains($successPage["body"], "pendente"));

$notFound = request("{$base}/rota-que-nao-existe");
check("rota inexistente devolve 404", $notFound["status"] === 404, (string)$notFound["status"]);

// ---------------------------------------------------------------------------
section("6. Fase 3/4 — aceitação, fiscal e recibos verdes (HTTP)");

$pending = request("{$base}/api?action=admin-service-pending-list", "GET", null, $employeeJar);
check("admin-service-pending-list (funcionario) 200", $pending["status"] === 200, (string)$pending["status"]);
check("pendentes devolvem categorias e config", isset($pending["json"]["categories"], $pending["json"]["config"]), json_encode(array_keys($pending["json"] ?? [])));

$accepted = request("{$base}/api?action=admin-service-accepted-list", "GET", null, $employeeJar);
check("admin-service-accepted-list 200", $accepted["status"] === 200, (string)$accepted["status"]);

$managerPending = request("{$base}/api?action=admin-service-pending-list", "GET", null, $managerJar);
check("gestor NAO acede a lista de aceitacao (403)", $managerPending["status"] === 403, (string)$managerPending["status"]);

$clientAccept = request("{$base}/api?action=admin-service-accept", "POST", ["bookingServiceId" => 1], $clientJar);
check("cliente NAO pode aceitar servicos (403)", $clientAccept["status"] === 403, (string)$clientAccept["status"]);

$calendarHttp = request("{$base}/api?action=admin-fiscal-calendar-list", "GET", null, $managerJar);
check("admin-fiscal-calendar-list 200", $calendarHttp["status"] === 200, (string)$calendarHttp["status"]);
check("calendario devolve resumo", isset($calendarHttp["json"]["summary"]["today"]), json_encode(array_keys($calendarHttp["json"]["summary"] ?? [])));

$fiscalCreated = request("{$base}/api?action=admin-fiscal-obligation-create", "POST", [
    "type" => "iva", "name" => "IVA HTTP (teste)", "periodicity" => "trimestral",
    "estimatedValue" => 500.00, "dueDate" => date("Y-m-d", strtotime("+3 day"))
], $managerJar);
check("admin-fiscal-obligation-create funciona", ($fiscalCreated["json"]["obligationId"] ?? 0) > 0, json_encode($fiscalCreated["json"]));

$fiscalInvalid = request("{$base}/api?action=admin-fiscal-obligation-create", "POST", [
    "type" => "invalido", "name" => "X", "periodicity" => "mensal", "dueDate" => date("Y-m-d")
], $managerJar);
check("obrigacao com tipo invalido devolve 422", $fiscalInvalid["status"] === 422, (string)$fiscalInvalid["status"]);

$alertsHttp = request("{$base}/api?action=admin-fiscal-alert-list", "GET", null, $managerJar);
check("admin-fiscal-alert-list 200", $alertsHttp["status"] === 200, (string)$alertsHttp["status"]);
check("alertas progressivos gerados (3 dias)", count(array_filter($alertsHttp["json"]["alerts"] ?? [], fn($a) => ($a["type"] ?? "") === "3_dias")) >= 1, json_encode($alertsHttp["json"]["alerts"] ?? []));

$paidHttp = request("{$base}/api?action=admin-fiscal-obligation-paid", "POST", [
    "obligationId" => (int)($fiscalCreated["json"]["obligationId"] ?? 0)
], $managerJar);
check("admin-fiscal-obligation-paid funciona", ($paidHttp["json"]["obligationId"] ?? 0) > 0, json_encode($paidHttp["json"]));

$clientFiscal = request("{$base}/api?action=admin-fiscal-calendar-list", "GET", null, $clientJar);
check("cliente NAO acede ao fiscal (403)", $clientFiscal["status"] === 403, (string)$clientFiscal["status"]);

$grConfig = request("{$base}/api?action=admin-green-receipt-config", "GET", null, $managerJar);
check("admin-green-receipt-config 200", $grConfig["status"] === 200, (string)$grConfig["status"]);
check("config ativa 70/30 disponivel", ($grConfig["json"]["active"]["employeePercentage"] ?? null) == 70, json_encode($grConfig["json"]["active"] ?? null));

$grBadSave = request("{$base}/api?action=admin-green-receipt-config-save", "POST", [
    "employeePercentage" => 90, "platformPercentage" => 30, "effectiveFrom" => date("Y-m-d")
], $managerJar);
check("config com soma != 100 devolve 422", $grBadSave["status"] === 422, (string)$grBadSave["status"]);

$grSimulate = request("{$base}/api?action=admin-green-receipt-simulate&amount=200", "GET", null, $managerJar);
check("simulador 200 EUR -> 140/60", ($grSimulate["json"]["simulation"]["employeeValue"] ?? 0) == 140.0, json_encode($grSimulate["json"]["simulation"] ?? null));

$feedbackPublic = request("{$base}/api?action=feedback-list&limit=3");
check("feedback-list publico 200", $feedbackPublic["status"] === 200, (string)$feedbackPublic["status"]);

$feedbackMine = request("{$base}/api?action=feedback-my", "GET", null, $clientJar);
check("feedback-my (cliente) 200", $feedbackMine["status"] === 200, (string)$feedbackMine["status"]);

$feedbackAnon = request("{$base}/api?action=feedback-my", "GET", null, $anonJar);
check("feedback-my sem sessao devolve 401", $feedbackAnon["status"] === 401, (string)$feedbackAnon["status"]);

// ---------------------------------------------------------------------------
section("7. Páginas do backoffice por perfil (HTTP)");
$boServices = request("{$base}/gestao/servicos", "GET", null, $employeeJar);
check("GET /gestao/servicos responde 200 (funcionario)", $boServices["status"] === 200, (string)$boServices["status"]);
check("pagina de servicos carrega JS", str_contains($boServices["body"], "components/services.js"));
check("menu do funcionario NAO mostra fiscal", !str_contains($boServices["body"], "/gestao/fiscal"));

$boFiscalClient = request("{$base}/gestao/fiscal", "GET", null, $clientJar);
check("cliente redirecionado do fiscal", $boFiscalClient["status"] === 302, (string)$boFiscalClient["status"]);

$boFiscalEmployee = request("{$base}/gestao/fiscal", "GET", null, $employeeJar);
check("funcionario redirecionado do fiscal", $boFiscalEmployee["status"] === 302, (string)$boFiscalEmployee["status"]);

$boFiscal = request("{$base}/gestao/fiscal", "GET", null, $managerJar);
check("GET /gestao/fiscal responde 200 (gestor)", $boFiscal["status"] === 200, (string)$boFiscal["status"]);
check("pagina fiscal carrega JS", str_contains($boFiscal["body"], "components/fiscal.js"));
check("menu do gestor mostra fiscal e recibos verdes", str_contains($boFiscal["body"], "/gestao/fiscal") && str_contains($boFiscal["body"], "/gestao/recibos-verdes"));

$boGreen = request("{$base}/gestao/recibos-verdes", "GET", null, $managerJar);
check("GET /gestao/recibos-verdes responde 200", $boGreen["status"] === 200, (string)$boGreen["status"]);
check("pagina de recibos verdes carrega JS", str_contains($boGreen["body"], "components/greenReceipts.js"));

// ---------------------------------------------------------------------------
section("8. Registo e login de cliente (end-to-end)");

// Contrato das chaves = nomes dos mappers/API (UserService, CustomerService,
// CustomerAddressService). O nome de cidade é lido da BD para não depender
// de acentuação no ficheiro de teste.
$e2eCityName = (string)$pdo->query("SELECT nome FROM cidade ORDER BY id ASC LIMIT 1")->fetchColumn();
$e2eEmail    = "e2e.cliente." . time() . "@secade.pt";
$e2eJar      = jarPath("e2e_cliente");
@unlink($e2eJar);

$registerPayload = [
    "name"          => "Cliente E2E",
    "email"         => $e2eEmail,
    "password"      => "Teste@12345",
    "phone"         => "+351911111190",
    "street"        => "Rua de Aviz",
    "doorNumber"    => "10",
    "floor"         => "1 Esq",
    "zipCode"       => "7000-123",
    "cityName"      => $e2eCityName,
    "termsAccepted" => true
];

$registerRes   = request("{$base}/api?action=auth-register", "POST", $registerPayload, $anonJar);
$e2eCustomerId = (int)($registerRes["json"]["id"] ?? 0);

check("registo de cliente devolve success", ($registerRes["json"]["success"] ?? false) === true, json_encode($registerRes["json"]));
check("registo devolve o id do cliente", $e2eCustomerId > 0, (string)$e2eCustomerId);

$e2eUser = $e2eCustomerId > 0
    ? $pdo->query("SELECT nome, telemovel, tipo_perfil, password_hash FROM utilizador WHERE id = {$e2eCustomerId}")->fetch(PDO::FETCH_ASSOC)
    : [];
check(
    "utilizador gravado com nome/telemovel/perfil",
    ($e2eUser["nome"] ?? "") === "Cliente E2E"
        && ($e2eUser["telemovel"] ?? "") === "+351911111190"
        && ($e2eUser["tipo_perfil"] ?? "") === "cliente",
    json_encode($e2eUser)
);
check("password gravada em bcrypt", (bool)password_verify("Teste@12345", (string)($e2eUser["password_hash"] ?? "")));

$e2eCustomerRows = $e2eCustomerId > 0
    ? (int)$pdo->query("SELECT COUNT(*) FROM cliente WHERE id = {$e2eCustomerId}")->fetchColumn()
    : 0;
check("registo cria a linha de perfil em cliente", $e2eCustomerRows === 1, (string)$e2eCustomerRows);

$e2eAddress = $e2eCustomerId > 0
    ? $pdo->query("SELECT rua, numero_porta, codigo_postal, principal FROM cliente_morada WHERE cliente_id = {$e2eCustomerId}")->fetch(PDO::FETCH_ASSOC)
    : [];
check(
    "morada gravada com os campos do contrato",
    ($e2eAddress["rua"] ?? "") === "Rua de Aviz"
        && ($e2eAddress["numero_porta"] ?? "") === "10"
        && ($e2eAddress["codigo_postal"] ?? "") === "7000-123",
    json_encode($e2eAddress)
);
check("primeira morada e marcada como principal", (int)($e2eAddress["principal"] ?? 0) === 1, json_encode($e2eAddress));

// ---------------------------------------------------------------------------
section("9. Validacoes do registo (end-to-end)");

$dupRes = request("{$base}/api?action=auth-register", "POST", $registerPayload, $anonJar);
check("email duplicado devolve 422", $dupRes["status"] === 422, (string)$dupRes["status"]);
check("erro identificado no campo email", !empty($dupRes["json"]["errors"]["email"]), json_encode($dupRes["json"]["errors"] ?? []));

$badCityRes = request("{$base}/api?action=auth-register", "POST", array_merge($registerPayload, [
    "email"    => "e2e.cidade." . time() . "@secade.pt",
    "cityName" => "Lisboa"
]), $anonJar);
check("cidade nao suportada devolve 422", $badCityRes["status"] === 422, (string)$badCityRes["status"]);
check("erro identificado no campo cityName", !empty($badCityRes["json"]["errors"]["cityName"]), json_encode($badCityRes["json"]["errors"] ?? []));

$noTermsRes = request("{$base}/api?action=auth-register", "POST", array_merge($registerPayload, [
    "email"         => "e2e.termos." . time() . "@secade.pt",
    "termsAccepted" => false
]), $anonJar);
check("termos nao aceites devolve 422", $noTermsRes["status"] === 422, (string)$noTermsRes["status"]);
check("erro identificado no campo termsAccepted", !empty($noTermsRes["json"]["errors"]["termsAccepted"]), json_encode($noTermsRes["json"]["errors"] ?? []));

// ---------------------------------------------------------------------------
section("10. Sessao do cliente: login, area reservada e logout");

$loginRes = request("{$base}/api?action=auth-login", "POST", ["email" => $e2eEmail, "password" => "Teste@12345"], $e2eJar);
check("login do cliente recem-registado", ($loginRes["json"]["success"] ?? false) === true, json_encode($loginRes["json"]));
check("login devolve o perfil cliente", ($loginRes["json"]["user"]["profileType"] ?? "") === "cliente", json_encode($loginRes["json"]["user"] ?? []));

$e2eProfilePage = request("{$base}/perfil", "GET", null, $e2eJar);
check("pagina /perfil acessivel com a nova sessao (200)", $e2eProfilePage["status"] === 200, (string)$e2eProfilePage["status"]);

$e2eProfileApi = request("{$base}/api?action=customer-profile", "GET", null, $e2eJar);
$e2eAddresses   = $e2eProfileApi["json"]["profile"]["addresses"] ?? [];
check("customer-profile devolve a morada registada", count($e2eAddresses) === 1, json_encode($e2eProfileApi["json"]["profile"] ?? []));
check("morada devolvida marcada como principal", ($e2eAddresses[0]["isMain"] ?? false) === true, json_encode($e2eAddresses[0] ?? []));

$badLogin = request("{$base}/api?action=auth-login", "POST", ["email" => $e2eEmail, "password" => "Errada@123"], $anonJar);
check("login com password errada devolve 422", $badLogin["status"] === 422, (string)$badLogin["status"]);

$logoutRes = request("{$base}/api?action=auth-logout", "POST", [], $e2eJar);
check("logout responde success", ($logoutRes["json"]["success"] ?? false) === true, json_encode($logoutRes["json"]));

$afterLogout = request("{$base}/api?action=customer-profile", "GET", null, $e2eJar);
check("sessao invalidada apos logout (401)", $afterLogout["status"] === 401, (string)$afterLogout["status"]);

// ---------------------------------------------------------------------------
section("11. Login de funcionario e gestor (end-to-end)");

$e2eEmployeeJar = jarPath("e2e_funcionario");
$e2eManagerJar  = jarPath("e2e_gestor");
@unlink($e2eEmployeeJar);
@unlink($e2eManagerJar);

$employeeLogin = request("{$base}/api?action=auth-login", "POST", ["email" => "funcionario@secade.pt", "password" => "Func@12345"], $e2eEmployeeJar);
check("login do funcionario responde success", ($employeeLogin["json"]["success"] ?? false) === true, json_encode($employeeLogin["json"]));
check("login devolve o perfil funcionario", ($employeeLogin["json"]["user"]["profileType"] ?? "") === "funcionario", json_encode($employeeLogin["json"]["user"] ?? []));

$employeeArea = request("{$base}/api?action=admin-service-pending-list", "GET", null, $e2eEmployeeJar);
check("funcionario acede a area de servicos (200)", $employeeArea["status"] === 200, (string)$employeeArea["status"]);

$managerLogin = request("{$base}/api?action=auth-login", "POST", ["email" => "gestor@secade.pt", "password" => "Gestor@123"], $e2eManagerJar);
check("login do gestor responde success", ($managerLogin["json"]["success"] ?? false) === true, json_encode($managerLogin["json"]));
check("login devolve o perfil gestor", ($managerLogin["json"]["user"]["profileType"] ?? "") === "gestor", json_encode($managerLogin["json"]["user"] ?? []));

$managerArea = request("{$base}/api?action=admin-routes-list", "GET", null, $e2eManagerJar);
check("gestor acede a area de rotas (200)", $managerArea["status"] === 200, (string)$managerArea["status"]);

// ---------------------------------------------------------------------------
section("12. Limpeza dos dados E2E");

// O ON DELETE CASCADE remove as linhas de `cliente` e `cliente_morada`
if ($e2eCustomerId > 0) {
    $pdo->exec("DELETE FROM utilizador WHERE id = " . $e2eCustomerId);
}

$e2eLeftoverUsers    = (int)$pdo->query("SELECT COUNT(*) FROM utilizador WHERE email LIKE 'e2e.%@secade.pt'")->fetchColumn();
$e2eLeftoverAddress  = (int)$pdo->query("SELECT COUNT(*) FROM cliente_morada WHERE cliente_id > 3")->fetchColumn();
check("utilizadores E2E removidos", $e2eLeftoverUsers === 0, (string)$e2eLeftoverUsers);
check("moradas E2E removidas (cascade)", $e2eLeftoverAddress === 0, (string)$e2eLeftoverAddress);

foreach (["e2e_cliente", "e2e_funcionario", "e2e_gestor"] as $jarToRemove) {
    @unlink(jarPath($jarToRemove));
}

// ---------------------------------------------------------------------------
section("RESULTADO FINAL (HTTP)");
echo ($failed === 0 ? "TODOS OS TESTES HTTP PASSARAM" : "EXISTEM FALHAS") . " => {$passed} pass, {$failed} fail\n";
exit($failed === 0 ? 0 : 1);