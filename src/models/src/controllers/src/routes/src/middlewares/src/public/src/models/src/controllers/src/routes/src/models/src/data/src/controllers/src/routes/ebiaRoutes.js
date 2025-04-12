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