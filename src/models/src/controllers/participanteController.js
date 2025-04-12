const Participante = require('../models/Participante');
const predictor = require('../../../../ml/predictor');

// Cadastrar novo participante
exports.cadastrar = async (req, res) => {
  try {
    // Extrair campos de geolocalização enviados (latitude e longitude)
    const { latitude, longitude, ...data } = req.body;
    if (latitude && longitude) {
      data.localizacao = { latitude: Number(latitude), longitude: Number(longitude) };
    }
    const participante = new Participante(data);
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

// Obter um participante por ID com populate apenas dos campos necessários
exports.obterPorId = async (req, res) => {
  try {
    const participante = await Participante.findOne({
      _id: req.params.id,
      ativo: true
    })
    // Popula somente os campos essenciais dos relacionamentos
    .populate('escolaridade', 'nome')
    .populate('estadoCivil', 'nome')
    .populate('situacaoEmprego', 'nome')
    .populate('religiao', 'nome')
    .lean();
    
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

// Filtrar participantes
exports.filtrarParticipantes = async (req, res) => {
  try {
    const filter = { ativo: true };
    const { idadeMin, idadeMax, genero, escolaridade } = req.query;
    if (idadeMin || idadeMax) {
      filter.idade = {};
      if (idadeMin) filter.idade.$gte = Number(idadeMin);
      if (idadeMax) filter.idade.$lte = Number(idadeMax);
    }
    if (genero) filter.genero = genero;
    if (escolaridade) filter.escolaridade = escolaridade;
    
    const participantes = await Participante.find(filter);
    res.json({
      success: true,
      count: participantes.length,
      data: participantes
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
};

// Novo método para prever risco de insegurança alimentar
exports.preverRisco = async (req, res) => {
  try {
    const { id } = req.params;
    const participante = await Participante.findOne({ _id: id, ativo: true });
    if (!participante) {
      return res.status(404).json({
        success: false,
        error: 'Participante não encontrado'
      });
    }
    // Extraia os dados socioeconômicos e hábitos relevantes
    // Exemplo: idade e número de dependentes (você pode expandir conforme necessário)
    const dados = {
      idade: participante.idade,
      dependentes: participante.dependentes || 0
    };
    const risco = predictor.predictRisk(dados);
    res.json({
      success: true,
      data: {
        participanteId: participante._id,
        risco
      }
    });
  } catch (error) {
    res.status(500).json({ success: false, error: error.message });
  }
};