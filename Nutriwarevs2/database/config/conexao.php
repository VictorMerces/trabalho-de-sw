<?php
if (!defined('HOST_BD')) {
    define('HOST_BD', 'localhost');
}
if (!defined('USR_BD')) {
    define('USR_BD', 'root');
}
if (!defined('PW_BD')) {
    define('PW_BD', '');
}
if (!defined('BD_BD')) {
    define('BD_BD', 'nutriware');
}

try {
    $conexao = new PDO("mysql:host=" . HOST_BD . ";dbname=" . BD_BD, USR_BD, PW_BD);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>