/**
 * @swagger
 * tags:
 *   name: EBIA
 *   description: Endpoints para o questionário EBIA e exportação de relatórios
 *
 * @swagger
 * /api/ebia/perguntas:
 *   get:
 *     summary: Lista as perguntas do questionário EBIA
 *     tags: [EBIA]
 *     responses:
 *       200:
 *         description: Lista de perguntas.
 *
 * @swagger
 * /api/ebia/questionarios:
 *   post:
 *     summary: Cria um novo questionário EBIA
 *     tags: [EBIA]
 *     security:
 *       - bearerAuth: []
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               participanteId:
 *                 type: string
 *               respostas:
 *                 type: array
 *                 items:
 *                   type: string
 *               anonimo:
 *                 type: boolean
 *             example:
 *               participanteId: "60d0fe4f5311236168a109ca"
 *               respostas: ["SIM", "NÃO", "SIM", "SIM", "NÃO", "SIM", "NÃO", "SIM"]
 *               anonimo: false
 *     responses:
 *       201:
 *         description: Questionário criado com sucesso.
 *
 * @swagger
 * /api/ebia/questionarios/{id}:
 *   get:
 *     summary: Obtém os dados de um questionário EBIA pelo ID
 *     tags: [EBIA]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         schema:
 *           type: string
 *         required: true
 *         description: ID do questionário EBIA
 *     responses:
 *       200:
 *         description: Dados do questionário.
 *
 * @swagger
 * /api/ebia/relatorio/pdf/{id}:
 *   get:
 *     summary: Gera o relatório PDF do questionário EBIA
 *     tags: [EBIA]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         schema:
 *           type: string
 *         required: true
 *         description: ID do questionário EBIA
 *     responses:
 *       200:
 *         description: Relatório em formato PDF.
 *
 * @swagger
 * /api/ebia/relatorio/csv/{id}:
 *   get:
 *     summary: Gera o relatório CSV do questionário EBIA
 *     tags: [EBIA]
 *     security:
 *       - bearerAuth: []
 *     parameters:
 *       - in: path
 *         name: id
 *         schema:
 *           type: string
 *         required: true
 *         description: ID do questionário EBIA
 *     responses:
 *       200:
 *         description: Relatório em formato CSV.
 */
import express from 'express';
import EBIAController, { exportPdf, exportCsv } from '../controllers/ebiaController';
import { autenticar } from '../middlewares/authMiddleware';

const router = express.Router();

// Rotas públicas
router.get('/perguntas', EBIAController.listarPerguntas);

// Rotas autenticadas
router.post('/questionarios', autenticar, EBIAController.criarQuestionario);
router.get('/questionarios/:id', autenticar, EBIAController.obterRelatorio);

// NOVOS endpoints para exportação
router.get('/relatorio/pdf/:id', autenticar, exportPdf);
router.get('/relatorio/csv/:id', autenticar, exportCsv);

export default router;