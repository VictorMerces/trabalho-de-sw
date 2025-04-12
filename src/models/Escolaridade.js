const mongoose = require('mongoose');
const EscolaridadeSchema = new mongoose.Schema({
  nome: { type: String, required: true, unique: true }
});
module.exports = mongoose.model('Escolaridade', EscolaridadeSchema);
