// src/ml/predictor.js

// Função dummy para prever risco de insegurança alimentar.
// Em um cenário real, integre TensorFlow.js ou outro modelo treinado.
function predictRisk(dados) {
	// Exemplo simples baseado na idade e número de dependentes
	if (dados.idade > 60 || dados.dependentes >= 3) {
		return "Alto";
	} else if (dados.idade >= 30 && dados.dependentes >= 1) {
		return "Médio";
	} 
	return "Baixo";
}

module.exports = { predictRisk };