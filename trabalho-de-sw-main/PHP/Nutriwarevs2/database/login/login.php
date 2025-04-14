<?php
 include '../config/conexao.php';
 session_start();


 if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = $_POST['email'];
  $senha = $_POST['senha'];
 

  try {
  $stmt = $conexao->prepare("SELECT id, senha FROM participantes WHERE email = :email");
  $stmt->bindParam(':email', $email);
  $stmt->execute();
  $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
 

  // Se nenhum usuário for encontrado, redirecionar para o cadastro
  if (!$usuario) {
  header("Location: ../cadastro/cadastro.html");
  exit;
  }
 

  if (password_verify($senha, $usuario['senha'])) {
  $_SESSION['usuario_id'] = $usuario['id'];
  header("Location: menu/menu.html");
  exit;
  } else {
  echo "E-mail ou senha incorretos.";
  }
  } catch (PDOException $e) {
  error_log($e->getMessage());
  echo "Erro ao fazer login.";
  }
 }
 ?>