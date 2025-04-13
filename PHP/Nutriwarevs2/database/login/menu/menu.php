<?php
 session_start();
 if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../login/login.html");
  exit;
 }
 

 echo "<h1>Bem-vindo ao Menu Inicial</h1>";
 echo "<p>Você está logado com o ID: " . $_SESSION['usuario_id'] . "</p>";
 echo "<a href='../../questionarios/consumo_alimentar.html'>Questionário Consumo Alimentar</a><br>";
 echo "<a href='questionarios/ebia.html'>Questionário EBIA</a><br>";
 echo "<a href='../logout.php'>Logout</a>";
 ?>