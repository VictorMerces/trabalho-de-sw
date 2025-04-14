<?php
 session_start();
 // Verifica se o PARTICIPANTE está logado (usando a variável CORRETA)
 if (!isset($_SESSION['participante_id'])) { // <--- CORRIGIDO AQUI
     // Redireciona para a página de login se não estiver logado
     header("Location: ../login/login.php"); // <--- CORRIGIDO (destino mais lógico)
     exit;
 }

 include '../config/conexao.php'; // Inclui a conexão com o banco

 $mensagem_resultado = ''; // Variável para armazenar a mensagem de feedback
 $css_class_resultado = ''; // Variável para a classe CSS do resultado

 // Verifica se o formulário foi enviado (método POST)
 if ($_SERVER["REQUEST_METHOD"] == "POST") {

     // Usa a variável de sessão CORRETA para obter o ID
     $participante_id = $_SESSION['participante_id']; // <--- CORRIGIDO AQUI
     $respostas = [];
     $pontuacao_total = 0;
     $todas_respondidas = true;

     // Coleta as respostas e calcula a pontuação
     for ($i = 1; $i <= 8; $i++) {
         $chave = 'p' . $i;
         if (isset($_POST[$chave])) {
             $valor = intval($_POST[$chave]); // Converte para inteiro (0 ou 1)
             $respostas[$i] = $valor; // Armazena a resposta (0 ou 1)
             $pontuacao_total += $valor; // Soma à pontuação total (só soma se for 1)
         } else {
             // Se alguma pergunta não foi enviada
             $todas_respondidas = false;
             $mensagem_resultado = "Erro: Por favor, responda todas as perguntas.";
             $css_class_resultado = 'alert alert-danger'; // Classe de erro do Bootstrap
             break; // Interrompe o loop
         }
     }

     // Procede somente se todas as perguntas foram respondidas
     if ($todas_respondidas) {
         // Determina a classificação com base na pontuação
         $classificacao = '';
         $css_class_resultado = ''; // Classe CSS para o box de resultado

         if ($pontuacao_total == 0) {
             $classificacao = 'seguranca_alimentar';
             $css_class_resultado = 'seguranca-alimentar';
         } elseif ($pontuacao_total >= 1 && $pontuacao_total <= 3) {
             $classificacao = 'inseguranca_leve';
             $css_class_resultado = 'inseguranca-leve';
         } elseif ($pontuacao_total >= 4 && $pontuacao_total <= 5) {
             $classificacao = 'inseguranca_moderada';
             $css_class_resultado = 'inseguranca-moderada';
         } elseif ($pontuacao_total >= 6 && $pontuacao_total <= 8) {
             $classificacao = 'inseguranca_grave';
             $css_class_resultado = 'inseguranca-grave';
         }

         // Mapeia a classificação textual para exibição
         $classificacao_texto = str_replace('_', ' ', ucfirst($classificacao));

         try {
              // Prepara a inserção no banco de dados (Query parece correta)
              $stmt = $conexao->prepare(
                 "INSERT INTO questionarios_ebia
                  (participante_id, resposta1, resposta2, resposta3, resposta4, resposta5, resposta6, resposta7, resposta8, pontuacao_total, classificacao, data_preenchimento)
                  VALUES
                  (:participante_id, :r1, :r2, :r3, :r4, :r5, :r6, :r7, :r8, :pontuacao, :classificacao, NOW())"
             );

             // Vincula os parâmetros (agora $participante_id terá o valor correto)
             $stmt->bindParam(':participante_id', $participante_id, PDO::PARAM_INT);
             for ($i = 1; $i <= 8; $i++) {
                 $stmt->bindParam(':r'.$i, $respostas[$i], PDO::PARAM_INT);
             }
             $stmt->bindParam(':pontuacao', $pontuacao_total, PDO::PARAM_INT);
             $stmt->bindParam(':classificacao', $classificacao, PDO::PARAM_STR);

             // Executa a query
             $stmt->execute();

             // Define a mensagem de sucesso
             $mensagem_resultado = "Questionário EBIA enviado com sucesso!<br>";
             $mensagem_resultado .= "<strong>Pontuação Total: " . htmlspecialchars($pontuacao_total) . "</strong><br>"; // Adicionado htmlspecialchars
             $mensagem_resultado .= "<strong>Classificação: " . htmlspecialchars($classificacao_texto) . "</strong>"; // Adicionado htmlspecialchars

         } catch (PDOException $e) {
             // Em caso de erro no banco
             error_log("Erro DB EBIA Insert: " . $e->getMessage()); // Loga o erro real
             $mensagem_resultado = "Erro ao salvar o questionário EBIA no banco de dados. Tente novamente.";
             $css_class_resultado = 'alert alert-danger'; // Classe de erro
         }

     } // fim if ($todas_respondidas)

 } else {
     // Se a página for acessada diretamente sem POST
     $mensagem_resultado = "Por favor, preencha o questionário a partir da página correta.";
     $css_class_resultado = 'alert alert-info';
 }

 // O restante do HTML para exibir o resultado permanece o mesmo
 ?>
 <!DOCTYPE html>
 <html lang="pt-br">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resultado Questionário EBIA</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <style>
    body { padding-top: 20px; }
    .seguranca-alimentar { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .inseguranca-leve { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    .inseguranca-moderada { background-color: #FDC7A9; color: #815314; border: 1px solid #FFDAB9; }
    .inseguranca-grave { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .resultado-box { padding: 15px; margin-top: 20px; border-radius: 5px; font-weight: bold; text-align: center; }
    .alert { /* Estilos padrão do Bootstrap */ }
  </style>
 </head>
 <body class="container">
  <h1>Resultado do Questionário EBIA</h1>

  <div class="resultado-box <?php echo $css_class_resultado; ?>">
      <?php echo $mensagem_resultado; // Mensagem já contém HTML seguro ?>
  </div>

  <div class="mt-4">
      <a href="ebia.html" class="btn btn-info">Responder Novamente</a>
      <a href="../login/menu/menu.php" class="btn btn-secondary">Voltar ao Menu</a> </div>

  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

 </body>
 </html>