document.addEventListener('DOMContentLoaded', () => {
    const perguntasContainer = document.getElementById('perguntas-container');
    const formEBIA = document.getElementById('formEBIA');
    const progressoElem = document.getElementById('progresso');
    const contadorElem = document.getElementById('contador-perguntas');
    const btnEnviar = document.getElementById('btnEnviar');
    const textoBotao = document.getElementById('textoBotao');
    const carregando = document.getElementById('carregando');
    const anonimoInput = document.getElementById('anonimo');
    const resultadoModal = new bootstrap.Modal('#resultadoModal');
    let perguntas = [];
  
    async function carregarPerguntas() {
      try {
        const response = await fetch('/api/ebia/perguntas');
        if (!response.ok) throw new Error('Erro ao carregar perguntas');
        perguntas = await response.json();
        renderizarPerguntas();
      } catch (error) {
        alert('Não foi possível carregar o questionário. Por favor, recarregue a página.');
        console.error(error);
      }
    }
  
    function renderizarPerguntas() {
      let html = '';
      perguntas.forEach((pergunta, index) => {
        html += `
          <div class="pergunta-card" data-id="${pergunta.id}">
            <p class="fw-bold mb-3">${index + 1}. ${pergunta.texto}</p>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="pergunta-${pergunta.id}" id="sim-${pergunta.id}" value="SIM" required>
              <label class="form-check-label" for="sim-${pergunta.id}">Sim</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="pergunta-${pergunta.id}" id="nao-${pergunta.id}" value="NÃO">
              <label class="form-check-label" for="nao-${pergunta.id}">Não</label>
            </div>
          </div>
        `;
      });
      perguntasContainer.innerHTML = html;
      atualizarProgresso();
    }
  
    function atualizarProgresso() {
      const respostas = document.querySelectorAll('input[type="radio"]:checked');
      const progresso = (respostas.length / perguntas.length) * 100;
      document.getElementById('progresso').style.width = `${progresso}%`;
      document.getElementById('contador-perguntas').textContent = `${respostas.length}/${perguntas.length} respondidas`;
      // Se os diagramas indicarem, adicione animação ou mensagem extra:
      // console.log('Progresso atualizado:', progresso);
    }
  
    perguntasContainer.addEventListener('change', (e) => {
      if (e.target.type === 'radio') atualizarProgresso();
    });
  
    formEBIA.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        btnEnviar.disabled = true;
        textoBotao.textContent = 'Enviando...';
        carregando.classList.remove('d-none');
  
        const respostas = perguntas.map(pergunta => {
          const selecionado = document.querySelector(`input[name="pergunta-${pergunta.id}"]:checked`);
          return selecionado ? selecionado.value : null;
        });
        if (respostas.some(r => r === null)) throw new Error('Por favor, responda todas as perguntas');
  
        const response = await fetch('/api/ebia/questionarios', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          },
          body: JSON.stringify({
            participanteId: localStorage.getItem('participanteId'),
            respostas,
            anonimo: anonimoInput.checked
          })
        });
        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.error || 'Erro ao enviar questionário');
        }
        const resultado = await response.json();
        mostrarResultado(resultado.data);
      } catch (error) {
        alert(error.message);
        console.error('Erro:', error);
      } finally {
        btnEnviar.disabled = false;
        textoBotao.textContent = 'Enviar Questionário';
        carregando.classList.add('d-none');
      }
    });
  
    function mostrarResultado(resultado) {
      const alerta = document.getElementById('resultado-alerta');
      let titulo, descricao, classe;
      switch (resultado.classificacao) {
        case 'SEGURANCA':
          titulo = 'Segurança Alimentar';
          descricao = 'Seu domicílio não apresenta preocupação ou dificuldade no acesso a alimentos.';
          classe = 'alert-success';
          break;
        case 'INSEGURANCA_LEVE':
          titulo = 'Insegurança Alimentar Leve';
          descricao = 'Seu domicílio apresenta preocupação ou risco de dificuldade no acesso a alimentos.';
          classe = 'alert-warning';
          break;
        case 'INSEGURANCA_MODERADA':
          titulo = 'Insegurança Alimentar Moderada';
          descricao = 'Seu domicílio apresenta redução quantitativa de alimentos entre os adultos.';
          classe = 'alert-danger';
          break;
        case 'INSEGURANCA_GRAVE':
          titulo = 'Insegurança Alimentar Grave';
          descricao = 'Seu domicílio apresenta redução quantitativa de alimentos também entre crianças.';
          classe = 'alert-dark';
          break;
      }
      alerta.className = `alert ${classe}`;
      document.getElementById('resultado-titulo').textContent = titulo;
      document.getElementById('resultado-descricao').textContent = descricao;
      document.getElementById('resultado-pontuacao').textContent = resultado.pontuacao;
      document.getElementById('btnRelatorio').onclick = () => {
        window.location.href = `/relatorio-ebia.html?id=${resultado.id}`;
      };
      resultadoModal.show();
    }
  
    carregarPerguntas();
  });