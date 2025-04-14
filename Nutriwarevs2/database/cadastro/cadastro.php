<?php
 session_start();
 // Gera token CSRF se não existir na sessão
 if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
 }
 ?>
 <!DOCTYPE html>
 <html lang="pt-br">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro de Participante - Nutriware</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> <style>
    body { background-color: #f8f9fa; padding-bottom: 3rem; } /* Fundo cinza claro */
    .form-section-card {
        margin-bottom: 2rem;
        border: 1px solid #d1e7dd; /* Borda verde clara */
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .form-section-card .card-header {
        background-color: #d1e7dd; /* Fundo cabeçalho verde claro */
        color: #0f5132; /* Texto verde escuro */
        font-weight: bold;
        border-bottom: 1px solid #badbcc;
    }
    .form-group { margin-bottom: 1.25rem; }
    .form-check { margin-bottom: 0.5rem; }
    .required-label::after { content: " *"; color: #dc3545; }
    .optional-label::after { content: " (opcional)"; color: #6c757d; font-size: 0.8em;}
    .btn-success { background-color: #28a745; border-color: #28a745;} /* Verde padrão */
    .btn-success:hover { background-color: #218838; border-color: #1e7e34;}
    h1 { color: #1E4620; text-align: center; margin-top: 1.5rem; margin-bottom: 0.5rem;}
    .sub-header { text-align: center; color: #5a6268; margin-bottom: 2rem; }
  </style>
 </head>
 <body class="container mt-4 mb-5">
  <h1><i class="fas fa-user-plus text-success"></i> Cadastro de Participante</h1>
  <p class="sub-header">Campos marcados com <span class="text-danger">*</span> são obrigatórios.</p>

  <?php
    // Exibe mensagem de erro se houver
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'erro') {
            $erroMsg = $_SESSION['cadastro_erro'] ?? 'Ocorreu um erro durante o cadastro. Verifique os dados e tente novamente.';
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . nl2br(htmlspecialchars($erroMsg)) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            unset($_SESSION['cadastro_erro']); // Limpa a mensagem de erro
        } elseif ($_GET['status'] == 'erro_csrf') {
             echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Erro de validação de segurança. Por favor, tente enviar o formulário novamente.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        }
        // Mensagem de sucesso agora é mostrada na página de login
    }
  ?>

  <form action="processa_cadastro.php" method="POST" id="cadastro-form" novalidate>
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

    <div class="card form-section-card">
        <div class="card-header">
            <i class="fas fa-lock"></i> Dados de Acesso
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="nome" class="required-label">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" required class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label for="email" class="required-label">E-mail:</label>
                    <input type="email" id="email" name="email" required class="form-control" placeholder="exemplo@dominio.com">
                </div>
            </div>
            <div class="form-row">
                 <div class="form-group col-md-6">
                    <label for="senha" class="required-label">Senha:</label>
                    <input type="password" id="senha" name="senha" required class="form-control" minlength="6">
                    <small class="form-text text-muted">Mínimo de 6 caracteres.</small>
                </div>
                 <div class="form-group col-md-6">
                    <label for="senha_confirm" class="required-label">Confirmar Senha:</label>
                    <input type="password" id="senha_confirm" name="senha_confirm" required class="form-control" minlength="6">
                </div>
            </div>
        </div>
    </div>

    <div class="card form-section-card">
         <div class="card-header">
             <i class="fas fa-info-circle"></i> Informações Adicionais
         </div>
         <div class="card-body">
            <div class="form-row">
                 <div class="form-group col-md-4">
                    <label for="idade" class="optional-label">Idade:</label>
                    <input type="number" id="idade" name="idade" class="form-control" min="0">
                </div>
                <div class="form-group col-md-4">
                    <label for="genero" class="optional-label">Gênero:</label>
                    <select id="genero" name="genero" class="form-control">
                        <option value="" selected>Selecione...</option>
                        <option value="masculino">Masculino</option>
                        <option value="feminino">Feminino</option>
                        <option value="transgenero">Transgênero</option>
                        <option value="nao_binario">Não Binário</option>
                        <option value="outro">Outro</option>
                        <option value="prefere_nao_dizer">Prefere não dizer</option>
                    </select>
                    <input type="text" id="genero_outro" name="genero_outro" class="form-control mt-1" placeholder="Qual?" style="display: none;">
                </div>
                 <div class="form-group col-md-4">
                    <label for="estado_civil" class="optional-label">Estado Civil:</label>
                    <select id="estado_civil" name="estado_civil" class="form-control">
                        <option value="" selected>Selecione...</option>
                        <option value="solteiro">Solteiro(a)</option>
                        <option value="casado">Casado(a)</option>
                        <option value="divorciado">Divorciado(a)</option>
                        <option value="viuvo">Viúvo(a)</option>
                        <option value="separado">Separado(a)</option>
                        <option value="uniao_estavel">União Estável</option>
                        <option value="prefere_nao_dizer">Prefere não dizer</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="optional-label">Raça/Cor:</label><br>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" id="raca_branco" name="raca" value="branco"><label class="form-check-label" for="raca_branco">Branco</label></div>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" id="raca_preto" name="raca" value="preto"><label class="form-check-label" for="raca_preto">Preto</label></div>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" id="raca_pardo" name="raca" value="pardo"><label class="form-check-label" for="raca_pardo">Pardo</label></div>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" id="raca_originario" name="raca" value="povos_originarios"><label class="form-check-label" for="raca_originario">Povos originários</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" id="raca_outro_check" name="raca" value="outro"><label class="form-check-label" for="raca_outro_check">Outro:</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" id="raca_prefere" name="raca" value="prefere_nao_dizer"><label class="form-check-label" for="raca_prefere">Prefere não dizer</label></div>
                    <input type="text" id="raca_outro" name="raca_outro" class="form-control mt-1" placeholder="Qual?" style="display: none;">
                </div>

                 <div class="form-group col-md-6">
                    <label class="optional-label">Escolaridade:</label><br>
                    <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_fund_inc" value="ensino_fundamental_incompleto"><label class="form-check-label" for="esc_fund_inc">Ens. fundamental incompleto</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_fund_com" value="ensino_fundamental_completo"><label class="form-check-label" for="esc_fund_com">Ens. fundamental completo</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_med_inc" value="ensino_medio_incompleto"><label class="form-check-label" for="esc_med_inc">Ens. médio incompleto</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_med_com" value="ensino_medio_completo"><label class="form-check-label" for="esc_med_com">Ens. médio completo</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_grad_inc" value="graduacao_incompleta"><label class="form-check-label" for="esc_grad_inc">Graduação incompleta</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_grad_com" value="graduacao_completa"><label class="form-check-label" for="esc_grad_com">Graduação completa</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_outro_check" value="outro"><label class="form-check-label" for="esc_outro_check">Outro:</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_prefere" value="prefere_nao_dizer"><label class="form-check-label" for="esc_prefere">Prefere não dizer</label></div>
                    <input type="text" id="escolaridade_outro" name="escolaridade_outro" class="form-control mt-1" placeholder="Qual?" style="display: none;">
                </div>
            </div>


             <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="optional-label">Situação de Trabalho:</label><br>
                    <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_meio" value="meio_periodo"><label class="form-check-label" for="emp_meio">Meio período</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_integral" value="tempo_integral"><label class="form-check-label" for="emp_integral">Tempo integral</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_autonomo" value="autonomo"><label class="form-check-label" for="emp_autonomo">Autônomo</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_desemp" value="desempregado"><label class="form-check-label" for="emp_desemp">Desempregado</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_incapaz" value="incapaz_trabalhar"><label class="form-check-label" for="emp_incapaz">Incapaz de trabalhar</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_apos" value="aposentado"><label class="form-check-label" for="emp_apos">Aposentado</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_estud" value="estudante"><label class="form-check-label" for="emp_estud">Estudante</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_outro_check" value="outro"><label class="form-check-label" for="emp_outro_check">Outro:</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="situacao_emprego" id="emp_prefere" value="prefere_nao_dizer"><label class="form-check-label" for="emp_prefere">Prefere não dizer</label></div>
                    <input type="text" id="situacao_emprego_outro" name="situacao_emprego_outro" class="form-control mt-1" placeholder="Qual?" style="display: none;">
                </div>
                 <div class="form-group col-md-6">
                    <label class="optional-label">Religião:</label><br>
                    <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_cat" value="catolico"><label class="form-check-label" for="rel_cat">Católico</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_evan" value="evangelico"><label class="form-check-label" for="rel_evan">Evangélico</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_esp" value="espirita"><label class="form-check-label" for="rel_esp">Espírita</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_umb" value="umbanda"><label class="form-check-label" for="rel_umb">Umbanda</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_cand" value="candomble"><label class="form-check-label" for="rel_cand">Candomblé</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_ateu" value="ateu"><label class="form-check-label" for="rel_ateu">Ateu</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_nenhum" value="nenhum"><label class="form-check-label" for="rel_nenhum">Nenhuma</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_outro_check" value="outro"><label class="form-check-label" for="rel_outro_check">Outra:</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="religiao" id="rel_prefere" value="prefere_nao_dizer"><label class="form-check-label" for="rel_prefere">Prefere não dizer</label></div>
                    <input type="text" id="religiao_outro" name="religiao_outro" class="form-control mt-1" placeholder="Qual?" style="display: none;">
                </div>
             </div>

             <div class="form-row">
                  <div class="form-group col-md-6">
                    <label class="optional-label">Recebe algum benefício social?</label><br>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="Bolsa Familia" id="ben_bf"><label class="form-check-label" for="ben_bf">Bolsa Família / Auxílio Brasil</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="Auxilio Gas" id="ben_gas"><label class="form-check-label" for="ben_gas">Auxílio Gás</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="BPC" id="ben_bpc"><label class="form-check-label" for="ben_bpc">BPC/LOAS</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="Nenhum" id="ben_nenhum"><label class="form-check-label" for="ben_nenhum">Nenhum</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="Outros" id="ben_outros_check"><label class="form-check-label" for="ben_outros_check">Outros:</label></div>
                    <input type="text" id="beneficios_sociais_outro" name="beneficios_sociais_outro" class="form-control mt-1" placeholder="Quais?" style="display: none;">
                </div>
                 <div class="form-group col-md-6">
                    <label class="optional-label">Número de dependentes:</label><br>
                    <small class="form-text text-muted mt-0 mb-2">Pessoas que dependem financeiramente de você.</small>
                    <div class="form-check"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_0" value="0"><label class="form-check-label" for="dep_0">0 (Nenhum)</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_1" value="1"><label class="form-check-label" for="dep_1">1</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_2" value="2"><label class="form-check-label" for="dep_2">2</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_3" value="3"><label class="form-check-label" for="dep_3">3</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_4" value="4 ou mais"><label class="form-check-label" for="dep_4">4 ou mais</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_outro_check" value="Outro"><label class="form-check-label" for="dep_outro_check">Outro (especificar):</label></div>
                    <input type="text" id="numero_dependentes_outro" name="numero_dependentes_outro" class="form-control mt-1" placeholder="Quantos/Quais?" style="display: none;">
                </div>
             </div>
         </div> </div> <div class="mt-4 text-center">
        <button type="submit" class="btn btn-success btn-lg mr-2">
            <i class="fas fa-check-circle"></i> Cadastrar
        </button>
        <a href="../login/login.php" class="btn btn-secondary btn-lg">
             <i class="fas fa-times-circle"></i> Cancelar / Voltar ao Login
        </a>
    </div>
  </form>

  <script>
    // Script para mostrar/ocultar campos "Outro" e validar senhas (mesmo da versão anterior, adaptado)
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('cadastro-form');
        const senhaInput = document.getElementById('senha');
        const senhaConfirmInput = document.getElementById('senha_confirm');

        // Função genérica para mostrar/ocultar campo "Outro" (Select e Radio)
        function setupOutroField(baseElementName, outroInputId) {
            const elements = document.querySelectorAll(`[name="${baseElementName}"]`); // Pode ser select ou radios
            const outroInput = document.getElementById(outroInputId);
            if (!elements.length || !outroInput) return;

            const toggle = () => {
                let show = false;
                if (elements[0].tagName === 'SELECT') {
                    show = elements[0].value === 'outro';
                } else { // Radio buttons
                    const checkedRadio = document.querySelector(`input[name="${baseElementName}"]:checked`);
                    show = checkedRadio && checkedRadio.value === 'outro';
                }
                outroInput.style.display = show ? 'block' : 'none';
                outroInput.required = show; // Torna obrigatório apenas se visível
                if (!show) outroInput.value = ''; // Limpa se oculto
            };
            elements.forEach(el => el.addEventListener('change', toggle));
            toggle(); // Estado inicial
        }

         // Função específica para o checkbox "Outros" de benefícios
        function setupBeneficiosOutro(outroCheckboxId, outroInputId) {
            const outroCheckbox = document.getElementById(outroCheckboxId);
            const outroInput = document.getElementById(outroInputId);
            if (!outroCheckbox || !outroInput) return;

            const toggle = () => {
                outroInput.style.display = outroCheckbox.checked ? 'block' : 'none';
                outroInput.required = outroCheckbox.checked;
                if (!outroCheckbox.checked) outroInput.value = '';
            };
            outroCheckbox.addEventListener('change', toggle);
            toggle(); // Estado inicial
        }


        // Configura os campos "Outro"
        setupOutroField('genero', 'genero_outro');
        setupOutroField('raca', 'raca_outro');
        setupOutroField('escolaridade', 'escolaridade_outro');
        setupOutroField('situacao_emprego', 'situacao_emprego_outro');
        setupOutroField('numero_dependentes', 'numero_dependentes_outro');
        setupOutroField('religiao', 'religiao_outro');
        setupBeneficiosOutro('ben_outros_check', 'beneficios_sociais_outro');


        // Validação de confirmação de senha no submit
         form.addEventListener('submit', function(event) {
            let isValid = true;
            // 1. Validar campos obrigatórios gerais (nome, email, senha)
             if (!document.getElementById('nome').value.trim()) {
                 alert('O campo Nome Completo é obrigatório.');
                 document.getElementById('nome').focus();
                 isValid = false;
             }
             else if (!document.getElementById('email').value.trim()) {
                 alert('O campo E-mail é obrigatório.');
                  document.getElementById('email').focus();
                  isValid = false;
             } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(document.getElementById('email').value)) {
                 alert('O formato do E-mail é inválido.');
                 document.getElementById('email').focus();
                 isValid = false;
             }
             else if (!senhaInput.value) {
                 alert('O campo Senha é obrigatório.');
                 senhaInput.focus();
                 isValid = false;
             }
             else if (senhaInput.value.length < 6) {
                alert('A senha deve ter pelo menos 6 caracteres.');
                senhaInput.focus();
                isValid = false;
             }
             else if (senhaInput.value !== senhaConfirmInput.value) {
                 alert('A Senha e a Confirmação de Senha não coincidem.');
                 senhaConfirmInput.focus();
                 isValid = false;
             }

             // 2. Validar campos 'Outro' obrigatórios (se visíveis)
             const outrosVisiveis = form.querySelectorAll('input[id$="_outro"]:required');
             outrosVisiveis.forEach(input => {
                 if (isValid && !input.value.trim()) { // Só valida se o resto estiver ok
                     // Acha o label associado (pode precisar de ajuste se a estrutura mudar)
                     let labelElement = input.closest('.form-group').querySelector('label:not(.form-check-label)');
                     let labelText = labelElement ? labelElement.textContent.replace(':', '').replace('*', '').trim() : 'Campo "Outro"';
                     alert(`Por favor, especifique o valor para "${labelText}".`);
                      input.focus();
                      isValid = false;
                 }
             });

            if (!isValid) {
                event.preventDefault(); // Impede o envio do formulário se inválido
            }
         });
    });
  </script>

  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

 </body>
 </html>