<?php
/**
 * Bateria de verificacao dos novos documentos (temporario, Git-ignored).
 * Uso: php .tmp-verificar.php
 * Cruza: Gastos e Rendimentos.xlsx / Obrigacoes Fiscais.xlsx / imagem(1) / Contabilidade.xlsx
 *        contra os PDFs oficiais (DR, Balancete, Balanco) ja extraidos em .tmp-pdf/.
 */
$n = fn($v, $d = 2) => number_format($v, $d, ',', ' ');
$ok = fn($got, $exp, $tol = 0.005) => abs($got - $exp) <= $tol ? 'OK ' : 'ERRO';

echo "===== 1. RAI — reconciliacao da divergencia =====\n";
$oficial = -12945.58;   // DR 31/03 (acumulado) — PDF oficial
$xlsx    = -21705.66;   // Gastos e Rendimentos.xlsx folha1
$dFse    = 15277.70 - 6527.30;   // xlsx 15 277,70  vs Balancete 62 = 6 527,30
$dJuros  = 425.00 - 415.32;      // xlsx   425,00  vs Balancete 69 =   415,32
printf("oficial            = %s\n", $n($oficial));
printf("+ delta FSE        = %s\n", $n($dFse));
printf("+ delta juros      = %s\n", $n($dJuros));
printf("  soma             = %s   [xlsx=%s]  %s\n", $n($oficial - $dFse - $dJuros), $n($xlsx),
    $ok($oficial - $dFse - $dJuros, $xlsx));

echo "\n===== 2. Folha de salarios (imagem 1) vs Balancete =====\n";
// [remuneracao, n_meses, subsidio, taxa_irs]
$emp = [
    ['Cassiana Tavares',   1200, 3, 387.45, 0.08],
    ['Delfina Benjamim',   1200, 3, 387.45, 0.08],
    ['Iracelma Joaquim',   1000, 3, 387.45, 0.036],
    ['Luis Chivela',       1000, 3, 387.45, 0.036],
    ['Teresa Elisabeth',   1000, 3, 387.45, 0.036],
    ['Sebenildes Cabamba', 1250, 3, 375.15, 0.0856],
];
$t = ['rem' => 0, 'sa' => 0, 'irs' => 0, 'ss11' => 0, 'ss2375' => 0, 'net' => 0];
foreach ($emp as [$nome, $base, $m, $sa, $irs]) {
    $R = $base * $m;
    $i = $R * $irs; $s1 = $R * 0.11; $s2 = $R * 0.2375; $p = $R + $sa - $i - $s1;
    $t['rem'] += $R; $t['sa'] += $sa; $t['irs'] += $i; $t['ss11'] += $s1; $t['ss2375'] += $s2; $t['net'] += $p;
    printf("  %-19s bruto=%9s IRS=%8s  SS11=%8s  SS23,75=%8s  PAGAR=%9s\n",
        $nome, $n($R), $n($i), $n($s1), $n($s2), $n($p));
}
$chk = [
    ['Remuneracao (D)',        $t['rem'],            19950.00],
    ['Subsidio (SA)',          $t['sa'],              2312.40],
    ['Remun + SA (E)',         $t['rem'] + $t['sa'], 22262.40],
    ['IRS (F)',                $t['irs'],             1221.00],
    ['SS 11 (G)',              $t['ss11'],            2194.50],
    ['SS 23,75 (H)',           $t['ss2375'],          4721.68],
    ['SS total',               $t['ss11'] + $t['ss2375'], 6916.18],
    ['PAGAR AO PESSOAL (I)',   $t['net'],            18846.90],
];
foreach ($chk as [$lbl, $got, $exp]) {
    printf("  %-21s %10s  (imagem %10s)  %s\n", $lbl, $n($got), $n($exp), $ok($got, $exp));
}
echo "\n  Cruzamento com o Balancete oficial:\n";
$bc = [
    ['6321 vencimentos mensais (debito)', 19950.00, 19950.00],
    ['6324 subsidio de alimentacao',       2312.40, $t['sa']],
    ['632 pessoal (debito)',              22262.40, $t['rem'] + $t['sa']],
    ['635 encargos sobre remuneracoes',    4721.70, $t['ss2375']],
];
foreach ($bc as [$lbl, $bal, $got]) {
    printf("  %-36s Balancete %10s  calculado %10s  %s\n", $lbl, $n($bal), $n($got), $ok($bal, $got, 0.025));
}
echo "\n  Conta 63 — composicao (saldo): 632 (22 262,40-69,23) + 635 + 636 + 638\n";
$c63 = (22262.40 - 69.23) + 4721.70 + 45.56 + 273.70;
printf("  calculado %s   [Balancete/DR = 27 234,13]  %s\n", $n($c63), $ok($c63, 27234.13, 0.01));

echo "\n===== 3. Dias uteis e subsidio/dia =====\n";
printf("  SA/dia 6,15 -> 387,45/6,15 = %s dias ; 375,15/6,15 = %s dias\n", round(387.45 / 6.15, 2), round(375.15 / 6.15, 2));
printf("  por mes (analise): 129,15/6,15=%s  123/6,15=%s  135,30/6,15=%s -> soma %s\n",
    round(129.15 / 6.15), round(123 / 6.15), round(135.30 / 6.15),
    round(129.15 / 6.15) + round(123 / 6.15) + round(135.30 / 6.15));
printf("  Sebenildes: 61 dias = 63 - 2  (diferenca de %s dias)\n", round(387.45 / 6.15) - round(375.15 / 6.15));

echo "\n===== 4. Depreciacoes e inventario =====\n";
$carr = 44715.45 / 4 / 4;  $port = 913.99 / 3 / 4;
printf("  Carrinha 44 715,45 / 4 anos / 4 trim = %s  [6424 = 2 794,71]  %s\n", $n($carr), $ok($carr, 2794.71, 0.01));
printf("  Portatil    913,99 / 3 anos / 4 trim = %s  [6425 =    76,17]  %s\n", $n($port), $ok($port, 76.17, 0.01));
printf("  Total trimestre = %s  [64 = 2 870,88]  %s\n", $n($carr + $port), $ok($carr + $port, 2870.88, 0.01));
printf("  Valor contabilistico = %s  [Balanco ANC marco = 42 758,56]  %s\n", $n(45629.44 - 2870.88), $ok(45629.44 - 2870.88, 42758.56, 0.01));

echo "\n===== 5. Racios (Menu Racios) vs Balanco de marco =====\n";
$anc = 42758.56; $ac = 50338.09; $cp = 8294.74; $pc = 84801.91;
$chk = [
    ['Liquidez Geral',       $ac / $pc,       0.593596, 6],
    ['Fundo de Maneio',      $ac - $pc,     -34463.82,   2],
    ['Autonomia Financeira', $cp / ($anc + $ac), 0.089098, 6],
    ['Endividamento',        1 - $cp / ($anc + $ac), 0.910902, 6],
    ['Solvabilidade',        $cp / $pc,       0.097813, 6],
];
foreach ($chk as [$lbl, $got, $exp, $dec]) {
    printf("  %-21s %12s  [Menu Racios %12s]  %s\n", $lbl, $n($got, $dec), $n($exp, $dec), $ok($got, $exp, 0.000001));
}
printf("  Ativo total = %s   [Balanco: 0 + 93 096,65]  %s\n", $n($anc + $ac), $ok($anc + $ac, 93096.65, 0.01));

echo "\n===== 6. Capital em divida: real vs plano =====\n";
printf("  real (conta 25111): 50 000,00 - 645,34 = %s\n", $n(50000 - 645.34));
printf("  plano do cliente:   50 000,00 - 25 983,20 = %s\n", $n(50000 - 25983.20));

echo "\n===== 7. Dias da semana (prazo em dia util?) =====\n";
$pt = ['Sun' => 'domingo', 'Mon' => 'segunda', 'Tue' => 'terca', 'Wed' => 'quarta', 'Thu' => 'quinta', 'Fri' => 'sexta', 'Sat' => 'sabado'];
$datas = ['2026-01-09', '2026-05-20', '2026-05-25', '2026-07-25', '2026-08-31', '2026-09-20', '2026-09-21', '2026-09-25', '2026-11-20', '2026-11-25', '2027-02-20', '2027-06-30', '2027-07-15'];
foreach ($datas as $d) {
    $w = date('D', strtotime($d));
    printf("  %s  %-8s%s\n", $d, $pt[$w], in_array($w, ['Sat', 'Sun']) ? '  <-- FIM DE SEMANA' : '');
}