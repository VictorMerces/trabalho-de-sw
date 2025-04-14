<?php
session_start();


// Ajuste o caminho se necessário
include '../config/conexao.php';

// --- Validação do ID ---
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    // Em produção, redirecionar para uma página de erro ou lista
    die("ID de participante inválido.");
}

$participante = null;
$erro_fetch = ''; // Erro ao buscar dados

// --- Busca dos Dados ---
try {
    $stmt = $conexao->prepare("SELECT * FROM participantes WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $participante = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$participante) {
        // Em produção, redirecionar
        die("Participante não encontrado.");
    }

    // --- Processamento inicial de campos com 'Outro' ---
    function separar_campo_outro($valor_completo, $base_value_outro = 'Outro') {
        $valor_base = $valor_completo;
        $outro_texto = '';
        $prefixo = $base_value_outro . ' - ';
        if ($valor_completo !== null && strpos($valor_completo, $prefixo) === 0) {
            $valor_base = $base_value_outro;
            $outro_texto = substr($valor_completo, strlen($prefixo));
        } elseif ($valor_completo === $base_value_outro) {
             $valor_base = $base_value_outro;
             $outro_texto = '';
        }
        return ['base' => $valor_base, 'outro' => $outro_texto];
    }

    // Aplica a função aos campos relevantes (atualize 'raca_cor' para 'raca')
    $raca_data = separar_campo_outro($participante['raca'] ?? null, 'Outro');
    $escolaridade_data = separar_campo_outro($participante['escolaridade'] ?? null, 'Outro');
    $emprego_data = separar_campo_outro($participante['situacao_emprego'] ?? null, 'Outro');
    $beneficios_data = separar_campo_outro($participante['beneficios_sociais'] ?? null, 'Outros');
    $dependentes_data = separar_campo_outro($participante['numero_dependentes'] ?? null, 'Outro');
    $religiao_data = separar_campo_outro($participante['religiao'] ?? null, 'Outro');


} catch (PDOException $e) {
    $erro_fetch = "Erro ao buscar dados do participante."; // Mensagem genérica para usuário
    error_log("Erro DB Fetch: " . $e->getMessage() . " para ID: " . $id);
    // Em produção, talvez redirecionar ou mostrar erro sem die()
    die($erro_fetch);
}

// --- Preparar dados para a View/JavaScript ---
// Gerar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Agrupa todos os dados necessários para o JavaScript
$viewData = [
    'participante' => $participante, // Contém todos os dados originais
    'csrfToken' => $_SESSION['csrf_token'],
    'id' => $id,
    // Dados processados dos campos 'Outro'
    'raca_data' => $raca_data,
    'escolaridade_data' => $escolaridade_data,
    'emprego_data' => $emprego_data,
    'beneficios_data' => $beneficios_data,
    'dependentes_data' => $dependentes_data,
    'religiao_data' => $religiao_data,
    // Mensagens de erro/sucesso da sessão (se houver de um redirect anterior)
    'update_error' => $_SESSION['update_error'] ?? null,
    'update_success' => $_SESSION['update_success'] ?? null,
];

// Limpa as mensagens da sessão depois de lê-las
unset($_SESSION['update_error'], $_SESSION['update_success']);

// Define o cabeçalho como HTML
header('Content-Type: text/html; charset=utf-8');

// Inclui o arquivo HTML da view. A variável $viewData estará acessível dentro dela.
include 'editar_conta_view.html';

?>