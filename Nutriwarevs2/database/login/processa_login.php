<?php
// Inclui arquivos necessários e inicia a sessão
include '../config/conexao.php';
include '../config/error_handler.php';
session_start();

// Verifica o método
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php"); // Volta para o form único
    exit;
}

// --- Validação CSRF ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log("Falha na validação CSRF no login."); // Mensagem genérica
    unset($_SESSION['csrf_token']); // Limpa token
    header("Location: login.php?status=erro_csrf");
    exit;
}
// Opcional: unset($_SESSION['csrf_token']);

// --- Obter dados do formulário ---
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$senha_digitada = $_POST['senha'] ?? '';

// Validação básica
if (empty($email) || empty($senha_digitada)) {
    $_SESSION['login_erro'] = "E-mail e senha são obrigatórios."; // Mensagem de erro padrão
    header("Location: login.php");
    exit;
}

// --- Lógica de Autenticação para PARTICIPANTES (único tipo) ---
try {
    // Busca o participante na tabela 'participantes' pelo email
    $stmt = $conexao->prepare("SELECT id, senha, nome FROM participantes WHERE email = :email"); // BUSCA NA TABELA 'participantes'
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $participante = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica se o participante foi encontrado
    if (!$participante) {
        $_SESSION['login_erro'] = "E-mail ou senha incorretos.";
        error_log("Tentativa de login falhou (email não encontrado): " . $email);
        header("Location: login.php");
        exit;
    }

    // Verifica a senha usando password_verify
    if (password_verify($senha_digitada, $participante['senha'])) {
        // Senha correta! Login bem-sucedido.

        unset($_SESSION['csrf_token']);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Armazena informações do PARTICIPANTE na sessão
        $_SESSION['participante_id'] = $participante['id'];
        $_SESSION['participante_nome'] = $participante['nome'];

        session_regenerate_id(true);

        // Redireciona para o menu principal do PARTICIPANTE (agora menu.php)
        header("Location: menu/menu.php");
        exit;

    } else {
        // Senha incorreta
        $_SESSION['login_erro'] = "E-mail ou senha incorretos.";
        error_log("Tentativa de login falhou (senha incorreta): " . $email);
        header("Location: login.php");
        exit;
    }

} catch (PDOException $e) {
    error_log("Erro DB Login: " . $e->getMessage()); // Mensagem genérica
    $_SESSION['login_erro'] = "Ocorreu um erro interno durante o login. Tente novamente mais tarde.";
    header("Location: login.php"); // Redireciona de volta para o login único
    exit;
}
?>