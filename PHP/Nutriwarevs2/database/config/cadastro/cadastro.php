<?php
// Inclui o arquivo de conexão com o banco de dados
include 'database/config/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitização dos dados de entrada
    $nome = htmlspecialchars(trim($_POST['nome']));
    $idade = filter_var($_POST['idade'], FILTER_VALIDATE_INT);
    $genero = htmlspecialchars(trim($_POST['genero']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    // Processamento dos campos opcionais
    $raca_cor = isset($_POST['raca_cor']) ? $_POST['raca_cor'] : null;
    if ($raca_cor === "Outro" && !empty($_POST['raca_outro'])) {
        $raca_cor .= " - " . $_POST['raca_outro'];
    }
    $escolaridade = isset($_POST['escolaridade']) ? $_POST['escolaridade'] : null;
    if ($escolaridade === "Outro" && !empty($_POST['escolaridade_outro'])) {
        $escolaridade .= " - " . $_POST['escolaridade_outro'];
    }
    $estado_civil = $_POST['estado_civil'] ?? null;
    $emprego = isset($_POST['emprego']) ? $_POST['emprego'] : null;
    if ($emprego === "Outro" && !empty($_POST['emprego_outro'])) {
        $emprego .= " - " . $_POST['emprego_outro'];
    }
    $auxilios = $_POST['auxilios'] ?? "";
    if ($auxilios === "Outros" && !empty($_POST['auxilios_outros'])) {
        $auxilios .= " - " . $_POST['auxilios_outros'];
    }
    $dependentes = $_POST['dependentes'] ?? null;
    if ($dependentes == "Outro" && !empty($_POST['dependentes_outro'])) {
        $dependentes .= " - " . $_POST['dependentes_outro'];
    }
    $religiao = isset($_POST['religiao']) ? $_POST['religiao'] : null;
    if ($religiao === "Outro" && !empty($_POST['religiao_outro'])) {
        $religiao .= " - " . $_POST['religiao_outro'];
    }

    try {
        // Prepara a consulta SQL para inserir os dados
        $stmt = $conexao->prepare("INSERT INTO participantes (nome, idade, genero, raca_cor, escolaridade, estado_civil, situacao_emprego, beneficios_sociais, numero_dependentes, religiao, email, senha)
            VALUES (:nome, :idade, :genero, :raca_cor, :escolaridade, :estado_civil, :situacao_emprego, :beneficios_sociais, :numero_dependentes, :religiao, :email, :senha)");

        // Vincula os parâmetros
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':idade', $idade);
        $stmt->bindParam(':genero', $genero);
        $stmt->bindParam(':raca_cor', $raca_cor);
        $stmt->bindParam(':escolaridade', $escolaridade);
        $stmt->bindParam(':estado_civil', $estado_civil);
        $stmt->bindParam(':situacao_emprego', $emprego);
        $stmt->bindParam(':beneficios_sociais', $auxilios);
        $stmt->bindParam(':numero_dependentes', $dependentes);
        $stmt->bindParam(':religiao', $religiao);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha);

        // Executa a consulta
        $stmt->execute();

        // Verifica se a inserção foi bem-sucedida
        if ($stmt->rowCount() > 0) {
            header("Location: ../login/login.html");
            exit;
        } else {
            echo "Erro ao cadastrar. Tente novamente.";
        }
    } catch (PDOException $e) {
        // Registra o erro e exibe uma mensagem genérica
        error_log($e->getMessage());
        echo "Erro ao cadastrar. Tente novamente mais tarde.";
    }
}
?>