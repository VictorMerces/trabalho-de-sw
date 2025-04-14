<?php
 session_start();
 // Verifica se o PARTICIPANTE está logado (usando a variável correta)
 if (!isset($_SESSION['participante_id'])) { // <--- CORRIGIDO
     // Redireciona para a página de login se não estiver logado
     header("Location: ../login/login.php"); // Redirecionar para o login
     exit;
 }

 include '../config/conexao.php';

 $mensagem_resultado = '';
 $css_class_resultado = 'alert alert-danger'; // Default para erro

 if ($_SERVER["REQUEST_METHOD"] == "POST") {
     // Usa a variável de sessão correta
     $participante_id = $_SESSION['participante_id']; // <--- CORRIGIDO

     // Sanitização e Validação (seu código atual parece ok, mas revise se necessário)
     $refeicoes_arr = filter_input(INPUT_POST, 'refeicoes', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? [];
     $refeicoes = !empty($refeicoes_arr) ? implode(",", array_map('strip_tags', $refeicoes_arr)) : null; // Tratar array vazio
     $usa_dispositivos = isset($_POST['usa_dispositivos']) ? filter_var($_POST['usa_dispositivos'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
     $feijao = isset($_POST['feijao']) ? 1 : 0; // Checkboxes podem ser 0 ou 1
     $frutas_frescas = isset($_POST['frutas_frescas']) ? 1 : 0;
     $verduras_legumes = isset($_POST['verduras_legumes']) ? 1 : 0;
     $hamburguer_embutidos = isset($_POST['hamburguer_embutidos']) ? 1 : 0;
     $bebidas_adocadas = isset($_POST['bebidas_adocadas']) ? 1 : 0;
     $macarrao_instantaneo = isset($_POST['macarrao_instantaneo']) ? 1 : 0;
     $biscoitos_recheados = isset($_POST['biscoitos_recheados']) ? 1 : 0;

     // Validação mínima (exemplo: pelo menos uma refeição ou resposta sobre dispositivos)
     if ($refeicoes === null && $usa_dispositivos === null) {
         $mensagem_resultado = "Erro: Por favor, responda pelo menos uma pergunta sobre refeições ou uso de dispositivos.";
     } else {
         try {
             $stmt_consumo = $conexao->prepare("INSERT INTO consumo_alimentar (participante_id, refeicoes, usa_dispositivos, feijao, frutas_frescas, verduras_legumes, hamburguer_embutidos, bebidas_adocadas, macarrao_instantaneo, biscoitos_recheados) VALUES (:participante_id, :refeicoes, :usa_dispositivos, :feijao, :frutas_frescas, :verduras_legumes, :hamburguer_embutidos, :bebidas_adocadas, :macarrao_instantaneo, :biscoitos_recheados)");

             // Bind com a variável correta
             $stmt_consumo->bindParam(':participante_id', $participante_id, PDO::PARAM_INT); // <--- CORRIGIDO
             $stmt_consumo->bindParam(':refeicoes', $refeicoes, PDO::PARAM_STR);
             $stmt_consumo->bindParam(':usa_dispositivos', $usa_dispositivos, PDO::PARAM_BOOL); // Usar PARAM_BOOL se o tipo da coluna for BOOLEAN/TINYINT(1)
             $stmt_consumo->bindParam(':feijao', $feijao, PDO::PARAM_INT); // Ou PARAM_BOOL
             $stmt_consumo->bindParam(':frutas_frescas', $frutas_frescas, PDO::PARAM_INT);
             $stmt_consumo->bindParam(':verduras_legumes', $verduras_legumes, PDO::PARAM_INT);
             $stmt_consumo->bindParam(':hamburguer_embutidos', $hamburguer_embutidos, PDO::PARAM_INT);
             $stmt_consumo->bindParam(':bebidas_adocadas', $bebidas_adocadas, PDO::PARAM_INT);
             $stmt_consumo->bindParam(':macarrao_instantaneo', $macarrao_instantaneo, PDO::PARAM_INT);
             $stmt_consumo->bindParam(':biscoitos_recheados', $biscoitos_recheados, PDO::PARAM_INT);

             $stmt_consumo->execute();

             $mensagem_resultado = "Questionário de Consumo Alimentar respondido com sucesso!";
             $css_class_resultado = 'alert alert-success'; // Classe de sucesso

         } catch (PDOException $e) {
             // $conexao->rollBack(); // Rollback só é útil se você estiver usando transações explícitas
             error_log("Erro DB Consumo: " . $e->getMessage());
             $mensagem_resultado = "Erro ao enviar o questionário de Consumo Alimentar. Tente novamente.";
             // $css_class_resultado permanece 'alert alert-danger'
         }
     }
 } else {
     // Se acessado via GET ou sem POST
     $mensagem_resultado = "Por favor, preencha o questionário de consumo alimentar.";
     $css_class_resultado = 'alert alert-info';
 }

 // Incluir HTML para exibir a mensagem de forma consistente
 ?>
 <!DOCTYPE html>
 <html lang="pt-br">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resultado Questionário Consumo Alimentar</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <style>
    body { padding-top: 20px; }
    .resultado-box { padding: 15px; margin-top: 20px; border-radius: 5px; font-weight: bold; text-align: center; }
    /* Adicione classes para success, danger, info se necessário, como no ebia.php */
  </style>
 </head>
 <body class="container">
  <h1>Resultado do Questionário de Consumo Alimentar</h1>

  <div class="resultado-box <?php echo $css_class_resultado; // Aplica a classe CSS dinâmica ?>">
      <?php echo htmlspecialchars($mensagem_resultado); // Usar htmlspecialchars para segurança ?>
  </div>

  <div class="mt-4">
      <a href="consumo_alimentar.html" class="btn btn-info">Responder Novamente</a>
      <a href="../login/menu/menu.php" class="btn btn-secondary">Voltar ao Menu</a> </div>

  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
 </body>
 </html>