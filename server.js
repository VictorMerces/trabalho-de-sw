const express = require('express');
const helmet = require('helmet'); // adicionado para segurança
const mongoose = require('./db'); // already connects via Mongoose
const participanteRoutes = require('./src/models/src/controllers/src/routes/participanteRoutes');
const consumoAlimentarRoutes = require('./src/models/src/controllers/src/routes/src/middlewares/src/public/src/models/src/controllers/src/routes/consumoAlimentarRoutes');
const ebiaRoutes = require('./src/models/src/controllers/src/routes/src/middlewares/src/public/src/models/src/controllers/src/routes/src/models/src/data/src/controllers/src/routes/ebiaRoutes');
const loginController = require('./src/models/src/controllers/loginController'); // new

const swaggerUi = require('swagger-ui-express'); // new
const swaggerSpec = require('./swagger'); // new

const Sentry = require('@sentry/node'); // Atualizado: usar require para Sentry em vez de import
Sentry.init({
  dsn: 'YOUR_SENTRY_DSN' // substitua pelo seu DSN real
});

const app = express();
const port = process.env.PORT || 3000;

app.use(helmet()); // define cabeçalhos seguros

// Capture todas as requisições
app.use(Sentry.Handlers.requestHandler());

// ...existing middleware...
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Se usar sessões com cookie, pode habilitar CSRF com:
// const csurf = require('csurf');
// app.use(csurf({ cookie: true }));

// Mount API routes
app.use('/participantes', participanteRoutes);
app.use('/api/consumo-alimentar', consumoAlimentarRoutes);
app.use('/api/ebia', ebiaRoutes);
app.post('/api/login', loginController.login); // new

// Documentação da API
app.use('/api-docs', swaggerUi.serve, swaggerUi.setup(swaggerSpec)); // new

// Serve static files from the workspace root
app.use(express.static(__dirname));

// Handler de erro do Sentry (deixe após as suas rotas)
app.use(Sentry.Handlers.errorHandler());

app.listen(port, () => {
  console.log(`Servidor rodando na porta ${port}`);
});

// Exportar app para testes quando estiver em modo de teste
if (process.env.NODE_ENV === 'test') {
  module.exports = app;
}
