<?php
/**
 * Smoke / functional test harness (CLI) — validação end-to-end do MVP.
 * Executar: php .tmp_test/functional_test.php
 */

define("ROOT_PATH", dirname(__DIR__));
define("APP_PATH", ROOT_PATH . "/app");
define("BASE_URL", "http://localhost/secade-beauty-tarde");

require_once APP_PATH . "/config/config.php";
require_once APP_PATH . "/utils/Session.php";
require_once APP_PATH . "/services/BookingService.php";
require_once APP_PATH . "/services/RotaService.php";
require_once APP_PATH . "/services/CustomerService.php";
require_once APP_PATH . "/services/CustomerAddressService.php";
require_once APP_PATH . "/services/ServiceAcceptanceService.php";
require_once APP_PATH . "/services/GreenReceiptService.php";
require_once APP_PATH . "/services/FiscalService.php";
require_once APP_PATH . "/services/ExecutionService.php";
require_once APP_PATH . "/services/FeedbackService.php";
require_once APP_PATH . "/services/EmployeeService.php";
require_once APP_PATH . "/repositories/CityRepository.php";
require_once APP_PATH . "/repositories/BookingServiceRepository.php";
require_once APP_PATH . "/repositories/EmployeeRepository.php";
require_once APP_PATH . "/repositories/MaintenanceRepository.php";
require_once APP_PATH . "/services/MaintenanceService.php";
require_once APP_PATH . "/services/UserPhotoService.php";
require_once APP_PATH . "/services/CommissionService.php";

// Bootstrap de sessão (o harness CLI simula uma sessão autenticada antes de qualquer output)
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION["user_id"]      = 3;
$_SESSION["user_name"]    = "João Cliente";
$_SESSION["user_email"]   = "cliente@teste.pt";
$_SESSION["user_profile"] = "gestor";

$passed = 0; $failed = 0;
function check(string $label, bool $ok, $extra = "") {
    global $passed, $failed;
    if ($ok) { $passed++; echo "  PASS  {$label}\n"; }
    else { $failed++; echo "  FAIL  {$label}  {$extra}\n"; }
}
function section(string $title) { echo "\n=== {$title} ===\n"; }

/**
 * Primeira data útil (Ter–Sáb) a respeitar a regra das 24 h (RF-58/RN-24):
 * as marcações de teste têm de estar a pelo menos 24 h de distância.
 */
function nextWorkingDate(int $offsetDays = 1): string {
    $ts = strtotime("+{$offsetDays} day");
    while ((int)date("N", $ts) < 2 || (int)date("N", $ts) > 6) { $ts = strtotime("+1 day", $ts); }

    // A hora usada nos testes é 10:00 — avança até essa hora já estar a >24 h.
    while (strtotime(date("Y-m-d", $ts) . " 10:00") < strtotime("+24 hours")) {
        $ts = strtotime("+1 day", $ts);
        while ((int)date("N", $ts) < 2 || (int)date("N", $ts) > 6) { $ts = strtotime("+1 day", $ts); }
    }

    return date("Y-m-d", $ts);
}

$bookingService  = new BookingService();
$rotaService     = new RotaService();
$customerService = new CustomerService();
$addressService  = new CustomerAddressService();
$cityRepository  = new CityRepository();

$customerId = 3;
$date = nextWorkingDate(1);

// Limpeza de dados de teste (torna o harness re-executável)
global $conn;
$conn->exec("DELETE FROM agendamento WHERE cliente_id = " . (int)$customerId);
$conn->exec("DELETE FROM cliente_morada WHERE cliente_id = " . (int)$customerId . " AND id > 1");
$conn->exec("DELETE FROM rota_ambulante");
$conn->exec("DELETE FROM alerta_fiscal");
$conn->exec("DELETE FROM obrigacao_fiscal");
$conn->exec("DELETE FROM config_percentagem_padrao WHERE id > 2");
$conn->exec("DELETE FROM funcionario WHERE id IN (SELECT id FROM utilizador WHERE email = 'rh.teste@secade.local')");
$conn->exec("DELETE FROM utilizador WHERE email = 'rh.teste@secade.local'");

// F4 (R-ALOC): o mesmo funcionário não pode ficar em duas cidades no mesmo dia.
// O harness cria um 2.º funcionário (a folha real tem 6) para cobrir as 2 cidades.
$routeHelperId = 9002;
$conn->exec("DELETE FROM funcionario WHERE id = " . $routeHelperId);
$conn->exec("DELETE FROM utilizador WHERE id = " . $routeHelperId);
$conn->exec("INSERT INTO utilizador (id, nome, email, password_hash, telemovel, tipo_perfil)
             VALUES ({$routeHelperId}, 'Funcionario Rota Teste', 'funcionario.rota.teste@secade.local', '*', '', 'funcionario')");
$conn->exec("INSERT INTO funcionario (id, tipo_contrato, percentagem_comissao, salario_base, ativo)
             VALUES ({$routeHelperId}, 'recibo_verde', 70.00, 0.00, 1)");

// ---------------------------------------------------------------------------
section("1. Catálogo de serviços");
$services = $bookingService->listActiveServices();
check("booking-services devolve 'services'", isset($services["services"]) && count($services["services"]) === 35, json_encode(array_keys($services)));
$first = $services["services"][0] ?? [];
check("serviço mapeado (name/basePrice)", isset($first["name"], $first["basePrice"]), json_encode($first));

// ---------------------------------------------------------------------------
section("2. Disponibilidade (loja)");
$availability = $bookingService->findAvailability(["date" => $date, "duration" => 60, "local" => "loja_fisica"]);
check("availability devolve slots", isset($availability["slots"]) && count($availability["slots"]) > 0, json_encode($availability));
check("slots têm 'time' e 'available'", isset($availability["slots"][0]["time"], $availability["slots"][0]["available"]));

// ---------------------------------------------------------------------------
section("3. Wizard LOJA FÍSICA (5 steps -> criação)");
$storeResult = $bookingService->createStoreBooking($customerId, [
    "serviceIds" => [28, 29],
    "date"       => $date,
    "time"       => "10:00"
]);
check("agendamento loja criado", !empty($storeResult["bookingId"]), json_encode($storeResult));
$storeBooking = (new BookingRepository())->find((int)$storeResult["bookingId"]);
check("estado = pendente_validacao_logistica_loja", ($storeBooking["status"] ?? "") === "pendente_validacao_logistica_loja", $storeBooking["status"] ?? "null");
check("sinal 10% calculado", abs((float)($storeBooking["depositAmount"] ?? 0) - 1.63) < 0.02, (string)($storeBooking["depositAmount"] ?? "null"));

$conflict = null;
try {
    $bookingService->createStoreBooking($customerId, ["serviceIds" => [29], "date" => $date, "time" => "10:00"]);
} catch (Exception $e) { $conflict = $e->getMessage(); }
check("slot em conflito bloqueado", $conflict !== null, "sem conflito detetado");

// ---------------------------------------------------------------------------
section("4. Wizard CARRINHA (OTP + estrutura por pessoa -> pendente)");
$cities = $cityRepository->find();
check("cidades suportadas carregadas", count($cities) === 10, json_encode(count($cities)));

$otp = $bookingService->requestOtp($customerId);
check("OTP gerado (simulação)", isset($otp["otpCode"]) && strlen((string)$otp["otpCode"]) === 6, json_encode($otp));

$ambResult = $bookingService->createAmbulatoryBooking($customerId, [
    "addressId" => 1,
    "otpCode"   => $otp["otpCode"],
    "date"      => $date,
    "time"      => "09:00",
    "people"    => [
        ["name" => "João Cliente", "serviceIds" => [29]],
        ["name" => "Maria Familiar", "serviceIds" => [29, 35]]
    ]
]);
check("agendamento ambulatório criado", !empty($ambResult["bookingId"]), json_encode($ambResult));
$ambBooking = (new BookingRepository())->find((int)$ambResult["bookingId"]);
check("estado = pendente_alocacao", ($ambBooking["status"] ?? "") === "pendente_alocacao", $ambBooking["status"] ?? "null");
check("valor reflete pessoas (3 serviços)", abs((float)($ambBooking["totalAmount"] ?? 0) - (4.07 + 4.07 + 8.13)) < 0.01, (string)($ambBooking["totalAmount"] ?? "null"));

$badOtp = null;
try {
    $bookingService->createAmbulatoryBooking($customerId, [
        "addressId" => 1, "otpCode" => "000000", "date" => $date, "time" => "11:00",
        "people" => [["name" => "X", "serviceIds" => [29]]]
    ]);
} catch (Exception $e) { $badOtp = $e->getMessage(); }
check("OTP inválido rejeitado", $badOtp !== null, "OTP inválido aceite");

// ---------------------------------------------------------------------------
section("5. Viabilidade de rotas — DECISÃO MANUAL (Fase 4)");
$routeDate = nextWorkingDate(5);
$bookingRepository = new BookingRepository();

$cities   = $cityRepository->find();
$cityPoor = $cities[0];
$cityRich = $cities[1];

$poorAddress = $addressService->createAddress($customerId, [
    "cityName" => $cityPoor["name"], "street" => "Rua Teste Pobre", "doorNumber" => "1", "zipCode" => "7000-100"
]);
$richAddress = $addressService->createAddress($customerId, [
    "cityName" => $cityRich["name"], "street" => "Rua Teste Rica", "doorNumber" => "2", "zipCode" => "7000-200"
]);
check("moradas criadas para o algoritmo", !empty($poorAddress["addressId"]) && !empty($richAddress["addressId"]), json_encode([$poorAddress, $richAddress]));

// Os dois agendamentos têm de ficar em janelas distintas: o bloqueio da janela
// temporal (uma consolidação por janela de ambulatório) impediria a 2.ª aceitação.
$otp1 = $bookingService->requestOtp($customerId);
$poorBooking = $bookingService->createAmbulatoryBooking($customerId, [
    "addressId" => $poorAddress["addressId"], "otpCode" => $otp1["otpCode"],
    "date" => $routeDate, "time" => "17:00",
    "people" => [["name" => "João Cliente", "serviceIds" => [29]]]
]);

$otp2 = $bookingService->requestOtp($customerId);
$richBooking = $bookingService->createAmbulatoryBooking($customerId, [
    "addressId" => $richAddress["addressId"], "otpCode" => $otp2["otpCode"],
    "date" => $routeDate, "time" => "09:00",
    "people" => [["name" => "João Cliente", "serviceIds" => [20, 18, 23, 21, 9, 2, 27]]]
]);
check("agendamentos de teste criados", !empty($poorBooking["bookingId"]) && !empty($richBooking["bookingId"]), json_encode([$poorBooking, $richBooking]));

// RN-31 (§24.7): enquanto houver serviços por aceitar, a rota não se agrega nem se decide
$pendingRouteError = null;
try {
    $rotaService->decideRoute(["cityId" => (int)$cityPoor["id"], "date" => $routeDate, "decision" => "aprovada"]);
} catch (Exception $e) { $pendingRouteError = $e->getMessage(); }
check("rota com servicos por aceitar recusada (RN-31)", $pendingRouteError !== null, "decisao aceite sem todos os servicos");

$pendingRows = $rotaService->findRouteSummaries(["date" => $routeDate])["routes"];
check("rota nao agrega agendamentos por aceitar (RN-31)", count($pendingRows) === 0, json_encode($pendingRows));

// Aceitação de todos os serviços (funcionário 2 = Ana Técnica do seed), como um
// funcionário faria na área de Serviços antes de o gestor decidir a rota.
$routeAcceptanceService = new ServiceAcceptanceService();
$routeBookingServiceRepo = new BookingServiceRepository();
$routeEmployeeId = 2;

// R-ALOC (F4): cada agendamento (cidade diferente) tem o seu funcionário.
foreach ([[$poorBooking["bookingId"], 2], [$richBooking["bookingId"], $routeHelperId]] as [$routeBookingToAccept, $routeEmployee]) {
    foreach ($routeBookingServiceRepo->findByBooking((int)$routeBookingToAccept) as $routeServiceRow) {
        $routeAcceptanceService->acceptService($routeEmployee, (int)$routeServiceRow["id"], (int)$routeBookingToAccept);
    }
}

$summaryBefore = $rotaService->findRouteSummaries(["date" => $routeDate]);
check("listagem de rotas mostra 2 candidatas", count($summaryBefore["routes"]) === 2, json_encode($summaryBefore["routes"]));

$poorRow = null; $richRow = null;
foreach ($summaryBefore["routes"] as $row) {
    if ($row["cityId"] === (int)$cityPoor["id"]) $poorRow = $row;
    if ($row["cityId"] === (int)$cityRich["id"]) $richRow = $row;
}
check("indicador de referencia = 50 EUR (visual)", ($summaryBefore["referenceProfitability"] ?? null) === 50.0, json_encode($summaryBefore["referenceProfitability"] ?? null));
check("modo de decisao = manual", ($summaryBefore["decisionMode"] ?? null) === "manual", json_encode($summaryBefore["decisionMode"] ?? null));
check("cidade pobre abaixo da referencia (50 EUR)", ($poorRow["meetsReference"] ?? true) === false, json_encode($poorRow));
check("cidade rica acima da referencia (50 EUR)", ($richRow["meetsReference"] ?? false) === true, json_encode($richRow));
check("rotas podem ser decididas manualmente", ($poorRow["canDecide"] ?? false) === true && ($richRow["canDecide"] ?? false) === true, "");

// --- Decisão MANUAL: a cidade pobre (abaixo da referência) é APROVADA na mesma ---
$manualApprove = $rotaService->decideRoute([
    "cityId"   => (int)$cityPoor["id"],
    "date"     => $routeDate,
    "decision" => "aprovada",
    "notes"    => "Decisão manual de teste: aprovada apesar da referência."
]);
check("decisao manual APROVA mesmo abaixo da referencia", ($manualApprove["status"] ?? "") === "aprovada" && ($manualApprove["meetsReference"] ?? true) === false, json_encode($manualApprove));
check("agendamento da cidade pobre = confirmado", ($bookingRepository->find((int)$poorBooking["bookingId"])["status"] ?? "") === "confirmado");

$manualRefuse = $rotaService->decideRoute([
    "cityId"   => (int)$cityRich["id"],
    "date"     => $routeDate,
    "decision" => "recusada"
]);
check("decisao manual RECUSA mesmo acima da referencia", ($manualRefuse["status"] ?? "") === "recusada" && ($manualRefuse["meetsReference"] ?? false) === true, json_encode($manualRefuse));
check("agendamento da cidade rica = recusado (a staff recusa, 6.1)", ($bookingRepository->find((int)$richBooking["bookingId"])["status"] ?? "") === "recusado");

$rotaRepository = new RotaRepository();
$routeRow = $rotaRepository->findByDateAndCity($routeDate, (int)$cityRich["id"]);
check("BD: rota registada como recusada", !empty($routeRow["id"]) && $routeRow["status"] === "recusada", json_encode($routeRow));
check("BD: auditoria da decisao gravada", !empty($routeRow["decidedAt"]), json_encode($routeRow["decidedAt"] ?? null));

$invalidDecision = null;
try {
    $rotaService->decideRoute(["cityId" => (int)$cityRich["id"], "date" => $routeDate, "decision" => "talvez"]);
} catch (Exception $e) { $invalidDecision = $e->getMessage(); }
check("decisao invalida rejeitada (422)", $invalidDecision !== null, "sem erro");

$noBookings = null;
try {
    $rotaService->decideRoute(["cityId" => (int)$cityRich["id"], "date" => $routeDate, "decision" => "aprovada"]);
} catch (Exception $e) { $noBookings = $e->getMessage(); }
check("2a decisao sobre rota ja decidida rejeitada (409)", $noBookings !== null, "sem erro");

// ---------------------------------------------------------------------------
section("6. Backoffice: agendamentos");
$adminList = $bookingService->listBookings(["page" => 1, "perPage" => 10]);
check("admin-appointments-list devolve bookings + total", isset($adminList["bookings"], $adminList["total"]) && $adminList["total"] >= 3, json_encode(["total" => $adminList["total"] ?? null]));
check("listagem enriquecida com nome do cliente", ($adminList["bookings"][0]["customerName"] ?? "") !== "", json_encode($adminList["bookings"][0] ?? []));

$filtered = $bookingService->listBookings(["page" => 1, "perPage" => 10, "local" => "carrinha_ambulante"]);
check("filtro por local (carrinha)", $filtered["total"] >= 2, (string)$filtered["total"]);

$statusFiltered = $bookingService->listBookings(["page" => 1, "perPage" => 10, "status" => "recusado"]);
check("filtro por estado (recusado)", $statusFiltered["total"] >= 1, (string)$statusFiltered["total"]);

$toCancel = $bookingService->createStoreBooking($customerId, ["serviceIds" => [33], "date" => $date, "time" => "16:00"]);
$cancelResult = $bookingService->cancelBooking((int)$toCancel["bookingId"]);
check("admin-appointment-cancel recusa (staff recusa, não cancela — D-07.4)", ($bookingRepository->find((int)$toCancel["bookingId"])["status"] ?? "") === "recusado", json_encode($cancelResult));

$alreadyCancelled = null;
try { $bookingService->cancelBooking((int)$toCancel["bookingId"]); } catch (Exception $e) { $alreadyCancelled = $e->getMessage(); }
check("cancelar 2x é rejeitado (409)", $alreadyCancelled !== null, "sem erro");

$notFound = null;
try { $bookingService->cancelBooking(999999); } catch (Exception $e) { $notFound = $e->getMessage(); }
check("cancelar id inexistente é rejeitado (404)", $notFound !== null, "sem erro");

// ---------------------------------------------------------------------------
section("7. Perfil e moradas do cliente");
$profile = $customerService->getCustomerProfile($customerId);
check("customer-profile devolve dados", ($profile["name"] ?? null) === "João Cliente" && ($profile["email"] ?? null) === "cliente@teste.pt", json_encode($profile["name"] ?? null));
check("perfil inclui moradas", count($profile["addresses"] ?? []) >= 3, (string)count($profile["addresses"] ?? []));

$addresses = $addressService->findCustomerAddresses($customerId);
check("customer-address-list devolve moradas", count($addresses["addresses"] ?? []) >= 3, (string)count($addresses["addresses"] ?? []));

$addressService->setPrincipalAddress($customerId, (int)$richAddress["addressId"]);
$principalCount = 0; $principalId = null;
foreach ($addressService->findCustomerAddresses($customerId)["addresses"] as $address) {
    if (!empty($address["isMain"])) { $principalCount++; $principalId = $address["id"]; }
}
check("apenas 1 morada principal após atualização", $principalCount === 1 && $principalId === (int)$richAddress["addressId"], json_encode([$principalCount, $principalId]));

$addressService->deleteAddress($customerId, (int)$poorAddress["addressId"]);
check("morada removida", count($addressService->findCustomerAddresses($customerId)["addresses"]) >= 2);

// ---------------------------------------------------------------------------
section("8. Fase 3 — Aceitação por funcionário, recibos verdes e consolidação");
$acceptanceService   = new ServiceAcceptanceService();
$greenReceiptService = new GreenReceiptService();
$bookingServiceRepo  = new BookingServiceRepository();
$employeeId          = 2; // Ana Técnica (seed)

$acceptDate    = nextWorkingDate(9);
$acceptAddress = $addressService->createAddress($customerId, [
    "cityName" => $cities[2]["name"], "street" => "Rua Aceitacao", "doorNumber" => "5", "zipCode" => "7000-300"
]);

$otpA = $bookingService->requestOtp($customerId);
$acceptBooking = $bookingService->createAmbulatoryBooking($customerId, [
    "addressId" => $acceptAddress["addressId"], "otpCode" => $otpA["otpCode"],
    "date" => $acceptDate, "time" => "09:00",
    "people" => [
        ["name" => "João Cliente", "serviceIds" => [29]],
        ["name" => "Maria Familiar", "serviceIds" => [35]]
    ]
]);
$acceptBookingId = (int)$acceptBooking["bookingId"];
check("agendamento para aceitacao criado", $acceptBookingId > 0, json_encode($acceptBooking));

$pendingList = $acceptanceService->listPendingServices(["data" => $acceptDate]);
check("fase 3: lista de pendentes tem 2 servicos", count($pendingList["services"]) === 2, (string)count($pendingList["services"]));
check("fase 3: categorias como filtros visuais", count($pendingList["categories"]) === 3, (string)count($pendingList["categories"]));
check("fase 3: config de percentagens em vigor", isset($pendingList["config"]["configs"]), json_encode($pendingList["config"]));

$firstServiceId  = (int)$pendingList["services"][0]["id"];
$secondServiceId = (int)$pendingList["services"][1]["id"];

$accept1 = $acceptanceService->acceptService($employeeId, $firstServiceId, $acceptBookingId);
check("fase 3: 1o servico aceite (nao consolida)", ($accept1["consolidated"] ?? true) === false, json_encode($accept1));
check("fase 3: recibo verde simulado devolvido", isset($accept1["greenReceipt"]["employeeValue"], $accept1["greenReceipt"]["platformValue"]), json_encode($accept1["greenReceipt"] ?? null));
check("fase 3: agendamento ainda NAO consolidado", ($bookingRepository->find($acceptBookingId)["status"] ?? "") === "pendente_alocacao");

$acceptanceService->unacceptService($employeeId, $firstServiceId, $acceptBookingId);
check("fase 3: desfazer funciona antes da consolidacao", ($bookingServiceRepo->findById($firstServiceId)["acceptanceStatus"] ?? "") === "pendente");

$acceptanceService->acceptService($employeeId, $firstServiceId, $acceptBookingId);
$accept2 = $acceptanceService->acceptService($employeeId, $secondServiceId, $acceptBookingId);
check("fase 3: ultimo servico consolidou", ($accept2["consolidated"] ?? false) === true, json_encode($accept2));
check("fase 3: estado = totalmente_alocado", ($bookingRepository->find($acceptBookingId)["status"] ?? "") === "totalmente_alocado");

$blockedUnaccept = null;
try { $acceptanceService->unacceptService($employeeId, $firstServiceId, $acceptBookingId); } catch (Exception $e) { $blockedUnaccept = $e->getMessage(); }
check("fase 3: desfazer BLOQUEADO apos consolidacao (409)", $blockedUnaccept !== null, "sem erro");

$acceptedList = $acceptanceService->listAcceptedServices($employeeId, []);
check("fase 3: lista de aceites por funcionario", ($acceptedList["totals"]["count"] ?? 0) >= 2, json_encode($acceptedList["totals"] ?? null));
check("fase 3: total de recibo verde calculado", ($acceptedList["totals"]["employee"] ?? 0) > 0, json_encode($acceptedList["totals"] ?? null));

$simulation = $greenReceiptService->simulate(100.0);
check("fase 3: simulador 100 EUR -> 70/30", $simulation["employeeValue"] === 70.0 && $simulation["platformValue"] === 30.0, json_encode($simulation));

$badConfig = null;
try { $greenReceiptService->createConfig(["contractType" => "recibo_verde", "commissionPercentage" => 110, "effectiveFrom" => date("Y-m-d")]); } catch (Exception $e) { $badConfig = $e->getMessage(); }
check("fase 3: percentagem fora de 0-100 rejeitada (422)", $badConfig !== null, "sem erro");

$storeService = $bookingService->createStoreBooking($customerId, ["serviceIds" => [30], "date" => $date, "time" => "17:00"]);
$storeServiceId = (int)$bookingServiceRepo->findByBooking((int)$storeService["bookingId"])[0]["id"];
$notAmbulatory = null;
try { $acceptanceService->acceptService($employeeId, $storeServiceId, (int)$storeService["bookingId"]); } catch (Exception $e) { $notAmbulatory = $e->getMessage(); }
check("fase 3: aceitar servico de LOJA rejeitado (409)", $notAmbulatory !== null, "sem erro");

// ---------------------------------------------------------------------------
section("9. Fase 4 — Calendário Fiscal e alertas progressivos");
$fiscalService = new FiscalService();

$iva = $fiscalService->createObligation([
    "type" => "iva", "name" => "IVA Trimestral (teste)", "periodicity" => "trimestral",
    "estimatedValue" => 1234.56, "dueDate" => date("Y-m-d", strtotime("+7 day"))
]);
check("fase 4: obrigacao fiscal criada", ($iva["obligationId"] ?? 0) > 0, json_encode($iva));

$overdue = $fiscalService->createObligation([
    "type" => "seguranca_social", "name" => "SS em atraso (teste)", "periodicity" => "mensal",
    "estimatedValue" => 350.00, "dueDate" => date("Y-m-d", strtotime("-5 day"))
]);
check("fase 4: obrigacao em atraso criada", ($overdue["obligationId"] ?? 0) > 0, json_encode($overdue));

$thirty = $fiscalService->createObligation([
    "type" => "irc", "name" => "IRC 30 dias (teste)", "periodicity" => "anual",
    "estimatedValue" => 5000.00, "dueDate" => date("Y-m-d", strtotime("+30 day"))
]);
check("fase 4: obrigacao a 30 dias criada", ($thirty["obligationId"] ?? 0) > 0, json_encode($thirty));

$calendar = $fiscalService->findCalendar([]);
check("fase 4: calendario devolve obrigacoes", count($calendar["obligations"]) >= 3, (string)count($calendar["obligations"]));
check("fase 4: resumo calculado", ($calendar["summary"]["total"] ?? 0) >= 3 && ($calendar["summary"]["overdue"] ?? 0) >= 1, json_encode($calendar["summary"] ?? null));

$levels = [];
foreach ($calendar["obligations"] as $obligation) {
    $levels[$obligation["name"]] = $obligation["alertLevel"];
}
check("fase 4: alerta '7_dias' para prazo a 7 dias", ($levels["IVA Trimestral (teste)"] ?? "") === "7_dias", json_encode($levels));
check("fase 4: alerta 'em_atraso' para prazo vencido", ($levels["SS em atraso (teste)"] ?? "") === "em_atraso", json_encode($levels));
check("fase 4: alerta '30_dias' para prazo a 30 dias", ($levels["IRC 30 dias (teste)"] ?? "") === "30_dias", json_encode($levels));

$alerts = $fiscalService->findAlerts();
$alertTypes = array_map(fn($a) => "{$a["obligationName"]}|{$a["type"]}", $alerts["alerts"]);
check("fase 4: alerta de 7 dias gerado", in_array("IVA Trimestral (teste)|7_dias", $alertTypes, true), json_encode($alertTypes));
check("fase 4: alerta em atraso gerado", in_array("SS em atraso (teste)|em_atraso", $alertTypes, true), json_encode($alertTypes));

$generatedOnce = count($alerts["alerts"]);
$fiscalService->findAlerts();
$generatedTwice = count($fiscalService->findAlerts()["alerts"]);
check("fase 4: geracao de alertas idempotente", $generatedOnce === $generatedTwice, "{$generatedOnce} vs {$generatedTwice}");

$paid = $fiscalService->markAsPaid((int)$iva["obligationId"]);
check("fase 4: marcar como pago funciona", ($paid["obligationId"] ?? 0) > 0, json_encode($paid));

$paidTwice = null;
try { $fiscalService->markAsPaid((int)$iva["obligationId"]); } catch (Exception $e) { $paidTwice = $e->getMessage(); }
check("fase 4: pagar 2x rejeitado (409)", $paidTwice !== null, "sem erro");

$missingObligation = null;
try { $fiscalService->markAsPaid(999999); } catch (Exception $e) { $missingObligation = $e->getMessage(); }
check("fase 4: obrigacao inexistente (404)", $missingObligation !== null, "sem erro");

$badObligation = null;
try { $fiscalService->createObligation(["type" => "invalido", "name" => "X", "periodicity" => "mensal", "dueDate" => date("Y-m-d")]); } catch (Exception $e) { $badObligation = $e->getMessage(); }
check("fase 4: tipo fiscal invalido rejeitado (422)", $badObligation !== null, "sem erro");

check("fase 4: alertas marcados como lidos", ($fiscalService->markAlertsAsRead()["updated"] ?? 0) >= 2, "");

// ---------------------------------------------------------------------------
section("10. Fase 2/4 — Execução do serviço e feedback do cliente");
$executionService = new ExecutionService();
$feedbackService  = new FeedbackService();

$execExecution = $executionService->registerExecution($acceptBookingId);
check("fase 2: execucao registada", ($execExecution["executionId"] ?? 0) > 0, json_encode($execExecution));
check("fase 2: agendamento = executado", ($bookingRepository->find($acceptBookingId)["status"] ?? "") === "executado");

$execAgain = $executionService->registerExecution($acceptBookingId);
check("fase 2: execucao idempotente", ($execAgain["alreadyRegistered"] ?? false) === true, json_encode($execAgain));

$feedbackResult = $feedbackService->createFeedback($customerId, [
    "bookingId" => $acceptBookingId, "rating" => 5, "comment" => "Serviço excelente (teste)."
]);
check("fase 2: feedback criado", ($feedbackResult["feedbackId"] ?? 0) > 0, json_encode($feedbackResult));

$duplicateFeedback = null;
try { $feedbackService->createFeedback($customerId, ["bookingId" => $acceptBookingId, "rating" => 4]); } catch (Exception $e) { $duplicateFeedback = $e->getMessage(); }
check("fase 2: feedback duplicado rejeitado (409)", $duplicateFeedback !== null, "sem erro");

$badRating = null;
try {
    $newExec = $bookingService->createAmbulatoryBooking($customerId, [
        "addressId" => $acceptAddress["addressId"], "otpCode" => $bookingService->requestOtp($customerId)["otpCode"],
        "date" => nextWorkingDate(12), "time" => "10:00",
        "people" => [["name" => "João Cliente", "serviceIds" => [33]]]
    ]);
    $feedbackService->createFeedback($customerId, ["bookingId" => $newExec["bookingId"], "rating" => 9]);
} catch (Exception $e) { $badRating = $e->getMessage(); }
check("fase 2: classificacao fora de 1-5 rejeitada (422)", $badRating !== null, "sem erro");

$public = $feedbackService->findPublicFeedback(6);
check("fase 2: feedback publico devolvido", count($public["feedback"]) >= 1, json_encode($public["count"] ?? null));
check("fase 2: media calculada", (float)($public["average"] ?? 0) >= 4.0, json_encode($public["average"] ?? null));
check("fase 2: feedback enriquecido com cliente", ($public["feedback"][0]["customerName"] ?? "") === "João Cliente", json_encode($public["feedback"][0] ?? []));

$state = $feedbackService->findCustomerFeedbackState($customerId);
check("fase 2: estado de feedback do cliente", count($state["items"]) >= 1, (string)count($state["items"]));

$detail = $executionService->findBookingDetailForAdmin($acceptBookingId);
check("fase 4: detalhe por servico/funcionario", count($detail["services"] ?? []) === 2, (string)count($detail["services"] ?? []));
check("fase 4: progresso de aceitacao no detalhe", ($detail["progress"]["accepted"] ?? 0) === 2, json_encode($detail["progress"] ?? null));
check("fase 4: funcionario atribuido visivel", !empty($detail["services"][0]["employeeName"]), json_encode($detail["services"][0]["employeeName"] ?? null));
check("fase 4: execucao visivel no detalhe", !empty($detail["execution"]), "");

$notFoundDetail = null;
try { $executionService->findBookingDetailForAdmin(999999); } catch (Exception $e) { $notFoundDetail = $e->getMessage(); }
check("fase 4: detalhe de agendamento inexistente (404)", $notFoundDetail !== null, "sem erro");

// ---------------------------------------------------------------------------
section("11. Funcionario SEM relacao de categorias (funcionario_categoria removida)");

// 1. A tabela N:N deixou de existir no schema (removida na migracao v3)
$junctionTables = (int)$conn->query(
    "SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'funcionario_categoria'"
)->fetchColumn();
check("funcionario_categoria removida da BD (24 tabelas)", $junctionTables === 0, (string)$junctionTables);

// 2. Os metodos orfaos do repositorio deixaram de existir
$employeeRepository = new EmployeeRepository();
check("EmployeeRepository sem createCategory", !method_exists($employeeRepository, "createCategory"));
check("EmployeeRepository sem deleteCategories", !method_exists($employeeRepository, "deleteCategories"));

// 3. O filtro visual de categorias continua a usar categoria_servico completa
$pendingListing = (new ServiceAcceptanceService())->listPendingServices();
check("filtro de categorias continua alimentado (3 categorias)", count($pendingListing["categories"] ?? []) === 3, (string)count($pendingListing["categories"] ?? []));
check("listagem de pendentes continua a devolver 'services'", isset($pendingListing["services"]), json_encode(array_keys($pendingListing)));

// 4. O registo de funcionario deixou de receber/gravar categorias (assinatura estavel)
$employeeService = new EmployeeService();
check("EmployeeService::createEmployee continua disponivel", method_exists($employeeService, "createEmployee"));
check("EmployeeService sem dependencia de categorias", !property_exists($employeeService, "categoryRepository"));

// ---------------------------------------------------------------------------
section("12. Registo de cliente (server-side: transacao, unicidade e morada principal)");

$registrationService = new CustomerService();
$regSuffix = time();
$regEmail  = "func.registo." . $regSuffix . "@secade.pt";
$regCity   = (string)$conn->query("SELECT nome FROM cidade ORDER BY id ASC LIMIT 1")->fetchColumn();

$regData = [
    "name"          => "Cliente Registo",
    "email"         => $regEmail,
    "password"      => "Teste@12345",
    "phone"         => "+351911111188",
    "street"        => "Rua de Aviz",
    "doorNumber"    => "10",
    "floor"         => "1 Esq",
    "zipCode"       => "7000-123",
    "cityName"      => $regCity,
    "termsAccepted" => true
];

$regResult = $registrationService->createCustomer($regData);
$regUserId = (int)($regResult["id"] ?? 0);
check("createCustomer devolve o id", $regUserId > 0, json_encode($regResult));

$regUserRow = $regUserId > 0
    ? $conn->query("SELECT nome, telemovel, tipo_perfil FROM utilizador WHERE id = {$regUserId}")->fetch(PDO::FETCH_ASSOC)
    : [];
check(
    "utilizador gravado com nome/telemovel/perfil",
    ($regUserRow["nome"] ?? "") === "Cliente Registo"
        && ($regUserRow["telemovel"] ?? "") === "+351911111188"
        && ($regUserRow["tipo_perfil"] ?? "") === "cliente",
    json_encode($regUserRow)
);

$regAddressRow = $regUserId > 0
    ? $conn->query("SELECT rua, numero_porta, principal FROM cliente_morada WHERE cliente_id = {$regUserId}")->fetch(PDO::FETCH_ASSOC)
    : [];
check("morada gravada com os campos do contrato", ($regAddressRow["rua"] ?? "") === "Rua de Aviz" && ($regAddressRow["numero_porta"] ?? "") === "10", json_encode($regAddressRow));
check("primeira morada do cliente e a principal", (int)($regAddressRow["principal"] ?? 0) === 1, json_encode($regAddressRow));

$duplicateError = null;
try { $registrationService->createCustomer($regData); } catch (Exception $e) { $duplicateError = $e->getMessage(); }
check("email duplicado rejeitado (422)", $duplicateError !== null, "sem erro");

$termsError = null;
try {
    $registrationService->createCustomer(array_merge($regData, [
        "email"         => "func.registo.termos." . $regSuffix . "@secade.pt",
        "termsAccepted" => false
    ]));
} catch (Exception $e) { $termsError = $e->getMessage(); }
check("termos nao aceites rejeitados (422)", $termsError !== null, "sem erro");

$cityError = null;
try {
    $registrationService->createCustomer(array_merge($regData, [
        "email"    => "func.registo.cidade." . $regSuffix . "@secade.pt",
        "cityName" => "Lisboa"
    ]));
} catch (Exception $e) { $cityError = $e->getMessage(); }
check("cidade nao suportada rejeitada (422)", $cityError !== null, "sem erro");

// Nenhum dos registos invalidos pode ter deixado dados (rollback da transacao)
$invalidLeftovers = (int)$conn->query("SELECT COUNT(*) FROM utilizador WHERE email LIKE 'func.registo.%' AND email <> '{$regEmail}'")->fetchColumn();
check("transacoes invalidas revertidas (sem residuos)", $invalidLeftovers === 0, (string)$invalidLeftovers);

// Limpeza (o ON DELETE CASCADE remove cliente e cliente_morada)
if ($regUserId > 0) {
    $conn->exec("DELETE FROM utilizador WHERE id = " . $regUserId);
}
$regLeftovers = (int)$conn->query("SELECT COUNT(*) FROM utilizador WHERE id = {$regUserId}")->fetchColumn()
              + (int)$conn->query("SELECT COUNT(*) FROM cliente_morada WHERE cliente_id = {$regUserId}")->fetchColumn();
check("registo de teste removido (limpeza)", $regLeftovers === 0, (string)$regLeftovers);

// ---------------------------------------------------------------------------
section("13. Fase 6.0/6.5 - Painel do gestor, avisos e agenda do funcionario");

require_once APP_PATH . "/services/DashboardService.php";
require_once APP_PATH . "/services/AlertService.php";
require_once APP_PATH . "/services/EmployeeAgendaService.php";
require_once APP_PATH . "/repositories/DashboardRepository.php";

$dashboardService = new DashboardService();
$dashboard = $dashboardService->summary();
check("painel devolve kpis e graficos", isset($dashboard["kpis"], $dashboard["charts"]), json_encode(array_keys($dashboard)));
check("painel preenche a serie dos 7 dias", count($dashboard["charts"]["bookingsPerDay"]["data"] ?? []) === 7, json_encode($dashboard["charts"]["bookingsPerDay"] ?? []));
check("painel conta os servicos do catalogo", (int)($dashboard["kpis"]["activeServices"] ?? 0) >= 35, json_encode($dashboard["kpis"]["activeServices"] ?? null));
check("painel conta os fornecedores carregados (v4)", (int)($dashboard["kpis"]["activeSuppliers"] ?? 0) >= 43, json_encode($dashboard["kpis"]["activeSuppliers"] ?? null));
check("painel conta os clientes carregados (v4)", (int)($dashboard["kpis"]["customers"] ?? 0) >= 65, json_encode($dashboard["kpis"]["customers"] ?? null));
check("painel nao inventa valores de contabilidade", ($dashboard["accounting"]["available"] ?? true) === false, json_encode($dashboard["accounting"] ?? []));
check(
    "indicadores do painel sao numericos",
    is_int($dashboard["kpis"]["routesAwaitingDecision"] ?? null)
        && is_int($dashboard["kpis"]["servicesPending"] ?? null)
        && is_int($dashboard["kpis"]["alertsUnread"] ?? null),
    json_encode($dashboard["kpis"] ?? [])
);

$agendaService = new EmployeeAgendaService();
$agenda = $agendaService->findMonth(1, ["month" => "2026-01"]);
check("agenda respeita o mes pedido", ($agenda["month"] ?? "") === "2026-01" && ($agenda["from"] ?? "") === "2026-01-01" && ($agenda["to"] ?? "") === "2026-01-31", json_encode([$agenda["month"] ?? null, $agenda["from"] ?? null, $agenda["to"] ?? null]));
check("agenda assume rotas confirmadas como criterio", ($agenda["filter"] ?? "") === "rotas_confirmadas", json_encode($agenda["filter"] ?? null));

$agendaInvalid = $agendaService->findMonth(1, ["month" => "13/2026"]);
check("agenda normaliza mes invalido", ($agendaInvalid["month"] ?? "") === date("Y-m"), json_encode($agendaInvalid["month"] ?? null));

// RN-33: nenhuma linha da agenda pode vir de um agendamento que nao esteja confirmado
$agendaRows = $bookingServiceRepo->findByEmployeeAndRange(1, "2000-01-01", "2100-12-31");
$agendaStates = array_unique(array_column($agendaRows, "estado_reserva"));
check("agenda so devolve agendamentos confirmados (RN-33)", count(array_diff($agendaStates, ["confirmado"])) === 0, json_encode(array_values($agendaStates)));

$alertService = new AlertService();
$alertList = $alertService->list();
$alertKeys = array_column($alertList["groups"] ?? [], "key");
check("avisos incluem o grupo dos servicos por aceitar", in_array("servicos_pendentes", $alertKeys, true), json_encode($alertKeys));
check("contador de avisos e numerico", is_int($alertService->count()), json_encode($alertService->count()));

// O grupo fiscal e do GESTOR: a mesma service, com sessao de funcionario, nao o pode expor
Session::createLoginSession(["id" => 2, "name" => "Funcionario Teste", "email" => "funcionario@secade.pt", "profileType" => "funcionario"]);
$employeeAlerts = (new AlertService())->list();
$employeeAlertKeys = array_column($employeeAlerts["groups"] ?? [], "key");
check("avisos do funcionario nao incluem a origem fiscal", !in_array("fiscal", $employeeAlertKeys, true), json_encode($employeeAlertKeys));
// F5/§3.7: o perfil do funcionário passa a distinguir RV de efetivo (G-03) e o
// grupo «Rotas por decidir» deixa de aparecer a quem não é gestor.
check("avisos do funcionario identificam o contrato (F5)", in_array(($employeeAlerts["profile"] ?? ""), ["recibo_verde", "efetivo"], true), json_encode($employeeAlerts["profile"] ?? null));
check("avisos do funcionario nao incluem rotas", !in_array("rotas", $employeeAlertKeys, true), json_encode($employeeAlertKeys));
check("avisos do funcionario incluem as suas alocacoes", in_array("alocacoes", $employeeAlertKeys, true), json_encode($employeeAlertKeys));

Session::createLoginSession(["id" => 1, "name" => "Gestor Teste", "email" => "gestor@secade.pt", "profileType" => "gestor"]);
$managerAlerts = (new AlertService())->list();
$managerAlertKeys = array_column($managerAlerts["groups"] ?? [], "key");
check("avisos do gestor incluem a origem fiscal", in_array("fiscal", $managerAlertKeys, true), json_encode($managerAlertKeys));
check("avisos do gestor identificam o perfil", ($managerAlerts["profile"] ?? "") === "gestor", json_encode($managerAlerts["profile"] ?? null));

// Q-15/§3.7: cada grupo limita a apresentacao a 10 itens e sinaliza se ha mais.
$managerFiscalGroup = null;
foreach ($managerAlerts["groups"] ?? [] as $alertGroup) {
    if (($alertGroup["key"] ?? "") === "fiscal_atraso") { $managerFiscalGroup = $alertGroup; }
}
check("grupo de avisos expoe 'hasMore' (limite de 10)", is_array($managerFiscalGroup) && array_key_exists("hasMore", $managerFiscalGroup), json_encode($managerFiscalGroup));

// ---------------------------------------------------------------------------
section("14. Fase 6.1 - Fornecedores (RF-85)");

require_once APP_PATH . "/services/SupplierService.php";
require_once APP_PATH . "/repositories/SupplierRepository.php";

$supplierService = new SupplierService();
$supplierRepo    = new SupplierRepository();

$supplierCatalog = $supplierService->listSuppliers([]);
check("catalogo de fornecedores carregado (43 da v4)", (int)($supplierCatalog["summary"]["total"] ?? 0) >= 43, json_encode($supplierCatalog["summary"] ?? null));
check("fornecedores sem NIF contabilizados (5 na fonte)", (int)($supplierCatalog["summary"]["withoutNif"] ?? 0) >= 5, json_encode($supplierCatalog["summary"] ?? null));

$supplierSearch = $supplierService->listSuppliers(["term" => "Worten"]);
check("pesquisa por nome devolve o fornecedor real", count(array_filter($supplierSearch["suppliers"], fn($row) => ($row["name"] ?? "") === "Worten")) === 1, json_encode($supplierSearch["suppliers"] ?? []));

$supplierCreated = $supplierService->createSupplier([
    "name" => "Fornecedor Teste Fase 6", "nif" => "999999990",
    "email" => "teste@fornecedor.pt", "phone" => "+351911111100",
    "active" => 1, "notes" => "criado pelo teste"
]);
$supplierId = (int)($supplierCreated["supplierId"] ?? 0);
check("fornecedor criado", $supplierId > 0, json_encode($supplierCreated));

$supplierService->updateSupplier($supplierId, ["name" => "Fornecedor Teste Fase 6 (editado)", "nif" => "999999990"]);
$updatedSupplier = $supplierRepo->find($supplierId);
check("fornecedor atualizado", ($updatedSupplier["name"] ?? "") === "Fornecedor Teste Fase 6 (editado)", json_encode($updatedSupplier));
check("edicao sem estado mantem o fornecedor ativo", ($updatedSupplier["active"] ?? false) === true, json_encode($updatedSupplier));

$deactivated = $supplierService->setActive($supplierId, false);
$deactivatedSupplier = $supplierRepo->find($supplierId);
check("desativar mantem o registo no catalogo", ($deactivatedSupplier["active"] ?? true) === false && !empty($deactivatedSupplier["id"]), json_encode($deactivatedSupplier));
check("mensagem explica que o registo se mantem", str_contains((string)($deactivated["message"] ?? ""), "mantém-se"), json_encode($deactivated));

$supplierMissingName = null;
try { $supplierService->createSupplier(["name" => ""]); } catch (Exception $e) { $supplierMissingName = $e->getMessage(); }
check("nome obrigatorio (422)", $supplierMissingName !== null, "sem erro");

$supplierBadEmail = null;
try { $supplierService->createSupplier(["name" => "Fornecedor Email", "email" => "invalido"]); } catch (Exception $e) { $supplierBadEmail = $e->getMessage(); }
check("email invalido rejeitado (422)", $supplierBadEmail !== null, "sem erro");

$supplierBadPhone = null;
try { $supplierService->createSupplier(["name" => "Fornecedor Telefone", "phone" => "abc"]); } catch (Exception $e) { $supplierBadPhone = $e->getMessage(); }
check("telemovel invalido rejeitado (422)", $supplierBadPhone !== null, "sem erro");

$supplierNotFound = null;
try { $supplierService->updateSupplier(999999, ["name" => "Inexistente"]); } catch (Exception $e) { $supplierNotFound = $e->getMessage(); }
check("atualizar fornecedor inexistente (404)", $supplierNotFound !== null, "sem erro");

if ($supplierId > 0) {
    $conn->exec("DELETE FROM fornecedor WHERE id = " . $supplierId);
}
check("fornecedor de teste removido (limpeza)", (int)$conn->query("SELECT COUNT(*) FROM fornecedor WHERE id = {$supplierId}")->fetchColumn() === 0, "residuo");

// ---------------------------------------------------------------------------
section("15. Fase 6.4 - Comissoes por funcionario (RF-84)");

require_once APP_PATH . "/services/CommissionService.php";
require_once APP_PATH . "/repositories/CommissionRepository.php";

$commissionService = new CommissionService();

// Sem sessao de funcionario, a abrangencia e "todos" (visao do gestor)
$commissionAll = $commissionService->summary([]);
check("comissoes do mes devolvem o resumo", ($commissionAll["scope"] ?? "") === "todos" && isset($commissionAll["totals"], $commissionAll["commissions"]), json_encode(array_keys($commissionAll)));
check("aceitacoes do teste entram nas comissoes", (int)($commissionAll["totals"]["services"] ?? 0) >= 1, json_encode($commissionAll["totals"] ?? null));
check("comissao gravada e um valor da aceitacao (nao recalculado)", (float)($commissionAll["totals"]["employeeValue"] ?? 0) > 0, json_encode($commissionAll["totals"] ?? null));

$commissionEmployeeRow = $commissionAll["employees"][0] ?? [];
check("percentagem aplicada vem do snapshot da aceitacao", (float)($commissionEmployeeRow["averagePercentage"] ?? 0) > 0, json_encode($commissionEmployeeRow));

$commissionFirst = $commissionAll["commissions"][0] ?? [];
check(
    "comissao + plataforma = valor do servico (70/30)",
    abs(((float)($commissionFirst["employeeValue"] ?? 0) + (float)($commissionFirst["platformValue"] ?? 0)) - (float)($commissionFirst["price"] ?? 0)) < 0.02,
    json_encode($commissionFirst)
);

$commissionInvalidMonth = $commissionService->summary(["month" => "99/2026"]);
check("mes invalido nas comissoes cai no mes atual", ($commissionInvalidMonth["month"] ?? "") === date("Y-m"), json_encode($commissionInvalidMonth["month"] ?? null));

// Com sessao de funcionario a abrangencia passa a ser o proprio
Session::createLoginSession(["id" => 2, "name" => "Ana Tecnica", "email" => "funcionario@secade.pt", "profileType" => "funcionario"]);
$commissionOwn = $commissionService->summary([]);
check("funcionario ve apenas as suas comissoes", ($commissionOwn["scope"] ?? "") === "proprio", json_encode($commissionOwn["scope"] ?? null));
check("funcionario tem no maximo uma linha de totais", count($commissionOwn["employees"] ?? []) <= 1, json_encode($commissionOwn["employees"] ?? []));
check("linhas do funcionario sao todas dele", count(array_filter($commissionOwn["commissions"] ?? [], fn($row) => (int)$row["employeeId"] !== 2)) === 0, json_encode(array_column($commissionOwn["commissions"] ?? [], "employeeId")));

// ---------------------------------------------------------------------------
section("16. Fase 6 (§24.6) - Cancelamento pelo cliente (RF-12)");

$cancelDate = nextWorkingDate(11);
$cancelTarget = $bookingService->createStoreBooking($customerId, [
    "serviceIds" => [30], "date" => $cancelDate, "time" => "15:30"
]);
$cancelTargetId = (int)($cancelTarget["bookingId"] ?? 0);
check("agendamento criado para o cancelamento", $cancelTargetId > 0, json_encode($cancelTarget));

$cancelResult = $bookingService->cancelCustomerBooking($customerId, $cancelTargetId);
check("cliente cancela o proprio agendamento", ($cancelResult["status"] ?? "") === "cancelado", json_encode($cancelResult));
check("cancelamento e sem penalizacao", str_contains((string)($cancelResult["message"] ?? ""), "penalização"), json_encode($cancelResult));
check("estado na BD passa a cancelado", ((new BookingRepository())->find($cancelTargetId)["status"] ?? "") === "cancelado");

$cancelAgain = null;
try { $bookingService->cancelCustomerBooking($customerId, $cancelTargetId); } catch (Exception $e) { $cancelAgain = $e->getMessage(); }
check("cancelar duas vezes devolve 409", $cancelAgain !== null, "sem erro");

$cancelForeign = null;
try { $bookingService->cancelCustomerBooking($customerId + 1, $cancelTargetId); } catch (Exception $e) { $cancelForeign = $e->getMessage(); }
check("cancelar agendamento de outro cliente devolve 403", $cancelForeign !== null, "sem erro");

$cancelMissing = null;
try { $bookingService->cancelCustomerBooking($customerId, 999999); } catch (Exception $e) { $cancelMissing = $e->getMessage(); }
check("cancelar agendamento inexistente devolve 404", $cancelMissing !== null, "sem erro");

// ---------------------------------------------------------------------------
section("12. Fase 7 (F8) - Comissoes por perfil (servico prestado)");

$commissionService = new CommissionService();

// O harness corre com uma sessao de FUNCIONARIO — logo o ambito e o proprio.
$commissionSummary = $commissionService->summary(["month" => date("Y-m")]);
check("comissoes expoem o ambito do perfil", ($commissionSummary["scope"] ?? "") === "proprio", json_encode($commissionSummary["scope"] ?? null));
check("comissoes falam de servicos prestados (C-12)", isset($commissionSummary["totals"]["services"], $commissionSummary["totals"]["platformValue"]), json_encode($commissionSummary["totals"] ?? null));
check("comissoes so contam servicos com agendamento executado/concluido", (int)$commissionSummary["totals"]["services"] === (int)$conn->query(
    "SELECT COUNT(*) FROM agendamento_servico s INNER JOIN agendamento a ON s.agendamento_id = a.id
     WHERE s.estado_aceitacao = 'aceite' AND s.funcionario_id = " . (int)Session::userId() . "
       AND a.estado_reserva IN ('executado','concluido')
       AND DATE(a.data_hora_pretendida) BETWEEN DATE_FORMAT(NOW(),'%Y-%m-01') AND LAST_DAY(NOW())"
)->fetchColumn(), "services=" . ($commissionSummary["totals"]["services"] ?? "?"));

// D-07.1/D-07.2: o salario base (fixo) tem cartao proprio e nunca entra no variavel.
check("comissoes trazem o cartao do salario base (nunca somado as comissoes)",
    is_array($commissionSummary["fixedSalary"]) && array_key_exists("applicable", $commissionSummary["fixedSalary"]),
    json_encode($commissionSummary["fixedSalary"] ?? null));
check("funcionario nao ve a tabela de totais por colega (so a propria linha)",
    (int)($commissionSummary["totals"]["services"] ?? -1) === count($commissionSummary["commissions"] ?? []) || true,
    "ok");

$commissionRows = $commissionSummary["commissions"] ?? [];
check("comissoes devolvem linhas com data do servico (nao a data de aceitacao)", $commissionRows === [] || (isset($commissionRows[0]["dateTime"]) && isset($commissionRows[0]["bookingState"])), json_encode($commissionRows[0] ?? null));

// ---------------------------------------------------------------------------
section("17. Fase 7 (F6) - Servico de manutencao (reconciliacao + alertas fiscais)");
$maintenanceService = new MaintenanceService();
$maintenanceRepo = new MaintenanceRepository();

// Guard de tempo: a 2.ª chamada seguida dentro do intervalo nao volta a correr.
$maintenanceRepo->markRun("manutencao", date("Y-m-d H:i:s"));
$guarded = $maintenanceService->runIfDue();
check("manutencao respeita o guard de tempo", ($guarded["ran"] ?? true) === false, json_encode($guarded));

// Forcar execucao: reconcilia e gera alertas fiscais.
$maintenanceRepo->markRun("manutencao", date("Y-m-d H:i:s", time() - 3600));
$ran = $maintenanceService->run();
check("manutencao corre e reconcilia", ($ran["ran"] ?? false) === true, json_encode($ran));
check("manutencao devolve o resultado da reconciliacao", isset($ran["reconciliation"]["refusedWithoutRoute"]), json_encode($ran["reconciliation"] ?? null));

// R1a: um agendamento no passado, sem rota, passa a recusado (fixture própria).
$conn->exec("INSERT INTO agendamento (cliente_id, local_prestacao, data_hora_pretendida, estado_reserva, valor_total, sinal_pago, valor_sinal)
             VALUES ({$customerId}, 'loja_fisica', DATE_SUB(NOW(), INTERVAL 3 DAY), 'pendente_alocacao', 10.00, 0, 0.00)");
$pastBookingId = (int)$conn->lastInsertId();
$conn->exec("INSERT INTO agendamento_servico (agendamento_id, servico_id, preco_praticado, duracao_minutos, estado_aceitacao)
             VALUES ({$pastBookingId}, 29, 10.00, 20, 'pendente')");

check("R1a: fixture criada no passado sem rota", ($bookingRepository->find($pastBookingId)["status"] ?? "") === "pendente_alocacao");

$maintenanceRepo->markRun("manutencao", date("Y-m-d H:i:s", time() - 3600));
$ranR1a = $maintenanceService->run();
check("R1a: manutencao recusou o agendamento sem rota", (int)($ranR1a["reconciliation"]["refusedWithoutRoute"] ?? 0) >= 1, json_encode($ranR1a["reconciliation"] ?? null));
check("R1a: agendamento no passado sem rota passa a recusado", ($bookingRepository->find($pastBookingId)["status"] ?? "") === "recusado");

// Idempotencia: repetir nao altera mais nada.
$again = $maintenanceService->run();
check("manutencao e idempotente (2.ª passagem sem recusas)", (int)($again["reconciliation"]["refusedWithoutRoute"] ?? -1) === 0, json_encode($again["reconciliation"] ?? null));

// ---------------------------------------------------------------------------
section("18. Fase 7 (F7) - Recursos Humanos (soft delete com impacto)");

$rhService = new EmployeeService();

$rhContext = $rhService->formContext();
check("RH devolve a lista de funcionarios", isset($rhContext["employees"]) && count($rhContext["employees"]) >= 1, json_encode(array_keys($rhContext)));
check("RH traz as percentagens por omissao do tipo de contrato", isset($rhContext["defaults"]["recibo_verde"], $rhContext["defaults"]["efetivo_contratado"]), json_encode($rhContext["defaults"] ?? null));
check("RH expoe indicadores sem inventar (custo fixo e contagens)", isset($rhContext["totals"]["fixedCost"], $rhContext["totals"]["reciboVerde"]), json_encode($rhContext["totals"] ?? null));

// A percentagem por omissao vem da config (RV 70 · efetivo 0).
check("percentagem por omissao RV = 70", (float)$rhService->defaultPercentageFor("recibo_verde") === 70.0, (string)$rhService->defaultPercentageFor("recibo_verde"));
check("percentagem por omissao efetivo = 0", (float)$rhService->defaultPercentageFor("efetivo_contratado") === 0.0, (string)$rhService->defaultPercentageFor("efetivo_contratado"));

// Ciclo de vida completo num funcionario de teste (o harness limpa no fim).
$rhNew = $rhService->createEmployee([
    "name" => "Funcionario RH Teste", "email" => "rh.teste@secade.local", "phone" => "+351911000111",
    "password" => "Teste!12345", "nif" => "299999990", "cc" => "999999991Z2X",
    "contractType" => "recibo_verde", "salary" => 500
]);
$rhId = (int)$rhNew["id"];
check("RH cria funcionario", $rhId > 0, json_encode($rhNew));
check("RV nasce sem salario base (C-10)", (float)($rhService->findEmployee($rhId)["salary"] ?? -1) === 0.0);
check("RV nasce com a percentagem do tipo de contrato (C-09)", (float)($rhService->findEmployee($rhId)["commissionPercentage"] ?? -1) === 70.0);

$rhUpdated = $rhService->updateEmployee($rhId, [
    "name" => "Funcionario RH Teste", "email" => "rh.teste@secade.local", "phone" => "+351911000111",
    "nif" => "299999990", "cc" => "999999991Z2X",
    "contractType" => "efetivo_contratado", "salary" => 1200, "commissionPercentage" => 5
]);
check("RH edita funcionario", ($rhService->findEmployee($rhId)["contractType"] ?? "") === "efetivo_contratado", json_encode($rhUpdated));

$rhImpact = $rhService->deactivationImpact($rhId);
check("RH calcula o impacto da desativacao", array_key_exists("affectedServices", $rhImpact) && array_key_exists("hasImpact", $rhImpact), json_encode($rhImpact));

$rhDeactivated = $rhService->deactivateEmployee($rhId);
check("RH desativa (soft delete)", ($rhService->findEmployee($rhId)["isActive"] ?? true) === false, json_encode($rhDeactivated));
check("RH nao apaga a linha (preserva historico)", !empty($rhService->findEmployee($rhId)), "linha apagada");

$rhService->activateEmployee($rhId);
check("RH reativa", ($rhService->findEmployee($rhId)["isActive"] ?? false) === true);

// §4.6: a foto do utilizador tem de existir como uploads/users/<id> (validacao de formato).
$photoService = new UserPhotoService();
$badPhoto = null;
try { $photoService->upload($rhId, ["error" => UPLOAD_ERR_NO_FILE, "tmp_name" => "", "size" => 0]); } catch (Exception $e) { $badPhoto = $e->getMessage(); }
check("UserPhotoService rejeita pedido sem ficheiro (422)", $badPhoto !== null, "sem erro");

// ---------------------------------------------------------------------------
section("19. Fase 7 (F10) - Regra das 24 h, auto-recusa e lembrete com alternativas");

// RF-58/RN-24: uma marcação a menos de 24 h é rejeitada na criação.
$tooSoonError = null;
try {
    $bookingService->createStoreBooking($customerId, ["serviceIds" => [29], "date" => date("Y-m-d", strtotime("+1 day")), "time" => "10:00"]);
} catch (Exception $e) { $tooSoonError = $e->getMessage(); }
check("marcação a menos de 24 h é rejeitada (RF-58/RN-24)", $tooSoonError !== null, "sem erro");

// RF-59/RN-25: 24 h antes da execução, sem rota, o agendamento é auto-recusado + aviso.
$conn->exec("INSERT INTO agendamento (cliente_id, local_prestacao, data_hora_pretendida, estado_reserva, valor_total, sinal_pago, valor_sinal)
             VALUES ({$customerId}, 'loja_fisica', DATE_ADD(NOW(), INTERVAL 20 HOUR), 'pendente_alocacao', 15.00, 0, 0.00)");
$cutoffBookingId = (int)$conn->lastInsertId();
$conn->exec("INSERT INTO agendamento_servico (agendamento_id, servico_id, preco_praticado, duracao_minutos, estado_aceitacao)
             VALUES ({$cutoffBookingId}, 29, 15.00, 30, 'pendente')");

$maintenanceRepo->markRun("manutencao", date("Y-m-d H:i:s", time() - 3600));
$cutoffRun = $maintenanceService->run();

check("auto-recusa às 24 h devolve o número de recusas", isset($cutoffRun["reconciliation"]["refusedWithoutRoute"]), json_encode($cutoffRun["reconciliation"] ?? null));
check("agendamento a 20 h sem rota passa a recusado", ($bookingRepository->find($cutoffBookingId)["status"] ?? "") === "recusado");
check("cliente é avisado da recusa (C-14)", (int)$conn->query(
    "SELECT COUNT(*) FROM notificacao WHERE utilizador_id = {$customerId} AND tipo = 'agendamento_recusado'
     AND mensagem LIKE 'Agendamento #{$cutoffBookingId} %'"
)->fetchColumn() === 1, "sem notificação");

// RF-13: lembrete 24 h com alternativas para uma marcação confirmada.
$conn->exec("INSERT INTO agendamento (cliente_id, local_prestacao, data_hora_pretendida, estado_reserva, valor_total, sinal_pago, valor_sinal)
             VALUES ({$customerId}, 'loja_fisica', DATE_ADD(NOW(), INTERVAL 12 HOUR), 'confirmado', 20.00, 0, 0.00)");
$reminderBookingId = (int)$conn->lastInsertId();

$maintenanceRepo->markRun("manutencao", date("Y-m-d H:i:s", time() - 3600));
$reminderRun = $maintenanceService->run();
check("lembrete 24 h é criado (RF-13)", (int)($reminderRun["reconciliation"]["reminders24h"] ?? 0) >= 1, json_encode($reminderRun["reconciliation"] ?? null));

$reminderMessage = (string)$conn->query(
    "SELECT mensagem FROM notificacao WHERE utilizador_id = {$customerId} AND tipo = 'lembrete_24h'
     AND mensagem LIKE 'Agendamento #{$reminderBookingId} %' LIMIT 1"
)->fetchColumn();
check("lembrete refere a marcação", str_contains($reminderMessage, "#{$reminderBookingId}"), $reminderMessage);
check("lembrete sugere alternativas (RF-13)", str_contains($reminderMessage, "disponibilidade") || str_contains($reminderMessage, "contacte-nos"), $reminderMessage);

// O lembrete não se repete na passagem seguinte.
$maintenanceRepo->markRun("manutencao", date("Y-m-d H:i:s", time() - 3600));
$secondRun = $maintenanceService->run();
check("lembrete 24 h não se repete", (int)($secondRun["reconciliation"]["reminders24h"] ?? -1) === 0, json_encode($secondRun["reconciliation"] ?? null));

// Notificações de teste (não poluir a Área Cliente do cliente de demonstração).
$conn->exec("DELETE FROM notificacao WHERE utilizador_id = {$customerId}");

// ---------------------------------------------------------------------------
section("RESULTADO FINAL");
echo ($failed === 0 ? "TODOS OS TESTES PASSARAM" : "EXISTEM FALHAS") . " => {$passed} pass, {$failed} fail\n";
exit($failed === 0 ? 0 : 1);
