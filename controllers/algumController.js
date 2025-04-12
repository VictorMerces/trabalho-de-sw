const sequelize = require('../dbSQL');
const Participante = require('../modelsSQL/Participante');

exports.exemploTransacao = async (req, res) => {
  const t = await sequelize.transaction();
  try {
    const participante = await Participante.create(req.body, { transaction: t });
    // Outras operações que dependem desta
    await t.commit();
    res.json({ success: true, data: participante });
  } catch (error) {
    await t.rollback();
    res.status(500).json({ success: false, error: error.message });
  }
};
