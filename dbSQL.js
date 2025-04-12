const { Sequelize } = require('sequelize');

const sequelize = new Sequelize(process.env.PG_URI || 'postgres://user:pass@localhost:5432/trabalho_de_sw', {
  dialect: 'postgres',
  logging: false,
});

module.exports = sequelize;
