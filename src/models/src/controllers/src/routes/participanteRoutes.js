const express = require('express');
const router = express.Router();
const participanteController = require('../controllers/participanteController');

// Rotas CRUD
router.post('/', participanteController.cadastrar);
router.get('/', participanteController.listar);
router.get('/:id', participanteController.obterPorId);
router.put('/:id', participanteController.atualizar);
router.delete('/:id', participanteController.excluir);

module.exports = router;    