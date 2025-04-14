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
  <title>Login - Nutriware</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> <style>
      html, body { height: 100%; }
      body {
          display: flex;
          align-items: center;
          justify-content: center;
          background-color: #e9f5e9; /* Mesmo fundo da index */
          padding-top: 40px;
          padding-bottom: 40px;
      }
      .card-container {
          max-width: 400px;
          width: 100%;
      }
      .card {
          padding: 2rem;
          border: none;
          box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      }
       .nutri-icon {
          font-size: 3rem;
          color: #28a745; /* Verde Bootstrap Success */
          margin-bottom: 1rem;
          display: block; /* Para centralizar o ícone */
          text-align: center; /* Para centralizar o ícone */
      }
      .form-signin-heading {
          text-align: center;
          margin-bottom: 1.5rem;
          color: #1E4620; /* Verde mais escuro */
      }
      .form-label-group { /* Para labels flutuantes (opcional, requer JS extra ou usar input-group) */
          position: relative;
          margin-bottom: 1rem;
      }
      .alert {
          font-size: 0.9rem;
      }
  </style>
 </head>
 <body>
    <div class="card-container">
         <div class="card">
            <i class="fas fa-leaf nutri-icon"></i> <h1 class="h3 mb-4 font-weight-normal form-signin-heading">Acessar Nutriware</h1>

            <?php
            // Exibe mensagem de erro se houver (definida por processa_login.php)
            if (isset($_SESSION['login_erro'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['login_erro']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                unset($_SESSION['login_erro']); // Limpa a mensagem
            }
            // Mensagem para erro CSRF
            if (isset($_GET['status']) && $_GET['status'] == 'erro_csrf') {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Erro de validação de segurança. Por favor, tente fazer login novamente.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            }
             if (isset($_GET['status']) && $_GET['status'] == 'erro') {
                 echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Ocorreu um erro. Verifique os dados ou tente mais tarde.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
             }
             // Mensagem de sucesso do cadastro
             if (isset($_GET['cadastro']) && $_GET['cadastro'] == 'sucesso') {
                 echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Cadastro realizado com sucesso! Faça seu login.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
             }
            ?>

            <form action="processa_login.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <div class="form-group">
                    <label for="email" class="sr-only">E-mail:</label> <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        </div>
                        <input type="email" id="email" name="email" required class="form-control form-control-lg" placeholder="Seu e-mail" autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="senha" class="sr-only">Senha:</label>
                     <div class="input-group">
                         <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        </div>
                        <input type="password" id="senha" name="senha" required class="form-control form-control-lg" placeholder="Sua senha">
                    </div>
                </div>

                <button type="submit" class="btn btn-lg btn-success btn-block mt-4">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>

            <p class="mt-4 mb-0 text-center text-muted">
                Não tem cadastro? <a href="../cadastro/cadastro.php" class="text-success">Cadastre-se aqui</a>
            </p>
            <p class="mt-4 mb-1 text-center text-muted" style="font-size: 0.8em;">
                <a href="../../index.html" class="text-secondary">Voltar à página inicial</a>
            </p>

        </div>
    </div>

  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
 </body>
 </html>