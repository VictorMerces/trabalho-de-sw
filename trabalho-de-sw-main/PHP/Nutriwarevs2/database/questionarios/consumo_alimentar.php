<?php
 session_start();
 if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../login.html");
  exit;
 }


 include '../config/conexao.php';


 // Sanitização e Validação
 $refeicoes_arr = filter_input(INPUT_POST, 'refeicoes', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? [];
 $refeicoes = implode(",", array_map('strip_tags', $refeicoes_arr));
 $usa_dispositivos = isset($_POST['usa_dispositivos']) ? 1 : 0;
 $feijao = isset($_POST['feijao']) ? 1 : 0;
 $frutas_frescas = isset($_POST['frutas_frescas']) ? 1 : 0;
 $verduras_legumes = isset($_POST['verduras_legumes']) ? 1 : 0;
 $hamburguer_embutidos = isset($_POST['hamburguer_embutidos']) ? 1 : 0;
 $bebidas_adocadas = isset($_POST['bebidas_adocadas']) ? 1 : 0;
 $macarrao_instantaneo = isset($_POST['macarrao_instantaneo']) ? 1 : 0;
 $biscoitos_recheados = isset($_POST['biscoitos_recheados']) ? 1 : 0;


 // Exemplo de validação: verifica se pelo menos uma refeição foi selecionada.
 if (empty($refeicoes)) {
  echo "Selecione ao menos uma opção de refeição.";
  exit;
 }


 try {
  // Inserir dados em consumo_alimentar
  $stmt_consumo = $conexao->prepare("INSERT INTO consumo_alimentar (participante_id, refeicoes, usa_dispositivos, feijao, frutas_frescas, verduras_legumes, hamburguer_embutidos, bebidas_adocadas, macarrao_instantaneo, biscoitos_recheados) VALUES (:participante_id, :refeicoes, :usa_dispositivos, :feijao, :frutas_frescas, :verduras_legumes, :hamburguer_embutidos, :bebidas_adocadas, :macarrao_instantaneo, :biscoitos_recheados)");
  $stmt_consumo->bindParam(':participante_id', $_SESSION['usuario_id']);
  $stmt_consumo->bindParam(':refeicoes', $refeicoes);
  $stmt_consumo->bindParam(':usa_dispositivos', $usa_dispositivos);
  $stmt_consumo->bindParam(':feijao', $feijao);
  $stmt_consumo->bindParam(':frutas_frescas', $frutas_frescas);
  $stmt_consumo->bindParam(':verduras_legumes', $verduras_legumes);
  $stmt_consumo->bindParam(':hamburguer_embutidos', $hamburguer_embutidos);
  $stmt_consumo->bindParam(':bebidas_adocadas', $bebidas_adocadas);
  $stmt_consumo->bindParam(':macarrao_instantaneo', $macarrao_instantaneo);
  $stmt_consumo->bindParam(':biscoitos_recheados', $biscoitos_recheados);
  $stmt_consumo->execute();


  echo "Questionário de Consumo Alimentar respondido com sucesso!";
 } catch (PDOException $e) {
  $conexao->rollBack();
  error_log($e->getMessage());
  echo "Erro ao enviar o questionário de Consumo Alimentar. Tente novamente.";
 }
 ?>