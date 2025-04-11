const Participante = require('../models/Participante');

// Cadastrar novo participante
exports.cadastrar = async (req, res) => {
  try {
    const participante = new Participante(req.body);
    await participante.save();
    res.status(201).json({
      success: true,
      data: participante,
      message: 'Participante cadastrado com sucesso!'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
};

// Listar todos participantes ativos
exports.listar = async (req, res) => {
  try {
    const participantes = await Participante.find({ ativo: true });
    res.json({
      success: true,
      count: participantes.length,
      data: participantes
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Erro ao listar participantes'
    });
  }
};

// Obter um participante por ID
exports.obterPorId = async (req, res) => {
  try {
    const participante = await Participante.findOne({
      _id: req.params.id,
      ativo: true
    });
    
    if (!participante) {
      return res.status(404).json({
        success: false,
        error: 'Participante não encontrado'
      });
    }
    
    res.json({
      success: true,
      data: participante
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Erro ao buscar participante'
    });
  }
};

// Atualizar participante
exports.atualizar = async (req, res) => {
  try {
    const participante = await Participante.findOneAndUpdate(
      { _id: req.params.id, ativo: true },
      req.body,
      { new: true, runValidators: true }
    );
    
    if (!participante) {
      return res.status(404).json({
        success: false,
        error: 'Participante não encontrado'
      });
    }
    
    res.json({
      success: true,
      data: participante,
      message: 'Participante atualizado com sucesso!'
    });
  } catch (error) {
    res.status(400).json({
      success: false,
      error: error.message
    });
  }
};

// Exclusão lógica
exports.excluir = async (req, res) => {
  try {
    const participante = await Participante.findById(req.params.id);
    
    if (!participante || !participante.ativo) {
      return res.status(404).json({
        success: false,
        error: 'Participante não encontrado'
      });
    }
    
    await participante.excluirLogicamente();
    
    res.json({
      success: true,
      message: 'Participante excluído com sucesso!'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Erro ao excluir participante'
    });
  }
};