<?php
/**
 * Sanity check de sintaxe JS (sem Node): remove strings/comentários/regex e
 * verifica o balanceamento de (), {}, [] e a presença de delimitadores de bloco.
 */

$files = [
    "modules/main/js/components/services.js",
    "modules/main/js/components/bookingWizard.js",
    "modules/main/js/components/profile.js",
    "modules/main/js/components/appointments.js",
    "modules/main/js/components/testimonial.js",
    "modules/common/js/validators/booking.validator.js",
    "modules/common/js/utils/general.utils.js",
    "modules/common/js/api/api.js",
    "modules/backoffice/js/bo.js",
    "modules/backoffice/js/bo.utils.js",
    "modules/backoffice/js/components/appointments.js",
    "modules/backoffice/js/components/routes.js",
    "modules/backoffice/js/components/services.js",
    "modules/backoffice/js/components/fiscal.js",
    "modules/backoffice/js/components/greenReceipts.js"
];

$root = dirname(__DIR__, 2);
$failures = 0;

function stripJs(string $src): string {
    $out = "";
    $len = strlen($src);
    $i = 0;
    $prevSignificant = "";

    // Contextos: code | template | interp (interpolação ${ ... } dentro de template literal)
    $stack = [["type" => "code"]];

    while ($i < $len) {
        $ctx = $stack[count($stack) - 1];
        $ch = $src[$i];
        $next = $i + 1 < $len ? $src[$i + 1] : "";

        // --- Dentro de um template literal ---
        if ($ctx["type"] === "template") {
            if ($ch === "\\") { $i += 2; continue; }
            if ($ch === "`") { array_pop($stack); $prevSignificant = "x"; $i++; continue; }
            if ($ch === "$" && $next === "{") { $stack[] = ["type" => "interp", "depth" => 0]; $i += 2; continue; }
            $i++;
            continue;
        }

        // --- Dentro de uma interpolação ${ ... } ---
        if ($ctx["type"] === "interp") {
            if ($ch === "{") { $stack[count($stack) - 1]["depth"]++; $out .= "{"; $i++; continue; }
            if ($ch === "}") {
                if ($ctx["depth"] === 0) { array_pop($stack); $i++; continue; }
                $stack[count($stack) - 1]["depth"]--;
                $out .= "}";
                $i++;
                continue;
            }
        }

        // --- Contexto de código ---
        if ($ch === "/" && $next === "/") {
            while ($i < $len && $src[$i] !== "\n") { $i++; }
            continue;
        }

        if ($ch === "/" && $next === "*") {
            $i += 2;
            while ($i < $len - 1 && !($src[$i] === "*" && $src[$i + 1] === "/")) { $i++; }
            $i += 2;
            continue;
        }

        if ($ch === "'" || $ch === '"') {
            $quote = $ch;
            $i++;
            while ($i < $len) {
                if ($src[$i] === "\\") { $i += 2; continue; }
                if ($src[$i] === $quote) { $i++; break; }
                $i++;
            }
            $prevSignificant = "x";
            continue;
        }

        if ($ch === "`") {
            $stack[] = ["type" => "template"];
            $i++;
            continue;
        }

        if ($ch === "/" && ($prevSignificant === "" || str_contains("(,=:[!&|?{};+-*%<>~^", $prevSignificant))) {
            $i++;
            $inClass = false;
            while ($i < $len) {
                if ($src[$i] === "\\") { $i += 2; continue; }
                if ($src[$i] === "[") { $inClass = true; }
                elseif ($src[$i] === "]") { $inClass = false; }
                elseif ($src[$i] === "/" && !$inClass) { $i++; break; }
                elseif ($src[$i] === "\n") { break; }
                $i++;
            }
            $prevSignificant = "x";
            continue;
        }

        if (!ctype_space($ch)) { $prevSignificant = $ch; }
        $out .= $ch;
        $i++;
    }

    return $out;
}

foreach ($files as $relative) {
    $path = $root . "/" . $relative;

    if (!file_exists($path)) { continue; }

    $source = file_get_contents($path);
    $cleaned = stripJs($source);

    $counts = [
        "(" => substr_count($cleaned, "("), ")" => substr_count($cleaned, ")"),
        "{" => substr_count($cleaned, "{"), "}" => substr_count($cleaned, "}"),
        "[" => substr_count($cleaned, "["), "]" => substr_count($cleaned, "]")
    ];

    $balanced = $counts["("] === $counts[")"] && $counts["{"] === $counts["}"] && $counts["["] === $counts["]"];
    $hasContent = strlen(trim($source)) > 50;

    if ($balanced && $hasContent) {
        echo "  PASS  {$relative}  (braces {$counts["{"]}/{$counts["}"]}, parens {$counts["("]}/{$counts[")"]}, brackets {$counts["["]}/{$counts["]"]})\n";
    } else {
        $failures++;
        echo "  FAIL  {$relative}  " . json_encode($counts) . ($hasContent ? "" : " [ficheiro vazio]") . "\n";
    }
}

echo "\n" . ($failures === 0 ? "SINTAXE JS: OK" : "SINTAXE JS: {$failures} falha(s)") . "\n";
exit($failures === 0 ? 0 : 1);