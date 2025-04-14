<?php
 session_start();
 // Verifica se o PARTICIPANTE está logado
 if (!isset($_SESSION['participante_id'])) {
  header("Location: ../login/login.php"); // Redireciona para o login único
  exit;
 }

 // Cabeçalho e Boas-vindas
 ?>
 <!DOCTYPE html>
 <html lang="pt-br">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu Principal - Nutriware</title> <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
 </head>
 <body class="container mt-4">
  <h1>Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['participante_nome']); ?>!</h1>
  <p>Selecione uma opção:</p>
  <hr>

  <div class="list-group">
      <a href="../../questionarios/ebia.html" class="list-group-item list-group-item-action">Responder Questionário EBIA</a>
      <a href="../../questionarios/consumo_alimentar.html" class="list-group-item list-group-item-action">Responder Questionário Consumo Alimentar</a>
      <a href="../../questionarios/relatorios/relatorios.html" class="list-group-item list-group-item-action">Visualizar Relatórios</a> <a href="../lagout/logout.php" class="list-group-item list-group-item-action list-group-item-danger mt-3">Sair (Logout)</a>
  </div>

  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
 </body>
 </html>
 <?php
 ?>