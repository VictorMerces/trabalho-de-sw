import mongoose from 'mongoose';

const EsquemaEBIA = new mongoose.Schema({
  participanteId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Participante',
    required: false
  },
  respostas: [{
    perguntaId: {
      type: Number,
      required: true,
      enum: [1, 2, 3, 4, 5, 6, 7, 8]
    },
    resposta: {
      type: String,
      enum: ['SIM', 'NÃO'],
      required: true
    }
  }],
  pontuacao: {
    type: Number,
    required: true,
    min: 0,
    max: 8
  },
  classificacao: {
    type: String,
    enum: ['SEGURANCA', 'INSEGURANCA_LEVE', 'INSEGURANCA_MODERADA', 'INSEGURANCA_GRAVE'],
    required: true
  },
  dataPreenchimento: {
    type: Date,
    default: Date.now
  },
  anonimo: {
    type: Boolean,
    default: false
  }
});

// Cálculo automático da pontuação e classificação
EsquemaEBIA.pre('save', function(next) {
  this.pontuacao = this.respostas.filter(r => r.resposta === 'SIM').length;
  
  if (this.pontuacao === 0) {
    this.classificacao = 'SEGURANCA';
  } else if (this.pontuacao <= 3) {
    this.classificacao = 'INSEGURANCA_LEVE';
  } else if (this.pontuacao <= 5) {
    this.classificacao = 'INSEGURANCA_MODERADA';
  } else {
    this.classificacao = 'INSEGURANCA_GRAVE';
  }
  
  next();
});

export default mongoose.model('QuestionarioEBIA', EsquemaEBIA);