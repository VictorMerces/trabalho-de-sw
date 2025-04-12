const mongoose = require('mongoose');
const uri = process.env.MONGO_URI || 'mongodb://localhost/trabalho_de_sw';

mongoose.connect(uri, { useNewUrlParser: true, useUnifiedTopology: true });

// Adicionar handlers de eventos na conexão
mongoose.connection.on('connected', () => {
  console.log('Conectado ao MongoDB com Mongoose');
});

mongoose.connection.on('error', (err) => {
  console.error('Erro na conexão com o MongoDB:', err);
});

mongoose.connection.on('disconnected', () => {
  console.log('Conexão com o MongoDB foi encerrada');
});

process.on('SIGINT', async () => {
  await mongoose.connection.close();
  console.log('Conexão com o MongoDB encerrada por término da aplicação');
  process.exit(0);
});

module.exports = mongoose;