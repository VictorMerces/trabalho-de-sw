# Documentação do Esquema do Banco de Dados

Este documento descreve os modelos Mongoose utilizados no sistema, detalhando coleções, campos, tipos de dados, restrições e relacionamentos.

---

## 1. Modelo: Participante

**Coleção:** `participantes`

### Campos:

- **nome** (String):  
  - Obrigatório.  
  - Sanitizado (trim).  
  - Ex.: "João Silva".

- **idade** (Number):  
  - Obrigatório.  
  - Deve estar entre 0 e 120.

- **genero** (String):  
  - Obrigatório.  
  - Valores permitidos: "Masculino", "Feminino", "Transgênero", "Não binário", "Outro".  
  - Caso "Outro", o campo *generoPersonalizado* pode ser informado.

- **generoPersonalizado** (String):  
  - Opcional.

- **racaCor** (String):  
  - Obrigatório.  
  - Valores permitidos: "Branco", "Preto", "Pardo", "Povos originários", "Prefere não dizer".

- **escolaridade** (String):  
  - Obrigatório.  
  - Valores permitidos: "Ensino fundamental incompleto", "Ensino fundamental completo", "Ensino médio incompleto", "Ensino médio completo", "Graduação incompleta", "Graduação completa", "Prefere não dizer".

- **estadoCivil** (String):  
  - Obrigatório.  
  - Valores permitidos: "Casado", "Viúvo", "Divorciado", "Separado", "Solteiro", "Prefere não dizer".

- **situacaoEmprego** (String):  
  - Obrigatório.  
  - Valores permitidos: "Meio período", "Desempregado", "Incapaz de trabalhar", "Aposentado", "Prefere não dizer", "Outro".

- **empregoPersonalizado** (String):  
  - Opcional.  

- **beneficiosSociais** (Array de Strings):  
  - Valor padrão: `[]`.

- **outrosBeneficios** (String):  
  - Opcional.

- **dependentes** (Number):  
  - Obrigatório.  
  - Valor mínimo 0, máximo 20.

- **religiao** (String):  
  - Obrigatório.  
  - Valores permitidos: "Católico", "Evangélico", "Candomblé", "Umbanda", "Espírita", "Nenhum", "Prefere não dizer", "Outro".

- **religiaoPersonalizada** (String):  
  - Opcional.

- **email** (String):  
  - Obrigatório, único, convertido para minúsculas, sanitizado.

- **senha** (String):  
  - Obrigatório.

- **localizacao** (Objeto):  
  - Contém:
    - **latitude** (Number)
    - **longitude** (Number)

- **dataCriacao** (Date):  
  - Valor padrão: data atual.

- **dataAtualizacao** (Date):  
  - Valor padrão: data atual.  
  - Atualizado no `pre('save')`.

- **ativo** (Boolean):  
  - Valor padrão: `true`.  
  - Índice criado para acelerar queries.

### Regras Importantes:
- Antes de salvar, o campo `dataAtualizacao` é atualizado com a data atual.
- Existe o método `excluirLogicamente` que define o campo `ativo` como `false` (exclusão lógica).

---

## 2. Modelo: ConsumoAlimentar

**Coleção:** `consumoalimentares`

### Campos:

- **participanteId** (ObjectId):  
  - Obrigatório.  
  - Referência para a coleção `participantes`.  
  - Índice para busca.

- **dispositivoRefeicao** (String):  
  - Obrigatório.  
  - Valores permitidos: "SIM", "NÃO", "NÃO SEI".

- **refeicoesDia** (Array de Strings):  
  - Obrigatório.  
  - Valores permitidos: "Café da manhã", "Lanche da manhã", "Almoço", "Lanche da tarde", "Jantar", "Ceia/lanche da noite".

- **consumoAlimentos** (Array de Subdocumentos):  
  Cada item tem:
  - **alimento** (String):  
    - Obrigatório.  
    - Enum com valores:
      - "Feijão", 
      - "Frutas frescas", 
      - "Verduras ou legumes", 
      - "Hambúrguer e/ou embutidos", 
      - "Bebidas adoçadas", 
      - "Macarrão instantâneo, salgadinhos de pacote ou biscoitos salgados", 
      - "Biscoitos recheados, doces ou guloseimas".
  - **consumo** (String):  
    - Obrigatório.  
    - Valores permitidos: "SIM", "NÃO", "NÃO SEI".

- **comentarios** (String):  
  - Campo opcional, com trim.

- **dataPreenchimento** (Date):  
  - Valor padrão: data atual.  
  - Índice para ordenação e filtragem.

- **rascunho** (Boolean):  
  - Valor padrão: `true`.  
  - Índice para buscas de rascunho.

---

## 3. Modelo: QuestionarioEBIA

**Coleção:** `questionarioebias`

### Campos:

- **participanteId** (ObjectId):  
  - Opcional.  
  - Referência para a coleção `participantes`.  
  - Índice criado caso seja necessária consulta por participante.
  
- **respostas** (Array de Subdocumentos):  
  Cada item possui:
  - **perguntaId** (Number):  
    - Obrigatório.  
    - Enum: [1, 2, 3, 4, 5, 6, 7, 8] (correspondendo às perguntas definidas no arquivo de dados).
  - **resposta** (String):  
    - Obrigatório.  
    - Valores permitidos: "SIM" ou "NÃO".

- **pontuacao** (Number):  
  - Obrigatório.  
  - Calculado automaticamente com base no número de respostas "SIM".  
  - Valor mínimo: 0; valor máximo: 8.

- **classificacao** (String):  
  - Obrigatório.  
  - Possíveis valores: "SEGURANCA", "INSEGURANCA_LEVE", "INSEGURANCA_MODERADA", "INSEGURANCA_GRAVE".  
  - Definido automaticamente no hook `pre('save')`.

- **dataPreenchimento** (Date):  
  - Valor padrão: data atual.  
  - Índice para buscas por período.

- **anonimo** (Boolean):  
  - Valor padrão: `false`.

### Regras Importantes:
- No `pre('save')` do esquema, a pontuação é calculada como o número de respostas "SIM" e a classificação é definida com base nesta pontuação:
  - Se pontuação for 0: "SEGURANCA".
  - Se pontuação for até 3: "INSEGURANCA_LEVE".
  - Se pontuação for até 5: "INSEGURANCA_MODERADA".
  - Caso contrário: "INSEGURANCA_GRAVE".

---

Este documento foi criado para ajudar os desenvolvedores e administradores do sistema a compreender e manter o banco de dados, garantindo que todas as restrições e relacionamentos sejam respeitados.

---

*FIM DO DOCUMENTO*
