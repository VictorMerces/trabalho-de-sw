const mongoose = require('mongoose');

const ParticipanteSchema = new mongoose.Schema({
  // Dados pessoais
  nome: { 
    type: String, 
    required: [true, 'Nome é obrigatório'],
    trim: true
  },
  idade: {
    type: Number,
    required: [true, 'Idade é obrigatória'],
    min: [0, 'Idade não pode ser negativa'],
    max: [120, 'Idade máxima é 120 anos']
  },
  genero: {
    type: String,
    required: [true, 'Gênero é obrigatório'],
    enum: {
      values: ['Masculino', 'Feminino', 'Transgênero', 'Não binário', 'Outro'],
      message: 'Gênero inválido'
    }
  },
  generoPersonalizado: {
    type: String,
    trim: true
  },
  racaCor: {
    type: String,
    required: [true, 'Raça/cor é obrigatória'],
    enum: ['Branco', 'Preto', 'Pardo', 'Povos originários', 'Prefere não dizer']
  },
  escolaridade: {
    type: String,
    required: [true, 'Escolaridade é obrigatória'],
    enum: [
      'Ensino fundamental incompleto',
      'Ensino fundamental completo',
      'Ensino médio incompleto',
      'Ensino médio completo',
      'Graduação incompleta',
      'Graduação completa',
      'Prefere não dizer'
    ]
  },
  estadoCivil: {
    type: String,
    required: [true, 'Estado civil é obrigatório'],
    enum: [
      'Casado',
      'Viúvo',
      'Divorciado',
      'Separado',
      'Solteiro',
      'Prefere não dizer'
    ]
  },
  situacaoEmprego: {
    type: String,
    required: [true, 'Situação de emprego é obrigatória'],
    enum: [
      'Meio período',
      'Desempregado',
      'Incapaz de trabalhar',
      'Aposentado',
      'Prefere não dizer',
      'Outro'
    ]
  },
  empregoPersonalizado: {
    type: String,
    trim: true
  },
  beneficiosSociais: {
    type: [String],
    default: []
  },
  outrosBeneficios: {
    type: String,
    trim: true
  },
  dependentes: {
    type: Number,
    required: [true, 'Número de dependentes é obrigatório'],
    min: [0, 'Número de dependentes não pode ser negativo'],
    max: [20, 'Número máximo de dependentes é 20']
  },
  religiao: {
    type: String,
    required: [true, 'Religião é obrigatória'],
    enum: [
      'Católico',
      'Evangélico',
      'Candomblé',
      'Umbanda',
      'Espírita',
      'Nenhum',
      'Prefere não dizer',
      'Outro'
    ]
  },
  religiaoPersonalizada: {
    type: String,
    trim: true
  },
  // New fields for cadastro e login
  email: {
    type: String,
    required: [true, 'E-mail é obrigatório'],
    unique: true,
    lowercase: true,
    trim: true
  },
  senha: {
    type: String,
    required: [true, 'Senha é obrigatória']
  },
  // Controle
  dataCriacao: {
    type: Date,
    default: Date.now
  },
  dataAtualizacao: {
    type: Date,
    default: Date.now
  },
  ativo: {
    type: Boolean,
    default: true
  }
});

// Atualiza data de modificação
ParticipanteSchema.pre('save', function(next) {
  this.dataAtualizacao = Date.now();
  next();
});

// Exclusão lógica
ParticipanteSchema.methods.excluirLogicamente = async function() {
  this.ativo = false;
  await this.save();
};

module.exports = mongoose.model('Participante', ParticipanteSchema);