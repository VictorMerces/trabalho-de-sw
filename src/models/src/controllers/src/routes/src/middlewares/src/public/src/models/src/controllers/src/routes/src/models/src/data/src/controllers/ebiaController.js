import QuestionarioEBIA from '../models/QuestionarioEBIA';
import perguntasEBIA from '../data/perguntasEBIA';

export default {
  async listarPerguntas(req, res) {
    try {
      res.json(perguntasEBIA);
    } catch (error) {
      res.status(500).json({
        success: false,
        error: 'Falha ao carregar perguntas'
      });
    }
  },

  async criarQuestionario(req, res) {
    try {
      const { participanteId, respostas, anonimo } = req.body;

      // Validação
      if (respostas.length !== perguntasEBIA.length) {
        return res.status(400).json({
          success: false,
          error: `O questionário EBIA deve conter ${perguntasEBIA.length} respostas`
        });
      }

      const questionario = new QuestionarioEBIA({
        participanteId: anonimo ? null : participanteId,
        respostas: perguntasEBIA.map((pergunta, index) => ({
          perguntaId: pergunta.id,
          resposta: respostas[index]
        })),
        anonimo: !!anonimo
      });

      await questionario.save();

      res.status(201).json({
        success: true,
        data: {
          id: questionario._id,
          pontuacao: questionario.pontuacao,
          classificacao: questionario.classificacao
        }
      });

    } catch (error) {
      res.status(400).json({
        success: false,
        error: error.message
      });
    }
  },

  async obterRelatorio(req, res) {
    try {
      const { id } = req.params;
      const questionario = await QuestionarioEBIA.findById(id)
        .populate('participanteId', 'nome email idade') // popula dados essenciais do participante
        .lean();
      
      if (!questionario) {
        return res.status(404).json({
          success: false,
          error: 'Questionário não encontrado'
        });
      }

      res.json({
        success: true,
        data: questionario
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        error: error.message
      });
    }
  }
};