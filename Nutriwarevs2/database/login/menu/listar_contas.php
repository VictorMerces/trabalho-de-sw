<?php
session_start();

// Ajuste o caminho se colocar o arquivo em local diferente de database/contas/
include '../config/conexao.php';

$participantes = [];
$erro = '';

try {
    // Seleciona id, nome e email para a listagem
    $stmt = $conexao->query("SELECT id, nome, email FROM participantes ORDER BY nome ASC");
    $participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar participantes: Entre em contato com o suporte."; // Mensagem genérica
    // Logar o erro real para depuração interna
    error_log("Erro DB Listar Contas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar/Editar Contas</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>
<body class="container mt-4">
    <h1>Gerenciar Contas de Participantes</h1>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['list_error'])): // Exibe outros erros, como de permissão ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['list_error']); unset($_SESSION['list_error']); ?></div>
    <?php endif; ?>


    <?php if (empty($participantes) && !$erro): ?>
        <div class="alert alert-info">Nenhum participante cadastrado no momento.</div>
    <?php elseif (!empty($participantes)): ?>
        <p>Selecione um participante para visualizar ou editar os dados.</p>
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participantes as $participante): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($participante['nome']); ?></td>
                            <td><?php echo htmlspecialchars($participante['email']); ?></td>
                            <td>
                                <a href="editar_conta.php?id=<?php echo $participante['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Editar </a>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="mt-3">
         <a href="../login/menu/menu.html" class="btn btn-secondary">Voltar ao Menu</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>
</html>