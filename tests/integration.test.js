const request = require('supertest');
const mongoose = require('mongoose');
const app = require('../server'); // importando o app exportado em server.js
const Participante = require('../src/models/Participante');

describe('API Integration Tests', () => {
  // Conectar ao banco de dados de teste antes de executar os testes
  beforeAll(async () => {
    // Caso use um DB de teste, ajuste a URI (ex.: mongodb://localhost/trabalho_de_sw_test)
    await mongoose.connect(process.env.MONGO_URI_TEST || 'mongodb://localhost/trabalho_de_sw_test', {
      useNewUrlParser: true,
      useUnifiedTopology: true,
    });
  });

  // Limpar coleções após cada teste
  afterEach(async () => {
    await Participante.deleteMany();
    // ...existing code for other modelos se necessário...
  });

  afterAll(async () => {
    await mongoose.connection.close();
  });

  describe('Participantes Endpoints', () => {
    it('POST /participantes - deve cadastrar um novo participante', async () => {
      const participanteData = {
        nome: 'Teste Integrado',
        email: 'teste_int@test.com',
        senha: 'senha123',
        idade: 30,
        genero: 'Masculino',
        racaCor: 'Branco',
        escolaridade: 'Ensino médio completo',
        estadoCivil: 'Solteiro',
        situacaoEmprego: 'Desempregado',
        dependentes: 0,
        religiao: 'Nenhum'
      };

      const res = await request(app)
        .post('/participantes')
        .send(participanteData)
        .expect(201);
      
      expect(res.body).toHaveProperty('success', true);
      expect(res.body.data).toMatchObject({ nome: 'Teste Integrado', email: 'teste_int@test.com' });
    });

    it('GET /participantes - deve listar participantes ativos', async () => {
      // Criar um participante para teste
      await new Participante({
        nome: 'Listagem Teste',
        email: 'list@test.com',
        senha: '123',
        idade: 25,
        genero: 'Feminino',
        racaCor: 'Pardo',
        escolaridade: 'Ensino superior completo',
        estadoCivil: 'Solteiro',
        situacaoEmprego: 'Desempregado',
        dependentes: 0,
        religiao: 'Nenhum'
      }).save();

      const res = await request(app)
        .get('/participantes')
        .expect(200);
      expect(res.body.count).toBe(1);
      expect(res.body.data[0]).toHaveProperty('nome', 'Listagem Teste');
    });
  });

  describe('Consumo Alimentar Endpoints', () => {
    it('POST /api/consumo-alimentar/:participanteId - deve criar um questionário de consumo alimentar', async () => {
      // Cria primeiro um participante
      const participante = await new Participante({
        nome: 'Consumo Teste',
        email: 'consumo@test.com',
        senha: '123',
        idade: 35,
        genero: 'Masculino',
        racaCor: 'Branco',
        escolaridade: 'Ensino fundamental completo',
        estadoCivil: 'Casado',
        situacaoEmprego: 'Aposentado',
        dependentes: 1,
        religiao: 'Católico'
      }).save();

      const questionarioData = {
        dispositivoRefeicao: 'SIM',
        refeicoesDia: ['Almoço'],
        consumoAlimentos: [
          { alimento: 'Feijão', consumo: 'SIM' }
        ],
        comentarios: 'Teste de consumo',
        rascunho: true
      };

      const res = await request(app)
        .post(`/api/consumo-alimentar/${participante._id}`)
        .send(questionarioData)
        .expect(201);
      
      expect(res.body).toHaveProperty('_id');
      expect(res.body).toMatchObject(questionarioData);
    });
  });

  describe('EBIA Endpoints', () => {
    it('GET /api/ebia/perguntas - deve retornar a lista de perguntas EBIA', async () => {
      const res = await request(app)
        .get('/api/ebia/perguntas')
        .expect(200);
      // Assumindo que perguntasEBIA é um array com 8 perguntas, por exemplo
      expect(Array.isArray(res.body)).toBe(true);
    });

    it('POST /api/ebia/questionarios - deve criar um questionário EBIA se as respostas tiverem o número correto', async () => {
      // O teste assume que perguntasEBIA possui 8 itens
      const questionarioData = {
        participanteId: '123', // Para teste, use um valor dummy ou null se anonimo
        respostas: ['SIM', 'NÃO', 'SIM', 'SIM', 'NÃO', 'SIM', 'NÃO', 'SIM'],
        anonimo: false
      };

      const res = await request(app)
        .post('/api/ebia/questionarios')
        .set('Authorization', 'Bearer dummy-token') // Caso a rota exija autenticação, simule o token
        .send(questionarioData)
        .expect(201);
      
      expect(res.body).toHaveProperty('success', true);
      expect(res.body.data).toHaveProperty('pontuacao');
      expect(res.body.data).toHaveProperty('classificacao');
    });
  });

  describe('Integration Tests', () => {
    it('should return API docs', (done) => {
      request(app)
        .get('/api-docs')
        .expect(200, done);
    });
  });
});
