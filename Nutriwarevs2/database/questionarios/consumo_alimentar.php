<?php
 session_start();
 // Verifica se o PARTICIPANTE está logado
 if (!isset($_SESSION['participante_id'])) {
     header("Location: ../login/login.php");
     exit;
 }
 // Pega o nome do usuário da sessão
 $nomeUsuario = $_SESSION['participante_nome'] ?? 'Participante';
 $userId = $_SESSION['participante_id'];
 $paginaAtiva = 'questionarios'; // Define a página ativa para a sidebar

 include __DIR__ . '/../config/conexao.php'; // Ajusta caminho para incluir da pasta config
 include __DIR__ . '/../config/error_handler.php'; // Ajusta caminho

 $mensagem_resultado = '';
 $css_class_resultado = 'alert-danger'; // Default erro

 if ($_SERVER["REQUEST_METHOD"] == "POST") {
     $participante_id = $_SESSION['participante_id'];
     // Sanitização e Validação (Original)
     $refeicoes_arr = filter_input(INPUT_POST, 'refeicoes', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? [];
     $refeicoes = !empty($refeicoes_arr) ? implode(",", array_map('strip_tags', $refeicoes_arr)) : null;
     $usa_dispositivos = isset($_POST['usa_dispositivos']) ? filter_var($_POST['usa_dispositivos'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
     $feijao = isset($_POST['feijao']) ? 1 : 0;
     $frutas_frescas = isset($_POST['frutas_frescas']) ? 1 : 0;
     $verduras_legumes = isset($_POST['verduras_legumes']) ? 1 : 0;
     $hamburguer_embutidos = isset($_POST['hamburguer_embutidos']) ? 1 : 0;
     $bebidas_adocadas = isset($_POST['bebidas_adocadas']) ? 1 : 0;
     $macarrao_instantaneo = isset($_POST['macarrao_instantaneo']) ? 1 : 0;
     $biscoitos_recheados = isset($_POST['biscoitos_recheados']) ? 1 : 0;

     if ($refeicoes === null && $usa_dispositivos === null) {
         $mensagem_resultado = "Erro: Por favor, responda pelo menos uma pergunta sobre refeições ou uso de dispositivos.";
     } else {
         try {
             $stmt_consumo = $conexao->prepare("INSERT INTO consumo_alimentar (participante_id, refeicoes, usa_dispositivos, feijao, frutas_frescas, verduras_legumes, hamburguer_embutidos, bebidas_adocadas, macarrao_instantaneo, biscoitos_recheados, data_preenchimento) VALUES (:participante_id, :refeicoes, :usa_dispositivos, :feijao, :frutas_frescas, :verduras_legumes, :hamburguer_embutidos, :bebidas_adocadas, :macarrao_instantaneo, :biscoitos_recheados, NOW())");
             $stmt_consumo->bindParam(':participante_id', $participante_id, PDO::PARAM_INT);
             $stmt_consumo->bindParam(':refeicoes', $refeicoes, PDO::PARAM_STR);
             $stmt_consumo->bindParam(':usa_dispositivos', $usa_dispositivos, PDO::PARAM_BOOL);
             $stmt_consumo->bindParam(':feijao', $feijao, PDO::PARAM_INT);
             $stmt_consumo->bindParam(':frutas_frescas', $frutas_frescas, PDO::PARAM_INT);
             $stmt_consumo->bindParam(':verduras_legumes', $verduras_legumes, PDO::PARAM_INT);
             $stmt_consumo->bindParam(':hamburguer_embutidos', $hamburguer_embutidos, PDO::PARAM_INT);
             $stmt_consumo->bindParam(':bebidas_adocadas', $bebidas_adocadas, PDO::PARAM_INT);
             $stmt_consumo->bindParam(':macarrao_instantaneo', $macarrao_instantaneo, PDO::PARAM_INT);
             $stmt_consumo->bindParam(':biscoitos_recheados', $biscoitos_recheados, PDO::PARAM_INT);
             $stmt_consumo->execute();
             $mensagem_resultado = "Questionário de Consumo Alimentar respondido com sucesso!";
             $css_class_resultado = 'alert-success';
         } catch (PDOException $e) {
             error_log("Erro DB Consumo Insert: " . $e->getMessage());
             $mensagem_resultado = "Erro ao enviar o questionário de Consumo Alimentar. Tente novamente.";
         }
     }
 } else {
     $mensagem_resultado = "Por favor, preencha o questionário de consumo alimentar.";
     $css_class_resultado = 'alert-info';
 }
 ?>
 <!DOCTYPE html>
 <html lang="pt-br">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <title>Resultado Questionário Consumo Alimentar</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    /* Estilos Sidebar */
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
    .badge-admin { font-size: 0.7em; background-color: #17a2b8; color: white; }
    /* Estilos Resultado */
    .resultado-card { border: 1px solid; border-radius: .25rem; }
    .resultado-card.alert-success { border-color: #badbcc; }
    .resultado-card.alert-danger { border-color: #f5c6cb; }
    .resultado-card.alert-info { border-color: #bee5eb; }
    .resultado-card .card-body { padding: 2rem; }
    .alert-heading { margin-bottom: 1rem; }
    .alert-heading i { margin-right: 0.5rem; }
  </style>
 </head>
 <body>
 <div class="wrapper">
    <nav id="sidebar">
        <div class="sidebar-header"><h3><i class="fas fa-leaf"></i> Nutriware</h3></div>
        <ul class="list-unstyled components">
            <li><a href="../login/menu/menu.php"><i class="fas fa-home"></i> Menu Principal</a></li>
            <li class="active"> {/* Marcar Questionários como ativo */}
                <a href="#questionariosSubmenu" data-toggle="collapse" aria-expanded="true" class="dropdown-toggle"><i class="fas fa-clipboard-list"></i> Questionários</a>
                <ul class="collapse list-unstyled show custom-dropdown-menu" id="questionariosSubmenu">
                    <li><a href="ebia.html"><i class="fas fa-balance-scale-right"></i> EBIA</a></li>
                    <li class="active"><a href="consumo_alimentar.html"><i class="fas fa-utensils"></i> Consumo Alimentar</a></li>
                </ul>
            </li>
            <li><a href="relatorios/relatorios.php"><i class="fas fa-chart-pie"></i> Relatórios</a></li>
            <div class="line"></div>
            <li>
                <a href="#gerenciarSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-cogs"></i> Gerenciar</a>
                <ul class="collapse list-unstyled custom-dropdown-menu" id="gerenciarSubmenu">
                    <li><a href="../login/menu/listar_contas.php"><i class="fas fa-users-cog"></i> Contas <span class="badge badge-admin ml-1">Admin</span></a></li>
                    <li><a href="../login/menu/editar_conta.php?id=<?php echo $userId; ?>"><i class="fas fa-user-edit"></i> Minha Conta</a></li>
                </ul>
            </li>
            <li><a href="../login/lagout/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
        </ul>
    </nav>

    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-white rounded navbar-top">
             <div class="container-fluid">
                 <span class="navbar-text ml-auto"> Olá, <strong><?php echo htmlspecialchars($nomeUsuario); ?></strong>! </span>
             </div>
         </nav>

         <h2 class="text-center mb-4"><i class="fas fa-poll-h text-success mr-2"></i>Resultado do Questionário de Consumo</h2>

         <div class="card resultado-card shadow-sm <?php echo $css_class_resultado; ?>">
             <div class="card-body text-center">
                 <?php if($css_class_resultado == 'alert-success'): ?>
                     <h4 class="alert-heading"><i class="fas fa-check-circle"></i> Sucesso!</h4>
                 <?php elseif($css_class_resultado == 'alert-danger'): ?>
                     <h4 class="alert-heading"><i class="fas fa-times-circle"></i> Erro!</h4>
                 <?php else: ?>
                      <h4 class="alert-heading"><i class="fas fa-info-circle"></i> Informação</h4>
                 <?php endif; ?>

                 <p class="lead"><?php echo htmlspecialchars($mensagem_resultado); ?></p>

                 <div class="mt-4">
                     <a href="consumo_alimentar.html" class="btn btn-info mr-2"><i class="fas fa-redo"></i> Responder Novamente</a>
                     <a href="../login/menu/menu.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar ao Menu</a>
                 </div>
             </div>
         </div>

    </div></div><script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
 <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js" integrity="sha384-+sLIOodYLS7CIrQpBjl+C7nPvqq+FbNUBDunl/OZv93DB7Ln/533i8e/mZXLi/P+" crossorigin="anonymous"></script>
 </body>
 </html>