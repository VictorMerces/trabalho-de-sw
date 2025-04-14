<?php
 session_start();
 // Verifica se o PARTICIPANTE está logado
 if (!isset($_SESSION['participante_id'])) {
  header("Location: ../login.php"); // Redireciona para o login único
  exit;
 }

 // Pega o nome do usuário da sessão
 $nomeUsuario = $_SESSION['participante_nome'] ?? 'Participante';
 $userId = $_SESSION['participante_id']; // Pega o ID para link de editar conta

 // Define qual item do menu está ativo (exemplo, poderia vir de um parâmetro GET)
 $paginaAtiva = 'menu'; // Ou 'ebia', 'consumo', 'relatorios', 'contas', 'minha_conta'

 // Incluir Font Awesome (se não estiver globalmente)
 // <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

 // Incluir CSS (se não estiver globalmente - *NÃO USAR NESTA SOLUÇÃO*)
 // <link rel="stylesheet" href="path/to/style.css">

 ?>
 <!DOCTYPE html>
 <html lang="pt-br">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu Principal - Nutriware</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
      /* Estilos básicos para a Sidebar usando apenas Bootstrap */
      .wrapper { display: flex; width: 100%; align-items: stretch; min-height: 100vh; }
      #sidebar { min-width: 250px; max-width: 250px; background: #28a745; /* Verde Success */ color: #fff; transition: all 0.3s; }
      #sidebar .sidebar-header { padding: 20px; background: #218838; /* Verde Success mais escuro */ text-align: center;}
      #sidebar ul.components { padding: 20px 0; }
      #sidebar ul li a { padding: 10px 20px; font-size: 1.1em; display: block; color: rgba(255, 255, 255, 0.8); border-left: 3px solid transparent; }
      #sidebar ul li a:hover { color: #fff; background: #218838; text-decoration: none; }
      #sidebar ul li.active > a { color: #fff; background: #218838; border-left-color: #90EE90; /* Verde Claro */ }
      #sidebar ul li a i { margin-right: 10px; }
      #content { width: 100%; padding: 20px; transition: all 0.3s; background-color: #f8f9fa; /* Fundo cinza claro */ }
      .navbar { padding: 15px 10px; background: #fff; border: none; border-radius: 0; margin-bottom: 20px; box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1); }
      .navbar-btn { box-shadow: none; outline: none !important; border: none; }
      .line { width: 100%; height: 1px; border-bottom: 1px dashed #ddd; margin: 20px 0; }

      /* Ocultar sidebar em telas pequenas - requer JS para botão toggle */
      @media (max-width: 768px) {
          #sidebar { margin-left: -250px; }
          #sidebar.active { margin-left: 0; }
           /* #sidebarCollapse span { display: none; } */ /* Oculta texto do botão se houver */
      }
  </style>
 </head>
 <body>

 <div class="wrapper">
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-leaf"></i> Nutriware</h3>

        </div>

        <ul class="list-unstyled components">
            <li class="<?php echo ($paginaAtiva == 'menu') ? 'active' : ''; ?>">
                <a href="menu.php">
                    <i class="fas fa-home"></i>
                    Menu Principal
                </a>
            </li>
            <li class="<?php echo ($paginaAtiva == 'ebia') ? 'active' : ''; ?>">
                <a href="../../questionarios/ebia.html">
                    <i class="fas fa-clipboard-list"></i>
                    Questionário EBIA
                </a>
            </li>
            <li class="<?php echo ($paginaAtiva == 'consumo') ? 'active' : ''; ?>">
                <a href="../../questionarios/consumo_alimentar.html">
                    <i class="fas fa-utensils"></i>
                    Consumo Alimentar
                </a>
            </li>
             <li class="<?php echo ($paginaAtiva == 'relatorios') ? 'active' : ''; ?>">
                <a href="../../questionarios/relatorios/relatorios.html">
                    <i class="fas fa-chart-pie"></i>
                    Relatórios
                </a>
            </li>
            <div class="line"></div>
             <li class="<?php echo ($paginaAtiva == 'contas') ? 'active' : ''; ?>">
                <a href="listar_contas.php">
                    <i class="fas fa-users-cog"></i>
                    Gerenciar Contas <span class="badge badge-info ml-1">Admin</span>
                </a>
            </li>
             <li class="<?php echo ($paginaAtiva == 'minha_conta') ? 'active' : ''; ?>">
                <a href="editar_conta.php?id=<?php echo $userId; ?>">
                    <i class="fas fa-user-edit"></i>
                    Minha Conta
                </a>
            </li>
            <li>
                <a href="../lagout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Sair
                </a>
            </li>
        </ul>
    </nav>

    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                 <div class="ml-auto"> <span class="navbar-text mr-3">
                         Olá, <?php echo htmlspecialchars($nomeUsuario); ?>!
                     </span>

                 </div>
            </div>
        </nav>

        <h2><i class="fas fa-home"></i> Menu Principal</h2>
        <p>Bem-vindo(a) ao sistema Nutriware. Utilize o menu lateral para navegar entre as funcionalidades.</p>
        <p>Selecione uma das opções para começar:</p>

         <div class="list-group mt-4">
            <a href="../../questionarios/ebia.html" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center list-group-item-success">
                <span><i class="fas fa-clipboard-list mr-2"></i>Responder Questionário EBIA</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            <a href="../../questionarios/consumo_alimentar.html" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center list-group-item-success">
                 <span><i class="fas fa-utensils mr-2"></i>Responder Questionário Consumo Alimentar</span>
                 <i class="fas fa-chevron-right"></i>
            </a>
            <a href="../../questionarios/relatorios/relatorios.html" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center list-group-item-info">
                <span><i class="fas fa-chart-pie mr-2"></i>Visualizar Relatórios</span>
                 <i class="fas fa-chevron-right"></i>
            </a>
            <a href="listar_contas.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center list-group-item-warning">
                <span><i class="fas fa-users-cog mr-2"></i>Gerenciar Contas <span class="badge badge-dark ml-1">Admin</span></span>
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>

        <div class="line"></div>

        <p>Para sair do sistema, clique em "Sair" no menu lateral ou <a href="../lagout/logout.php" class="text-danger">clique aqui</a>.</p>
        </div> </div> <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
  <script>
      // Exemplo de JS para o toggle da sidebar (opcional)
      /*
      $(document).ready(function () {
          $('#sidebarCollapse').on('click', function () {
              $('#sidebar').toggleClass('active');
          });
      });
      */
  </script>
 </body>
 </html>