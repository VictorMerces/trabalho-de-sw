<?php // database/login/menu/editar_conta.php (MODIFICADO)
session_start();

// CORREÇÃO: Usar __DIR__ para garantir o caminho correto relativo ao arquivo atual
include __DIR__ . '/../../config/conexao.php';
include __DIR__ . '/../../config/error_handler.php';

// --- Validação do ID ---
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEditingSelf = false; // Flag para saber se está editando a própria conta

if (!$id && isset($_SESSION['participante_id'])) {
    $id = $_SESSION['participante_id']; // Pega ID do usuário logado se não veio por GET
}

if (!$id) {
    $_SESSION['list_error'] = "ID de participante inválido ou não fornecido para edição.";
    // Decide para onde redirecionar baseado se há usuário logado
    $redirect_page = isset($_SESSION['participante_id']) ? 'menu.php' : '../login.php'; // Ajustado caminho relativo
    header('Location: ' . $redirect_page);
    exit;
}

// --- Verificação de Permissão (Admin ou Próprio Usuário) ---
$permitido = false;
if (isset($_SESSION['participante_id']) && $_SESSION['participante_id'] == $id) {
    $permitido = true;
    $isEditingSelf = true; // Editando a própria conta
}
// --- LÓGICA ADMIN (Exemplo - REMOVA OU AJUSTE CONFORME SUA IMPLEMENTAÇÃO) ---
$isAdmin = false; // Defina isso baseado na sua lógica de sessão para admin
// Exemplo: if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') { $isAdmin = true; }
if ($isAdmin) {
    $permitido = true;
}
// --- FIM LÓGICA ADMIN ---

if (!$permitido) {
    $_SESSION['list_error'] = "Você não tem permissão para editar esta conta.";
    header('Location: menu.php');
    exit;
}

$participante = null;
$erro_fetch = '';

// --- Busca dos Dados ---
try {
    $stmt = $conexao->prepare("SELECT * FROM participantes WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $participante = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$participante) {
        $_SESSION['list_error'] = "Participante com ID $id não encontrado.";
        // Redireciona para lista SE for admin e NÃO estiver editando a própria conta
        $redirect_page = ($isAdmin && !$isEditingSelf) ? 'listar_contas.php' : 'menu.php';
        header('Location: ' . $redirect_page);
        exit;
    }

} catch (PDOException $e) {
    $erro_fetch = "Erro ao buscar dados do participante.";
    error_log("Erro DB Fetch Conta: " . $e->getMessage() . " para ID: " . $id);
}

// --- Preparar dados para a View ---
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

// Funções auxiliares (mantidas, mas usadas abaixo para preencher $viewData)
function prepararDadosCampoOutro($participante, $campoBase, $campoOutro) { $base = $participante[$campoBase] ?? null; $outro = $participante[$campoOutro] ?? null; return ['base' => $base, 'outro' => $outro]; }
function prepararDadosBeneficios($participante) { $json = $participante['beneficios_sociais'] ?? null; $base = []; $outro = null; if (!empty($json)) { $decoded = json_decode($json, true); if (is_array($decoded)) { foreach ($decoded as $key => $value) { if ($key === 'Outros' && is_string($value)) { $outro = $value; $base[] = 'Outros'; } elseif (is_numeric($key) && is_string($value)) { $base[] = $value; } elseif ($key !== 'Outros'){ $base[] = $value; } } if (count($base) > 1 && in_array('Nenhum', $base)) { $base = array_filter($base, fn($b) => $b !== 'Nenhum'); } } } return ['base' => $base, 'outro' => $outro]; }
function prepararDadosDependentes($participante) { $valorDB = $participante['numero_dependentes'] ?? null; $opcoesPadrao = ['0', '1', '2', '3', '4 ou mais']; if (in_array($valorDB, $opcoesPadrao)) { return ['base' => $valorDB, 'outro' => null]; } elseif (!empty($valorDB)) { return ['base' => 'Outro', 'outro' => $valorDB]; } return ['base' => null, 'outro' => null]; }

// Agrupa dados para a view
$viewData = [
    'participante' => $participante, 'csrfToken' => $_SESSION['csrf_token'] ?? '', 'id' => $id,
    'genero_data' => $participante ? prepararDadosCampoOutro($participante, 'genero', 'genero_outro') : ['base'=>null, 'outro'=>null],
    'raca_data' => $participante ? prepararDadosCampoOutro($participante, 'raca', 'raca_outro') : ['base'=>null, 'outro'=>null],
    'escolaridade_data' => $participante ? prepararDadosCampoOutro($participante, 'escolaridade', 'escolaridade_outro') : ['base'=>null, 'outro'=>null],
    'emprego_data' => $participante ? prepararDadosCampoOutro($participante, 'situacao_emprego', 'situacao_emprego_outro') : ['base'=>null, 'outro'=>null],
    'religiao_data' => $participante ? prepararDadosCampoOutro($participante, 'religiao', 'religiao_outro') : ['base'=>null, 'outro'=>null],
    'dependentes_data' => $participante ? prepararDadosDependentes($participante) : ['base'=>null, 'outro'=>null],
    'beneficios_data' => $participante ? prepararDadosBeneficios($participante) : ['base'=>[], 'outro'=>null],
    'update_error' => $erro_fetch ?: ($_SESSION['update_error'] ?? null),
    'update_success' => $_SESSION['update_success'] ?? null,
];

unset($_SESSION['update_error'], $_SESSION['update_success']); // Limpa mensagens da sessão

// Variáveis para Sidebar
$paginaAtiva = $isEditingSelf ? 'minha_conta' : ($isAdmin ? 'contas' : ''); // Define página ativa
$nomeUsuario = $_SESSION['participante_nome'] ?? 'Usuário';
$currentUserId = $_SESSION['participante_id'] ?? 0; // Usado para link "Minha Conta" na sidebar

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Editar <?php echo $isEditingSelf ? 'Minha Conta' : 'Participante'; ?> - Nutriware</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Estilos da Sidebar (copiados de menu.php para consistência) */
         .wrapper { display: flex; width: 100%; align-items: stretch; min-height: 100vh; }
         #sidebar { min-width: 250px; max-width: 250px; background: #28a745; color: #fff; transition: all 0.3s; }
         #sidebar .sidebar-header { padding: 20px; background: #218838; text-align: center; }
         #sidebar .sidebar-header h3 i { margin-right: 8px; }
         #sidebar ul.components { padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
         #sidebar ul li a { padding: 10px 20px; font-size: 1.1em; display: block; color: rgba(255, 255, 255, 0.8); border-left: 3px solid transparent; text-decoration: none; transition: all 0.3s; }
         #sidebar ul li a:hover { color: #fff; background: #218838; }
         #sidebar ul li.active > a, a[aria-expanded="true"] { color: #fff; background: #218838; border-left-color: #90EE90; }
         #sidebar ul li a i { margin-right: 10px; }
         a[data-toggle="collapse"] { position: relative; }
         .dropdown-toggle::after { display: block; position: absolute; top: 50%; right: 20px; transform: translateY(-50%); }
         #sidebar .custom-dropdown-menu { font-size: 0.9em !important; padding-left: 30px !important; background: #218838; }
         .line { width: 90%; height: 1px; border-bottom: 1px dashed rgba(255,255,255,0.2); margin: 15px auto; }
         #content { width: 100%; padding: 20px; transition: all 0.3s; background-color: #f8f9fa; }
         .navbar-top { margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        /* Estilos específicos do formulário */
        .form-section-card { margin-bottom: 2rem; border: 1px solid #badbcc; /* Borda verde mais clara */ box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .form-section-card .card-header { background-color: #28a745; /* Verde principal */ color: white; font-weight: bold; }
        .form-section-card .card-header i { margin-right: 8px; }
        .form-group { margin-bottom: 1rem; }
        .form-check { margin-bottom: 0.5rem; }
        .form-check-label { font-weight: normal; }
        .required::after { content: " *"; color: #dc3545; }
        .optional::after { content: " (opcional)"; color: #6c757d; font-size: 0.85em; font-weight: normal; }
        label:not(.form-check-label) { font-weight: 500; } /* Labels um pouco mais fortes */
        h2 i { margin-right: 10px; color: #28a745; }
        .badge-admin { font-size: 0.7em; background-color: #17a2b8; color: white; }
    </style>
</head>
<body>
<div class="wrapper">
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-leaf"></i> Nutriware</h3>
        </div>
        <ul class="list-unstyled components">
            <li> <a href="menu.php"><i class="fas fa-home"></i> Menu Principal</a> </li>
             <li> <a href="#questionariosSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-clipboard-list"></i> Questionários</a>
                 <ul class="collapse list-unstyled custom-dropdown-menu" id="questionariosSubmenu">
                    <li><a href="../../questionarios/ebia.html"><i class="fas fa-balance-scale-right"></i> EBIA</a></li>
                    <li><a href="../../questionarios/consumo_alimentar.html"><i class="fas fa-utensils"></i> Consumo</a></li>
                 </ul>
             </li>
             <li> <a href="../../questionarios/relatorios/relatorios.html"><i class="fas fa-chart-pie"></i> Relatórios</a> </li>
             <div class="line"></div>
             <li class="<?php echo ($paginaAtiva === 'contas' || $paginaAtiva === 'minha_conta') ? 'active' : ''; ?>">
                  <a href="#gerenciarSubmenu" data-toggle="collapse" aria-expanded="<?php echo ($paginaAtiva === 'contas' || $paginaAtiva === 'minha_conta') ? 'true' : 'false'; ?>" class="dropdown-toggle"><i class="fas fa-cogs"></i> Gerenciar</a>
                  <ul class="collapse list-unstyled custom-dropdown-menu <?php echo ($paginaAtiva === 'contas' || $paginaAtiva === 'minha_conta') ? 'show' : ''; ?>" id="gerenciarSubmenu">
                     <li class="<?php echo ($paginaAtiva === 'contas') ? 'active' : ''; ?>"> <a href="listar_contas.php"><i class="fas fa-users-cog"></i> Contas <span class="badge badge-admin ml-1">Admin</span></a> </li>
                     <li class="<?php echo ($paginaAtiva === 'minha_conta') ? 'active' : ''; ?>"> <a href="editar_conta.php?id=<?php echo $currentUserId; ?>"><i class="fas fa-user-edit"></i> Minha Conta</a> </li>
                  </ul>
             </li>
             <li> <a href="../lagout/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a> </li>
        </ul>
    </nav>

    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-white rounded navbar-top">
             <div class="container-fluid">
                 <span class="navbar-text ml-auto"> Olá, <strong><?php echo htmlspecialchars($nomeUsuario); ?></strong>! </span>
             </div>
         </nav>

        <h2 id="form-title"><i class="fas fa-edit"></i>Editar <?php echo $isEditingSelf ? 'Minha Conta' : 'Participante'; ?></h2>
        <p class="text-muted mb-4">Atualize os dados da conta. Campos marcados com <span class="text-danger">*</span> são obrigatórios.</p>

        <div id="message-area" class="my-3">
            <?php if (isset($viewData['update_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Erro!</h5>
                    <?php echo nl2br(htmlspecialchars($viewData['update_error'])); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            <?php elseif (isset($viewData['update_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                     <h5 class="alert-heading"><i class="fas fa-check-circle"></i> Sucesso!</h5>
                     <?php echo htmlspecialchars($viewData['update_success']); ?>
                     <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            <?php endif; ?>
            <?php if ($erro_fetch): /* Erro ao buscar dados iniciais */ ?>
                <div class="alert alert-danger">Erro ao carregar dados: <?php echo htmlspecialchars($erro_fetch); ?></div>
            <?php endif; ?>
        </div>

        <?php // Só mostra o formulário se os dados foram carregados com sucesso
        if ($participante && !$erro_fetch): ?>
            <form action="update_conta.php" method="POST" id="edit-form" novalidate>
                <input type="hidden" name="id" id="participante-id" value="<?php echo htmlspecialchars($viewData['id'] ?? ''); ?>">
                <input type="hidden" name="csrf_token" id="csrf-token" value="<?php echo htmlspecialchars($viewData['csrfToken'] ?? ''); ?>">

                <div class="card form-section-card mb-4">
                    <div class="card-header"> <i class="fas fa-lock"></i> Dados de Acesso </div>
                    <div class="card-body">
                         <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nome" class="required">Nome Completo:</label>
                                <input type="text" id="nome" name="nome" required class="form-control" value="<?php echo htmlspecialchars($viewData['participante']['nome'] ?? ''); ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="email" class="required">E-mail:</label>
                                <div class="input-group">
                                     <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-envelope"></i></span></div>
                                     <input type="email" id="email" name="email" required class="form-control" value="<?php echo htmlspecialchars($viewData['participante']['email'] ?? ''); ?>" placeholder="exemplo@dominio.com">
                                </div>
                            </div>
                         </div>
                         <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="senha">Nova Senha:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-key"></i></span></div>
                                    <input type="password" id="senha" name="senha" class="form-control" placeholder="Deixe em branco para não alterar" minlength="6">
                                </div>
                                <small class="form-text text-muted">Mínimo 6 caracteres. Preencha apenas se desejar alterar a senha.</small>
                            </div>
                             <div class="form-group col-md-6">
                                <label for="senha_confirm">Confirmar Nova Senha:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-key"></i></span></div>
                                    <input type="password" id="senha_confirm" name="senha_confirm" class="form-control" placeholder="Repita a nova senha" minlength="6">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card form-section-card mb-4">
                     <div class="card-header"> <i class="fas fa-info-circle"></i> Informações Adicionais </div>
                     <div class="card-body">
                        <div class="form-row">
                             <div class="form-group col-md-4">
                                <label for="idade" class="optional">Idade:</label>
                                <input type="number" id="idade" name="idade" class="form-control" value="<?php echo htmlspecialchars($viewData['participante']['idade'] ?? ''); ?>" min="0">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="genero" class="optional">Gênero:</label>
                                <select id="genero" name="genero" class="form-control custom-select">
                                     <option value="" <?php echo empty($viewData['genero_data']['base']) ? 'selected' : ''; ?>>Selecione...</option>
                                     <option value="masculino" <?php echo ($viewData['genero_data']['base'] ?? '') === 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                                     <option value="feminino" <?php echo ($viewData['genero_data']['base'] ?? '') === 'feminino' ? 'selected' : ''; ?>>Feminino</option>
                                     <option value="transgenero" <?php echo ($viewData['genero_data']['base'] ?? '') === 'transgenero' ? 'selected' : ''; ?>>Transgênero</option>
                                     <option value="nao_binario" <?php echo ($viewData['genero_data']['base'] ?? '') === 'nao_binario' ? 'selected' : ''; ?>>Não Binário</option>
                                     <option value="outro" <?php echo ($viewData['genero_data']['base'] ?? '') === 'outro' ? 'selected' : ''; ?>>Outro</option>
                                     <option value="prefere_nao_dizer" <?php echo ($viewData['genero_data']['base'] ?? '') === 'prefere_nao_dizer' ? 'selected' : ''; ?>>Prefere não dizer</option>
                                </select>
                                <input type="text" id="genero_outro" name="genero_outro" class="form-control mt-2" placeholder="Qual?" value="<?php echo htmlspecialchars($viewData['genero_data']['outro'] ?? ''); ?>" style="<?php echo ($viewData['genero_data']['base'] ?? '') === 'outro' ? 'display: block;' : 'display: none;'; ?>">
                            </div>
                             <div class="form-group col-md-4">
                                <label for="estado_civil" class="optional">Estado Civil:</label>
                                <select id="estado_civil" name="estado_civil" class="form-control custom-select">
                                     <option value="" <?php echo empty($viewData['participante']['estado_civil']) ? 'selected' : ''; ?>>Selecione...</option>
                                     <option value="solteiro" <?php echo ($viewData['participante']['estado_civil'] ?? '') === 'solteiro' ? 'selected' : ''; ?>>Solteiro(a)</option>
                                     <option value="casado" <?php echo ($viewData['participante']['estado_civil'] ?? '') === 'casado' ? 'selected' : ''; ?>>Casado(a)</option>
                                     <option value="divorciado" <?php echo ($viewData['participante']['estado_civil'] ?? '') === 'divorciado' ? 'selected' : ''; ?>>Divorciado(a)</option>
                                     <option value="viuvo" <?php echo ($viewData['participante']['estado_civil'] ?? '') === 'viuvo' ? 'selected' : ''; ?>>Viúvo(a)</option>
                                     <option value="separado" <?php echo ($viewData['participante']['estado_civil'] ?? '') === 'separado' ? 'selected' : ''; ?>>Separado(a)</option>
                                     <option value="uniao_estavel" <?php echo ($viewData['participante']['estado_civil'] ?? '') === 'uniao_estavel' ? 'selected' : ''; ?>>União Estável</option>
                                     <option value="prefere_nao_dizer" <?php echo ($viewData['participante']['estado_civil'] ?? '') === 'prefere_nao_dizer' ? 'selected' : ''; ?>>Prefere não dizer</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                             <div class="form-group col-md-6">
                                <label class="d-block optional">Raça/Cor:</label>
                                <?php $racaBase = $viewData['raca_data']['base'] ?? ''; ?>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" id="raca_branco" name="raca" value="branco" <?php echo $racaBase === 'branco' ? 'checked' : ''; ?>><label class="form-check-label" for="raca_branco">Branco</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" id="raca_preto" name="raca" value="preto" <?php echo $racaBase === 'preto' ? 'checked' : ''; ?>><label class="form-check-label" for="raca_preto">Preto</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" id="raca_pardo" name="raca" value="pardo" <?php echo $racaBase === 'pardo' ? 'checked' : ''; ?>><label class="form-check-label" for="raca_pardo">Pardo</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" id="raca_povos_originarios" name="raca" value="povos_originarios" <?php echo $racaBase === 'povos_originarios' ? 'checked' : ''; ?>><label class="form-check-label" for="raca_povos_originarios">Povos originários</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" id="raca_outro_check" name="raca" value="outro" <?php echo $racaBase === 'outro' ? 'checked' : ''; ?>><label class="form-check-label" for="raca_outro_check">Outro:</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" id="raca_prefere" name="raca" value="prefere_nao_dizer" <?php echo $racaBase === 'prefere_nao_dizer' ? 'checked' : ''; ?>><label class="form-check-label" for="raca_prefere">Prefere não dizer</label></div>
                                <input type="text" id="raca_outro" name="raca_outro" class="form-control mt-1" placeholder="Qual?" value="<?php echo htmlspecialchars($viewData['raca_data']['outro'] ?? ''); ?>" style="<?php echo $racaBase === 'outro' ? 'display: block;' : 'display: none;'; ?>">
                            </div>

                             <div class="form-group col-md-6">
                                <label class="d-block optional">Escolaridade:</label>
                                 <?php $escolaridadeBase = $viewData['escolaridade_data']['base'] ?? ''; ?>
                                <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_fund_inc" value="ensino_fundamental_incompleto" <?php echo $escolaridadeBase === 'ensino_fundamental_incompleto' ? 'checked' : ''; ?>><label class="form-check-label" for="esc_fund_inc">Ens. fundamental incompleto</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_fund_com" value="ensino_fundamental_completo" <?php echo $escolaridadeBase === 'ensino_fundamental_completo' ? 'checked' : ''; ?>><label class="form-check-label" for="esc_fund_com">Ens. fundamental completo</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_med_inc" value="ensino_medio_incompleto" <?php echo $escolaridadeBase === 'ensino_medio_incompleto' ? 'checked' : ''; ?>><label class="form-check-label" for="esc_med_inc">Ens. médio incompleto</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_med_com" value="ensino_medio_completo" <?php echo $escolaridadeBase === 'ensino_medio_completo' ? 'checked' : ''; ?>><label class="form-check-label" for="esc_med_com">Ens. médio completo</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_grad_inc" value="graduacao_incompleta" <?php echo $escolaridadeBase === 'graduacao_incompleta' ? 'checked' : ''; ?>><label class="form-check-label" for="esc_grad_inc">Graduação incompleta</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_grad_com" value="graduacao_completa" <?php echo $escolaridadeBase === 'graduacao_completa' ? 'checked' : ''; ?>><label class="form-check-label" for="esc_grad_com">Graduação completa</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_outro_check" value="outro" <?php echo $escolaridadeBase === 'outro' ? 'checked' : ''; ?>><label class="form-check-label" for="esc_outro_check">Outro:</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_prefere" value="prefere_nao_dizer" <?php echo $escolaridadeBase === 'prefere_nao_dizer' ? 'checked' : ''; ?>><label class="form-check-label" for="esc_prefere">Prefere não dizer</label></div>
                                <input type="text" id="escolaridade_outro" name="escolaridade_outro" class="form-control mt-1" placeholder="Qual?" value="<?php echo htmlspecialchars($viewData['escolaridade_data']['outro'] ?? ''); ?>" style="<?php echo $escolaridadeBase === 'outro' ? 'display: block;' : 'display: none;'; ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="d-block optional">Situação de Trabalho:</label>
                                 <?php $empregoBase = $viewData['emprego_data']['base'] ?? ''; ?>
                                <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_meio" value="meio_periodo" <?php echo $empregoBase === 'meio_periodo' ? 'checked' : ''; ?>><label class="form-check-label" for="emp_meio">Meio período</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_integral" value="tempo_integral" <?php echo $empregoBase === 'tempo_integral' ? 'checked' : ''; ?>><label class="form-check-label" for="emp_integral">Tempo integral</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_autonomo" value="autonomo" <?php echo $empregoBase === 'autonomo' ? 'checked' : ''; ?>><label class="form-check-label" for="emp_autonomo">Autônomo</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_desemp" value="desempregado" <?php echo $empregoBase === 'desempregado' ? 'checked' : ''; ?>><label class="form-check-label" for="emp_desemp">Desempregado</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_incapaz" value="incapaz_trabalhar" <?php echo $empregoBase === 'incapaz_trabalhar' ? 'checked' : ''; ?>><label class="form-check-label" for="emp_incapaz">Incapaz de trabalhar</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_apos" value="aposentado" <?php echo $empregoBase === 'aposentado' ? 'checked' : ''; ?>><label class="form-check-label" for="emp_apos">Aposentado</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_estud" value="estudante" <?php echo $empregoBase === 'estudante' ? 'checked' : ''; ?>><label class="form-check-label" for="emp_estud">Estudante</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_outro_check" value="outro" <?php echo $empregoBase === 'outro' ? 'checked' : ''; ?>><label class="form-check-label" for="emp_outro_check">Outro:</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_prefere" value="prefere_nao_dizer" <?php echo $empregoBase === 'prefere_nao_dizer' ? 'checked' : ''; ?>><label class="form-check-label" for="emp_prefere">Prefere não dizer</label></div>
                                <input type="text" id="situacao_emprego_outro" name="situacao_emprego_outro" class="form-control mt-1" placeholder="Qual?" value="<?php echo htmlspecialchars($viewData['emprego_data']['outro'] ?? ''); ?>" style="<?php echo $empregoBase === 'outro' ? 'display: block;' : 'display: none;'; ?>">
                            </div>
                             <div class="form-group col-md-6">
                                <label class="d-block optional">Religião:</label>
                                <?php $religiaoBase = $viewData['religiao_data']['base'] ?? ''; ?>
                                <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_cat" value="catolico" <?php echo $religiaoBase === 'catolico' ? 'checked' : ''; ?>><label class="form-check-label" for="rel_cat">Católico</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_evan" value="evangelico" <?php echo $religiaoBase === 'evangelico' ? 'checked' : ''; ?>><label class="form-check-label" for="rel_evan">Evangélico</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_esp" value="espirita" <?php echo $religiaoBase === 'espirita' ? 'checked' : ''; ?>><label class="form-check-label" for="rel_esp">Espírita</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_umb" value="umbanda" <?php echo $religiaoBase === 'umbanda' ? 'checked' : ''; ?>><label class="form-check-label" for="rel_umb">Umbanda</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_cand" value="candomble" <?php echo $religiaoBase === 'candomble' ? 'checked' : ''; ?>><label class="form-check-label" for="rel_cand">Candomblé</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_ateu" value="ateu" <?php echo $religiaoBase === 'ateu' ? 'checked' : ''; ?>><label class="form-check-label" for="rel_ateu">Ateu</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_nenhum" value="nenhum" <?php echo $religiaoBase === 'nenhum' ? 'checked' : ''; ?>><label class="form-check-label" for="rel_nenhum">Nenhuma</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_outro_check" value="outro" <?php echo $religiaoBase === 'outro' ? 'checked' : ''; ?>><label class="form-check-label" for="rel_outro_check">Outra:</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_prefere" value="prefere_nao_dizer" <?php echo $religiaoBase === 'prefere_nao_dizer' ? 'checked' : ''; ?>><label class="form-check-label" for="rel_prefere">Prefere não dizer</label></div>
                                <input type="text" id="religiao_outro" name="religiao_outro" class="form-control mt-1" placeholder="Qual?" value="<?php echo htmlspecialchars($viewData['religiao_data']['outro'] ?? ''); ?>" style="<?php echo $religiaoBase === 'outro' ? 'display: block;' : 'display: none;'; ?>">
                            </div>
                         </div>

                         <div class="form-row">
                              <div class="form-group col-md-6">
                                <label class="d-block optional">Recebe algum benefício social?</label>
                                 <?php $beneficiosBase = $viewData['beneficios_data']['base'] ?? []; $beneficiosOutro = $viewData['beneficios_data']['outro'] ?? ''; ?>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="Bolsa Familia" id="ben_bf" <?php echo in_array('Bolsa Familia', $beneficiosBase) ? 'checked' : ''; ?>><label class="form-check-label" for="ben_bf">Bolsa Família / Auxílio Brasil</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="Auxilio Gas" id="ben_gas" <?php echo in_array('Auxilio Gas', $beneficiosBase) ? 'checked' : ''; ?>><label class="form-check-label" for="ben_gas">Auxílio Gás</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="BPC" id="ben_bpc" <?php echo in_array('BPC', $beneficiosBase) ? 'checked' : ''; ?>><label class="form-check-label" for="ben_bpc">BPC/LOAS</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="Nenhum" id="ben_nenhum" <?php echo in_array('Nenhum', $beneficiosBase) ? 'checked' : ''; ?>><label class="form-check-label" for="ben_nenhum">Nenhum</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="Outros" id="ben_outros_check" <?php echo in_array('Outros', $beneficiosBase) ? 'checked' : ''; ?>><label class="form-check-label" for="ben_outros_check">Outros:</label></div>
                                <input type="text" id="beneficios_sociais_outro" name="beneficios_sociais_outro" class="form-control mt-1" placeholder="Quais?" value="<?php echo htmlspecialchars($beneficiosOutro); ?>" style="<?php echo in_array('Outros', $beneficiosBase) ? 'display: block;' : 'display: none;'; ?>">
                            </div>
                             <div class="form-group col-md-6">
                                <label class="d-block optional">Número de dependentes:</label>
                                <small class="form-text text-muted mt-0 mb-2">Pessoas que dependem financeiramente de você.</small>
                                 <?php $depBase = $viewData['dependentes_data']['base'] ?? ''; $depOutro = $viewData['dependentes_data']['outro'] ?? '';?>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_0" value="0" <?php echo $depBase === '0' ? 'checked' : ''; ?>><label class="form-check-label" for="dep_0">0</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_1" value="1" <?php echo $depBase === '1' ? 'checked' : ''; ?>><label class="form-check-label" for="dep_1">1</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_2" value="2" <?php echo $depBase === '2' ? 'checked' : ''; ?>><label class="form-check-label" for="dep_2">2</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_3" value="3" <?php echo $depBase === '3' ? 'checked' : ''; ?>><label class="form-check-label" for="dep_3">3</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_4" value="4 ou mais" <?php echo $depBase === '4 ou mais' ? 'checked' : ''; ?>><label class="form-check-label" for="dep_4">4+</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_outro_check" value="Outro" <?php echo $depBase === 'Outro' ? 'checked' : ''; ?>><label class="form-check-label" for="dep_outro_check">Outro (especificar):</label></div>
                                <input type="text" id="numero_dependentes_outro" name="numero_dependentes_outro" class="form-control mt-1" placeholder="Quantos/Quais?" value="<?php echo htmlspecialchars($depOutro); ?>" style="<?php echo $depBase === 'Outro' ? 'display: block;' : 'display: none;'; ?>">
                            </div>
                         </div>
                     </div>
                 </div>

                <hr class="my-4">

                <div class="text-center mt-4 mb-3">
                    <button type="submit" class="btn btn-success btn-lg mr-2">
                         <i class="fas fa-save mr-2"></i>Atualizar Dados
                    </button>
                     <?php if ($isEditingSelf): // Botão Cancelar leva ao menu principal se editando a própria conta ?>
                       <a href="menu.php" class="btn btn-secondary btn-lg mr-2">
                         <i class="fas fa-times-circle mr-2"></i>Cancelar
                       </a>
                     <?php elseif ($isAdmin): // Botão Cancelar leva à lista se for admin editando outro ?>
                        <a href="listar_contas.php" class="btn btn-secondary btn-lg mr-2">
                           <i class="fas fa-arrow-left mr-2"></i>Voltar à Lista
                       </a>
                    <?php endif; ?>

                    <?php if ($isAdmin && !$isEditingSelf): // Mostra botão extra para lista apenas se for admin e não estiver editando a própria conta ?>
                    <a href="listar_contas.php" class="btn btn-outline-info btn-lg">
                        <i class="fas fa-list-ul mr-2"></i>Ver Lista Completa
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; // Fim do if ($participante && !$erro_fetch) ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js" integrity="sha384-+sLIOodYLS7CIrQpBjl+C7nPvqq+FbNUBDunl/OZv93DB7Ln/533i8e/mZXLi/P+" crossorigin="anonymous"></script>

<script>
    // JS para campos 'Outro' e validação de senha (Mantido e adaptado para IDs e nomes corretos)
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('edit-form');
        if (!form) return;

        const senhaInput = document.getElementById('senha');
        const senhaConfirmInput = document.getElementById('senha_confirm');

        function setupOutroFieldVisibility(baseElementName, outroInputId) {
            const elements = document.querySelectorAll(`[name="${baseElementName}"]`); // Pode ser select ou radios
            const outroInput = document.getElementById(outroInputId);
            if (!elements.length || !outroInput) return;

            const toggle = () => {
                let show = false;
                if (elements[0].tagName === 'SELECT') {
                    show = elements[0].value === 'outro';
                } else { // Radio buttons
                    const checkedRadio = document.querySelector(`input[name="${baseElementName}"]:checked`);
                    // Verifica se o valor é 'outro' ou 'Outro' (para dependentes)
                    show = checkedRadio && (checkedRadio.value.toLowerCase() === 'outro');
                }
                outroInput.style.display = show ? 'block' : 'none';
                outroInput.required = show; // Torna obrigatório apenas se visível
                if (!show) outroInput.value = ''; // Limpa se oculto
            };

            elements.forEach(el => el.addEventListener('change', toggle));
            toggle(); // Chama no início para definir o estado inicial
        }

        function setupBeneficiosOutro(outroCheckboxId, outroInputId) {
            const outroCheckbox = document.getElementById(outroCheckboxId);
            const outroInput = document.getElementById(outroInputId);
            if (!outroCheckbox || !outroInput) return;

            const toggle = () => {
                outroInput.style.display = outroCheckbox.checked ? 'block' : 'none';
                outroInput.required = outroCheckbox.checked;
                if (!outroCheckbox.checked) outroInput.value = '';
            };
            outroCheckbox.addEventListener('change', toggle);
            toggle(); // Chama no início
        }

        // Configura os campos "Outro"
        setupOutroFieldVisibility('genero', 'genero_outro');
        setupOutroFieldVisibility('raca', 'raca_outro');
        setupOutroFieldVisibility('escolaridade', 'escolaridade_outro');
        setupOutroFieldVisibility('situacao_emprego', 'situacao_emprego_outro');
        setupOutroFieldVisibility('numero_dependentes', 'numero_dependentes_outro'); // Nome do radio é numero_dependentes
        setupOutroFieldVisibility('religiao', 'religiao_outro');
        setupBeneficiosOutro('ben_outros_check', 'beneficios_sociais_outro');


        // Validação de confirmação de senha no submit
         form.addEventListener('submit', function(event) {
            let isValid = true;
            // 1. Validar campos obrigatórios gerais (nome, email)
             if (!document.getElementById('nome').value.trim()) {
                 alert('O campo Nome Completo é obrigatório.');
                 document.getElementById('nome').focus();
                 isValid = false;
             }
             else if (!document.getElementById('email').value.trim()) {
                 alert('O campo E-mail é obrigatório.');
                  document.getElementById('email').focus();
                  isValid = false;
             } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(document.getElementById('email').value)) {
                 alert('O formato do E-mail é inválido.');
                 document.getElementById('email').focus();
                 isValid = false;
             }

             // 2. Validação de senha (APENAS se preenchida)
             if (isValid && senhaInput.value !== '') { // Só valida se a senha foi digitada
                 if (senhaInput.value.length < 6) {
                    alert('A nova senha deve ter pelo menos 6 caracteres.');
                    senhaInput.focus();
                    isValid = false;
                 }
                 else if (senhaInput.value !== senhaConfirmInput.value) {
                     alert('A Nova Senha e a Confirmação de Senha não coincidem.');
                     senhaConfirmInput.focus();
                     isValid = false;
                 }
             } else if (isValid && senhaConfirmInput.value !== '' && senhaInput.value === '') {
                 // Caso o usuário preencha apenas a confirmação
                 alert('Por favor, preencha o campo "Nova Senha" se deseja alterá-la.');
                 senhaInput.focus();
                 isValid = false;
             }

             // 3. Validar campos 'Outro' obrigatórios (se visíveis)
             const outrosVisiveis = form.querySelectorAll('input[id$="_outro"]:required');
             outrosVisiveis.forEach(input => {
                 if (isValid && !input.value.trim()) { // Só valida se o resto estiver ok
                     let labelElement = input.closest('.form-group').querySelector('label:not(.form-check-label)');
                     let labelText = labelElement ? labelElement.textContent.replace(/[:*]| \(opcional\)/gi, '').trim() : 'Campo "Outro"'; // Limpa melhor o label
                     alert(`Por favor, especifique o valor para "${labelText}".`);
                      input.focus();
                      isValid = false;
                 }
             });

            if (!isValid) {
                event.preventDefault(); // Impede o envio do formulário se inválido
            }
         });
    });
</script>
</body>
</html>