import express from 'express';
import EBIAController from '../controllers/ebiaController';
import { autenticar } from '../middlewares/authMiddleware';

const router = express.Router();

// Rotas públicas
router.get('/perguntas', EBIAController.listarPerguntas);

// Rotas autenticadas
router.post('/questionarios', autenticar, EBIAController.criarQuestionario);
router.get('/questionarios/:id', autenticar, EBIAController.obterRelatorio);

export default router;