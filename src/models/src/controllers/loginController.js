const Participante = require('../models/Participante');

exports.login = async (req, res) => {
  const { email, senha } = req.body;
  try {
    const participante = await Participante.findOne({ email, senha, ativo: true });
    if (!participante) {
      return res.status(401).json({ error: 'Credenciais inválidas' });
    }
    res.json({ message: 'Login realizado com sucesso', participanteId: participante._id });
  } catch (error) {
    // Não revelar detalhes internos
    res.status(500).json({ error: 'Erro interno' });
  }
};
