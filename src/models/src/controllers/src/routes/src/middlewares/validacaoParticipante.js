exports.validarCadastro = (req, res, next) => {
    // Verificar campos obrigatórios
    const camposObrigatorios = [
      'nome', 'idade', 'genero', 'racaCor', 'escolaridade',
      'estadoCivil', 'situacaoEmprego', 'dependentes', 'religiao'
    ];
    
    for (const campo of camposObrigatorios) {
      if (!req.body[campo]) {
        return res.status(400).json({
          success: false,
          error: `Campo ${campo} é obrigatório`
        });
      }
    }
    
    // Validação específica para gênero personalizado
    if (req.body.genero === 'Outro' && !req.body.generoPersonalizado) {
      return res.status(400).json({
        success: false,
        error: 'Especifique o gênero quando selecionar "Outro"'
      });
    }
    
    next();
  };