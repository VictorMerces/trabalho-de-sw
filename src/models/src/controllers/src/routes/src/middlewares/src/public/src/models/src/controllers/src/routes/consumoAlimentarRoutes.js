const express = require('express');
const router = express.Router();
const consumoAlimentarController = require('../controllers/consumoAlimentarController');

router.post('/:participanteId', consumoAlimentarController.criarQuestionario);
router.put('/:participanteId/rascunho', consumoAlimentarController.salvarRascunho);
router.put('/:participanteId/finalizar', consumoAlimentarController.finalizarQuestionario);
router.get('/:participanteId', consumoAlimentarController.obterQuestionario);

module.exports = router;