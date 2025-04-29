<?php
// Inclui o arquivo de conexão com o banco de dados
include __DIR__ . '/conexao.php';

/**
 * Gera relatório de insegurança alimentar com base nos filtros fornecidos.
 *
 * @param PDO $conexao Conexão com o banco de dados.
 * @param array $filtros Filtros opcionais (ex.: gênero, faixa etária).
 * @return array Relatório gerado.
 */
function gerar_relatorio_inseguranca_alimentar($conexao, $filtros = []) {
    $query = "SELECT p.nome, p.idade, p.genero, q.classificacao, q.data_preenchimento
              FROM participantes p
              JOIN questionarios_ebia q ON p.id = q.participante_id
              WHERE 1=1";

    // Adiciona filtros opcionais
    if (!empty($filtros['genero'])) {
        $query .= " AND p.genero = :genero";
    }
    if (!empty($filtros['faixa_etaria'])) {
        $query .= " AND p.idade BETWEEN :idade_min AND :idade_max";
    }

    $stmt = $conexao->prepare($query);

    // Vincula os parâmetros dos filtros
    if (!empty($filtros['genero'])) {
        $stmt->bindParam(':genero', $filtros['genero']);
    }
    if (!empty($filtros['faixa_etaria'])) {
        $faixa = explode('-', $filtros['faixa_etaria']);
        $stmt->bindParam(':idade_min', $faixa[0], PDO::PARAM_INT);
        $stmt->bindParam(':idade_max', $faixa[1], PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Gera relatório de consumo alimentar com base nos filtros fornecidos.
 *
 * @param PDO $conexao Conexão com o banco de dados.
 * @param array $filtros Filtros opcionais (ex.: gênero, faixa etária).
 * @return array Relatório gerado.
 */
function gerar_relatorio_consumo_alimentar($conexao, $filtros = []) {
    $query = "SELECT p.nome, p.idade, p.genero, h.refeicoes_dia, h.usa_dispositivos_refeicao, h.data_preenchimento
              FROM participantes p
              JOIN habitos_alimentares h ON p.id = h.participante_id
              WHERE 1=1";

    // Adiciona filtros opcionais
    if (!empty($filtros['genero'])) {
        $query .= " AND p.genero = :genero";
    }
    if (!empty($filtros['faixa_etaria'])) {
        $query .= " AND p.idade BETWEEN :idade_min AND :idade_max";
    }

    $stmt = $conexao->prepare($query);

    // Vincula os parâmetros dos filtros
    if (!empty($filtros['genero'])) {
        $stmt->bindParam(':genero', $filtros['genero']);
    }
    if (!empty($filtros['faixa_etaria'])) {
        $faixa = explode('-', $filtros['faixa_etaria']);
        $stmt->bindParam(':idade_min', $faixa[0], PDO::PARAM_INT);
        $stmt->bindParam(':idade_max', $faixa[1], PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>