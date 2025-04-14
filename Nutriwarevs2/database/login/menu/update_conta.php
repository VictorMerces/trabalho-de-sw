<?php
session_start();
// Inicia sessão ANTES de qualquer output

// Ajuste o caminho conforme a localização do arquivo
// __DIR__ garante que o caminho seja relativo ao arquivo atual
include __DIR__ . '/../../config/conexao.php';
include __DIR__ . '/../../config/error_handler.php';

// --- Validação CSRF ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['update_error'] = "Erro de validação de segurança (CSRF). Por favor, tente atualizar novamente a partir do formulário.";
    // Tenta obter o ID do participante do POST para redirecionar de volta, se possível
    $redirect_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($redirect_id) {
         header('Location: editar_conta.php?id=' . $redirect_id);
    } else {
         // Se não conseguir o ID, redireciona para a lista (ou outra página padrão)
         header('Location: listar_contas.php'); // Ou menu.php
    }
    exit;
}
// Opcional: unset($_SESSION['csrf_token']); // Remover após uso se for token de uso único

// --- Validação do Método HTTP ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Location: listar_contas.php'); // Ou menu.php
    exit;
}

// --- Obter e Validar ID ---
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['update_error'] = "ID de participante inválido fornecido.";
    header('Location: listar_contas.php'); // Ou menu.php
    exit;
}

// --- Obter e Sanitizar Dados do Formulário ---
$erros = []; // Array para erros de validação

$nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
if (empty($nome)) $erros[] = "O campo Nome é obrigatório.";

$idade_str = trim($_POST['idade'] ?? '');
$idade = null; // Permitir nulo
if ($idade_str !== '') {
    if (!filter_var($idade_str, FILTER_VALIDATE_INT) || (int)$idade_str < 0) {
        $erros[] = "A Idade deve ser um número inteiro não negativo.";
    } else {
        $idade = (int)$idade_str;
    }
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
if (empty($email)) {
    $erros[] = "O campo E-mail é obrigatório.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = "O formato do E-mail é inválido.";
} else {
    // Verificar se o email já existe para OUTRO participante
    try {
        $stmtCheckEmail = $conexao->prepare("SELECT id FROM participantes WHERE email = :email AND id != :id");
        $stmtCheckEmail->bindParam(':email', $email);
        $stmtCheckEmail->bindParam(':id', $id);
        $stmtCheckEmail->execute();
        if ($stmtCheckEmail->fetch()) {
            $erros[] = "Este e-mail já está em uso por outro participante.";
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar e-mail duplicado na atualização: " . $e->getMessage());
        // Não adicionar erro ao usuário aqui, apenas logar. A atualização pode prosseguir.
    }
}


// Função auxiliar para processar campos com opção 'outro' (adaptada para atualização)
function processar_campo_atualizacao($post_field_name, &$valor_principal, &$valor_outro) {
    $valor_principal = isset($_POST[$post_field_name]) ? htmlspecialchars(trim($_POST[$post_field_name])) : null;
    $outro_field_name = $post_field_name . '_outro';
    $valor_outro = null;

    if ($valor_principal === 'outro') {
        $valor_outro = isset($_POST[$outro_field_name]) ? htmlspecialchars(trim($_POST[$outro_field_name])) : '';
        // Não adicionar erro aqui, validação principal deve ocorrer antes
    }
}

// Processar campos usando a função auxiliar
processar_campo_atualizacao('genero', $genero, $genero_outro);
processar_campo_atualizacao('raca', $raca, $raca_outro); // Usa 'raca' (corrigido)
processar_campo_atualizacao('escolaridade', $escolaridade, $escolaridade_outro);
processar_campo_atualizacao('situacao_emprego', $situacao_emprego, $situacao_emprego_outro); // Usa 'situacao_emprego' (corrigido)
processar_campo_atualizacao('religiao', $religiao, $religiao_outro);

// Estado Civil
$estado_civil = isset($_POST['estado_civil']) && !empty($_POST['estado_civil']) ? htmlspecialchars(trim($_POST['estado_civil'])) : null;

// Número de Dependentes (VARCHAR)
$numero_dependentes_base = isset($_POST['numero_dependentes']) ? htmlspecialchars(trim($_POST['numero_dependentes'])) : null;
$numero_dependentes_outro_val = null;
$numero_dependentes_final = $numero_dependentes_base;
if ($numero_dependentes_base === 'Outro') {
    $numero_dependentes_outro_val = isset($_POST['numero_dependentes_outro']) ? htmlspecialchars(trim($_POST['numero_dependentes_outro'])) : '';
    $numero_dependentes_final = $numero_dependentes_outro_val; // Salva o texto
}

// Benefícios Sociais (JSON) - Similar ao cadastro
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
     error_log("Erro no json_encode dos benefícios na atualização.");
}


// Campos de Senha
$senha = $_POST['senha'] ?? '';
$senha_confirm = $_POST['senha_confirm'] ?? '';
$senha_hash = null; // Será definido apenas se a senha for válida e alterada

if (!empty($senha)) {
    if (strlen($senha) < 6) {
        $erros[] = "A nova senha deve ter pelo menos 6 caracteres.";
    } elseif ($senha !== $senha_confirm) {
        $erros[] = "A Nova Senha e a Confirmação de Senha não coincidem.";
    } else {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        if ($senha_hash === false) {
             $erros[] = "Ocorreu um erro interno ao processar a nova senha.";
             error_log("Erro no password_hash para atualização participante ID: " . $id);
        }
    }
}

// --- Processamento Final (Atualização no Banco) ---
if (!empty($erros)) {
    // Se houver erros, volta para o formulário de edição
    $_SESSION['update_error'] = "Foram encontrados erros:<br>" . implode("<br>", $erros);
    header("Location: editar_conta.php?id=" . $id);
    exit;
} else {
    // Se não houver erros, tenta atualizar o banco
    try {
        // --- Monta a Query SQL ---
        $sql_parts = [
            "nome = :nome",
            "email = :email",
            "idade = :idade",
            "genero = :genero",
            "genero_outro = :genero_outro",
            "raca = :raca", // Corrigido
            "raca_outro = :raca_outro", // Adicionado
            "escolaridade = :escolaridade",
            "escolaridade_outro = :escolaridade_outro", // Adicionado
            "estado_civil = :estado_civil",
            "situacao_emprego = :situacao_emprego", // Corrigido
            "situacao_emprego_outro = :situacao_emprego_outro", // Adicionado
            "beneficios_sociais = :beneficios_sociais",
            "numero_dependentes = :numero_dependentes",
            "religiao = :religiao",
            "religiao_outro = :religiao_outro" // Adicionado
        ];

        // Adiciona a atualização da senha APENAS se um $senha_hash foi gerado
        if ($senha_hash !== null) {
            $sql_parts[] = "senha = :senha";
        }

        // Junta as partes da query
        $sql = "UPDATE participantes SET " . implode(", ", $sql_parts) . " WHERE id = :id";

        // Prepara a declaração
        $stmt = $conexao->prepare($sql);

        // --- Bind dos Parâmetros ---
        $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
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

        $stmt->bindValue(':numero_dependentes', $numero_dependentes_final, $numero_dependentes_final === null ? PDO::PARAM_NULL : PDO::PARAM_STR); // VARCHAR

        $stmt->bindValue(':religiao', $religiao, $religiao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':religiao_outro', $religiao_outro, $religiao_outro === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        // Bind da senha APENAS se ela foi alterada
        if ($senha_hash !== null) {
            $stmt->bindParam(':senha', $senha_hash, PDO::PARAM_STR);
        }

        // Bind do ID (sempre no final, na cláusula WHERE)
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        // Executa a query
        $stmt->execute();

        // Define mensagem de sucesso na sessão
        $_SESSION['update_success'] = "Dados do participante atualizados com sucesso!";
        // Limpa token CSRF se desejar
        // unset($_SESSION['csrf_token']);
        // Redireciona de volta para o formulário de edição (para ver a msg de sucesso)
        header("Location: editar_conta.php?id=" . $id);
        exit;

    } catch (PDOException $e) {
        // Se ocorrer um erro no banco de dados
        error_log("Erro DB Update: " . $e->getMessage() . " para ID: " . $id . " Dados: " . print_r($_POST, true)); // Loga o erro real e os dados
        $_SESSION['update_error'] = "Ocorreu um erro ao tentar atualizar os dados no banco. Por favor, tente novamente. Se o erro persistir, contate o suporte.";
        // Redireciona de volta para o formulário de edição
        header("Location: editar_conta.php?id=" . $id);
        exit;
    }
}
?>