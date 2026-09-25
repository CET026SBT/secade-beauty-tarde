<?php
/**
 * Verificação final: assets servidos (200) e injeção de scripts por register_script().
 */
$base = "http://localhost/secade-beauty-tarde";
$jar = sys_get_temp_dir() . "/sb_final_cookies.txt";
@unlink($jar);

function http(string $url, ?string $jar = null, ?array $jsonBody = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    if ($jar) { curl_setopt($ch, CURLOPT_COOKIEJAR, $jar); curl_setopt($ch, CURLOPT_COOKIEFILE, $jar); }
    if ($jsonBody !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonBody));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    }
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ["status" => $status, "body" => (string)$body];
}

$passed = 0; $failed = 0;
function check(string $label, bool $ok, string $extra = "") {
    global $passed, $failed;
    if ($ok) { $passed++; echo "  PASS  {$label}\n"; }
    else { $failed++; echo "  FAIL  {$label}  {$extra}\n"; }
}

// Login como cliente (para aceder ao wizard) e como gestor (backoffice)
http("{$base}/api?action=auth-login", $jar, ["email" => "cliente@teste.pt", "password" => "Cliente@123"]);
$gestorJar = sys_get_temp_dir() . "/sb_final_gestor.txt";
@unlink($gestorJar);
http("{$base}/api?action=auth-login", $gestorJar, ["email" => "gestor@secade.pt", "password" => "Gestor@123"]);

echo "\n=== Assets estáticos (devem ser 200 e servir JS/CSS real) ===\n";
$assets = [
    "modules/main/js/components/services.js",
    "modules/main/js/components/bookingWizard.js",
    "modules/main/js/components/profile.js",
    "modules/main/js/components/appointments.js",
    "modules/main/js/components/testimonial.js",
    "modules/main/js/components/customerRegister.js",
    "modules/common/js/validators/booking.validator.js",
    "modules/common/js/validators/customer.validator.js",
    "modules/common/js/validators/user.validator.js",
    "modules/common/js/utils/addressAutocomplete.js",
    "modules/common/js/utils/general.utils.js",
    "modules/common/js/api/api.js",
    "modules/backoffice/js/bo.utils.js",
    "modules/backoffice/js/components/appointments.js",
    "modules/backoffice/js/components/routes.js",
    "modules/backoffice/js/components/services.js",
    "modules/backoffice/js/components/fiscal.js",
    "modules/backoffice/js/components/greenReceipts.js",
    "modules/common/css/ext-bootstrap.css",
    "modules/common/css/style.css"
];

foreach ($assets as $asset) {
    $response = http("{$base}/{$asset}");
    check(basename($asset) . " serve 200 com conteúdo", $response["status"] === 200 && strlen($response["body"]) > 100,
        "status={$response["status"]} bytes=" . strlen($response["body"]));
}

echo "\n=== Injeção de scripts por página (register_script) ===\n";
$pages = [
    ["/servicos/cabelereiro", null,  ["components/services.js"]],
    ["/agendar",              $jar,  ["components/bookingWizard.js", "validators/booking.validator.js"]],
    ["/perfil",               $jar,  ["components/profile.js"]],
    ["/agendamentos",         $jar,  ["components/appointments.js"]],
    ["/gestao/agendamentos",  $gestorJar, ["components/appointments.js", "bo.utils.js"]],
    ["/gestao/rotas",         $gestorJar, ["components/routes.js", "bo.utils.js"]],
    ["/gestao/fiscal",        $gestorJar, ["components/fiscal.js", "bo.utils.js"]],
    ["/gestao/recibos-verdes",$gestorJar, ["components/greenReceipts.js", "bo.utils.js"]]
];

foreach ($pages as [$path, $cookieJar, $expectedScripts]) {
    $response = http($base . $path, $cookieJar);
    $ok = $response["status"] === 200;
    foreach ($expectedScripts as $script) {
        $ok = $ok && str_contains($response["body"], $script);
    }
    check("{$path} injeta " . implode(" + ", $expectedScripts), $ok, "status={$response["status"]}");
}

echo "\n=== Elementos-chave do backoffice ===\n";
$routesPage = http("{$base}/gestao/rotas", $gestorJar);
check("página de rotas mostra a referência de 50 €", str_contains($routesPage["body"], "50"));
check("página de rotas indica decisão manual", str_contains($routesPage["body"], "Decisão manual"));
check("página de rotas tem filtro de data pré-preenchido", preg_match('/id="routeDate"[^>]*value="\d{4}-\d{2}-\d{2}"/', $routesPage["body"]) === 1);

$fiscalPage = http("{$base}/gestao/fiscal", $gestorJar);
check("página fiscal mostra alertas 30/15/7/3/1", str_contains($fiscalPage["body"], "30 / 15 / 7 / 3 / 1"));
check("página fiscal tem formulário de obrigação", str_contains($fiscalPage["body"], "obligationFormCard"));

$servicesPage = http("{$base}/gestao/servicos", $gestorJar);
check("página de serviços do funcionário carrega", str_contains($servicesPage["body"], "pendingList") && str_contains($servicesPage["body"], "acceptedList"));

echo "\n=== Contrato de nomes do formulário de registo (alinhado com os mappers) ===\n";

$registerPage = http("{$base}/registo");
check("GET /registo responde 200", $registerPage["status"] === 200, (string)$registerPage["status"]);

// Os atributos `name` refletem as chaves que a API espera
foreach (["name", "email", "password", "confirmPassword", "phone", "street", "doorNumber", "floor", "zipCode", "cityName", "termsAccepted"] as $fieldName) {
    check("campo do registo usa o nome '{$fieldName}'", str_contains($registerPage["body"], "name=\"{$fieldName}\""));
}

foreach (["nome", "telemovel", "morada", "numPorta", "andarBloco", "termosCondicoes", "codigoPostal", "cidade"] as $legacyName) {
    check("campo legado '{$legacyName}' ausente do registo", !str_contains($registerPage["body"], "name=\"{$legacyName}\""));
}

$customerValidatorJs = http("{$base}/modules/common/js/validators/customer.validator.js")["body"];
foreach (["street", "doorNumber", "zipCode", "cityName", "termsAccepted"] as $validatorKey) {
    check("customer.validator.js valida '{$validatorKey}'", str_contains($customerValidatorJs, "{$validatorKey}(val"));
}
check("customer.validator.js sem chaves legadas", !str_contains($customerValidatorJs, "morada(val") && !str_contains($customerValidatorJs, "termosCondicoes(val"));

$userValidatorJs = http("{$base}/modules/common/js/validators/user.validator.js")["body"];
check("user.validator.js valida 'name'", str_contains($userValidatorJs, "name(val"));
check("user.validator.js valida 'phone'", str_contains($userValidatorJs, "phone(val"));
check("user.validator.js sem chaves legadas", !str_contains($userValidatorJs, "nome(val") && !str_contains($userValidatorJs, "telemovel(val"));

$autocompleteJs = http("{$base}/modules/common/js/utils/addressAutocomplete.js")["body"];
check("addressAutocomplete declara FIELD_NAMES", str_contains($autocompleteJs, "FIELD_NAMES"));
check("addressAutocomplete sem seletores legados", !str_contains($autocompleteJs, 'name="morada"') && !str_contains($autocompleteJs, 'name="numPorta"') && !str_contains($autocompleteJs, 'name="cidade"'));
check("addressAutocomplete mapeia campos com nomes da API", str_contains($autocompleteJs, "doorNumber") && str_contains($autocompleteJs, "zipCode") && str_contains($autocompleteJs, "cityName"));

echo "\n" . ($failed === 0 ? "VERIFICAÇÃO FINAL OK" : "VERIFICAÇÃO FINAL COM FALHAS") . " => {$passed} pass, {$failed} fail\n";
exit($failed === 0 ? 0 : 1);