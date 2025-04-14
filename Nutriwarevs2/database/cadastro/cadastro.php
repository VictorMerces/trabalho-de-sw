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
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <title>Cadastro de Participante</title>
  <style>
    .form-group { margin-bottom: 1.5rem; }
    .form-check { margin-bottom: 0.5rem; }
    .required-label::after { content: " *"; color: red; }
    .optional-label::after { content: " (opcional)"; color: gray; font-size: 0.8em;}
  </style>
 </head>
 <body class="container mt-4 mb-5">
  <h1>Cadastro de Participante</h1>
  <p>Campos marcados com <span style="color: red;">*</span> são obrigatórios.</p>

  <?php
    // Exibe mensagem de sucesso/erro se houver
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'sucesso') {
            echo '<div class="alert alert-success">Participante cadastrado com sucesso!</div>';
        } elseif ($_GET['status'] == 'erro') {
            // Tenta obter a mensagem de erro da sessão (se processa_cadastro.php a definir)
            $erroMsg = $_SESSION['cadastro_erro'] ?? 'Ocorreu um erro durante o cadastro. Verifique os dados e tente novamente.';
            echo '<div class="alert alert-danger">' . htmlspecialchars($erroMsg) . '</div>';
            unset($_SESSION['cadastro_erro']); // Limpa a mensagem de erro
        } elseif ($_GET['status'] == 'erro_csrf') {
             echo '<div class="alert alert-danger">Erro de validação de segurança. Por favor, tente enviar o formulário novamente.</div>';
        }
    }
  ?>

  <form action="processa_cadastro.php" method="POST" id="cadastro-form" novalidate>
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

  <div class="form-group">
      <label for="nome" class="required-label">Nome Completo:</label>
      <input type="text" id="nome" name="nome" required class="form-control">
  </div>

  <div class="form-group">
      <label for="email" class="required-label">E-mail:</label>
      <input type="email" id="email" name="email" required class="form-control" placeholder="exemplo@dominio.com">
  </div>

  <div class="form-group">
      <label for="senha" class="required-label">Senha:</label>
      <input type="password" id="senha" name="senha" required class="form-control" minlength="6">
      <small class="form-text text-muted">Mínimo de 6 caracteres.</small>
  </div>

   <div class="form-group">
      <label for="senha_confirm" class="required-label">Confirmar Senha:</label>
      <input type="password" id="senha_confirm" name="senha_confirm" required class="form-control" minlength="6">
  </div>


  <hr>
  <h4>Informações Adicionais</h4>

  <div class="form-group">
      <label for="idade" class="optional-label">Idade:</label>
      <input type="number" id="idade" name="idade" class="form-control" min="0">
  </div>

  <div class="form-group">
      <label for="genero" class="optional-label">Gênero:</label>
      <select id="genero" name="genero" class="form-control">
          <option value="">Selecione...</option>
          <option value="masculino">Masculino</option>
          <option value="feminino">Feminino</option>
          <option value="transgenero">Transgênero</option>
          <option value="nao_binario">Não Binário</option>
          <option value="outro">Outro</option>
          <option value="prefere_nao_dizer">Prefere não dizer</option>
      </select>
      <input type="text" id="genero_outro" name="genero_outro" class="form-control mt-1" placeholder="Qual?" style="display: none;">
  </div>

  <div class="form-group">
      <label class="optional-label">Raça/Cor:</label><br>
      <div class="form-check"><input class="form-check-input" type="radio" id="raca_branco" name="raca" value="branco"><label class="form-check-label" for="raca_branco">Branco</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" id="raca_preto" name="raca" value="preto"><label class="form-check-label" for="raca_preto">Preto</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" id="raca_pardo" name="raca" value="pardo"><label class="form-check-label" for="raca_pardo">Pardo</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" id="raca_originario" name="raca" value="povos_originarios"><label class="form-check-label" for="raca_originario">Povos originários</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" id="raca_outro_check" name="raca" value="outro"><label class="form-check-label" for="raca_outro_check">Outro:</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" id="raca_prefere" name="raca" value="prefere_nao_dizer"><label class="form-check-label" for="raca_prefere">Prefere não dizer</label></div>
      <input type="text" id="raca_outro" name="raca_outro" class="form-control mt-1" placeholder="Qual?" style="display: none;">
  </div>

  <div class="form-group">
      <label class="optional-label">Escolaridade:</label><br>
      <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_fund_inc" value="ensino_fundamental_incompleto"><label class="form-check-label" for="esc_fund_inc">Ensino fundamental incompleto</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_fund_com" value="ensino_fundamental_completo"><label class="form-check-label" for="esc_fund_com">Ensino fundamental completo</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_med_inc" value="ensino_medio_incompleto"><label class="form-check-label" for="esc_med_inc">Ensino médio incompleto</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_med_com" value="ensino_medio_completo"><label class="form-check-label" for="esc_med_com">Ensino médio completo</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_grad_inc" value="graduacao_incompleta"><label class="form-check-label" for="esc_grad_inc">Graduação incompleta</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_grad_com" value="graduacao_completa"><label class="form-check-label" for="esc_grad_com">Graduação completa</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_outro_check" value="outro"><label class="form-check-label" for="esc_outro_check">Outro:</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" name="escolaridade" id="esc_prefere" value="prefere_nao_dizer"><label class="form-check-label" for="esc_prefere">Prefere não dizer</label></div>
      <input type="text" id="escolaridade_outro" name="escolaridade_outro" class="form-control mt-1" placeholder="Qual?" style="display: none;">
  </div>

   <div class="form-group">
      <label for="estado_civil" class="optional-label">Estado Civil:</label>
      <select id="estado_civil" name="estado_civil" class="form-control">
          <option value="">Selecione...</option>
          <option value="solteiro">Solteiro(a)</option>
          <option value="casado">Casado(a)</option>
          <option value="divorciado">Divorciado(a)</option>
          <option value="viuvo">Viúvo(a)</option>
          <option value="separado">Separado(a)</option>
          <option value="uniao_estavel">União Estável</option>
          <option value="prefere_nao_dizer">Prefere não dizer</option>
      </select>
  </div>

  <div class="form-group">
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

  <div class="form-group">
        <label class="optional-label">Recebe algum benefício social?</label><br>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="Bolsa Familia" id="ben_bf">
            <label class="form-check-label" for="ben_bf">Bolsa Família / Auxílio Brasil</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="Auxilio Gas" id="ben_gas">
            <label class="form-check-label" for="ben_gas">Auxílio Gás</label>
        </div>
         <div class="form-check">
            <input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="BPC" id="ben_bpc">
            <label class="form-check-label" for="ben_bpc">BPC/LOAS</label>
        </div>
         <div class="form-check">
            <input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="Nenhum" id="ben_nenhum">
            <label class="form-check-label" for="ben_nenhum">Nenhum</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="beneficios_sociais[]" value="Outros" id="ben_outros_check">
            <label class="form-check-label" for="ben_outros_check">Outros:</label>
            <input type="text" id="beneficios_sociais_outro" name="beneficios_sociais_outro" class="form-control mt-1" placeholder="Quais?" style="display: none;">
        </div>
    </div>

  <div class="form-group">
      <label class="optional-label">Número de dependentes (pessoas que dependem financeiramente de você):</label><br>
      <div class="form-check"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_0" value="0"><label class="form-check-label" for="dep_0">0 (Nenhum)</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_1" value="1"><label class="form-check-label" for="dep_1">1</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_2" value="2"><label class="form-check-label" for="dep_2">2</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_3" value="3"><label class="form-check-label" for="dep_3">3</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_4" value="4 ou mais"><label class="form-check-label" for="dep_4">4 ou mais</label></div>
      <div class="form-check"><input class="form-check-input" type="radio" name="numero_dependentes" id="dep_outro_check" value="Outro"><label class="form-check-label" for="dep_outro_check">Outro (especificar):</label></div>
       <input type="text" id="numero_dependentes_outro" name="numero_dependentes_outro" class="form-control mt-1" placeholder="Quantos/Quais?" style="display: none;">
  </div>

  <div class="form-group">
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


  <button type="submit" class="btn btn-primary">Cadastrar</button>
  <a href="../login/login.php" class="btn btn-secondary">Cancelar / Voltar ao Login</a>
  </form>

  <script>
    // Script para mostrar/ocultar campos "Outro" e validar senhas
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('cadastro-form');
        const senhaInput = document.getElementById('senha');
        const senhaConfirmInput = document.getElementById('senha_confirm');

        // Função genérica para mostrar/ocultar campo "Outro"
        function setupOutroField(selectOrRadioName, outroInputId) {
            const elements = document.querySelectorAll(`[name="${selectOrRadioName}"]`);
            const outroInput = document.getElementById(outroInputId);

            if (!elements.length || !outroInput) return;

            const toggleOutro = () => {
                let show = false;
                if (elements[0].tagName === 'SELECT') { // Handle select dropdown
                   show = elements[0].value === 'outro';
                } else { // Handle radio buttons
                    const checkedRadio = document.querySelector(`input[name="${selectOrRadioName}"]:checked`);
                    show = checkedRadio && checkedRadio.value === 'outro';
                }
                outroInput.style.display = show ? 'block' : 'none';
                outroInput.required = show; // Torna obrigatório apenas se visível
                if (!show) outroInput.value = ''; // Limpa se oculto
            };

            elements.forEach(el => el.addEventListener('change', toggleOutro));
            toggleOutro(); // Run once on load
        }

        // Configura os campos "Outro"
        setupOutroField('genero', 'genero_outro');
        setupOutroField('raca', 'raca_outro');
        setupOutroField('escolaridade', 'escolaridade_outro');
        setupOutroField('situacao_emprego', 'situacao_emprego_outro');
        setupOutroField('numero_dependentes', 'numero_dependentes_outro');
        setupOutroField('religiao', 'religiao_outro');

        // Mostrar campo "Outros" para benefícios sociais
        const beneficiosOutroCheck = document.getElementById('ben_outros_check');
        const beneficiosOutroInput = document.getElementById('beneficios_sociais_outro');
        if (beneficiosOutroCheck && beneficiosOutroInput) {
            beneficiosOutroCheck.addEventListener('change', function() {
                 beneficiosOutroInput.style.display = this.checked ? 'block' : 'none';
                 beneficiosOutroInput.required = this.checked;
                 if (!this.checked) beneficiosOutroInput.value = '';
            });
             beneficiosOutroInput.style.display = beneficiosOutroCheck.checked ? 'block' : 'none'; // Estado inicial
        }

        // Validação de confirmação de senha no submit
         form.addEventListener('submit', function(event) {
             if (senhaInput.value !== senhaConfirmInput.value) {
                 alert('A Senha e a Confirmação de Senha não coincidem.');
                 senhaConfirmInput.focus();
                 event.preventDefault(); // Impede o envio do formulário
                 return false;
             }
             // Adicionar mais validações de frontend se necessário (ex: complexidade da senha)
         });
    });
  </script>

  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

 </body>
 </html>