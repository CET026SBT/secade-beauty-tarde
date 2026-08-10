<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "secade_beauty";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

/*function wFicheiroError($texto){
    $file = __DIR__ . '/../error.txt';
    $linha = date("Y-m-d H:i:s") . " - " . $texto;
    $current = file_exists($file) ? file_get_contents($file) : "";
    $current .= $linha . "\n";
    file_put_contents($file, $current);
}*/

?>
