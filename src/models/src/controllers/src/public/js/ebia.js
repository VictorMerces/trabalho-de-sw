document.addEventListener('DOMContentLoaded', () => {
    const perguntasContainer = document.getElementById('perguntas-container');
    const formEBIA = document.getElementById('formEBIA');
    const resultadoModal = new bootstrap.Modal('#resultadoModal');
    let perguntas = [];
  
    // Carregar perguntas da API
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
  
    // Renderizar perguntas na tela
    function renderizarPerguntas() {
      perguntasContainer.innerHTML = '';
      
      perguntas.forEach((pergunta, index) => {
        const perguntaHTML = `
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
        perguntasContainer.insertAdjacentHTML('beforeend', perguntaHTML);
      });
      
      atualizarProgresso();
    }
  
    // Atualizar barra de progresso
    function atualizarProgresso() {
      const respostas = document.querySelectorAll('input[type="radio"]:checked');
      const progresso = (respostas.length / perguntas.length) * 100;
      
      document.getElementById('progresso').style.width = `${progresso}%`;
      document.getElementById('contador-perguntas').textContent = 
        `${respostas.length}/${perguntas.length} respondidas`;
    }
  
    // Ouvinte de eventos para atualizar progresso
    perguntasContainer.addEventListener('change', (e) => {
      if (e.target.type === 'radio') {
        atualizarProgresso();
      }
    });
  
    // Enviar formulário
    formEBIA.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const btnEnviar = document.getElementById('btnEnviar');
      const textoBotao = document.getElementById('textoBotao');
      const carregando = document.getElementById('carregando');
      
      // Desativar botão durante o envio
      btnEnviar.disabled = true;
      textoBotao.textContent = 'Enviando...';
      carregando.classList.remove('d-none');
      
      try {
        // Coletar respostas
        const respostas = perguntas.map(pergunta => {
          const selecionado = document.querySelector(
            `input[name="pergunta-${pergunta.id}"]:checked`
          );
          return selecionado ? selecionado.value : null;
        });
        
        // Verificar se todas foram respondidas
        if (respostas.some(r => r === null)) {
          throw new Error('Por favor, responda todas as perguntas');
        }
        
        // Enviar para o backend
        const response = await fetch('/api/ebia/questionarios', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          },
          body: JSON.stringify({
            participanteId: localStorage.getItem('participanteId'),
            respostas: respostas,
            anonimo: document.getElementById('anonimo').checked
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
        // Restaurar botão
        btnEnviar.disabled = false;
        textoBotao.textContent = 'Enviar Questionário';
        carregando.classList.add('d-none');
      }
    });
  
    // Mostrar resultado na modal
    function mostrarResultado(resultado) {
      // Configurar alerta conforme classificação
      const alerta = document.getElementById('resultado-alerta');
      let titulo, descricao, classe;
      
      switch(resultado.classificacao) {
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
      
      // Atualizar elementos
      alerta.className = `alert ${classe}`;
      document.getElementById('resultado-titulo').textContent = titulo;
      document.getElementById('resultado-descricao').textContent = descricao;
      document.getElementById('resultado-pontuacao').textContent = resultado.pontuacao;
      
      // Configurar botão do relatório
      document.getElementById('btnRelatorio').onclick = () => {
        window.location.href = `/relatorio-ebia.html?id=${resultado.id}`;
      };
      
      // Mostrar modal
      resultadoModal.show();
    }
  
    // Inicializar
    carregarPerguntas();
  });