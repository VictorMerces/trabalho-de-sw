const mongoose = require('mongoose');

const ParticipanteSchema = new mongoose.Schema({
  // Dados pessoais
  nome: { 
    type: String, 
    required: [true, 'Nome é obrigatório'],
    trim: true,
    maxlength: 100  // otimização para textos curtos
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
    },
    maxlength: 20
  },
  generoPersonalizado: {
    type: String,
    trim: true,
    maxlength: 50
  },
  racaCor: {
    type: String,
    required: [true, 'Raça/cor é obrigatória'],
    enum: ['Branco', 'Preto', 'Pardo', 'Povos originários', 'Prefere não dizer'],
    maxlength: 30
  },
  escolaridade: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Escolaridade',
    required: [true, 'Escolaridade é obrigatória']
  },
  estadoCivil: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'EstadoCivil',
    required: [true, 'Estado civil é obrigatório']
  },
  situacaoEmprego: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'SituacaoEmprego',
    required: [true, 'Situação de emprego é obrigatória']
  },
  empregoPersonalizado: {
    type: String,
    trim: true,
    maxlength: 50
  },
  beneficiosSociais: {
    type: [String],
    default: []
  },
  outrosBeneficios: {
    type: String,
    trim: true,
    maxlength: 100
  },
  dependentes: {
    type: Number,
    required: [true, 'Número de dependentes é obrigatório'],
    min: [0, 'Número de dependentes não pode ser negativo'],
    max: [20, 'Número máximo de dependentes é 20']
  },
  religiao: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Religiao',
    required: [true, 'Religião é obrigatória']
  },
  religiaoPersonalizada: {
    type: String,
    trim: true,
    maxlength: 50
  },
  // New fields for cadastro e login
  email: {
    type: String,
    required: [true, 'E-mail é obrigatório'],
    unique: true,
    lowercase: true,
    trim: true,
    maxlength: 200
  },
  senha: {
    type: String,
    required: [true, 'Senha é obrigatória'],
    minlength: 60, // supondo hash com tamanho fixo, por exemplo bcrypt
    maxlength: 60
  },
  // Nova funcionalidade de geolocalização
  localizacao: {
    latitude: {
      type: Number,
      min: [-90, 'Latitude deve ser >= -90'],
      max: [90, 'Latitude deve ser <= 90']
    },
    longitude: {
      type: Number,
      min: [-180, 'Longitude deve ser >= -180'],
      max: [180, 'Longitude deve ser <= 180']
    }
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
    default: true,
    index: true // adicionado índice para acelerar consultas filtrando por 'ativo'
  }
});

// Atualiza data de modificação
ParticipanteSchema.pre('save', function(next) {
  this.dataAtualizacao = Date.now();
  next();
});

// Exclusão lógica: marca o participante como inativo para preservar histórico e manter referências em outros registros.
// Em situações que demandem a exclusão física, recomenda-se implementar uma função separada (com os devidos cuidados, como remoção em cascata e backup dos dados) 
// para garantir a integridade dos dados.
ParticipanteSchema.methods.excluirLogicamente = async function() {
  this.ativo = false;
  await this.save();
};

module.exports = mongoose.model('Participante', ParticipanteSchema);