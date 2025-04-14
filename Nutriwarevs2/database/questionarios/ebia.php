<?php
 session_start();
 // Verifica se o PARTICIPANTE está logado
 if (!isset($_SESSION['participante_id'])) {
     header("Location: ../login/login.php");
     exit;
 }

 // Incluir arquivos necessários (ajuste caminhos se preciso)
 require_once '../config/conexao.php'; // Use require_once
 require_once '../config/error_handler.php'; // Incluir handler

 $mensagem_resultado = '';
 $css_class_resultado = ''; // Classe Bootstrap 5 (ex: alert-success, alert-danger)
 $icone_resultado = ''; // Ícone Bootstrap (ex: bi-check-circle-fill)
 $pontuacao_total = null;
 $classificacao_texto = '';

 // Verifica se o formulário foi enviado
 if ($_SERVER["REQUEST_METHOD"] == "POST") {
     $participante_id = $_SESSION['participante_id'];
     $respostas = [];
     $pontuacao_total = 0;
     $todas_respondidas = true;

     for ($i = 1; $i <= 8; $i++) {
         $chave = 'p' . $i;
         if (isset($_POST[$chave]) && ($_POST[$chave] === '0' || $_POST[$chave] === '1')) { // Valida se é 0 ou 1
             $valor = intval($_POST[$chave]);
             $respostas[$i] = $valor;
             $pontuacao_total += $valor;
         } else {
             $todas_respondidas = false;
             $mensagem_resultado = "Erro: Por favor, responda todas as perguntas com 'Sim' ou 'Não'.";
             $css_class_resultado = 'alert-danger';
             $icone_resultado = 'bi-exclamation-triangle-fill';
             break;
         }
     }

     if ($todas_respondidas) {
         $classificacao = '';
         $css_class_bootstrap = '';
         $icone_bootstrap = '';

         if ($pontuacao_total == 0) {
             $classificacao = 'seguranca_alimentar';
             $css_class_bootstrap = 'alert-success';
             $icone_bootstrap = 'bi-check-circle-fill';
             $classificacao_texto = 'Segurança Alimentar';
         } elseif ($pontuacao_total >= 1 && $pontuacao_total <= 3) {
             $classificacao = 'inseguranca_leve';
             $css_class_bootstrap = 'alert-warning';
             $icone_bootstrap = 'bi-exclamation-triangle-fill';
              $classificacao_texto = 'Insegurança Leve';
         } elseif ($pontuacao_total >= 4 && $pontuacao_total <= 5) {
             $classificacao = 'inseguranca_moderada';
             $css_class_bootstrap = 'alert-warning'; // BS5 não tem laranja por padrão, usar warning ou customizar
             $icone_bootstrap = 'bi-exclamation-triangle-fill';
              $classificacao_texto = 'Insegurança Moderada';
         } elseif ($pontuacao_total >= 6 && $pontuacao_total <= 8) {
             $classificacao = 'inseguranca_grave';
             $css_class_bootstrap = 'alert-danger';
             $icone_bootstrap = 'bi-exclamation-octagon-fill';
              $classificacao_texto = 'Insegurança Grave';
         }

         try {
              // Usar prepared statements para segurança
              $stmt = $conexao->prepare(
                 "INSERT INTO questionarios_ebia (participante_id, resposta1, resposta2, resposta3, resposta4, resposta5, resposta6, resposta7, resposta8, pontuacao_total, classificacao, data_preenchimento) VALUES (:participante_id, :r1, :r2, :r3, :r4, :r5, :r6, :r7, :r8, :pontuacao, :classificacao, NOW())"
             );
             $stmt->bindParam(':participante_id', $participante_id, PDO::PARAM_INT);
             for ($i = 1; $i <= 8; $i++) { $stmt->bindParam(':r'.$i, $respostas[$i], PDO::PARAM_INT); } // Usar PARAM_INT para 0/1
             $stmt->bindParam(':pontuacao', $pontuacao_total, PDO::PARAM_INT);
             $stmt->bindParam(':classificacao', $classificacao, PDO::PARAM_STR);
             $stmt->execute();

             $mensagem_resultado = "<h4><i class='bi " . $icone_bootstrap . " me-2'></i>Questionário EBIA enviado com sucesso!</h4>";
             $mensagem_resultado .= "<p class='mb-1'><strong>Pontuação Total:</strong> " . htmlspecialchars($pontuacao_total) . "</p>";
             $mensagem_resultado .= "<p><strong>Classificação:</strong> " . htmlspecialchars($classificacao_texto) . "</p>";
             $css_class_resultado = $css_class_bootstrap;
             $icone_resultado = $icone_bootstrap; // Usado no H4 acima

         } catch (PDOException $e) {
             error_log("Erro DB EBIA Insert: " . $e->getMessage());
             $mensagem_resultado = "Erro ao salvar o questionário EBIA no banco de dados. Tente novamente.";
             $css_class_resultado = 'alert-danger';
             $icone_resultado = 'bi-exclamation-triangle-fill';
         }
     }
 } else {
     // Se acessado via GET
     $mensagem_resultado = "Por favor, preencha o questionário a partir da página correta.";
     $css_class_resultado = 'alert-info';
     $icone_resultado = 'bi-info-circle-fill';
 }

 // Dados para a Sidebar (necessário mesmo em páginas de resultado)
 $nomeUsuario = $_SESSION['participante_nome'] ?? 'Participante';
 $userId = $_SESSION['participante_id'];
 $paginaAtiva = 'ebia'; // Define a página ativa para a sidebar
 ?>
 <!DOCTYPE html>
 <html lang="pt-br">
 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Questionário EBIA - Nutriware</title>
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
         .card-questionario { margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
         .card-questionario .card-body { padding: 1.5rem; }
         .form-group { margin-bottom: 1.5rem; }
         .form-check-label { margin-right: 15px; font-weight: normal; }
         label:not(.form-check-label) { font-weight: bold; color: #1E4620; } /* Label da pergunta */
         .required-label::after { content: " *"; color: red; }
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
             <li><a href="../login/menu/menu.php"><i class="fas fa-home"></i> Menu Principal</a></li>
             <li class="active"><a href="ebia.html"><i class="fas fa-clipboard-list"></i> Questionário EBIA</a></li>
             <li><a href="consumo_alimentar.html"><i class="fas fa-utensils"></i> Consumo Alimentar</a></li>
             <li><a href="relatorios/relatorios.html"><i class="fas fa-chart-pie"></i> Relatórios</a></li>
             <div style="height: 1px; background-color: rgba(255,255,255,0.1); margin: 10px 20px;"></div>
             <li><a href="../login/menu/listar_contas.php"><i class="fas fa-users-cog"></i> Gerenciar Contas <span class="badge badge-light ml-1">Admin</span></a></li>
             <li><a href="../login/menu/editar_conta.php?id=<?php echo $_SESSION['participante_id'] ?? ''; /* Precisa do ID aqui */ ?>"><i class="fas fa-user-edit"></i> Minha Conta</a></li>
             <li><a href="../login/lagout/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
         </ul>
     </nav>
 <div class="wrapper">


     <div id="content">
         <nav class="navbar navbar-expand-lg navbar-light navbar-top">
             <div class="container-fluid">
                 <button type="button" id="sidebarCollapse" class="btn btn-success d-md-none">
                     <i class="bi bi-list"></i>
                 </button>
                 <span class="navbar-text ms-auto">
                      Olá, <strong><?php echo htmlspecialchars($nomeUsuario); ?></strong>!
                  </span>
             </div>
         </nav>

         <div class="card shadow-sm border-success">
               <div class="card-header bg-success bg-gradient text-white">
                    <h4 class="mb-0"><i class="bi bi-clipboard-check-fill me-2"></i>Resultado do Questionário EBIA</h4>
               </div>
               <div class="card-body p-4">
                   <div class="alert <?php echo $css_class_resultado; ?> resultado-box" role="alert">
                       <?php echo $mensagem_resultado; // Mensagem já contém HTML seguro ?>
                   </div>

                   <div class="mt-4 text-center d-grid gap-2 d-sm-flex justify-content-sm-center">
                       <a href="ebia.html" class="btn btn-info px-4"><i class="bi bi-arrow-clockwise me-2"></i>Responder Novamente</a>
                       <a href="../login/menu/menu.php" class="btn btn-secondary px-4"><i class="bi bi-arrow-left-circle me-2"></i>Voltar ao Menu</a>
                  </div>
               </div>
          </div>

          <footer class="text-center text-muted mt-5 py-3">
              <p>&copy; <?php echo date("Y"); ?> Nutriware. Todos os direitos reservados.</p>
          </footer>

     </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
 <script>
     // Script para toggle da sidebar em telas pequenas (opcional, se usar o botão)
     document.getElementById('sidebarCollapse')?.addEventListener('click', function () {
         document.getElementById('sidebar')?.classList.toggle('active');
     });
 </script>
 </body>
 </html>