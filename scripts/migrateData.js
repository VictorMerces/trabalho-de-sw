const mongoose = require('mongoose');
const ParticipanteMongo = require('../src/models/Participante'); // modelo Mongoose
const sequelize = require('../dbSQL');
const ParticipanteSQL = require('../modelsSQL/Participante');

async function migrateParticipantes() {
  await mongoose.connect(process.env.MONGO_URI || 'mongodb://localhost/mongo_db', { useNewUrlParser: true, useUnifiedTopology: true });
  const participantes = await ParticipanteMongo.find({});
  for (const p of participantes) {
    await ParticipanteSQL.create({
      nome: p.nome,
      idade: p.idade,
      genero: p.genero,
      email: p.email,
      senha: p.senha,
      // ... mapear demais campos
    });
  }
  console.log('Migração finalizada.');
  process.exit(0);
}

migrateParticipantes();
