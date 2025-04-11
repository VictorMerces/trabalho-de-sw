const ConsumoAlimentar = require('../models/ConsumoAlimentar');

exports.criarQuestionario = async (req, res) => {
  try {
    const questionario = new ConsumoAlimentar({
      participanteId: req.params.participanteId,
      ...req.body
    });
    await questionario.save();
    res.status(201).json(questionario);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

exports.salvarRascunho = async (req, res) => {
  try {
    const questionario = await ConsumoAlimentar.findOneAndUpdate(
      { participanteId: req.params.participanteId, rascunho: true },
      { ...req.body },
      { new: true, upsert: true }
    );
    res.json(questionario);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

exports.finalizarQuestionario = async (req, res) => {
  try {
    const questionario = await ConsumoAlimentar.findOneAndUpdate(
      { participanteId: req.params.participanteId, rascunho: true },
      { ...req.body, rascunho: false },
      { new: true }
    );
    res.json(questionario);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};

exports.obterQuestionario = async (req, res) => {
  try {
    const questionario = await ConsumoAlimentar.findOne({
      participanteId: req.params.participanteId
    });
    res.json(questionario);
  } catch (error) {
    res.status(400).json({ error: error.message });
  }
};