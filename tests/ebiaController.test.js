const ebiaController = require('../src/models/src/controllers/src/routes/src/middlewares/src/public/src/models/src/controllers/src/routes/src/models/src/data/src/controllers/ebiaController');
const QuestionarioEBIA = require('../src/models/src/controllers/src/routes/src/middlewares/src/public/src/models/src/controllers/src/routes/src/models/src/data/src/controllers/src/routes/QuestionarioEBIA');
const request = require('supertest');
const app = require('../server');

jest.mock('../src/models/src/controllers/src/routes/src/middlewares/src/public/src/models/src/controllers/src/routes/src/models/src/data/src/controllers/src/routes/QuestionarioEBIA');

describe('EBIA Controller', () => {
  let req, res;
  beforeEach(() => {
    req = { params: {}, body: {} };
    res = { status: jest.fn().mockReturnThis(), json: jest.fn() };
  });

  describe('listarPerguntas', () => {
    it('deve retornar a lista de perguntas', async () => {
      await ebiaController.listarPerguntas(req, res);
      // Assume que perguntasEBIA é importado diretamente e enviado na resposta
      expect(res.json).toHaveBeenCalled();
    });

    it('should list EBIA questions', async () => {
      const res = await request(app)
        .get('/api/ebia/perguntas');
      expect(res.statusCode).toEqual(200);
      expect(Array.isArray(res.body)).toBeTruthy();
    });
  });

  describe('criarQuestionario', () => {
    it('deve criar novo questionário EBIA e retornar 201', async () => {
      req.body = {
        participanteId: '123',
        respostas: ['SIM', 'NÃO', 'SIM', 'SIM', 'NÃO', 'SIM', 'NÃO', 'SIM'],
        anonimo: false
      };
      const dummyQuestionario = { _id: '1', pontuacao: 5, classificacao: 'INSEGURANCA_MODERADA' };
      QuestionarioEBIA.mockImplementation(() => {
        return { save: jest.fn().mockResolvedValue(dummyQuestionario) };
      });
      await ebiaController.criarQuestionario(req, res);
      expect(res.status).toHaveBeenCalledWith(201);
      expect(res.json).toHaveBeenCalledWith({
        success: true,
        data: dummyQuestionario
      });
    });

    it('deve retornar erro se o número de respostas for inválido', async () => {
      req.body = {
        participanteId: '123',
        respostas: ['SIM', 'NÃO'], // menos respostas que o esperado
        anonimo: false
      };
      await ebiaController.criarQuestionario(req, res);
      expect(res.status).toHaveBeenCalledWith(400);
      expect(res.json).toHaveBeenCalledWith(expect.objectContaining({
        success: false,
        error: expect.stringContaining('deve conter')
      }));
    });
  });

  describe('obterRelatorio', () => {
    it('deve retornar o questionário se encontrado', async () => {
      req.params.id = '1';
      const foundQuestionario = { _id: '1', pontuacao: 4, classificacao: 'INSEGURANCA_LEVE' };
      QuestionarioEBIA.findById.mockResolvedValue(foundQuestionario);
      await ebiaController.obterRelatorio(req, res);
      expect(res.json).toHaveBeenCalledWith({
        success: true,
        data: foundQuestionario
      });
    });

    it('deve retornar 404 se o questionário não for encontrado', async () => {
      req.params.id = '1';
      QuestionarioEBIA.findById.mockResolvedValue(null);
      await ebiaController.obterRelatorio(req, res);
      expect(res.status).toHaveBeenCalledWith(404);
      expect(res.json).toHaveBeenCalledWith({
        success: false,
        error: 'Questionário não encontrado'
      });
    });

    it('deve tratar erro ao buscar questionário', async () => {
      req.params.id = '1';
      QuestionarioEBIA.findById.mockRejectedValue(new Error('Erro no banco'));
      await ebiaController.obterRelatorio(req, res);
      expect(res.status).toHaveBeenCalledWith(500);
      expect(res.json).toHaveBeenCalledWith({
        success: false,
        error: 'Erro no banco'
      });
    });
  });
});
