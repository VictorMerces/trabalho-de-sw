const mongoose = require('mongoose');
const EstadoCivilSchema = new mongoose.Schema({
  nome: { type: String, required: true, unique: true }
});
module.exports = mongoose.model('EstadoCivil', EstadoCivilSchema);
