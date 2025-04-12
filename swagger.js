// Utilize comentários JSDoc com a tag @swagger em seus arquivos de rota para automatizar a geração de documentação da API.
const swaggerJSDoc = require('swagger-jsdoc');

const options = {
  definition: {
    openapi: '3.0.0',
    info: {
      title: 'trabalho-de-sw API',
      version: '1.0.0',
      description: 'Documentação da API do trabalho-de-sw'
    },
    servers: [
      {
        url: 'http://localhost:3000',
        description: 'Servidor de desenvolvimento'
      }
    ]
  },
  apis: [
    './src/models/src/controllers/src/routes/**/*.js' // Ajuste o caminho conforme necessário
  ]
};

const swaggerSpec = swaggerJSDoc(options);
module.exports = swaggerSpec;
