CREATE DATABASE nutriware CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; -- Definir charset recomendado
USE nutriware;

-- Tabela de participantes (único tipo de usuário)
CREATE TABLE participantes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  senha VARCHAR(255) NOT NULL, -- Senha para login do participante
  idade INT NULL, -- Permitir nulo ou validar entrada
  genero ENUM('masculino', 'feminino', 'transgenero', 'nao_binario', 'outro', 'prefere_nao_dizer') NULL,
  genero_outro VARCHAR(100) NULL,
  raca ENUM('branco', 'preto', 'pardo', 'povos_originarios', 'outro', 'prefere_nao_dizer') NULL,
  raca_outro VARCHAR(100) NULL,
  escolaridade ENUM(
      'ensino_fundamental_incompleto',
      'ensino_fundamental_completo',
      'ensino_medio_incompleto',
      'ensino_medio_completo',
      'graduacao_incompleta',
      'graduacao_completa',
      'outro',
      'prefere_nao_dizer'
  ) NULL,
  escolaridade_outro VARCHAR(100) NULL,
  estado_civil ENUM('solteiro', 'casado', 'divorciado', 'viuvo', 'separado', 'uniao_estavel', 'prefere_nao_dizer') NULL,
  situacao_emprego ENUM(
      'meio_periodo',
      'desempregado',
      'incapaz_trabalhar',
      'aposentado',
      'estudante',
      'tempo_integral',
      'autonomo',
      'outro',
      'prefere_nao_dizer'
  ) NULL,
  situacao_emprego_outro VARCHAR(100) NULL,
  beneficios_sociais JSON NULL,
  numero_dependentes VARCHAR(100) NULL, -- Mantido como VARCHAR para flexibilidade com 'Outro'
  religiao ENUM(
      'catolico',
      'evangelico',
      'espirita',
      'umbanda',
      'candomble',
      'nenhum',
      'ateu',
      'outro',
      'prefere_nao_dizer'
  ) NULL,
  religiao_outro VARCHAR(100) NULL,
  data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  -- Coluna usuario_cadastro_id REMOVIDA
);

-- Tabela de questionários EBIA (sem alterações)
CREATE TABLE questionarios_ebia (
  id INT AUTO_INCREMENT PRIMARY KEY,
  participante_id INT NOT NULL,
  resposta1 TINYINT(1) NOT NULL,
  resposta2 TINYINT(1) NOT NULL,
  resposta3 TINYINT(1) NOT NULL,
  resposta4 TINYINT(1) NOT NULL,
  resposta5 TINYINT(1) NOT NULL,
  resposta6 TINYINT(1) NOT NULL,
  resposta7 TINYINT(1) NOT NULL,
  resposta8 TINYINT(1) NOT NULL,
  pontuacao_total INT NOT NULL,
  classificacao ENUM('seguranca_alimentar', 'inseguranca_leve', 'inseguranca_moderada', 'inseguranca_grave') NOT NULL,
  data_preenchimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE
);

-- Tabela de consumo alimentar (Adaptada para não usar tabelas separadas de habitos/alimentos/consumo)
-- Se precisar de detalhes de consumo, essa tabela pode ser reestruturada depois.
-- Por simplicidade, vamos assumir que as perguntas de consumo estão diretamente ligadas ao participante por enquanto.
-- A tabela original 'consumo_alimentar' parecia ser um script PHP, não uma tabela.
-- Se o seu consumo_alimentar.php insere dados, precisamos de uma tabela para ele.
-- Vamos criar uma tabela baseada nos campos de consumo_alimentar.php:
CREATE TABLE consumo_alimentar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participante_id INT NOT NULL,
    refeicoes TEXT NULL, -- Armazena a string das refeições (ex: "Café da manhã,Almoço")
    usa_dispositivos BOOLEAN NULL,
    feijao BOOLEAN NULL,
    frutas_frescas BOOLEAN NULL,
    verduras_legumes BOOLEAN NULL,
    hamburguer_embutidos BOOLEAN NULL,
    bebidas_adocadas BOOLEAN NULL,
    macarrao_instantaneo BOOLEAN NULL,
    biscoitos_recheados BOOLEAN NULL,
    data_preenchimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE
);


-- Índices para otimização (sem alterações, exceto remover referência a usuario_cadastro_id se houvesse)
CREATE INDEX idx_participante_nome ON participantes(nome);
CREATE INDEX idx_participante_idade ON participantes(idade);
CREATE INDEX idx_participante_genero ON participantes(genero);
CREATE INDEX idx_participante_raca ON participantes(raca);
CREATE INDEX idx_participante_escolaridade ON participantes(escolaridade);
CREATE INDEX idx_participante_religiao ON participantes(religiao);
CREATE INDEX idx_questionario_classificacao ON questionarios_ebia(classificacao);
CREATE INDEX idx_questionario_data ON questionarios_ebia(data_preenchimento);
CREATE INDEX idx_consumo_participante ON consumo_alimentar(participante_id); -- Índice para a nova tabela
