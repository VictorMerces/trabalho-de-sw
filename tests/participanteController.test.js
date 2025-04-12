const participanteController = require('../src/models/src/controllers/participanteController');
const Participante = require('../src/models/Participante');
const request = require('supertest');
const app = require('../server');

jest.mock('../src/models/Participante');

describe('Participante Controller', () => {
  let req, res;
  beforeEach(() => {
    req = { body: {}, params: {} };
    res = {
      status: jest.fn().mockReturnThis(),
      json: jest.fn()
    };
  });

  describe('cadastrar', () => {
    it('deve criar um novo participante e retornar sucesso', async () => {
      req.body = { nome: 'Teste', email: 'teste@test.com', senha: '123', idade: 25 };
      const participanteData = { _id: '1', ...req.body };
      Participante.mockImplementation(() => {
        return { save: jest.fn().mockResolvedValue(participanteData) };
      });
      await participanteController.cadastrar(req, res);
      expect(res.status).toHaveBeenCalledWith(201);
      expect(res.json).toHaveBeenCalledWith(expect.objectContaining({
        success: true,
        data: participanteData,
        message: expect.any(String)
      }));
    });

    it('deve retornar erro ao falhar ao criar participante', async () => {
      req.body = { nome: 'Teste', email: 'teste@test.com', senha: '123', idade: 25 };
      Participante.mockImplementation(() => {
        return { save: jest.fn().mockRejectedValue(new Error('Erro de criação')) };
      });
      await participanteController.cadastrar(req, res);
      expect(res.status).toHaveBeenCalledWith(400);
      expect(res.json).toHaveBeenCalledWith({
        success: false,
        error: 'Erro de criação'
      });
    });
  });

  describe('listar', () => {
    it('deve retornar participantes ativos', async () => {
      const participantes = [{ _id: '1', nome: 'A' }];
      Participante.find.mockResolvedValue(participantes);
      await participanteController.listar(req, res);
      expect(res.json).toHaveBeenCalledWith({
        success: true,
        count: participantes.length,
        data: participantes
      });
    });

    it('deve tratar erro ao listar participantes', async () => {
      Participante.find.mockRejectedValue(new Error('Erro no banco'));
      await participanteController.listar(req, res);
      expect(res.status).toHaveBeenCalledWith(500);
      expect(res.json).toHaveBeenCalledWith({
        success: false,
        error: 'Erro ao listar participantes'
      });
    });
  });

  it('should register a new participant', async () => {
    const res = await request(app)
      .post('/participantes')
      .send({
         nome: 'Test User',
         email: 'test@example.com',
         senha: '123456',
         idade: 25,
         genero: 'Masculino',
         racaCor: 'Branco',
         escolaridade: 'Ensino médio completo',
         estadoCivil: 'Solteiro',
         situacaoEmprego: 'Desempregado',
         dependentes: 0,
         religiao: 'Católico'
      });
    expect(res.statusCode).toEqual(201);
    expect(res.body).toHaveProperty('data');
  });
});
