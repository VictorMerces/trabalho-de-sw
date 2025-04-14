<?php
 session_start();
 // Gera token CSRF se não existir
 if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
 }
 ?>
 <!DOCTYPE html>
 <html lang="pt-br">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <title>Login - Nutriware</title> </head>
 <body class="container mt-5">
  <h1>Login</h1> <?php
   // Exibe mensagem de erro se houver (definida por processa_login.php)
    if (isset($_SESSION['login_erro'])) { // Usar 'login_erro' padrão
        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['login_erro']) . '</div>';
        unset($_SESSION['login_erro']); // Limpa a mensagem
    }
     // Mensagem para erro CSRF
     if (isset($_GET['status']) && $_GET['status'] == 'erro_csrf') {
         echo '<div class="alert alert-danger">Erro de validação de segurança. Por favor, tente fazer login novamente.</div>';
     }
      if (isset($_GET['status']) && $_GET['status'] == 'erro') {
          echo '<div class="alert alert-danger">Ocorreu um erro. Verifique os dados ou tente mais tarde.</div>';
      }
      // Mensagem de sucesso do cadastro
      if (isset($_GET['cadastro']) && $_GET['cadastro'] == 'sucesso') {
          echo '<div class="alert alert-success">Cadastro realizado com sucesso! Faça seu login.</div>';
      }
  ?>

  <form action="processa_login.php" method="POST" novalidate> <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
      <div class="form-group">
          <label for="email">E-mail:</label>
          <input type="email" id="email" name="email" required class="form-control" placeholder="seuemail@exemplo.com">
      </div>
      <div class="form-group">
          <label for="senha">Senha:</label>
          <input type="password" id="senha" name="senha" required class="form-control">
      </div>
      <button type="submit" class="btn btn-primary">Login</button>
  </form>
  <p class="mt-3">Não tem cadastro? <a href="../cadastro/cadastro.php">Cadastre-se aqui</a></p>
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
 </body>
 </html>