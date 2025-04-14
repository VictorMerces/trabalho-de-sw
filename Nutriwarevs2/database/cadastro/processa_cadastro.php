<?php
session_start(); // Inicia a sessão para usar CSRF e mensagens de erro/sucesso

// Inclui a conexão com o banco e o error handler
include '../config/conexao.php';
include '../config/error_handler.php'; // Garante que erros não sejam exibidos diretamente

// Verifica se o método é POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cadastro.php?status=erro"); // Redireciona se não for POST
    exit;
}

// --- 1. Validação CSRF ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log("Falha na validação CSRF no cadastro.");
    unset($_SESSION['csrf_token']);
    header("Location: cadastro.php?status=erro_csrf");
    exit;
}
// unset($_SESSION['csrf_token']); // Opcional: Remover após uso

// --- REMOVIDO: Obter ID do Usuário Admin/Operador Logado ---

// --- 3. Sanitização e Validação dos Dados de Entrada ---
$erros = []; // Array para armazenar mensagens de erro

// Campos obrigatórios básicos
$nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$senha = $_POST['senha'] ?? '';
$senha_confirm = $_POST['senha_confirm'] ?? '';

if (empty($nome)) $erros[] = "O campo Nome Completo é obrigatório.";
if (empty($email)) {
    $erros[] = "O campo E-mail é obrigatório.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = "O formato do E-mail é inválido.";
} else {
    // Verificar se o email já existe
    try {
        $stmtCheck = $conexao->prepare("SELECT id FROM participantes WHERE email = :email");
        $stmtCheck->bindParam(':email', $email);
        $stmtCheck->execute();
        if ($stmtCheck->fetch()) {
            $erros[] = "Este e-mail já está cadastrado.";
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar email duplicado: " . $e->getMessage());
        $erros[] = "Erro ao verificar o e-mail. Tente novamente."; // Informa erro genérico
    }
}

if (empty($senha)) {
     $erros[] = "O campo Senha é obrigatório.";
} elseif (strlen($senha) < 6) {
     $erros[] = "A senha deve ter no mínimo 6 caracteres.";
} elseif ($senha !== $senha_confirm) {
     $erros[] = "A Senha e a Confirmação de Senha não coincidem.";
}
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);
if ($senha_hash === false) {
    $erros[] = "Ocorreu um erro ao processar a senha.";
    error_log("Erro no password_hash durante cadastro.");
}

// Campos opcionais/ENUMs
$idade_str = trim($_POST['idade'] ?? '');
$idade = ($idade_str !== '' && filter_var($idade_str, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) ? (int)$idade_str : null;
if ($idade_str !== '' && $idade === null) $erros[] = "Idade inválida.";

// Função auxiliar para processar campos com opção 'outro' (sem alterações)
function processar_campo_com_outro($post_field_name, &$valor_principal, &$valor_outro) {
    $valor_principal = isset($_POST[$post_field_name]) ? htmlspecialchars(trim($_POST[$post_field_name])) : null;
    $outro_field_name = $post_field_name . '_outro';
    $valor_outro = null;

    if ($valor_principal === 'outro') {
        $valor_outro = isset($_POST[$outro_field_name]) ? htmlspecialchars(trim($_POST[$outro_field_name])) : '';
    }
}

// Processar campos usando a função auxiliar (sem alterações)
processar_campo_com_outro('genero', $genero, $genero_outro);
processar_campo_com_outro('raca', $raca, $raca_outro);
processar_campo_com_outro('escolaridade', $escolaridade, $escolaridade_outro);
processar_campo_com_outro('situacao_emprego', $situacao_emprego, $situacao_emprego_outro);
processar_campo_com_outro('religiao', $religiao, $religiao_outro);

// Estado Civil (Select simples - sem alterações)
$estado_civil = isset($_POST['estado_civil']) && !empty($_POST['estado_civil']) ? htmlspecialchars(trim($_POST['estado_civil'])) : null;

// Número de Dependentes (VARCHAR - sem alterações)
$numero_dependentes_base = isset($_POST['numero_dependentes']) ? htmlspecialchars(trim($_POST['numero_dependentes'])) : null;
$numero_dependentes_outro_val = null;
$numero_dependentes_final = $numero_dependentes_base;
if ($numero_dependentes_base === 'Outro') {
    $numero_dependentes_outro_val = isset($_POST['numero_dependentes_outro']) ? htmlspecialchars(trim($_POST['numero_dependentes_outro'])) : '';
    $numero_dependentes_final = $numero_dependentes_outro_val;
}

// Benefícios Sociais (JSON - sem alterações)
$beneficios_selecionados = $_POST['beneficios_sociais'] ?? [];
$beneficios_outro_texto = isset($_POST['beneficios_sociais_outro']) ? htmlspecialchars(trim($_POST['beneficios_sociais_outro'])) : '';
$beneficios_finais = [];
foreach ($beneficios_selecionados as $beneficio) {
    $beneficio_limpo = htmlspecialchars(trim($beneficio));
    if ($beneficio_limpo === 'Outros' && !empty($beneficios_outro_texto)) {
        $beneficios_finais['Outros'] = $beneficios_outro_texto;
    } elseif ($beneficio_limpo !== 'Outros') {
        $beneficios_finais[] = $beneficio_limpo;
    }
}
$beneficios_json = !empty($beneficios_finais) ? json_encode($beneficios_finais) : null;
if ($beneficios_json === false) {
     $erros[] = "Erro ao processar os benefícios sociais.";
     error_log("Erro no json_encode dos benefícios.");
}


// --- 4. Verificar Erros e Inserir no Banco ---
if (!empty($erros)) {
    $_SESSION['cadastro_erro'] = implode("<br>", $erros);
    header("Location: cadastro.php?status=erro");
    exit;
} else {
    try {
        // SQL REMOVEU usuario_cadastro_id
        $sql = "INSERT INTO participantes (
                    nome, email, senha, idade,
                    genero, genero_outro,
                    raca, raca_outro,
                    escolaridade, escolaridade_outro,
                    estado_civil,
                    situacao_emprego, situacao_emprego_outro,
                    beneficios_sociais,
                    numero_dependentes,
                    religiao, religiao_outro
                ) VALUES (
                    :nome, :email, :senha, :idade,
                    :genero, :genero_outro,
                    :raca, :raca_outro,
                    :escolaridade, :escolaridade_outro,
                    :estado_civil,
                    :situacao_emprego, :situacao_emprego_outro,
                    :beneficios_sociais,
                    :numero_dependentes,
                    :religiao, :religiao_outro
                )";

        $stmt = $conexao->prepare($sql);

        // Bind dos Parâmetros (sem alterações aqui, exceto remover o bind de usuario_cadastro_id)
        $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':senha', $senha_hash, PDO::PARAM_STR);
        $stmt->bindValue(':idade', $idade, $idade === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':genero', $genero, $genero === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':genero_outro', $genero_outro, $genero_outro === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':raca', $raca, $raca === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':raca_outro', $raca_outro, $raca_outro === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':escolaridade', $escolaridade, $escolaridade === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':escolaridade_outro', $escolaridade_outro, $escolaridade_outro === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':estado_civil', $estado_civil, $estado_civil === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':situacao_emprego', $situacao_emprego, $situacao_emprego === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':situacao_emprego_outro', $situacao_emprego_outro, $situacao_emprego_outro === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':beneficios_sociais', $beneficios_json, $beneficios_json === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':numero_dependentes', $numero_dependentes_final, $numero_dependentes_final === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':religiao', $religiao, $religiao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':religiao_outro', $religiao_outro, $religiao_outro === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        // REMOVIDO bind de usuario_cadastro_id

        $stmt->execute();

        // Redireciona para a página de LOGIN do participante com status de sucesso no cadastro
        // unset($_SESSION['csrf_token']); // Opcional
        header("Location: ../login/login.php?cadastro=sucesso"); // Redireciona para o login único
        exit;

    } catch (PDOException $e) {
        error_log("Erro DB Cadastro: " . $e->getMessage() . " Dados: " . print_r($_POST, true));
        $_SESSION['cadastro_erro'] = "Ocorreu um erro interno ao tentar salvar os dados no banco. Por favor, tente novamente. Se o erro persistir, contate o suporte.";
        // Redireciona de volta para o formulário de cadastro com erro (CORRIGIDO)
        header("Location: cadastro.php?status=erro");
        exit;
    }
}
?>