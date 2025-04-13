<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login/login.html");
    exit;
}

// Atualizado para caminhos corretos:
include '../../config/conexao.php';
include '../../config/banco.php';

$relatorio = [];
$tipo_relatorio = $_POST['tipo_relatorio'] ?? 'inseguranca';
$filtros = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['genero']) && !empty($_POST['genero'])) {
        $filtros['genero'] = $_POST['genero'];
    }
    if (isset($_POST['faixa_etaria']) && !empty($_POST['faixa_etaria'])) {
        $filtros['faixa_etaria'] = $_POST['faixa_etaria'];
    }

    if ($tipo_relatorio == 'inseguranca') {
        $relatorio = gerar_relatorio_inseguranca_alimentar($conexao, $filtros);
    } elseif ($tipo_relatorio == 'consumo') {
        $relatorio = gerar_relatorio_consumo_alimentar($conexao, $filtros);
    }
}

if (isset($_POST['exportar']) && $_POST['exportar'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=relatorio.csv');
    $output = fopen('php://output', 'w');
    if ($tipo_relatorio == 'inseguranca' && !empty($relatorio)) {
        fputcsv($output, array_keys($relatorio[0]));
        foreach ($relatorio as $linha) {
            fputcsv($output, $linha);
        }
    } elseif ($tipo_relatorio == 'consumo' && !empty($relatorio)) {
        fputcsv($output, array_keys($relatorio[0]));
        foreach ($relatorio as $linha) {
            fputcsv($output, $linha);
        }
    }
    fclose($output);
    exit;
}
?>