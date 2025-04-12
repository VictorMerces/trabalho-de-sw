const express = require('express');
const mongoose = require('./db'); // already connects via Mongoose
const participanteRoutes = require('./src/models/src/controllers/src/routes/participanteRoutes');
const consumoAlimentarRoutes = require('./src/models/src/controllers/src/routes/src/middlewares/src/public/src/models/src/controllers/src/routes/consumoAlimentarRoutes');
const ebiaRoutes = require('./src/models/src/controllers/src/routes/src/middlewares/src/public/src/models/src/controllers/src/routes/src/models/src/data/src/controllers/src/routes/ebiaRoutes');
const loginController = require('./src/models/src/controllers/loginController'); // new

const app = express();
const port = process.env.PORT || 3000;

// ...existing middleware...
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Mount API routes
app.use('/participantes', participanteRoutes);
app.use('/api/consumo-alimentar', consumoAlimentarRoutes);
app.use('/api/ebia', ebiaRoutes);
app.post('/api/login', loginController.login); // new

// Serve static files from the workspace root
app.use(express.static(__dirname));

app.listen(port, () => {
  console.log(`Servidor rodando na porta ${port}`);
});
