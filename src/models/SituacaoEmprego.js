const mongoose = require('mongoose');
const SituacaoEmpregoSchema = new mongoose.Schema({
  nome: { type: String, required: true, unique: true }
});
module.exports = mongoose.model('SituacaoEmprego', SituacaoEmpregoSchema);
