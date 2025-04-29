<?php
 include '../config/conexao.php';
 

 if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Sanitização dos dados de entrada
  $nome = htmlspecialchars(trim($_POST['nome']));
  $idade = filter_var($_POST['idade'], FILTER_VALIDATE_INT);
  $genero = htmlspecialchars(trim($_POST['genero']));
  $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
  $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
 

  // Processamento dos campos de resposta única
  $raca_cor = isset($_POST['raca_cor']) ? $_POST['raca_cor'] : null;
  if ($raca_cor === "Outro" && !empty($_POST['raca_outro'])) {
  $raca_cor .= " - " . $_POST['raca_outro'];
  }
  $escolaridade = isset($_POST['escolaridade']) ? $_POST['escolaridade'] : null;
  if ($escolaridade === "Outro" && !empty($_POST['escolaridade_outro'])) {
  $escolaridade .= " - " . $_POST['escolaridade_outro'];
  }
  $estado_civil = $_POST['estado_civil'] ?? null;
  $emprego = isset($_POST['emprego']) ? $_POST['emprego'] : null;
  if ($emprego === "Outro" && !empty($_POST['emprego_outro'])) {
  $emprego .= " - " . $_POST['emprego_outro'];
  }
  
  // Re-adicionado processamento de auxílios
  $auxilios = $_POST['auxilios'] ?? "";
  if ($auxilios === "Outros" && !empty($_POST['auxilios_outros'])) {
  $auxilios .= " - " . $_POST['auxilios_outros'];
  }
  
  $dependentes = $_POST['dependentes'] ?? null;
  if ($dependentes == "Outro" && !empty($_POST['dependentes_outro'])) {
  $dependentes .= " - " . $_POST['dependentes_outro'];
  }
  $religiao = isset($_POST['religiao']) ? $_POST['religiao'] : null;
  if ($religiao === "Outro" && !empty($_POST['religiao_outro'])) {
  $religiao .= " - " . $_POST['religiao_outro'];
  }
 

  try {
  $stmt = $conexao->prepare("INSERT INTO participantes (nome, idade, genero, raca_cor, escolaridade, estado_civil, situacao_emprego, beneficios_sociais, numero_dependentes, religiao, email, senha) VALUES (:nome, :idade, :genero, :raca_cor, :escolaridade, :estado_civil, :situacao_emprego, :beneficios_sociais, :numero_dependentes, :religiao, :email, :senha)");
  
  $stmt->bindParam(':nome', $nome);
  $stmt->bindParam(':idade', $idade);
  $stmt->bindParam(':genero', $genero);
  $stmt->bindParam(':raca_cor', $raca_cor);
  $stmt->bindParam(':escolaridade', $escolaridade);
  $stmt->bindParam(':estado_civil', $estado_civil);
  $stmt->bindParam(':situacao_emprego', $emprego);
  $stmt->bindParam(':beneficios_sociais', $auxilios);
  $stmt->bindParam(':numero_dependentes', $dependentes);
  $stmt->bindParam(':religiao', $religiao);
  $stmt->bindParam(':email', $email);
  $stmt->bindParam(':senha', $senha);
 

  $stmt->execute();
 

  // Feedback e redirecionamento
  header("Location: ../login/login.html");
  exit;
  
  } catch (PDOException $e) {
  error_log($e->getMessage());
  echo "Erro ao cadastrar. Tente novamente mais tarde.";
  }
 }
 ?>