const consumoAlimentarController = require('../src/models/src/controllers/src/routes/src/middlewares/src/public/src/models/src/controllers/consumoAlimentarController');
const ConsumoAlimentar = require('../src/models/src/controllers/src/routes/src/middlewares/src/public/src/models/ConsumoAlimentar');
const request = require('supertest');
const app = require('../server');

jest.mock('../src/models/src/controllers/src/routes/src/middlewares/src/public/src/models/ConsumoAlimentar');

describe('ConsumoAlimentar Controller', () => {
  let req, res;
  beforeEach(() => {
    req = { body: {}, params: {} };
    res = { status: jest.fn().mockReturnThis(), json: jest.fn() };
  });

  describe('criarQuestionario', () => {
    it('deve criar novo questionário e retornar 201', async () => {
      req.params.participanteId = '123';
      req.body = { dispositivoRefeicao: 'SIM', refeicoesDia: ['Almoço'] };
      const docCriado = { _id: '1', participanteId: '123', ...req.body };
      ConsumoAlimentar.mockImplementation(() => {
        return { save: jest.fn().mockResolvedValue(docCriado) };
      });
      await consumoAlimentarController.criarQuestionario(req, res);
      expect(res.status).toHaveBeenCalledWith(201);
      expect(res.json).toHaveBeenCalledWith(docCriado);
    });

    it('deve tratar erro na criação do questionário', async () => {
      req.params.participanteId = '123';
      req.body = { dispositivoRefeicao: 'SIM', refeicoesDia: ['Almoço'] };
      ConsumoAlimentar.mockImplementation(() => {
        return { save: jest.fn().mockRejectedValue(new Error('Erro de criação')) };
      });
      await consumoAlimentarController.criarQuestionario(req, res);
      expect(res.status).toHaveBeenCalledWith(400);
      expect(res.json).toHaveBeenCalledWith({ error: 'Erro de criação' });
    });
  });

  it('should create a new questionario for a participant', async () => {
    const participantId = '60d0fe4f5311236168a109ca'; // ex.: ajuste conforme necessário
    const payload = {
      dispositivoRefeicao: 'SIM',
      refeicoesDia: ['Almoço'],
      consumoAlimentos: [ { alimento: 'Feijão', consumo: 'SIM' } ]
    };
    const res = await request(app)
      .post(`/api/consumo-alimentar/${participantId}`)
      .send(payload);
    expect(res.statusCode).toEqual(201);
  });

  // Testes similares podem ser criados para salvarRascunho, finalizarQuestionario e obterQuestionario
});
