// Exemplo com Sequelize:
const { DataTypes } = require('sequelize');
const sequelize = require('../dbSQL');

const Participante = sequelize.define('Participante', {
  id: { 
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  nome: { type: DataTypes.STRING(100), allowNull: false },
  idade: { type: DataTypes.INTEGER, allowNull: false },
  genero: { type: DataTypes.STRING(20), allowNull: false },
  generoPersonalizado: { type: DataTypes.STRING(50) },
  racaCor: { type: DataTypes.STRING(30), allowNull: false },
  email: { type: DataTypes.STRING(200), allowNull: false, unique: true },
  senha: { type: DataTypes.STRING(60), allowNull: false },
  dataCriacao: { type: DataTypes.DATE, defaultValue: DataTypes.NOW },
  dataAtualizacao: { type: DataTypes.DATE, defaultValue: DataTypes.NOW },
  ativo: { type: DataTypes.BOOLEAN, defaultValue: true }
  // ... outros campos e relacionamentos (ex.: escolaridade, estadoCivil etc. com foreign keys)
});

module.exports = Participante;
