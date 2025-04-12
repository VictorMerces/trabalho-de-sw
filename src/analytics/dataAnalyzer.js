// src/analytics/dataAnalyzer.js

// Função para analisar questionários EBIA: calcula total, média de pontuação e contagem por classificação.
function analyzeQuestionarioEBIA(questionarios) {
	// Somar as pontuações e contar as classificações
	const total = questionarios.length;
	let sum = 0;
	const classificacoes = { SEGURANCA: 0, INSEGURANCA_LEVE: 0, INSEGURANCA_MODERADA: 0, INSEGURANCA_GRAVE: 0 };
	questionarios.forEach(q => {
		sum += q.pontuacao;
		if (classificacoes[q.classificacao] !== undefined) {
			classificacoes[q.classificacao]++;
		}
	});
	const mediaPontuacao = total > 0 ? (sum / total).toFixed(2) : 0;
	return { total, mediaPontuacao, classificacoes };
}

// Função para filtrar participantes com base em parâmetros
function filterParticipantes(participantes, filters) {
	return participantes.filter(p => {
		let valid = true;
		if (filters.idadeMin) valid = valid && p.idade >= Number(filters.idadeMin);
		if (filters.idadeMax) valid = valid && p.idade <= Number(filters.idadeMax);
		if (filters.genero) valid = valid && p.genero === filters.genero;
		if (filters.escolaridade) valid = valid && p.escolaridade === filters.escolaridade;
		return valid;
	});
}

module.exports = { analyzeQuestionarioEBIA, filterParticipantes };