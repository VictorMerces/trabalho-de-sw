const request = require('supertest');
const app = require('../server');

describe('E2E Tests', () => {
  beforeEach(() => {
    // Visita a página de login para iniciar os testes
    cy.visit('/login.html');
  });

  it('deve exibir a página de login e realizar o login com sucesso', () => {
    // Verifica que o título da página contém "Login"
    cy.get('h1').should('contain', 'Login');
    // Preenche o formulário de login
    cy.get('#email').type('teste_int@test.com');
    cy.get('#senha').type('senha123');
    cy.get('#formLogin').submit();
    // Verifica que houve redirecionamento para o menu
    cy.url().should('include', '/menu.html');
  });

  it('deve navegar para a página de cadastro, preencher e submeter o formulário', () => {
    cy.visit('/cadastro.html');
    // Verifica que o título contém "Cadastro"
    cy.get('h1').should('contain', 'Cadastro');
    // Preenche os campos essenciais
    cy.get('#nome').type('Teste E2E');
    cy.get('#email').type('teste_e2e@test.com');
    cy.get('#senha').type('senha123');
    cy.get('#idade').type('30');
    cy.get('#genero').select('Masculino');
    // Submete o formulário e verifica mensagem de sucesso
    cy.get('form').submit();
    cy.get('#mensagem').should('contain', 'Participante cadastrado');
    // Opcional: verifique redirecionamento para login
    cy.url().should('include', '/login.html');
  });

  it('deve preencher o questionário EBIA e exibir a modal de resultado', () => {
    // Para simular o login, defina valores no localStorage
    cy.visit('/login.html');
    cy.window().then(win => {
      win.localStorage.setItem('token', 'dummy-token');
      win.localStorage.setItem('participanteId', 'dummy-id');
    });
    // Visita a página do questionário EBIA
    cy.visit('/src/models/src/controllers/src/middlewares/src/public/src/models/src/controllers/src/routes/public/questionario-ebia.html');
    // Preenche todas as 8 perguntas (supondo que os inputs se chamem "perguntaX")
    for (let i = 1; i <= 8; i++) {
      cy.get(`input[name="pergunta${i}"]`).first().check();
    }
    // Submete o formulário
    cy.get('form').submit();
    // Verifica que o resultado (ou modal) foi exibido
    cy.get('#resultado').should('be.visible');
    cy.get('#resultado').contains('Classificação');
  });

  it('should load the menu page', (done) => {
    request(app)
      .get('/menu.html')
      .expect(200, done);
  });
});
