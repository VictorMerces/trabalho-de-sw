const mongoose = require('mongoose');
const uri = process.env.MONGO_URI || 'mongodb://localhost/trabalho_de_sw';

mongoose.connect(uri, { useNewUrlParser: true, useUnifiedTopology: true })
  .then(() => console.log('Conectado ao MongoDB com Mongoose'))
  .catch(err => {
    console.error('Erro ao conectar com o MongoDB:', err);
    process.exit(1);
  });

module.exports = mongoose;