const Participante = require('../modelsSQL/Participante');

exports.cadastrar = async (req, res) => {
  try {
    const participante = await Participante.create(req.body);
    res.status(201).json({ success: true, data: participante });
  } catch (error) {
    res.status(400).json({ success: false, error: error.message });
  }
};
