const mongoose = require('mongoose');

const ConsumoAlimentarSchema = new mongoose.Schema({
  participanteId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Participante',
    required: true
  },
  dispositivoRefeicao: {
    type: String,
    enum: ['SIM', 'NÃO', 'NÃO SEI'],
    required: true
  },
  refeicoesDia: {
    type: [String],
    enum: [
      'Café da manhã',
      'Lanche da manhã',
      'Almoço',
      'Lanche da tarde',
      'Jantar',
      'Ceia/lanche da noite'
    ],
    required: true
  },
  consumoAlimentos: [{
    alimento: {
      type: String,
      enum: [
        'Feijão',
        'Frutas frescas',
        'Verduras ou legumes',
        'Hambúrguer e/ou embutidos',
        'Bebidas adoçadas',
        'Macarrão instantâneo, salgadinhos de pacote ou biscoitos salgados',
        'Biscoitos recheados, doces ou guloseimas'
      ],
      required: true
    },
    consumo: {
      type: String,
      enum: ['SIM', 'NÃO', 'NÃO SEI'],
      required: true
    }
  }],
  comentarios: {
    type: String,
    trim: true
  },
  dataPreenchimento: {
    type: Date,
    default: Date.now
  },
  rascunho: {
    type: Boolean,
    default: true
  }
});

module.exports = mongoose.model('ConsumoAlimentar', ConsumoAlimentarSchema);