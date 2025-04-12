import QuestionarioEBIA from '../models/QuestionarioEBIA';
import perguntasEBIA from '../data/perguntasEBIA';
import PDFDocument from 'pdfkit'; // se necessário, instale o pdfkit
import { Parser as Json2csvParser } from 'json2csv'; // instale o json2csv se necessário

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
      const questionario = await QuestionarioEBIA.findById(id);
      
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
  },

  async exportPdf(req, res) {
    try {
      const { id } = req.params;
      const questionario = await QuestionarioEBIA.findById(id);
      if (!questionario) {
        return res.status(404).json({ success: false, error: 'Questionário não encontrado' });
      }
      // Configurar resposta para PDF
      res.setHeader('Content-Type', 'application/pdf');
      res.setHeader('Content-Disposition', `attachment; filename="relatorio_${id}.pdf"`);

      const doc = new PDFDocument();
      doc.pipe(res);
      doc.fontSize(18).text('Relatório EBIA', { align: 'center' });
      doc.moveDown();


























};  }    }      res.status(500).json({ success: false, error: error.message });    } catch (error) {      res.send(csv);      res.setHeader('Content-Disposition', `attachment; filename="relatorio_${id}.csv"`);      res.setHeader('Content-Type', 'text/csv');      // Configurar resposta para CSV      const csv = json2csvParser.parse(questionario);      const json2csvParser = new Json2csvParser({ fields });      const fields = ['_id', 'pontuacao', 'classificacao', 'dataPreenchimento', 'anonimo'];      // Converter os dados para CSV      }        return res.status(404).json({ success: false, error: 'Questionário não encontrado' });      if (!questionario) {      const questionario = await QuestionarioEBIA.findById(id).lean();      const { id } = req.params;    try {  async exportCsv(req, res) {  },    }      res.status(500).json({ success: false, error: error.message });    } catch (error) {      doc.fontSize(12).text(`ID: ${questionario._id}`);      doc.text(`Pontuação: ${questionario.pontuacao}`);
      doc.text(`Classificação: ${questionario.classificacao}`);
      doc.text(`Data: ${questionario.dataPreenchimento.toLocaleString()}`);
      doc.end();
    } catch (error) {
      res.status(500).json({ success: false, error: error.message });
    }
  }
};