<?php
/**
 * Gera `database_migration_v4.sql` a partir das fontes entregues pelo cliente (28/09/2026):
 *   - "Secade Duração Serviços 1.ods"          -> duracao_estimada_minutos dos 35 servicos
 *   - "Serviços, Clientes e Fornecedores.xlsx" -> fornecedores (43) e clientes (65)
 * Uso unico e local (nao faz parte do produto). Falha alto quando algo nao casa.
 */
const NS_TABLE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
const NS_OFFICE_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

function odsTables(string $file): array
{
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) { fwrite(STDERR, "nao abriu ods: {$file}\n"); exit(1); }
    $xml = $zip->getFromName('content.xml');
    $zip->close();

    $doc = new DOMDocument();
    $doc->loadXML($xml);

    $tables = [];
    foreach ($doc->getElementsByTagNameNS(NS_TABLE, 'table') as $table) {
        $rows = [];
        foreach ($table->getElementsByTagNameNS(NS_TABLE, 'table-row') as $row) {
            $cells = [];
            foreach ($row->childNodes as $cell) {
                if ($cell->localName !== 'table-cell' && $cell->localName !== 'covered-table-cell') { continue; }
                $repeat = (int)($cell->getAttributeNS(NS_TABLE, 'number-columns-repeated') ?: 1);
                $text = trim(preg_replace('~\s+~u', ' ', $cell->textContent));
                for ($i = 0; $i < $repeat; $i++) { $cells[] = $text; }
            }
            while ($cells && end($cells) === '') { array_pop($cells); }
            if ($cells) { $rows[] = $cells; }
        }
        $tables[] = $rows;
    }
    return $tables;
}

function xlsxSheet(string $file): array
{
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) { fwrite(STDERR, "nao abriu xlsx: {$file}\n"); exit(1); }

    $shared = [];
    $ss = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss !== false && $ss !== '') {
        $doc = new DOMDocument();
        $doc->loadXML($ss);
        foreach ($doc->getElementsByTagName('si') as $si) {
            $txt = '';
            foreach ($si->getElementsByTagName('t') as $t) { $txt .= $t->textContent; }
            $shared[] = $txt;
        }
    }

    $rels = [];
    $relXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($relXml !== false) {
        $doc = new DOMDocument();
        $doc->loadXML($relXml);
        foreach ($doc->getElementsByTagName('Relationship') as $rel) {
            $rels[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
        }
    }

    $wb = $zip->getFromName('xl/workbook.xml');
    $doc = new DOMDocument();
    $doc->loadXML($wb);
    $first = $doc->getElementsByTagName('sheet')->item(0);
    $target = ltrim(str_replace('xl/', '', $rels[$first->getAttributeNS(NS_OFFICE_REL, 'id')] ?? ''), '/');

    $xml = $zip->getFromName('xl/' . $target);
    $zip->close();

    $doc = new DOMDocument();
    $doc->loadXML($xml);

    $rows = [];
    foreach ($doc->getElementsByTagName('row') as $row) {
        $cells = [];
        foreach ($row->getElementsByTagName('c') as $c) {
            $ref = $c->getAttribute('r');
            $col = preg_replace('/\d+/', '', $ref);
            $idx = 0;
            for ($i = 0, $n = strlen($col); $i < $n; $i++) { $idx = $idx * 26 + (ord($col[$i]) - 64); }
            $idx--;

            $v = $c->getElementsByTagName('v')->item(0);
            if ($c->getAttribute('t') === 's' && $v) {
                $cells[$idx] = $shared[(int)$v->textContent] ?? '';
            } elseif ($v) {
                $cells[$idx] = $v->textContent;
            }
        }
        if ($cells) { $rows[(int)$row->getAttribute('r')] = $cells; }
    }
    ksort($rows);
    return $rows;
}

/** Normaliza para comparacao: minusculas, sem acentos, so alfanumericos. */
function norm(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value) ?: $value;
    return preg_replace('/[^a-z0-9]/', '', $value);
}

/** Slug para e-mail: minusculas, sem acentos, pontos. */
function slug(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value) ?: $value;
    return trim(preg_replace('/[^a-z0-9]+/', '.', $value), '.');
}

/** Duracao textual do .ods -> minutos. */
function minutos(string $value): ?int
{
    $value = trim($value);
    if (preg_match('~^(\d+)\s*h\s*(\d+)?~iu', $value, $m)) {
        return ((int)$m[1]) * 60 + (int)($m[2] ?? 0);
    }
    if (preg_match('~^(\d+)\s*min~iu', $value, $m)) {
        return (int)$m[1];
    }
    return null;
}

$dir = __DIR__;
$ods = $dir . "/Secade Duração Serviços 1.ods";
$xlsx = $dir . "/Serviços, Clientes e Fornecedores.xlsx";
$dst = dirname($dir, 2) . "/database_migration_v4.sql";

/* ---------- 1. duracao dos servicos (Secade Duracao Servicos 1.ods) ---------- */

$tables = odsTables($ods);
$linhasOds = $tables[0] ?? [];

$blocos = null;
$duracoes = [];
foreach ($linhasOds as $row) {
    $campo = array_map('norm', $row);
    if ($blocos === null) {
        if (in_array('duracao', $campo, true)) {
            $blocos = [];
            foreach ($campo as $i => $celula) {
                if ($celula === 'servico') { $blocos[] = $i; }
            }
            if (!$blocos) { fwrite(STDERR, "cabecalho do .ods sem blocos\n"); exit(1); }
        }
        continue;
    }
    foreach ($blocos as $inicio) {
        $nome = trim($row[$inicio] ?? '');
        $duracao = minutos($row[$inicio + 1] ?? '');
        if ($nome === '' || $duracao === null) { continue; }
        $duracoes[$nome] = $duracao;
    }
}

echo "duracao: " . count($duracoes) . " servicos lidos do .ods\n";

/* ---------- 2. servicos e cidades na BD (chave de casamento) ---------- */

$pdo = new PDO("mysql:host=127.0.0.1;dbname=secade_beauty;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$servicoPorNome = [];
foreach ($pdo->query("SELECT id, nome FROM servico") as $row) {
    $servicoPorNome[norm($row["nome"])] = (int)$row["id"];
}

$cidadePorNome = [];
foreach ($pdo->query("SELECT id, nome FROM cidade") as $row) {
    $cidadePorNome[norm($row["nome"])] = (int)$row["id"];
}

$updates = [];
foreach ($duracoes as $nome => $min) {
    $id = $servicoPorNome[norm($nome)] ?? null;
    // Grafias do ficheiro que divergem do catálogo (a corrigir na BD com decisão — §24.11):
    //   .ods/.xlsx "Cordolete"      = BD "Cordelete" (id 2)
    //   .ods "Tranças Box Braids"   = BD "Box Braids" (id 1)
    $alias = ["cordolete" => "cordelete", "trancasboxbraids" => "boxbraids"];
    if ($id === null) { $id = $servicoPorNome[$alias[norm($nome)] ?? ''] ?? null; }
    if ($id === null) { fwrite(STDERR, "SEM CASAMENTO (duracao): {$nome}\n"); continue; }
    $updates[$id] = [$nome, $min];
}
ksort($updates);
echo "duracao: " . count($updates) . " servicos casados\n";

/* ---------- 3. fornecedores e clientes (Servicos, Clientes e Fornecedores.xlsx) ---------- */

$folha = xlsxSheet($xlsx);

$fornecedores = [];
$clientes = [];
foreach ($folha as $n => $row) {
    if ($n === 1) { continue; }

    $nomeF = trim($row[7] ?? '');
    if ($nomeF !== '') {
        $fornecedores[] = ["nome" => $nomeF, "nif" => trim($row[8] ?? '')];
    }

    $nomeC = trim($row[11] ?? '');
    if ($nomeC !== '') {
        $clientes[] = [
            "nome" => $nomeC,
            "nif" => trim($row[12] ?? ''),
            "localidade" => trim($row[13] ?? '')
        ];
    }
}

echo "fornecedores: " . count($fornecedores) . "\n";
echo "clientes: " . count($clientes) . "\n";

/** NIF util = exatamente 9 digitos; os restantes rotulos do ficheiro vao para observacoes. */
function nifOuNulo(string $valor): ?string
{
    $digitos = preg_replace('/\D/', '', $valor);
    return strlen($digitos) === 9 ? $digitos : null;
}

/* ---------- 4. escrita do ficheiro ---------- */

$q = fn(?string $v): string => $v === null ? "NULL" : "'" . str_replace("'", "''", $v) . "'";
$nl = "\r\n";
$sql = "";

$sql .= "-- --------------------------------------------------------" . $nl;
$sql .= "-- MIGRAÇÃO INCREMENTAL secade_beauty: v3 -> v4 (Fase 6 — dados entregues pelo cliente)" . $nl;
$sql .= "-- Aplica sobre a BD existente SEM destruir dados de negócio." . $nl;
$sql .= "-- Idempotente: pode correr várias vezes (ids explícitos + ON DUPLICATE KEY UPDATE)." . $nl;
$sql .= "--" . $nl;
$sql .= "-- FONTES (28/09/2026, ambas do cliente):" . $nl;
$sql .= "--   * `Secade Duração Serviços 1.ods`          -> `servico.duracao_estimada_minutos` (35 serviços)" . $nl;
$sql .= "--   * `Serviços, Clientes e Fornecedores.xlsx` -> `fornecedor` (43) + clientes (65)" . $nl;
$sql .= "--" . $nl;
$sql .= "-- NOTAS DE INTERPRETAÇÃO (o que o ficheiro NÃO traz não foi inventado):" . $nl;
$sql .= "--   * Preços: os 35 valores base do .xlsx já coincidiam com a BD — nada a alterar." . $nl;
$sql .= "--     (o ficheiro mostra o PVP com IVA a 23 % e o valor base tributável)" . $nl;
$sql .= "--   * Durações: vêm do .ods e substituem as do seed (eram estimativas por defeito)." . $nl;
$sql .= "--   * Clientes: o ficheiro só traz nome, NIF e localidade. O e-mail de acesso é" . $nl;
$sql .= "--     gerado (<slug-do-nome>.<NIF>@cliente.secade.local) e a conta fica SEM" . $nl;
$sql .= "--     credenciais utilizáveis; o telemóvel fica em branco; a rua fica em branco" . $nl;
$sql .= "--     (só a cidade é conhecida). A completar pelo cliente/backoffice." . $nl;
$sql .= "--   * Fornecedores: 5 linhas não têm NIF (\"NP (não possui) NIF\" em 4 — Temu," . $nl;
$sql .= "--     ViceDeal.com, Aliexpress, Consumíveis — e \"NIF indisponível nas plataformas" . $nl;
$sql .= "--     digitais\" em Bandido Portugal.pt): o rótulo do ficheiro vai para" . $nl;
$sql .= "--     `observacoes` e o `nif` fica NULL. \"Manuel jacinto (renda)\" aparece duas" . $nl;
$sql .= "--     vezes com NIF diferente: são dois registos (rendas distintas)." . $nl;
$sql .= "--   * Rótulos de fornecedores e localidades vêm de células de FÓRMULA" . $nl;
$sql .= "--     (`t=\"str\"`) — lidas pelo valor, não pela fórmula." . $nl;
$sql .= "-- --------------------------------------------------------" . $nl;
$sql .= "USE `secade_beauty`;" . $nl . $nl;
$sql .= "SET NAMES utf8mb4;" . $nl;
$sql .= "SET FOREIGN_KEY_CHECKS = 0;" . $nl . $nl;

/* -- 1. durações --------------------------------------------------------- */

$sql .= "-- --------------------------------------------------------" . $nl;
$sql .= "-- 1. servico: duração estimada real (fonte: `Secade Duração Serviços 1.ods`)" . $nl;
$sql .= "--    O .ods é uma tabela de 3 blocos (Cabeleireiro | Estética | Barbearia)." . $nl;
$sql .= "-- --------------------------------------------------------" . $nl;
foreach ($updates as $id => [$nome, $min]) {
    $sql .= "UPDATE `servico` SET `duracao_estimada_minutos` = {$min} WHERE `id` = {$id};  -- {$nome}" . $nl;
}
$sql .= $nl;

/* -- 2. fornecedor ------------------------------------------------------- */

$sql .= "-- --------------------------------------------------------" . $nl;
$sql .= "-- 2. fornecedor (NOVO — módulo da Fase 6.1, prioridade máxima da §25.1)" . $nl;
$sql .= "--    Campos só com o que existe ou é laborável: o ficheiro traz nome + NIF." . $nl;
$sql .= "-- --------------------------------------------------------" . $nl;
$sql .= "CREATE TABLE IF NOT EXISTS `fornecedor` (" . $nl;
$sql .= "  `id` int NOT NULL AUTO_INCREMENT," . $nl;
$sql .= "  `nome` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL," . $nl;
$sql .= "  `nif` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL," . $nl;
$sql .= "  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL," . $nl;
$sql .= "  `telemovel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL," . $nl;
$sql .= "  `ativo` tinyint(1) NOT NULL DEFAULT '1'," . $nl;
$sql .= "  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci," . $nl;
$sql .= "  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP," . $nl;
$sql .= "  PRIMARY KEY (`id`)," . $nl;
$sql .= "  KEY `nome` (`nome`)" . $nl;
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;" . $nl . $nl;

$sql .= "INSERT INTO `fornecedor` (`id`, `nome`, `nif`, `observacoes`) VALUES" . $nl;
$linhas = [];
foreach ($fornecedores as $i => $f) {
    $id = $i + 1;
    $nif = nifOuNulo($f["nif"]);
    $obs = $nif === null ? ($f["nif"] !== '' ? $f["nif"] : null) : null;
    $linhas[] = "\t({$id}, " . $q($f["nome"]) . ", " . $q($nif) . ", " . $q($obs) . ")";
}
$sql .= implode("," . $nl, $linhas) . $nl;
$sql .= "ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `nif` = VALUES(`nif`), `observacoes` = VALUES(`observacoes`);" . $nl . $nl;

/* -- 3. clientes --------------------------------------------------------- */

$sql .= "-- --------------------------------------------------------" . $nl;
$sql .= "-- 3. clientes importados (fonte: `Serviços, Clientes e Fornecedores.xlsx`)" . $nl;
$sql .= "--    Ids reservados a partir de 100 (a BD usava 1, 2, 3, 53, 74)." . $nl;
$sql .= "--    `password_hash` = '*' — conta importada, sem credenciais utilizáveis:" . $nl;
$sql .= "--    `password_verify()` devolve sempre false, logo não há acesso." . $nl;
$sql .= "-- --------------------------------------------------------" . $nl;

$sql .= "INSERT INTO `utilizador` (`id`, `nome`, `email`, `password_hash`, `telemovel`, `nif`, `tipo_perfil`) VALUES" . $nl;
$linhas = [];
foreach ($clientes as $i => $c) {
    $id = 100 + $i;
    $nif = nifOuNulo($c["nif"]);
    $email = slug($c["nome"]) . "." . ($nif ?? "s" . $id) . "@cliente.secade.local";
    $linhas[] = "\t({$id}, " . $q($c["nome"]) . ", " . $q($email) . ", '*', '', " . $q($nif) . ", 'cliente')";
}
$sql .= implode("," . $nl, $linhas) . $nl;
$sql .= "ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `nif` = VALUES(`nif`);" . $nl . $nl;

$sql .= "INSERT INTO `cliente` (`id`, `telemovel_validado_otp`) VALUES" . $nl;
$linhas = [];
foreach ($clientes as $i => $c) {
    $linhas[] = "\t(" . (100 + $i) . ", 0)";
}
$sql .= implode("," . $nl, $linhas) . $nl;
$sql .= "ON DUPLICATE KEY UPDATE `telemovel_validado_otp` = VALUES(`telemovel_validado_otp`);" . $nl . $nl;

$semCidade = [];
// Grafia do ficheiro: "Arraiaolos" (5 clientes) = cidade "Arraiolos" — gralha do ficheiro.
$aliasCidade = ["arraiaolos" => "arraiolos"];
$sql .= "-- Localidade do ficheiro -> `cliente_morada` (rua e código postal NÃO constam do" . $nl;
$sql .= "-- ficheiro: ficam em branco em vez de inventados). \"Arraiaolos\" (gralha de 5" . $nl;
$sql .= "-- linhas) foi casado com a cidade \"Arraiolos\"." . $nl;
$sql .= "INSERT INTO `cliente_morada` (`id`, `cliente_id`, `cidade_id`, `designacao`, `rua`, `numero_porta`, `andar_bloco`, `codigo_postal`, `principal`) VALUES" . $nl;
$linhas = [];
foreach ($clientes as $i => $c) {
    $chave = norm($c["localidade"]);
    $cidade = $cidadePorNome[$chave] ?? $cidadePorNome[$aliasCidade[$chave] ?? ''] ?? null;
    if ($cidade === null) { $semCidade[] = $c["nome"] . " / " . $c["localidade"]; continue; }
    $linhas[] = "\t(" . (200 + $i) . ", " . (100 + $i) . ", {$cidade}, 'Casa', '', NULL, NULL, NULL, 1)";
}
$sql .= implode("," . $nl, $linhas) . $nl;
$sql .= "ON DUPLICATE KEY UPDATE `cidade_id` = VALUES(`cidade_id`);" . $nl . $nl;

$sql .= "SET FOREIGN_KEY_CHECKS = 1;" . $nl;
$sql .= "-- esperado: 35 durações novas · fornecedor=43 · utilizador cliente=65 · cliente=65" . $nl;

file_put_contents($dst, $sql);
echo "escrito: {$dst} (" . strlen($sql) . " bytes){$nl}";
if ($semCidade) { echo "SEM CIDADE:" . $nl . "  " . implode($nl . "  ", array_unique($semCidade)) . $nl; }