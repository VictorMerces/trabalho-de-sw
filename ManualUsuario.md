# Manual do Usuário

## Introdução
Bem-vindo ao sistema de gerenciamento de pesquisas! Este sistema permite:
- **Cadastrar participantes**
- **Preencher questionários alimentares e EBIA**
- **Gerar relatórios (PDF e CSV)**
- **Gerenciar usuários (cadastro, login e atualização de dados)**

## Cadastro de Participantes
Para cadastrar um novo participante:
1. Acesse a página de cadastro: abra o arquivo `cadastro.html` em seu navegador.
2. Preencha os campos essenciais, como Nome, E-mail, Senha, Idade e Gênero.
3. Caso deseje fornecer informações complementares (como raça/cor, escolaridade, estado civil etc.), clique no botão **"Inserir mais informações"** para exibir os campos adicionais.
4. Permita que o sistema acesse sua localização (caso você queira incluir os dados de geolocalização).
5. Após o preenchimento, clique em **"Cadastrar"**; uma mensagem confirmará o sucesso.

![Cadastro de Participante](./imagens/cadastro-participante.png)
*Exemplo de tela de cadastro*

## Preenchimento de Questionários
### Questionário EBIA
1. Após o cadastro e login, acesse o questionário EBIA pelo menu ou diretamente pela URL.
2. Responda cada pergunta do questionário, marcando **SIM** ou **NÃO**.  
3. Ao enviar o formulário, o sistema calculará automaticamente a pontuação e a classificação (ex: SEGURANÇA, INSEGURANÇA LEVE, etc.).
4. Uma modal exibirá o resultado, e você poderá clicar para gerar um relatório.

![Questionário EBIA](./imagens/questionario-ebia.png)
*Exemplo da interface do questionário EBIA*

### Questionário de Consumo Alimentar
1. Acesse a página de questionário de consumo alimentar (disponível pelo menu).
2. Responda as perguntas sobre hábitos alimentares, selecionando as opções (usando radio buttons e checkboxes).
3. Utilize os botões **"Salvar Rascunho"** para salvar seu progresso ou **"Enviar Questionário"** para finalizar as respostas.

![Questionário de Consumo Alimentar](./imagens/questionario-consumo.png)
*Exemplo da interface do questionário de consumo alimentar*

## Geração de Relatórios
O sistema permite gerar relatórios para os questionários respondidos:
- **Relatório PDF:**  
  Após responder o questionário EBIA, a partir da modal de resultado, clique no botão para visualizar/baixar o relatório em PDF. O sistema utiliza a biblioteca *PDFKit* para criar o documento.
- **Relatório CSV:**  
  Similarmente, existe um endpoint para obter a exportação dos dados em formato CSV, permitindo análises em planilhas.

Acesse os endpoints (para usuários autenticados):
- PDF: `GET /api/ebia/relatorio/pdf/{questionarioId}`
- CSV: `GET /api/ebia/relatorio/csv/{questionarioId}`

# Filtros Avançados em Relatórios

## Introdução aos Filtros
O sistema agora oferece a possibilidade de filtrar participantes para gerar relatórios mais precisos. Você pode segmentar os dados por diferentes critérios, como:

- Faixa etária (parâmetros: idadeMin e idadeMax)
- Gênero (parâmetro: genero)
- Escolaridade (parâmetro: escolaridade)

## Como Utilizar
Faça uma requisição GET para o endpoint abaixo, informando os parâmetros desejados:
```
GET /participantes/filtrar?idadeMin=20&idadeMax=40&genero=Feminino&escolaridade=Ensino%20médio%20completo
```

Exemplo:
- Para filtrar participantes com idade entre 20 e 40 anos, do gênero Feminino e com Ensino médio completo, use os query parameters conforme mostrado acima.

Os resultados serão retornados em formato JSON, contendo a lista dos participantes que atendem aos critérios definidos.

> **Dica:** Você pode utilizar ferramentas como Postman para testar esses filtros ou integrá-los em sua interface de relatórios.

## Gerenciamento de Usuários
### Cadastro e Login
- **Cadastro:** Utilize o formulário de cadastro para criar uma conta no sistema (consulte a seção de Cadastro de Participantes).
- **Login:** Na página `login.html`, informe seu e-mail e senha para acessar o sistema. Caso deseje, a opção de "Lembrar senha" está disponível.

![Tela de Login](./imagens/login.png)
*Exemplo da tela de login*

Após o login, você terá acesso ao menu principal, onde poderá:
- Preencher os questionários
- Visualizar e gerar relatórios
- Editar seus dados (função de gerenciamento de conta)

## Exemplos de Uso
- **Exemplo 1:** João Silva se cadastrou com sucesso, preencheu o questionário EBIA, obteve a classificação de **"Insegurança Alimentar Leve"** e baixou o relatório em PDF para análise.
- **Exemplo 2:** Maria Oliveira salvou seu questionário de consumo alimentar como rascunho e posteriormente completou as respostas antes de finalizar.

## Dicas e Recomendações
- Certifique-se de que o navegador permita o acesso à geolocalização durante o cadastro.
- Se houver dúvidas ou erros de validação (ex: e-mail inválido), o sistema exibirá mensagens de alerta para que você corrija os dados.

> **Nota:** Capturas de tela são exemplos ilustrativos. Em um ambiente de produção, atualize as imagens com as telas reais do sistema.

---

# Previsão de Risco de Insegurança Alimentar

## Introdução
O sistema agora conta com um módulo de machine learning que, com base em dados socioeconômicos (como idade e número de dependentes) e hábitos alimentares, prevê o risco de insegurança alimentar dos participantes.

## Como Utilizar
Faça uma requisição GET para o endpoint abaixo, informando o ID do participante:
```
GET /participantes/risco/{participanteId}
```

Exemplo:
- Se o ID do participante for 123, a chamada será:
```
GET /participantes/risco/123
```

A resposta retornará um JSON informando o risco predito (ex: "Baixo", "Médio" ou "Alto").

> **Nota:** Esta é uma implementação simples. Em ambiente de produção, recomenda-se utilizar um modelo treinado com dados históricos reais.

---

# Análise dos Dados

A partir dos questionários EBIA, o sistema permite extrair informações úteis através de análises simples, tais como:

- Cálculo da média de pontuação dos participantes.
- Distribuição das classificações (SEGURANCA, INSEGURANCA_LEVE, INSEGURANCA_MODERADA, INSEGURANCA_GRAVE).
- Aplicação de filtros avançados (por exemplo, faixa etária, gênero e escolaridade) para segmentar os dados.

Estas análises podem ser utilizadas para gerar gráficos e relatórios que apoiem a tomada de decisões. Em uma próxima etapa, você pode integrar bibliotecas de visualização (como Chart.js ou Plotly.js) para renderizar gráficos a partir destes dados.

---

Este manual serve como guia para que os usuários consigam aproveitar todas as funcionalidades do sistema. Para suporte adicional, consulte a documentação técnica ou entre em contato com a equipe de desenvolvimento.
