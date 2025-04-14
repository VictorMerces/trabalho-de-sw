<?php
session_start();

include __DIR__ . '/../../config/conexao.php';
include __DIR__ . '/../../config/error_handler.php';

// --- Verificação de Permissão (Exemplo - Adicione sua lógica de admin aqui) ---
// $permitido = false;
// if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin') {
//     $permitido = true;
// }
// if (!$permitido) {
//     $_SESSION['login_erro'] = "Você não tem permissão para acessar esta página.";
//     header('Location: ../login.php');
//     exit;
// }


$participantes = [];
$erro = '';

try {
    $stmt = $conexao->query("SELECT id, nome, email FROM participantes ORDER BY nome ASC");
    $participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar participantes: Entre em contato com o suporte.";
    error_log("Erro DB Listar Contas: " . $e->getMessage());
}

 // Variável para página ativa da sidebar
 $paginaAtiva = 'contas';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Contas - Nutriware</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Copie os estilos da Sidebar e Content do menu.php ou use CSS externo */
         .wrapper { display: flex; width: 100%; align-items: stretch; min-height: 100vh; }
         #sidebar { min-width: 250px; max-width: 250px; background: #28a745; color: #fff; transition: all 0.3s; }
         #sidebar .sidebar-header { padding: 20px; background: #218838; text-align: center; }
         #sidebar ul.components { padding: 20px 0; }
         #sidebar ul li a { padding: 10px 20px; font-size: 1.1em; display: block; color: rgba(255, 255, 255, 0.8); border-left: 3px solid transparent; }
         #sidebar ul li a:hover { color: #fff; background: #218838; text-decoration: none; }
         #sidebar ul li.active > a { color: #fff; background: #218838; border-left-color: #90EE90; }
         #sidebar ul li a i { margin-right: 10px; }
         #content { width: 100%; padding: 20px; transition: all 0.3s; background-color: #f8f9fa; }
         .table th { background-color: #e9ecef; } /* Fundo cinza claro para cabeçalho tabela */
         .table td, .table th { vertical-align: middle; }
         .btn-action { min-width: 80px; } /* Largura mínima para botões de ação */
         h2 i { margin-right: 10px; color: #28a745; }
    </style>
</head>
<body>
<div class="wrapper">
     <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-leaf"></i> Nutriware</h3>
        </div>
        <ul class="list-unstyled components">
             <li><a href="menu.php"><i class="fas fa-home"></i> Menu Principal</a></li>
             <li><a href="../../questionarios/ebia.html"><i class="fas fa-clipboard-list"></i> Questionário EBIA</a></li>
             <li><a href="../../questionarios/consumo_alimentar.html"><i class="fas fa-utensils"></i> Consumo Alimentar</a></li>
             <li><a href="../../questionarios/relatorios/relatorios.html"><i class="fas fa-chart-pie"></i> Relatórios</a></li>
             <div style="height: 1px; background-color: rgba(255,255,255,0.1); margin: 10px 20px;"></div>
             <li class="active"><a href="listar_contas.php"><i class="fas fa-users-cog"></i> Gerenciar Contas <span class="badge badge-light ml-1">Admin</span></a></li>
             <li><a href="editar_conta.php?id=<?php echo $_SESSION['participante_id'] ?? ''; ?>"><i class="fas fa-user-edit"></i> Minha Conta</a></li>
             <li><a href="../lagout/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
        </ul>
    </nav>

     <div id="content">
         <nav class="navbar navbar-expand-lg navbar-light bg-light">
             <div class="container-fluid">
                 <span class="navbar-text">
                    </span>
             </div>
         </nav>

        <h2><i class="fas fa-users-cog"></i>Gerenciar Contas de Participantes</h2>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <?php // Exibe outras mensagens da sessão (ex: sucesso na atualização, erro de permissão)
        if (isset($_SESSION['list_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['list_error']); unset($_SESSION['list_error']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['update_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                 <?php echo htmlspecialchars($_SESSION['update_success']); unset($_SESSION['update_success']); ?>
                 <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php endif; ?>


        <?php if (empty($participantes) && !$erro): ?>
            <div class="alert alert-info mt-3">Nenhum participante cadastrado no momento.</div>
        <?php elseif (!empty($participantes)): ?>
            <p class="text-muted">Selecione um participante abaixo para visualizar ou editar os dados cadastrais.</p>
            <div class="table-responsive shadow-sm mt-3">
                <table class="table table-striped table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th class="text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participantes as $participante): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($participante['nome']); ?></td>
                                <td><?php echo htmlspecialchars($participante['email']); ?></td>
                                <td class="text-center">
                                    <a href="editar_conta.php?id=<?php echo $participante['id']; ?>" class="btn btn-sm btn-warning btn-action" title="Editar Participante">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="menu.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar ao Menu
            </a>
            <a href="../../cadastro/cadastro.php" class="btn btn-success float-right">
                <i class="fas fa-user-plus"></i> Cadastrar Novo Participante
            </a>
        </div>

     </div></div><script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script>
        // Exemplo de função de confirmação para exclusão (se implementar)
        /*
        function confirmDelete(id) {
            if (confirm('Tem certeza que deseja excluir este participante? Esta ação não pode ser desfeita.')) {
                // Redirecionar para um script PHP que lida com a exclusão
                // window.location.href = 'excluir_conta.php?id=' + id + '&csrf_token=SEU_TOKEN_CSRF'; // Incluir CSRF
            }
        }
        */
    </script>
</body>
</html>