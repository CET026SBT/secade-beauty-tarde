<?php
/** Dias uteis por mes em 2026 (temporario). Uso: php .tmp-dias-uteis.php */
$fer = ['2026-01-01', '2026-02-17', '2026-04-03', '2026-04-05', '2026-04-25', '2026-05-01', '2026-06-04',
    '2026-06-10', '2026-08-15', '2026-10-05', '2026-11-01', '2026-12-01', '2026-12-08', '2026-12-25'];
$pt = [1 => 'seg', 2 => 'ter', 3 => 'qua', 4 => 'qui', 5 => 'sex', 6 => 'sab', 7 => 'dom'];
echo "Mes 2026 | dias uteis (seg-sex) | feriados em dia util | liquido\n";
$tot = 0;
foreach ([1, 2, 3] as $m) {
    $n = 0; $f = 0;
    $t = (int)date('t', mktime(0, 0, 0, $m, 1, 2026));
    for ($d = 1; $d <= $t; $d++) {
        $ts = mktime(0, 0, 0, $m, $d, 2026);
        if ((int)date('N', $ts) <= 5) {
            $n++;
            if (in_array(date('Y-m-d', $ts), $fer, true)) { $f++; }
        }
    }
    $liq = $n - $f; $tot += $liq;
    printf("  %02d   |         %2d            |          %d            |    %2d\n", $m, $n, $f, $liq);
}
echo "\n";
printf("Janeiro   21 x 6,15 = %s   [129,15]\n", number_format(21 * 6.15, 2, ',', ''));
printf("Fevereiro 20 x 6,15 = %s   [123,00]\n", number_format(20 * 6.15, 2, ',', ''));
printf("Marco     22 x 6,15 = %s   [135,30]\n", number_format(22 * 6.15, 2, ',', ''));
printf("Trimestre 63 x 6,15 = %s   [387,45]\n", number_format(63 * 6.15, 2, ',', ''));
echo "\nDias que o Balancete confirma: 6324 = 2 312,40 = 5 x 387,45 + 375,15\n";
printf("375,15 / 6,15 = %s dias (61) -> 2 dias de diferenca face a 63\n", round(375.15 / 6.15, 2));
echo "\nOutros meses de 2026 (para o RH):\n";
for ($m = 1; $m <= 12; $m++) {
    $n = 0; $f = 0;
    $t = (int)date('t', mktime(0, 0, 0, $m, 1, 2026));
    for ($d = 1; $d <= $t; $d++) {
        $ts = mktime(0, 0, 0, $m, $d, 2026);
        if ((int)date('N', $ts) <= 5) { $n++; if (in_array(date('Y-m-d', $ts), $fer, true)) { $f++; } }
    }
    $nom = date('F', mktime(0, 0, 0, $m, 1, 2026));
    printf("  %02d %-9s dias uteis=%2d feriados=%d -> %2d dias x 6,15 = %8s\n",
        $m, $nom, $n, $f, $n - $f, number_format(($n - $f) * 6.15, 2, ',', ''));
}