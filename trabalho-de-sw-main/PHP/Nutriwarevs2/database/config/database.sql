DROP DATABASE IF EXISTS nutriware_db;
 CREATE DATABASE nutriware_db;
 USE nutriware_db;
 

 -- Tabela de usuários (autenticação)
 CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(100) UNIQUE NOT NULL,
  senha VARCHAR(255) NOT NULL,
  nome VARCHAR(100) NOT NULL,
  tipo ENUM('administrador', 'operador', 'leitor') NOT NULL DEFAULT 'operador',
  data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ativo BOOLEAN DEFAULT TRUE,
  token_recuperacao VARCHAR(255) NULL,
  token_expiracao TIMESTAMP NULL
 );
 

 -- Tabela de participantes (pacientes)
 CREATE TABLE participantes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  idade INT NOT NULL,
  genero ENUM('masculino', 'feminino', 'transgenero', 'nao_binario', 'outro') NOT NULL,
  genero_outro VARCHAR(50) NULL,
  raca ENUM('branco', 'preto', 'pardo', 'indigena', 'outro') NOT NULL,
  escolaridade ENUM(
  'sem_escolaridade',
  'fundamental_incompleto',
  'fundamental_completo',
  'medio_incompleto',
  'medio_completo',
  'superior_incompleto',
  'superior_completo',
  'pos_graduacao',
  'prefere_nao_dizer'
  ) NOT NULL,
  estado_civil ENUM('solteiro', 'casado', 'divorciado', 'viuvo', 'separado', 'prefere_nao_dizer') NOT NULL,
  situacao_emprego ENUM(
  'empregado',
  'desempregado',
  'autonomo',
  'aposentado',
  'estudante',
  'prefere_nao_dizer',
  'outro'
  ) NOT NULL,
  situacao_emprego_outro VARCHAR(50) NULL,
  beneficios_sociais JSON NULL,
  numero_dependentes INT DEFAULT 0,
  religiao ENUM(
  'catolico',
  'evangelico',
  'espirita',
  'umbanda',
  'candomble',
  'ateu',
  'prefere_nao_dizer',
  'outro'
  ) NOT NULL,
  religiao_outro VARCHAR(50) NULL,
  data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  usuario_cadastro INT NOT NULL,
  FOREIGN KEY (usuario_cadastro) REFERENCES usuarios(id)
 );
 

 -- Tabela de alimentos
 CREATE TABLE alimentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  categoria ENUM('feijao', 'frutas', 'verduras', 'embutidos', 'bebidas', 'industrializados', 'doces') NOT NULL,
  UNIQUE KEY (nome)
 );
 

 -- Tabela de hábitos alimentares
 CREATE TABLE habitos_alimentares (
  id INT AUTO_INCREMENT PRIMARY KEY,
  participante_id INT NOT NULL,
  usa_dispositivos_refeicao BOOLEAN NOT NULL,
  refeicoes_dia JSON NOT NULL,
  data_preenchimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE
 );
 

 -- Tabela de consumo de alimentos
 CREATE TABLE consumo_alimentos (
  habito_id INT NOT NULL,
  alimento_id INT NOT NULL,
  consumido BOOLEAN NOT NULL,
  PRIMARY KEY (habito_id, alimento_id),
  FOREIGN KEY (habito_id) REFERENCES habitos_alimentares(id) ON DELETE CASCADE,
  FOREIGN KEY (alimento_id) REFERENCES alimentos(id)
 );
 

 -- Tabela de questionários EBIA
 CREATE TABLE questionarios_ebia (
  id INT AUTO_INCREMENT PRIMARY KEY,
  participante_id INT NOT NULL,
  resposta1 BOOLEAN NOT NULL,
  resposta2 BOOLEAN NOT NULL,
  resposta3 BOOLEAN NOT NULL,
  resposta4 BOOLEAN NOT NULL,
  resposta5 BOOLEAN NOT NULL,
  resposta6 BOOLEAN NOT NULL,
  resposta7 BOOLEAN NOT NULL,
  resposta8 BOOLEAN NOT NULL,
  pontuacao_total INT NOT NULL,
  classificacao ENUM('seguranca_alimentar', 'inseguranca_leve', 'inseguranca_moderada', 'inseguranca_grave') NOT NULL,
  data_preenchimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  anonimo BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE
 );
 

 -- Tabela de logs
 CREATE TABLE logs_ativade (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  acao VARCHAR(255) NOT NULL,
  tabela_afetada VARCHAR(50) NULL,
  registro_id INT NULL,
  data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ip VARCHAR(45) NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
 );
 

 -- Tabela de backups
 CREATE TABLE backups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_arquivo VARCHAR(255) NOT NULL,
  tamanho BIGINT NOT NULL,
  usuario_id INT NOT NULL,
  data_backup TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
 );
 

 -- Índices para otimização
 CREATE INDEX idx_participante_nome ON participantes(nome);
 CREATE INDEX idx_participante_idade ON participantes(idade);
 CREATE INDEX idx_participante_genero ON participantes(genero);
 CREATE INDEX idx_participante_religiao ON participantes(religiao);
 CREATE INDEX idx_habitos_dispositivos ON habitos_alimentares(usa_dispositivos_refeicao);
 CREATE INDEX idx_questionario_classificacao ON questionarios_ebia(classificacao);
 CREATE INDEX idx_questionario_data ON questionarios_ebia(data_preenchimento);
 CREATE INDEX idx_logs_data ON logs_ativade(data_hora);
 

 -- Inserção de alimentos padrão
 INSERT INTO alimentos (nome, categoria) VALUES
 ('Feijão', 'feijao'),
 ('Frutas frescas', 'frutas'),
 ('Verduras e legumes', 'verduras'),
 ('Hambúrguer', 'embutidos'),
 ('Embutidos (presunto, mortadela, etc.)', 'embutidos'),
 ('Refrigerante', 'bebidas'),
 ('Suco de caixinha', 'bebidas'),
 ('Bebidas adoçadas', 'bebidas'),
 ('Macarrão instantâneo', 'industrializados'),
 ('Salgadinhos de pacote', 'industrializados'),
 ('Biscoitos salgados', 'industrializados'),
 ('Biscoitos recheados', 'doces'),
 ('Doces e guloseimas', 'doces');
 

 -- Inserção de usuário admin padrão (senha: Admin@123)
 INSERT INTO usuarios (email, senha, nome, tipo) 
 VALUES ('admin@nutriware.com', '$2a$10$N9qo8uLOickgx3ZmrMZIu.7Y6/.d8w8/4X9b5h3Jm3z6z1JQ1q2W2', 'Administrador Padrão', 'administrador');
 

 -- Stored Procedure para busca avançada
 DELIMITER //
 CREATE PROCEDURE sp_busca_avancada(
  IN p_idade_min INT,
  IN p_idade_max INT,
  IN p_genero VARCHAR(20),
  IN p_religiao VARCHAR(20),
  IN p_alimento VARCHAR(100),
  IN p_classificacao_ebia VARCHAR(30)
 BEGIN
  SELECT DISTINCT
  p.id,
  p.nome,
  p.idade,
  p.genero,
  p.religiao,
  q.classificacao,
  GROUP_CONCAT(DISTINCT a.nome SEPARATOR ', ') AS alimentos_consumidos
  FROM participantes p
  LEFT JOIN questionarios_ebia q ON p.id = q.participante_id
  LEFT JOIN habitos_alimentares h ON p.id = h.participante_id
  LEFT JOIN consumo_alimentos ca ON h.id = ca.habito_id
  LEFT JOIN alimentos a ON ca.alimento_id = a.id AND ca.consumido = TRUE
  WHERE 
  (p_idade_min IS NULL OR p.idade >= p_idade_min) AND
  (p_idade_max IS NULL OR p.idade <= p_idade_max) AND
  (p_genero IS NULL OR p.genero = p_genero) AND
  (p_religiao IS NULL OR p.religiao = p_religiao) AND
  (p_alimento IS NULL OR a.nome = p_alimento) AND
  (p_classificacao_ebia IS NULL OR q.classificacao = p_classificacao_ebia)
  GROUP BY p.id, p.nome, p.idade, p.genero, p.religiao, q.classificacao
  ORDER BY p.nome;
 END //
 DELIMITER ;
 

 -- View para análise de consumo por perfil
 CREATE VIEW view_consumo_por_perfil AS
 SELECT 
  p.genero,
  p.idade,
  p.religiao,
  a.nome AS alimento,
  a.categoria,
  COUNT(CASE WHEN ca.consumido = TRUE THEN 1 END) AS total_consumo,
  COUNT(*) AS total_participantes,
  (COUNT(CASE WHEN ca.consumido = TRUE THEN 1 END) * 100.0 / COUNT(*)) AS percentual_consumo
 FROM participantes p
 JOIN habitos_alimentares h ON p.id = h.participante_id
 JOIN consumo_alimentos ca ON h.id = ca.habito_id
 JOIN alimentos a ON ca.alimento_id = a.id
 GROUP BY p.genero, p.idade, p.religiao, a.nome, a.categoria;
 

 -- Trigger para registrar criação de participantes
 DELIMITER //
 CREATE TRIGGER tr_after_insert_participante
 AFTER INSERT ON participantes
 FOR EACH ROW
 BEGIN
  INSERT INTO logs_ativade (usuario_id, acao, tabela_afetada, registro_id)
  VALUES (NEW.usuario_cadastro, 'CADASTRO_PARTICIPANTE', 'participantes', NEW.id);
 END //
 DELIMITER ;
 

 -- Trigger para registrar alterações em questionários
 DELIMITER //
 CREATE TRIGGER tr_after_update_questionario
 AFTER UPDATE ON questionarios_ebia
 FOR EACH ROW
 BEGIN
  INSERT INTO logs_ativade (usuario_id, acao, tabela_afetada, registro_id)
  VALUES ((SELECT usuario_cadastro FROM participantes WHERE id = NEW.participante_id), 
  'ATUALIZACAO_QUESTIONARIO', 'questionarios_ebia', NEW.id);
 END //
 DELIMITER ;
 

 -- Procedure para relatório por faixa etária
 DELIMITER //
 CREATE PROCEDURE sp_relatorio_faixa_etaria(IN min_idade INT, IN max_idade INT)
 BEGIN
  SELECT 
  p.*,
  q.classificacao,
  q.data_preenchimento
  FROM participantes p
  JOIN questionarios_ebia q ON p.id = q.participante_id
  WHERE p.idade BETWEEN min_idade AND max_idade
  ORDER BY p.idade, q.data_preenchimento DESC;
 END //
 DELIMITER ;