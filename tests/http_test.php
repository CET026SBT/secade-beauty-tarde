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
    $pdo->exec("DELETE FROM config_percentagem_padrao WHERE id > 2");
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

$catalog = request("{$base}/servicos/cabeleireiro");
check("catálogo /servicos/cabeleireiro responde 200", $catalog["status"] === 200, (string)$catalog["status"]);
check("catálogo contém contentor de serviços", str_contains($catalog["body"], "services-container"));
check("catálogo contém modal de detalhes", str_contains($catalog["body"], "serviceDetailsModal"));

$categories = request("{$base}/servicos");
check("/servicos responde 200", $categories["status"] === 200, (string)$categories["status"]);
check("/servicos serve o catálogo (reorganização do catálogo)", str_contains($categories["body"], "services-container"));

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

$ambBookingId = (int)($createAmb["json"]["bookingId"] ?? 0);

// RN-31 (§24.7): uma rota só agrega/decide agendamentos com TODOS os serviços
// aceites — antes da aceitação a decisão tem de devolver 409.
$earlyRoutes = request("{$base}/api?action=admin-routes-list&date={$bookingDate}", "GET", null, $managerJar);
check("rotas em modo de decisao manual", ($earlyRoutes["json"]["decisionMode"] ?? null) === "manual", json_encode($earlyRoutes["json"]["decisionMode"] ?? null));
check("indicador de referencia = 50 EUR", (float)($earlyRoutes["json"]["referenceProfitability"] ?? 0) === 50.0, json_encode($earlyRoutes["json"]["referenceProfitability"] ?? null));

$earlyAggregated = false;
foreach ($earlyRoutes["json"]["routes"] ?? [] as $earlyRow) {
    if (in_array($ambBookingId, array_map("intval", $earlyRow["bookingIds"] ?? []), true)) {
        $earlyAggregated = true;
    }
}
check("rota nao agrega agendamento com servicos por aceitar (RN-31)", $earlyAggregated === false, json_encode($earlyRoutes["json"]["routes"] ?? []));

$ambAddress = $addressApi["json"]["addresses"][0] ?? [];
$ambCityId  = (int)($ambAddress["cityId"] ?? 1);

$earlyDecide = request("{$base}/api?action=admin-route-decide", "POST", [
    "cityId" => $ambCityId, "date" => $bookingDate, "decision" => "aprovada"
], $managerJar);
check("decisao de rota com servicos por aceitar devolve 409 (RN-31)", $earlyDecide["status"] === 409, (string)$earlyDecide["status"]);

// Aceitação de todos os serviços do agendamento (passa a qualificado)
$ambDetails  = request("{$base}/api?action=admin-appointment-details&bookingId={$ambBookingId}", "GET", null, $managerJar);
$ambServices = $ambDetails["json"]["services"] ?? [];
$lastAccept  = null;

foreach ($ambServices as $ambService) {
    $lastAccept = request("{$base}/api?action=admin-service-accept", "POST", [
        "bookingServiceId" => (int)($ambService["id"] ?? 0),
        "bookingId"        => $ambBookingId
    ], $employeeJar);
}
check("servicos do agendamento aceites pelo funcionario", count($ambServices) >= 1 && ($lastAccept["json"]["success"] ?? false) === true, json_encode($lastAccept["json"] ?? []));

$routesList = request("{$base}/api?action=admin-routes-list&date={$bookingDate}", "GET", null, $managerJar);
$routeRow = null;
foreach ($routesList["json"]["routes"] ?? [] as $listedRow) {
    if (in_array($ambBookingId, array_map("intval", $listedRow["bookingIds"] ?? []), true)) {
        $routeRow = $listedRow;
    }
}
check("rota passa a agregar o agendamento qualificado", $routeRow !== null, json_encode($routesList["json"]["routes"] ?? []));
check("rota qualificada pode ser decidida", ($routeRow["canDecide"] ?? false) === true, json_encode($routeRow));
check("detalhe da rota expoe os agendamentos incluidos (RN-34)", count($routeRow["bookingsDetail"] ?? []) >= 1, json_encode($routeRow["bookingsDetail"] ?? []));

$routeRow = $routeRow ?? [];

$decide = request("{$base}/api?action=admin-route-decide", "POST", [
    "cityId"     => $routeRow["cityId"] ?? $ambCityId,
    "date"       => $routeRow["routeDate"] ?? $bookingDate,
    "decision"   => "aprovada",
    "bookingIds" => array_map("intval", $routeRow["bookingIds"] ?? [])
], $managerJar);
check("admin-route-decide APROVA manualmente", ($decide["json"]["status"] ?? "") === "aprovada", json_encode($decide["json"]));
check("decisao registra o conjunto incluido (RN-34)", (int)($decide["json"]["bookings"] ?? 0) >= 1 && (int)($decide["json"]["excluded"] ?? -1) === 0, json_encode($decide["json"] ?? []));

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
    "employeePercentage" => 150, "effectiveFrom" => date("Y-m-d")
], $managerJar);
check("config com percentagem fora de 0-100 devolve 422", $grBadSave["status"] === 422, (string)$grBadSave["status"]);

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
section("11.1 Fase 6.0/6.5: painel, avisos, agenda e encaminhamento de /gestao");

$dashboardRes = request("{$base}/api?action=admin-dashboard-summary", "GET", null, $e2eManagerJar);
check("gestor acede ao painel (200)", $dashboardRes["status"] === 200, (string)$dashboardRes["status"]);
check("painel devolve kpis e graficos", isset($dashboardRes["json"]["kpis"], $dashboardRes["json"]["charts"]), json_encode(array_keys($dashboardRes["json"] ?? [])));
check("painel conta os servicos ativos da BD", (int)($dashboardRes["json"]["kpis"]["activeServices"] ?? 0) >= 1, json_encode($dashboardRes["json"]["kpis"]["activeServices"] ?? null));
check("painel traz serie dos proximos 7 dias", count($dashboardRes["json"]["charts"]["bookingsPerDay"]["data"] ?? []) === 7, json_encode($dashboardRes["json"]["charts"]["bookingsPerDay"] ?? []));
check("contabilidade assume estado vazio (sem numero inventado)", ($dashboardRes["json"]["accounting"]["available"] ?? true) === false, json_encode($dashboardRes["json"]["accounting"] ?? []));

$dashboardEmployee = request("{$base}/api?action=admin-dashboard-summary", "GET", null, $e2eEmployeeJar);
check("painel negado ao funcionario (403)", $dashboardEmployee["status"] === 403, (string)$dashboardEmployee["status"]);

$dashboardClient = request("{$base}/api?action=admin-dashboard-summary", "GET", null, $clientJar);
check("painel negado ao cliente (403)", $dashboardClient["status"] === 403, (string)$dashboardClient["status"]);

$alertSummary = request("{$base}/api?action=admin-alert-summary", "GET", null, $e2eManagerJar);
check("sino devolve o contador ao gestor (200)", $alertSummary["status"] === 200 && is_numeric($alertSummary["json"]["count"] ?? null), json_encode($alertSummary["json"] ?? []));

$alertList = request("{$base}/api?action=admin-alert-list", "GET", null, $e2eManagerJar);
check("pagina de avisos devolve grupos", count($alertList["json"]["groups"] ?? []) >= 2, json_encode(array_keys($alertList["json"] ?? [])));
check("avisos do gestor incluem a origem fiscal", in_array("fiscal", array_column($alertList["json"]["groups"] ?? [], "key"), true), json_encode(array_column($alertList["json"]["groups"] ?? [], "key")));
check("avisos do gestor incluem os servicos por aceitar", in_array("servicos_pendentes", array_column($alertList["json"]["groups"] ?? [], "key"), true), json_encode(array_column($alertList["json"]["groups"] ?? [], "key")));

$alertListEmployee = request("{$base}/api?action=admin-alert-list", "GET", null, $e2eEmployeeJar);
check("funcionario tem avisos proprios (200)", $alertListEmployee["status"] === 200, (string)$alertListEmployee["status"]);
check("avisos do funcionario nao mostram o grupo fiscal", !in_array("fiscal", array_column($alertListEmployee["json"]["groups"] ?? [], "key"), true), json_encode(array_column($alertListEmployee["json"]["groups"] ?? [], "key")));

$alertListClient = request("{$base}/api?action=admin-alert-list", "GET", null, $clientJar);
check("avisos negados ao cliente (403)", $alertListClient["status"] === 403, (string)$alertListClient["status"]);

$alertRead = request("{$base}/api?action=admin-alert-read", "POST", [], $e2eManagerJar);
check("marcar avisos fiscais como lidos (200)", $alertRead["status"] === 200 && isset($alertRead["json"]["updated"]), json_encode($alertRead["json"] ?? []));

$alertReadEmployee = request("{$base}/api?action=admin-alert-read", "POST", [], $e2eEmployeeJar);
check("funcionario nao marca os alertas fiscais (403)", $alertReadEmployee["status"] === 403, (string)$alertReadEmployee["status"]);

$agendaRes = request("{$base}/api?action=admin-employee-agenda-list", "GET", null, $e2eEmployeeJar);
check("funcionario acede a agenda (200)", $agendaRes["status"] === 200, (string)$agendaRes["status"]);
check("agenda mostra so rotas confirmadas", ($agendaRes["json"]["filter"] ?? "") === "rotas_confirmadas", json_encode($agendaRes["json"]["filter"] ?? null));
check("agenda normaliza o mes pedido", ($agendaRes["json"]["month"] ?? "") === date("Y-m"), json_encode($agendaRes["json"]["month"] ?? null));

$agendaBadMonth = request("{$base}/api?action=admin-employee-agenda-list&month=nao-e-um-mes", "GET", null, $e2eEmployeeJar);
check("mes invalido cai no mes atual", ($agendaBadMonth["json"]["month"] ?? "") === date("Y-m"), json_encode($agendaBadMonth["json"]["month"] ?? null));

$agendaManager = request("{$base}/api?action=admin-employee-agenda-list", "GET", null, $e2eManagerJar);
check("agenda negada ao gestor (403)", $agendaManager["status"] === 403, (string)$agendaManager["status"]);

// D-14 (§3.14): `/gestao` passa a ser o painel do gestor e a agenda do funcionario.
$gestaoAsManager = request("{$base}/gestao", "GET", null, $e2eManagerJar);
check("gestor ve o painel em /gestao (200)", $gestaoAsManager["status"] === 200 && str_contains($gestaoAsManager["body"] ?? "", "chartBookingsByState"), (string)$gestaoAsManager["status"]);

$gestaoAsEmployee = request("{$base}/gestao", "GET", null, $e2eEmployeeJar);
check("funcionario e encaminhado de /gestao para a agenda", str_contains($gestaoAsEmployee["location"] ?? "", "/gestao/agenda"), (string)($gestaoAsEmployee["location"] ?? ""));

$painelPage = request("{$base}/gestao/painel", "GET", null, $e2eManagerJar);
check("pagina do painel responde 200 ao gestor", $painelPage["status"] === 200, (string)$painelPage["status"]);

$agendaPage = request("{$base}/gestao/agenda", "GET", null, $e2eEmployeeJar);
check("pagina da agenda responde 200 ao funcionario", $agendaPage["status"] === 200, (string)$agendaPage["status"]);

$agendaPageManager = request("{$base}/gestao/agenda", "GET", null, $e2eManagerJar);
check("pagina da agenda desvia o gestor (302)", $agendaPageManager["status"] === 302, (string)$agendaPageManager["status"]);

$avisosPage = request("{$base}/gestao/avisos", "GET", null, $e2eEmployeeJar);
check("pagina de avisos responde 200 ao funcionario", $avisosPage["status"] === 200, (string)$avisosPage["status"]);

// ---------------------------------------------------------------------------
section("11.2 Fase 6.1: fornecedores (HTTP)");

$supplierList = request("{$base}/api?action=admin-supplier-list", "GET", null, $e2eManagerJar);
check("gestor lista fornecedores (200)", $supplierList["status"] === 200, (string)$supplierList["status"]);
check("lista traz os 43 fornecedores do cliente", (int)($supplierList["json"]["summary"]["total"] ?? 0) >= 43, json_encode($supplierList["json"]["summary"] ?? null));
check("resumo conta os fornecedores sem NIF", (int)($supplierList["json"]["summary"]["withoutNif"] ?? 0) >= 5, json_encode($supplierList["json"]["summary"] ?? null));

$supplierEmployee = request("{$base}/api?action=admin-supplier-list", "GET", null, $e2eEmployeeJar);
check("fornecedores negados ao funcionario (403)", $supplierEmployee["status"] === 403, (string)$supplierEmployee["status"]);

$supplierClient = request("{$base}/api?action=admin-supplier-list", "GET", null, $clientJar);
check("fornecedores negados ao cliente (403)", $supplierClient["status"] === 403, (string)$supplierClient["status"]);

$supplierCreate = request("{$base}/api?action=admin-supplier-store", "POST", [
    "name" => "Fornecedor HTTP Teste", "nif" => "999999991", "active" => 1
], $e2eManagerJar);
$httpSupplierId = (int)($supplierCreate["json"]["supplierId"] ?? 0);
check("gestor cria fornecedor (HTTP)", $httpSupplierId > 0, json_encode($supplierCreate["json"] ?? []));

$supplierInvalid = request("{$base}/api?action=admin-supplier-store", "POST", ["name" => ""], $e2eManagerJar);
check("fornecedor sem nome devolve 422", $supplierInvalid["status"] === 422, (string)$supplierInvalid["status"]);
check("erro identificado no campo name", !empty($supplierInvalid["json"]["errors"]["name"]), json_encode($supplierInvalid["json"]["errors"] ?? []));

$supplierUpdate = request("{$base}/api?action=admin-supplier-update", "POST", [
    "supplierId" => $httpSupplierId, "name" => "Fornecedor HTTP Teste (editado)", "active" => 1
], $e2eManagerJar);
check("gestor atualiza fornecedor (HTTP)", (int)($supplierUpdate["json"]["supplierId"] ?? 0) === $httpSupplierId, json_encode($supplierUpdate["json"] ?? []));

$supplierToggle = request("{$base}/api?action=admin-supplier-set-active", "POST", [
    "supplierId" => $httpSupplierId, "active" => 0
], $e2eManagerJar);
check("gestor desativa fornecedor (HTTP)", ($supplierToggle["json"]["active"] ?? true) === false, json_encode($supplierToggle["json"] ?? []));

$supplierMissing = request("{$base}/api?action=admin-supplier-update", "POST", [
    "supplierId" => 999999, "name" => "Inexistente"
], $e2eManagerJar);
check("fornecedor inexistente devolve 404", $supplierMissing["status"] === 404, (string)$supplierMissing["status"]);

$supplierPage = request("{$base}/gestao/fornecedores", "GET", null, $e2eManagerJar);
check("pagina de fornecedores responde 200 ao gestor", $supplierPage["status"] === 200, (string)$supplierPage["status"]);

$supplierPageClient = request("{$base}/gestao/fornecedores", "GET", null, $clientJar);
check("pagina de fornecedores desvia o cliente (302)", $supplierPageClient["status"] === 302, (string)$supplierPageClient["status"]);

// ---------------------------------------------------------------------------
section("11.3 Fase 6.4: comissoes (HTTP)");

$commissionManager = request("{$base}/api?action=admin-commission-list", "GET", null, $e2eManagerJar);
check("gestor consulta comissoes (200)", $commissionManager["status"] === 200, (string)$commissionManager["status"]);
check("comissoes do gestor cobrem todos os funcionarios", ($commissionManager["json"]["scope"] ?? "") === "todos", json_encode($commissionManager["json"]["scope"] ?? null));

$commissionEmployeeHttp = request("{$base}/api?action=admin-commission-list", "GET", null, $e2eEmployeeJar);
check("funcionario consulta as suas comissoes (200)", $commissionEmployeeHttp["status"] === 200, (string)$commissionEmployeeHttp["status"]);
check("comissoes do funcionario ficam no proprio", ($commissionEmployeeHttp["json"]["scope"] ?? "") === "proprio", json_encode($commissionEmployeeHttp["json"]["scope"] ?? null));

$commissionClientHttp = request("{$base}/api?action=admin-commission-list", "GET", null, $clientJar);
check("comissoes negadas ao cliente (403)", $commissionClientHttp["status"] === 403, (string)$commissionClientHttp["status"]);

$commissionPageHttp = request("{$base}/gestao/comissoes", "GET", null, $e2eEmployeeJar);
check("pagina de comissoes responde 200 ao funcionario", $commissionPageHttp["status"] === 200, (string)$commissionPageHttp["status"]);

// ---------------------------------------------------------------------------
section("11.4 Fase 6 (24.6): cancelamento pelo cliente (HTTP)");

$clientCancellable = request("{$base}/api?action=booking-create-store", "POST", [
    "serviceIds" => [30], "date" => $bookingDate, "time" => "16:30"
], $clientJar);
$clientCancellableId = (int)($clientCancellable["json"]["bookingId"] ?? 0);
check("cliente cria agendamento para cancelar", $clientCancellableId > 0, json_encode($clientCancellable["json"] ?? []));

$clientCancel = request("{$base}/api?action=customer-booking-cancel", "POST", [
    "bookingId" => $clientCancellableId
], $clientJar);
check("cliente cancela o proprio agendamento (200)", $clientCancel["status"] === 200 && ($clientCancel["json"]["status"] ?? "") === "cancelado", json_encode($clientCancel["json"] ?? []));
check("cancelamento sem penalizacao (mensagem)", str_contains((string)($clientCancel["json"]["message"] ?? ""), "penalização"), json_encode($clientCancel["json"] ?? []));

$clientCancelAgain = request("{$base}/api?action=customer-booking-cancel", "POST", [
    "bookingId" => $clientCancellableId
], $clientJar);
check("cancelar duas vezes devolve 409", $clientCancelAgain["status"] === 409, (string)$clientCancelAgain["status"]);

$managerCancelCustomer = request("{$base}/api?action=customer-booking-cancel", "POST", [
    "bookingId" => $clientCancellableId
], $e2eManagerJar);
check("gestor nao usa o cancelamento do cliente (403)", $managerCancelCustomer["status"] === 403, (string)$managerCancelCustomer["status"]);

$anonymousCancel = request("{$base}/api?action=customer-booking-cancel", "POST", [
    "bookingId" => $clientCancellableId
], $anonJar);
check("cancelamento sem sessao devolve 401", $anonymousCancel["status"] === 401, (string)$anonymousCancel["status"]);

$appointmentsPageClient = request("{$base}/agendamentos", "GET", null, $clientJar);
check("pagina de agendamentos tem o botao de cancelamento", $appointmentsPageClient["status"] === 200 && str_contains($appointmentsPageClient["body"], "appointmentsSuccess"), (string)$appointmentsPageClient["status"]);

// ---------------------------------------------------------------------------
section("12. Limpeza dos dados E2E");

// O ON DELETE CASCADE remove as linhas de `cliente` e `cliente_morada`
if ($e2eCustomerId > 0) {
    $pdo->exec("DELETE FROM utilizador WHERE id = " . $e2eCustomerId);
}

// Fornecedor de teste criado em 11.2 (não é produto do cliente)
if ($httpSupplierId > 0) {
    $pdo->exec("DELETE FROM fornecedor WHERE id = " . $httpSupplierId);
    check("fornecedor de teste removido", (int)$pdo->query("SELECT COUNT(*) FROM fornecedor WHERE id = {$httpSupplierId}")->fetchColumn() === 0, "residuo");
}

$e2eLeftoverUsers    = (int)$pdo->query("SELECT COUNT(*) FROM utilizador WHERE email LIKE 'e2e.%@secade.pt'")->fetchColumn();
// ⚠️ A tabela `cliente_morada` tem agora moradas de clientes REAIS importados (clientes com id ≥ 100 —
// §24.11 · os 65 clientes reais entram pelo dump `DataBase.sql`), pelo que a asserção olha **só** para
// o que o teste criou
// (o cliente E2E), e não para o total da tabela.
$e2eLeftoverAddress = $e2eCustomerId > 0
    ? (int)$pdo->query("SELECT COUNT(*) FROM cliente_morada WHERE cliente_id = {$e2eCustomerId}")->fetchColumn()
    : 0;
check("utilizadores E2E removidos", $e2eLeftoverUsers === 0, (string)$e2eLeftoverUsers);
check("moradas E2E removidas (cascade)", $e2eLeftoverAddress === 0, (string)$e2eLeftoverAddress);

foreach (["e2e_cliente", "e2e_funcionario", "e2e_gestor"] as $jarToRemove) {
    @unlink(jarPath($jarToRemove));
}

// ---------------------------------------------------------------------------
section("RESULTADO FINAL (HTTP)");
echo ($failed === 0 ? "TODOS OS TESTES HTTP PASSARAM" : "EXISTEM FALHAS") . " => {$passed} pass, {$failed} fail\n";
exit($failed === 0 ? 0 : 1);
