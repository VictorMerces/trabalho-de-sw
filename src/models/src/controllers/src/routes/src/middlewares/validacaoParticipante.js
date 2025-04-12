const { body, validationResult } = require('express-validator');

exports.validarCadastro = [
  // Verificar campos obrigatórios e sanitizar entradas
  body('nome').notEmpty().withMessage('Campo nome é obrigatório').trim().escape(),
  body('idade').notEmpty().withMessage('Campo idade é obrigatório').isInt().toInt(),
  body('genero').notEmpty().withMessage('Campo gênero é obrigatório').trim().escape(),
  body('racaCor').notEmpty().withMessage('Campo raça/cor é obrigatório').trim().escape(),
  body('escolaridade').notEmpty().withMessage('Campo escolaridade é obrigatório').trim().escape(),
  body('estadoCivil').notEmpty().withMessage('Campo estado civil é obrigatório').trim().escape(),
  body('situacaoEmprego').notEmpty().withMessage('Campo situação de emprego é obrigatório').trim().escape(),
  body('dependentes').notEmpty().withMessage('Campo dependentes é obrigatório').isInt().toInt(),
  body('religiao').notEmpty().withMessage('Campo religião é obrigatório').trim().escape(),
  body('generoPersonalizado').optional().trim().escape(),

  (req, res, next) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({
        success: false,
        errors: errors.array()
      });
    }
    next();
  }
];