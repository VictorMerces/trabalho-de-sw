const mongoose = require('mongoose');
const ReligiaoSchema = new mongoose.Schema({
  nome: { type: String, required: true, unique: true }
});
module.exports = mongoose.model('Religiao', ReligiaoSchema);
