const express = require('express');
const router = express.Router();
const participanteController = require('../../participanteController');

/**
 * @swagger
 * tags:
 *   name: Participantes
 *   description: Endpoints para gerenciamento de participantes
 *
 * @swagger
 * /participantes:
 *   post:
 *     summary: Cadastra um novo participante
 *     tags: [Participantes]
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               nome:
 *                 type: string
 *               email:
 *                 type: string
 *               senha:
 *                 type: string
 *               idade:
 *                 type: number
 *             example:
 *               nome: "João Silva"
 *               email: "joao@exemplo.com"
 *               senha: "123456"
 *               idade: 30
 *     responses:
 *       201:
 *         description: Participante cadastrado com sucesso.
 *       400:
 *         description: Erro na validação.
 *
 *   get:
 *     summary: Lista todos os participantes ativos
 *     tags: [Participantes]
 *     responses:
 *       200:
 *         description: Array de participantes.
 */

// Rotas CRUD
router.post('/', participanteController.cadastrar);
router.get('/', participanteController.listar);
router.get('/:id', participanteController.obterPorId);
router.put('/:id', participanteController.atualizar);
router.delete('/:id', participanteController.excluir);
router.get('/filtrar', participanteController.filtrarParticipantes);

// Nova rota para predição de risco
router.get('/risco/:id', participanteController.preverRisco);

module.exports = router;