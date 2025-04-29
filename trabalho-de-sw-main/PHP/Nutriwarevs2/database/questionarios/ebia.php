<?php
 session_start();
 if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../login.html");
  exit;
 }


 include '../config/conexao.php';



 // Sanitização e Validação
 $p1 = isset($_POST['p1']) ? 1 : 0;
 $p2 = isset($_POST['p2']) ? 1 : 0;
 $p3 = isset($_POST['p3']) ? 1 : 0;
 $p4 = isset($_POST['p4']) ? 1 : 0;
 $p5 = isset($_POST['p5']) ? 1 : 0;
 $p6 = isset($_POST['p6']) ? 1 : 0;
 $p7 = isset($_POST['p7']) ? 1 : 0;
 $p8 = isset($_POST['p8']) ? 1 : 0;


 try {
  // Inserir dados em ebia
  $stmt_ebia = $conexao->prepare("INSERT INTO ebia (participante_id, p1, p2, p3, p4, p5, p6, p7, p8) VALUES (:participante_id, :p1, :p2, :p3, :p4, :p5, :p6, :p7, :p8)");
  $stmt_ebia->bindParam(':participante_id', $_SESSION['usuario_id']);
  $stmt_ebia->bindParam(':p1', $p1);
  $stmt_ebia->bindParam(':p2', $p2);
  $stmt_ebia->bindParam(':p3', $p3);
  $stmt_ebia->bindParam(':p4', $p4);
  $stmt_ebia->bindParam(':p5', $p5);
  $stmt_ebia->bindParam(':p6', $p6);
  $stmt_ebia->bindParam(':p7', $p7);
  $stmt_ebia->bindParam(':p8', $p8);
  $stmt_ebia->execute();


  echo "Questionário EBIA respondido com sucesso!";
 } catch (PDOException $e) {
  $conexao->rollBack();
  error_log($e->getMessage());
  echo "Erro ao enviar o questionário EBIA. Tente novamente.";
 }
 ?>