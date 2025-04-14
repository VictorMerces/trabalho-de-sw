<?php
// Inclui o arquivo de conexão com o banco de dados
// include __DIR__ . '/conexao.php'; // O include já está no relatorios.php, remover daqui se não for usado em outro lugar

/**
 * Gera relatório de insegurança alimentar com base nos filtros e colunas.
 * (Função gerar_relatorio_inseguranca_alimentar - SEM ALTERAÇÕES NECESSÁRIAS PARA ESTA ETAPA)
 * ... (código da função inalterado) ...
 */
function gerar_relatorio_inseguranca_alimentar($conexao, $filtros = [], $colunasVisiveis = []) {
    // Mapeamento de colunas para tabelas (alias)
    $mapaColunas = [
        // Tabela participantes (p)
        'id' => 'p', 'nome' => 'p', 'email' => 'p', 'idade' => 'p', 'genero' => 'p',
        'genero_outro' => 'p', 'raca' => 'p', 'raca_outro' => 'p', 'escolaridade' => 'p',
        'escolaridade_outro' => 'p', 'estado_civil' => 'p', 'situacao_emprego' => 'p',
        'situacao_emprego_outro' => 'p', 'beneficios_sociais' => 'p', 'numero_dependentes' => 'p',
        'religiao' => 'p', 'religiao_outro' => 'p', 'data_cadastro' => 'p',
        // Tabela questionarios_ebia (q)
        'resposta1' => 'q', 'resposta2' => 'q', 'resposta3' => 'q', 'resposta4' => 'q',
        'resposta5' => 'q', 'resposta6' => 'q', 'resposta7' => 'q', 'resposta8' => 'q',
        'pontuacao_total' => 'q', 'classificacao' => 'q', 'data_preenchimento' => 'q'
    ];
    $colunasProibidas = ['email', 'senha'];

    $colunasSelecionadas = [];
    $colunasPadraoDefault = [
        'nome', 'idade', 'genero', 'raca', 'escolaridade',
        'pontuacao_total', 'classificacao',
        'resposta1', 'resposta2', 'resposta3', 'resposta4',
        'resposta5', 'resposta6', 'resposta7', 'resposta8',
        'data_preenchimento'
    ];

    if (empty($colunasVisiveis)) {
        $colunasSelecionadas = $colunasPadraoDefault;
    } else {
        $colunasPermitidas = array_diff($colunasVisiveis, $colunasProibidas);
        $nomePresente = in_array('nome', $colunasPermitidas);
        if ($nomePresente) {
            $colunasSelecionadas = array_diff($colunasPermitidas, ['id']);
             if (!in_array('nome', $colunasSelecionadas) && isset($mapaColunas['nome'])) {
                 array_unshift($colunasSelecionadas, 'nome');
             }
        } else {
            $idPresente = in_array('id', $colunasPermitidas);
            if ($idPresente) {
                $colunasSelecionadas = $colunasPermitidas;
            } else {
                $colunasSelecionadas = $colunasPermitidas;
                 if (isset($mapaColunas['nome'])) {
                    array_unshift($colunasSelecionadas, 'nome');
                 } elseif (isset($mapaColunas['id'])) {
                     array_unshift($colunasSelecionadas, 'id');
                 }
            }
        }
    }
    $colunasFinais = array_intersect($colunasSelecionadas, array_keys($mapaColunas));
    $colunasFinais = array_unique($colunasFinais);
    if (empty($colunasFinais)) {
         $colunasFinaisFallback = ['nome', 'idade', 'classificacao'];
         $colunasFinais = array_intersect($colunasFinaisFallback, array_keys($mapaColunas));
         if (empty($colunasFinais) && isset($mapaColunas['nome'])) { $colunasFinais = ['nome']; }
         elseif (empty($colunasFinais) && isset($mapaColunas['id'])) { $colunasFinais = ['id']; }
    }

    $selectParts = [];
    foreach ($colunasFinais as $col) {
        if (isset($mapaColunas[$col])) {
            $alias = $mapaColunas[$col];
            $selectParts[] = "$alias.$col AS $col";
        }
    }
    if (empty($selectParts)) {
        error_log("Relatório EBIA: Nenhuma coluna válida encontrada para seleção.");
        return [];
    }
    $selectClause = implode(", ", $selectParts);

    $query = "SELECT $selectClause
              FROM participantes p
              JOIN questionarios_ebia q ON p.id = q.participante_id
              WHERE 1=1";
    $params = [];
    if (isset($filtros['genero']) && $filtros['genero'] !== '') { $query .= " AND p.genero = :genero"; $params[':genero'] = $filtros['genero']; }
    if (isset($filtros['idade_min']) && $filtros['idade_min'] !== '' && $filtros['idade_min'] !== null) { $query .= " AND p.idade >= :idade_min"; $params[':idade_min'] = $filtros['idade_min']; }
    if (isset($filtros['idade_max']) && $filtros['idade_max'] !== '' && $filtros['idade_max'] !== null) { $query .= " AND p.idade <= :idade_max"; $params[':idade_max'] = $filtros['idade_max']; }
    if (isset($filtros['raca']) && $filtros['raca'] !== '') { $query .= " AND p.raca = :raca"; $params[':raca'] = $filtros['raca']; }
    if (isset($filtros['escolaridade']) && $filtros['escolaridade'] !== '') { $query .= " AND p.escolaridade = :escolaridade"; $params[':escolaridade'] = $filtros['escolaridade']; }
    if (isset($filtros['estado_civil']) && $filtros['estado_civil'] !== '') { $query .= " AND p.estado_civil = :estado_civil"; $params[':estado_civil'] = $filtros['estado_civil']; }
    if (isset($filtros['situacao_emprego']) && $filtros['situacao_emprego'] !== '') { $query .= " AND p.situacao_emprego = :situacao_emprego"; $params[':situacao_emprego'] = $filtros['situacao_emprego']; }
    if (isset($filtros['religiao']) && $filtros['religiao'] !== '') { $query .= " AND p.religiao = :religiao"; $params[':religiao'] = $filtros['religiao']; }

    $stmt = $conexao->prepare($query);
    foreach ($params as $key => $value) {
        $type = PDO::PARAM_STR;
        if (strpos($key, 'idade') !== false) { $type = PDO::PARAM_INT; }
        $stmt->bindValue($key, $value, $type);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Gera relatório de consumo alimentar com base nos filtros e colunas.
 * (Função gerar_relatorio_consumo_alimentar - SEM ALTERAÇÕES NECESSÁRIAS PARA ESTA ETAPA)
 * ... (código da função inalterado) ...
 */
function gerar_relatorio_consumo_alimentar($conexao, $filtros = [], $colunasVisiveis = []) {
    $mapaColunasSql = [
        'id' => 'p.id AS id', 'nome' => 'p.nome AS nome',
        'idade' => 'p.idade AS idade', 'genero' => 'p.genero AS genero',
        'raca' => 'p.raca AS raca', 'escolaridade' => 'p.escolaridade AS escolaridade',
        'estado_civil' => 'p.estado_civil AS estado_civil', 'situacao_emprego' => 'p.situacao_emprego AS situacao_emprego',
        'beneficios_sociais' => 'p.beneficios_sociais AS beneficios_sociais',
        'numero_dependentes' => 'p.numero_dependentes AS numero_dependentes', 'religiao' => 'p.religiao AS religiao',
        'refeicoes' => 'ca.refeicoes AS refeicoes', 'usa_dispositivos' => 'ca.usa_dispositivos AS usa_dispositivos',
        'feijao' => 'ca.feijao AS feijao', 'frutas_frescas' => 'ca.frutas_frescas AS frutas_frescas',
        'verduras_legumes' => 'ca.verduras_legumes AS verduras_legumes',
        'hamburguer_embutidos' => 'ca.hamburguer_embutidos AS hamburguer_embutidos',
        'bebidas_adocadas' => 'ca.bebidas_adocadas AS bebidas_adocadas',
        'macarrao_instantaneo' => 'ca.macarrao_instantaneo AS macarrao_instantaneo',
        'biscoitos_recheados' => 'ca.biscoitos_recheados AS biscoitos_recheados',
        'data_preenchimento' => 'ca.data_preenchimento AS data_preenchimento'
    ];
    $colunasPadrao = [
        'nome', 'idade', 'genero', 'raca', 'escolaridade', 'refeicoes', 'usa_dispositivos', 'feijao',
        'frutas_frescas', 'verduras_legumes', 'hamburguer_embutidos', 'bebidas_adocadas',
        'macarrao_instantaneo', 'biscoitos_recheados', 'data_preenchimento'
    ];
    $colunasProibidas = ['email', 'senha', 'genero_outro', 'raca_outro', 'escolaridade_outro', 'situacao_emprego_outro', 'religiao_outro'];

    $colunasSelecionadas = [];
    if (empty($colunasVisiveis)) { $colunasSelecionadas = $colunasPadrao; }
    else {
        $colunasPermitidas = array_diff($colunasVisiveis, $colunasProibidas);
        $nomePresente = in_array('nome', $colunasPermitidas);
        if ($nomePresente) { $colunasSelecionadas = array_diff($colunasPermitidas, ['id']); if (!in_array('nome', $colunasSelecionadas)) array_unshift($colunasSelecionadas, 'nome'); }
        else { $idPresente = in_array('id', $colunasPermitidas); if ($idPresente) { $colunasSelecionadas = $colunasPermitidas; } else { $colunasSelecionadas = $colunasPermitidas; if (isset($mapaColunasSql['nome'])) array_unshift($colunasSelecionadas, 'nome'); elseif (isset($mapaColunasSql['id'])) array_unshift($colunasSelecionadas, 'id'); } }
    }
    $colunasFinais = array_intersect($colunasSelecionadas, array_keys($mapaColunasSql)); $colunasFinais = array_unique($colunasFinais);
    if (empty($colunasFinais)) { $colunasFinaisFallback = ['nome', 'idade', 'refeicoes']; $colunasFinais = array_intersect($colunasFinaisFallback, array_keys($mapaColunasSql)); if (empty($colunasFinais) && isset($mapaColunasSql['nome'])) $colunasFinais = ['nome']; elseif (empty($colunasFinais) && isset($mapaColunasSql['id'])) $colunasFinais = ['id']; }

    $selectParts = []; foreach ($colunasFinais as $col) { if (isset($mapaColunasSql[$col])) $selectParts[] = $mapaColunasSql[$col]; }
    if (empty($selectParts)) { error_log("Relatório Consumo: Nenhuma coluna válida encontrada para seleção."); return []; }
    $selectClause = implode(", ", $selectParts);

    $query = "SELECT $selectClause FROM participantes p LEFT JOIN consumo_alimentar ca ON p.id = ca.participante_id WHERE 1=1";
    $params = [];
    if (isset($filtros['genero']) && $filtros['genero'] !== '') { $query .= " AND p.genero = :genero"; $params[':genero'] = $filtros['genero']; }
    if (isset($filtros['idade_min']) && $filtros['idade_min'] !== '' && $filtros['idade_min'] !== null) { $query .= " AND p.idade >= :idade_min"; $params[':idade_min'] = $filtros['idade_min']; }
    if (isset($filtros['idade_max']) && $filtros['idade_max'] !== '' && $filtros['idade_max'] !== null) { $query .= " AND p.idade <= :idade_max"; $params[':idade_max'] = $filtros['idade_max']; }
    if (isset($filtros['raca']) && $filtros['raca'] !== '') { $query .= " AND p.raca = :raca"; $params[':raca'] = $filtros['raca']; }
    if (isset($filtros['escolaridade']) && $filtros['escolaridade'] !== '') { $query .= " AND p.escolaridade = :escolaridade"; $params[':escolaridade'] = $filtros['escolaridade']; }
    if (isset($filtros['estado_civil']) && $filtros['estado_civil'] !== '') { $query .= " AND p.estado_civil = :estado_civil"; $params[':estado_civil'] = $filtros['estado_civil']; }
    if (isset($filtros['situacao_emprego']) && $filtros['situacao_emprego'] !== '') { $query .= " AND p.situacao_emprego = :situacao_emprego"; $params[':situacao_emprego'] = $filtros['situacao_emprego']; }
    if (isset($filtros['religiao']) && $filtros['religiao'] !== '') { $query .= " AND p.religiao = :religiao"; $params[':religiao'] = $filtros['religiao']; }

    $stmt = $conexao->prepare($query);
    foreach ($params as $key => $value) { $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR; $stmt->bindValue($key, $value, $type); }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Gera relatório de perfil dos participantes.
 * (Função gerar_relatorio_perfil - SEM ALTERAÇÕES NECESSÁRIAS PARA ESTA ETAPA)
 * ... (código da função inalterado) ...
 */
function gerar_relatorio_perfil($conexao, $filtros = [], $colunasVisiveis = []) {
    $mapaColunasSql = [
        'id' => 'id AS id', 'nome' => 'nome AS nome',
        'idade' => 'idade AS idade', 'genero' => 'genero AS genero',
        'genero_outro' => 'genero_outro AS genero_outro', 'raca' => 'raca AS raca',
        'raca_outro' => 'raca_outro AS raca_outro', 'escolaridade' => 'escolaridade AS escolaridade',
        'escolaridade_outro' => 'escolaridade_outro AS escolaridade_outro', 'estado_civil' => 'estado_civil AS estado_civil',
        'situacao_emprego' => 'situacao_emprego AS situacao_emprego',
        'situacao_emprego_outro' => 'situacao_emprego_outro AS situacao_emprego_outro',
        'beneficios_sociais' => 'beneficios_sociais AS beneficios_sociais',
        'numero_dependentes' => 'numero_dependentes AS numero_dependentes',
        'religiao' => 'religiao AS religiao',
        'religiao_outro' => 'religiao_outro AS religiao_outro'
    ];
    $colunasPadrao = [
        'id', 'idade', 'genero', 'raca', 'escolaridade', 'estado_civil',
        'situacao_emprego', 'religiao', 'numero_dependentes', 'beneficios_sociais'
    ];
    $colunasProibidas = ['email', 'senha'];

    $colunasParaSelecionar = [];
    if (empty($colunasVisiveis)) { $colunasParaSelecionar = $colunasPadrao; }
    else {
        $colunasSolicitadasSeguras = array_diff($colunasVisiveis, $colunasProibidas); $colunasSolicitadasSeguras = array_intersect($colunasSolicitadasSeguras, array_keys($mapaColunasSql));
        $colsComOutro = ['genero', 'raca', 'escolaridade', 'situacao_emprego', 'religiao'];
        foreach($colsComOutro as $colBase) { if (in_array($colBase, $colunasSolicitadasSeguras)) { $colOutro = $colBase . '_outro'; if (isset($mapaColunasSql[$colOutro]) && !in_array($colOutro, $colunasSolicitadasSeguras)) $colunasSolicitadasSeguras[] = $colOutro; } }
        if (empty($colunasSolicitadasSeguras)) { if (isset($mapaColunasSql['nome'])) $colunasParaSelecionar = ['nome', 'idade', 'genero']; elseif (isset($mapaColunasSql['id'])) $colunasParaSelecionar = ['id', 'idade', 'genero']; else $colunasParaSelecionar = []; }
        else { $colunasParaSelecionar = $colunasSolicitadasSeguras; }
    }
    $selectParts = []; $identificadorPresente = false; if (in_array('nome', $colunasParaSelecionar)) $identificadorPresente = true; if (!$identificadorPresente && in_array('id', $colunasParaSelecionar)) $identificadorPresente = true;
    if (!$identificadorPresente) { if (isset($mapaColunasSql['nome'])) array_unshift($colunasParaSelecionar, 'nome'); elseif (isset($mapaColunasSql['id'])) array_unshift($colunasParaSelecionar, 'id'); }
    $colunasParaSelecionar = array_unique($colunasParaSelecionar);
    foreach ($colunasParaSelecionar as $col) { if (isset($mapaColunasSql[$col])) $selectParts[] = $mapaColunasSql[$col]; }
    if (empty($selectParts)) { error_log("Relatório Perfil: Nenhuma coluna válida encontrada para seleção."); return []; } else { $selectClause = implode(", ", $selectParts); }

    $query = "SELECT $selectClause FROM participantes WHERE 1=1";
    $params = [];
    if (isset($filtros['idade_min']) && $filtros['idade_min'] !== '' && $filtros['idade_min'] !== null) { $query .= " AND idade >= :idade_min"; $params[':idade_min'] = $filtros['idade_min']; }
    if (isset($filtros['idade_max']) && $filtros['idade_max'] !== '' && $filtros['idade_max'] !== null) { $query .= " AND idade <= :idade_max"; $params[':idade_max'] = $filtros['idade_max']; }
    if (isset($filtros['genero']) && $filtros['genero'] !== '') { $query .= " AND genero = :genero"; $params[':genero'] = $filtros['genero']; }
    if (isset($filtros['raca']) && $filtros['raca'] !== '') { $query .= " AND raca = :raca"; $params[':raca'] = $filtros['raca']; }
    if (isset($filtros['escolaridade']) && $filtros['escolaridade'] !== '') { $query .= " AND escolaridade = :escolaridade"; $params[':escolaridade'] = $filtros['escolaridade']; }
    if (isset($filtros['estado_civil']) && $filtros['estado_civil'] !== '') { $query .= " AND estado_civil = :estado_civil"; $params[':estado_civil'] = $filtros['estado_civil']; }
    if (isset($filtros['situacao_emprego']) && $filtros['situacao_emprego'] !== '') { $query .= " AND situacao_emprego = :situacao_emprego"; $params[':situacao_emprego'] = $filtros['situacao_emprego']; }
    if (isset($filtros['religiao']) && $filtros['religiao'] !== '') { $query .= " AND religiao = :religiao"; $params[':religiao'] = $filtros['religiao']; }

    $stmt = $conexao->prepare($query);
    foreach ($params as $key => $value) { $type = (strpos($key, 'idade') !== false) ? PDO::PARAM_INT : PDO::PARAM_STR; $stmt->bindValue($key, $value, $type); }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**
 * Gera o relatório completo combinando dados das três tabelas principais.
 * (Função gerar_relatorio_completo - SEM ALTERAÇÕES NECESSÁRIAS PARA ESTA ETAPA)
 * ... (código da função inalterado, pois ela já busca os dados necessários) ...
 */
function gerar_relatorio_completo($conexao, $filtros = []) {
    $selectClause = "
        p.id AS participante_id, p.nome AS participante_nome, p.email AS participante_email,
        p.idade AS participante_idade, p.genero AS participante_genero, p.genero_outro AS participante_genero_outro,
        p.raca AS participante_raca, p.raca_outro AS participante_raca_outro,
        p.escolaridade AS participante_escolaridade, p.escolaridade_outro AS participante_escolaridade_outro,
        p.estado_civil AS participante_estado_civil, p.situacao_emprego AS participante_situacao_emprego,
        p.situacao_emprego_outro AS participante_situacao_emprego_outro, p.beneficios_sociais AS participante_beneficios_sociais,
        p.numero_dependentes AS participante_numero_dependentes, p.religiao AS participante_religiao,
        p.religiao_outro AS participante_religiao_outro, p.data_cadastro AS participante_data_cadastro,

        ca.refeicoes AS consumo_refeicoes, ca.usa_dispositivos AS consumo_usa_dispositivos,
        ca.feijao AS consumo_feijao, ca.frutas_frescas AS consumo_frutas_frescas,
        ca.verduras_legumes AS consumo_verduras_legumes, ca.hamburguer_embutidos AS consumo_hamburguer_embutidos,
        ca.bebidas_adocadas AS consumo_bebidas_adocadas, ca.macarrao_instantaneo AS consumo_macarrao_instantaneo,
        ca.biscoitos_recheados AS consumo_biscoitos_recheados, ca.data_preenchimento AS consumo_data_preenchimento,

        qe.resposta1 AS ebia_resposta1, qe.resposta2 AS ebia_resposta2, qe.resposta3 AS ebia_resposta3,
        qe.resposta4 AS ebia_resposta4, qe.resposta5 AS ebia_resposta5, qe.resposta6 AS ebia_resposta6,
        qe.resposta7 AS ebia_resposta7, qe.resposta8 AS ebia_resposta8,
        qe.pontuacao_total AS ebia_pontuacao_total, qe.classificacao AS ebia_classificacao,
        qe.data_preenchimento AS ebia_data_preenchimento
    ";

    $query = "SELECT {$selectClause}
              FROM participantes p
              LEFT JOIN consumo_alimentar ca ON ca.id = (
                  SELECT MAX(ca_inner.id)
                  FROM consumo_alimentar ca_inner
                  WHERE ca_inner.participante_id = p.id
              )
              LEFT JOIN questionarios_ebia qe ON qe.id = (
                  SELECT MAX(qe_inner.id)
                  FROM questionarios_ebia qe_inner
                  WHERE qe_inner.participante_id = p.id
              )
              WHERE 1=1";

    $params = [];
    if (isset($filtros['genero']) && $filtros['genero'] !== '') { $query .= " AND p.genero = :genero"; $params[':genero'] = $filtros['genero']; }
    if (isset($filtros['idade_min']) && $filtros['idade_min'] !== '' && $filtros['idade_min'] !== null) { $query .= " AND p.idade >= :idade_min"; $params[':idade_min'] = $filtros['idade_min']; }
    if (isset($filtros['idade_max']) && $filtros['idade_max'] !== '' && $filtros['idade_max'] !== null) { $query .= " AND p.idade <= :idade_max"; $params[':idade_max'] = $filtros['idade_max']; }
    if (isset($filtros['raca']) && $filtros['raca'] !== '') { $query .= " AND p.raca = :raca"; $params[':raca'] = $filtros['raca']; }
    if (isset($filtros['escolaridade']) && $filtros['escolaridade'] !== '') { $query .= " AND p.escolaridade = :escolaridade"; $params[':escolaridade'] = $filtros['escolaridade']; }
    if (isset($filtros['estado_civil']) && $filtros['estado_civil'] !== '') { $query .= " AND p.estado_civil = :estado_civil"; $params[':estado_civil'] = $filtros['estado_civil']; }
    if (isset($filtros['situacao_emprego']) && $filtros['situacao_emprego'] !== '') { $query .= " AND p.situacao_emprego = :situacao_emprego"; $params[':situacao_emprego'] = $filtros['situacao_emprego']; }
    if (isset($filtros['religiao']) && $filtros['religiao'] !== '') { $query .= " AND p.religiao = :religiao"; $params[':religiao'] = $filtros['religiao']; }

    $stmt = $conexao->prepare($query);
    foreach ($params as $key => $value) {
        $type = (strpos($key, 'idade') !== false) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// =========================================================================
// ===== MODIFICAÇÃO PRINCIPAL: preparar_dados_graficos_completo =========
// =========================================================================
/**
 * Prepara dados agregados para gráficos consolidados (EBIA Stacked, Consumo Agrupado)
 * e gráficos demográficos individuais.
 */
function preparar_dados_graficos_completo(array $dadosRelatorio, array $colunasDisponiveis, array $filtros): array {
    $graficos = [];
    $totalRegistros = count($dadosRelatorio);
    if ($totalRegistros === 0) return [];

    // Mapeamentos ENUM/Textos (reutilizados)
    $mapClassificacaoEbiaText = ['seguranca_alimentar' => 'Segurança Alimentar', 'inseguranca_leve' => 'Insegurança Leve', 'inseguranca_moderada' => 'Insegurança Moderada', 'inseguranca_grave' => 'Insegurança Grave'];
    // ... (outros maps: genero, raca, etc. - podem ser adicionados se necessários para tooltips ou formatação)

    // --- 1. Gráficos Demográficos Individuais (Mantidos como antes) ---
    $colsDemograficas = ['participante_genero', 'participante_raca', 'participante_escolaridade', 'participante_situacao_emprego', 'participante_religiao', 'participante_estado_civil', 'participante_numero_dependentes', 'participante_beneficios_sociais'];
    $mapTitulosDemograficos = [ /* ... (como na versão anterior) ... */
        'genero' => 'Distribuição por Gênero', 'raca' => 'Distribuição por Raça/Cor',
        'escolaridade' => 'Distribuição por Escolaridade', 'situacao_emprego' => 'Distribuição por Situação de Emprego',
        'religiao' => 'Distribuição por Religião', 'estado_civil' => 'Distribuição por Estado Civil',
        'numero_dependentes' => 'Distribuição por Nº de Dependentes',
        'beneficios_sociais' => 'Benefícios Sociais Recebidos (Frequência)',
    ];

    // Função interna genérica para contagem (similar à anterior, mas adaptada para flexibilidade)
    $gerarContagemSimples = function($colunaComPrefixo) use ($dadosRelatorio) {
        if (!isset($dadosRelatorio[0][$colunaComPrefixo])) return null;
        $valoresColuna = array_column($dadosRelatorio, $colunaComPrefixo);
        $valoresValidos = array_filter($valoresColuna, fn($val) => !is_null($val) && $val !== '');
        if (empty($valoresValidos)) return null;

        $contagem = [];
        $colunaLimpa = preg_replace('/^(participante_|consumo_|ebia_)/', '', $colunaComPrefixo);

        foreach ($valoresValidos as $valor) {
             if ($colunaLimpa === 'beneficios_sociais' && is_string($valor) && ($json = json_decode($valor, true)) !== null && json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                 // Lógica JSON para benefícios (como antes)
                 if (!empty($json)) {
                    $textoOutros = isset($json['Outros']) && is_string($json['Outros']) && !empty(trim($json['Outros'])) ? trim($json['Outros']) : null;
                    $beneficiosIndividuais = 0;
                    foreach ($json as $key => $item) {
                        if ($key === 'Outros') continue;
                        if (is_string($item)) {
                            $chave = trim($item); if (!empty($chave)) { $contagem[$chave] = ($contagem[$chave] ?? 0) + 1; $beneficiosIndividuais++; }
                        }
                    }
                    if ($textoOutros !== null && ($beneficiosIndividuais > 0 || count($json) === 1)) {
                         $chaveOutros = 'Outros Benefícios (detalhado)'; $contagem[$chaveOutros] = ($contagem[$chaveOutros] ?? 0) + 1;
                    }
                 }
             } else {
                  $chave = is_scalar($valor) ? trim((string)$valor) : '[Valor Não Escalar]';
                  if ($chave !== '') { $contagem[$chave] = ($contagem[$chave] ?? 0) + 1; }
             }
        }
        if (empty($contagem)) return null;
        arsort($contagem); // Ordena por frequência
        $originalLabels = array_keys($contagem);
        $data = array_values($contagem);
        // Mapeia labels originais para formatados (simplificado)
        $labels = array_map(function($orig) {
             if ($orig === 'Outros Benefícios (detalhado)') return 'Outros Benefícios';
             return is_numeric($orig) ? $orig : ucfirst(str_replace('_', ' ', $orig));
        }, $originalLabels);

        $totalContagem = array_sum($data);
        $percentuais = $totalContagem > 0 ? array_map(fn($c) => round(($c / $totalContagem) * 100, 1), $data) : [];
        return ['labels' => $labels, 'data' => $data, 'originalLabels' => $originalLabels, 'percentuais' => $percentuais];
    };

    foreach ($colsDemograficas as $colGraf) {
        if (in_array($colGraf, $colunasDisponiveis)) {
            $dadosGrafico = $gerarContagemSimples($colGraf);
            if ($dadosGrafico && !empty($dadosGrafico['labels'])) {
                $colunaLimpa = preg_replace('/^(participante_|consumo_|ebia_)/', '', $colGraf);
                $titulo = $mapTitulosDemograficos[$colunaLimpa] ?? 'Distribuição por ' . ucfirst(str_replace('_', ' ', $colunaLimpa));
                if (!empty($filtros[$colunaLimpa])) $titulo .= ' (Filtrado)';
                // Chave para o array de gráficos continua sendo a coluna limpa
                $graficos[$colunaLimpa] = $dadosGrafico + ['titulo' => $titulo, 'chaveOriginal' => $colGraf];
            }
        }
    }

    // --- 2. Gráfico de Classificação EBIA (Mantido como antes) ---
    $colEbiaClassificacao = 'ebia_classificacao';
    if (in_array($colEbiaClassificacao, $colunasDisponiveis)) {
         $dadosGraficoClassif = $gerarContagemSimples($colEbiaClassificacao);
         if ($dadosGraficoClassif && !empty($dadosGraficoClassif['originalLabels'])) {
             $order = ['seguranca_alimentar' => 1, 'inseguranca_leve' => 2, 'inseguranca_moderada' => 3, 'inseguranca_grave' => 4];
             // Reordena os dados com base na ordem definida
             $orderedData = [];
             foreach($dadosGraficoClassif['originalLabels'] as $index => $labelOrig) {
                 $orderedData[$labelOrig] = [
                     'label' => $mapClassificacaoEbiaText[$labelOrig] ?? ucfirst(str_replace('_', ' ', $labelOrig)),
                     'originalLabel' => $labelOrig,
                     'data' => $dadosGraficoClassif['data'][$index],
                     'percentual' => $dadosGraficoClassif['percentuais'][$index] ?? 0,
                     'order' => $order[$labelOrig] ?? 99
                 ];
             }
             uasort($orderedData, fn($a, $b) => $a['order'] <=> $b['order']);

             $graficos['classificacao'] = [
                 'labels' => array_column($orderedData, 'label'),
                 'data' => array_column($orderedData, 'data'),
                 'originalLabels' => array_column($orderedData, 'originalLabel'),
                 'percentuais' => array_column($orderedData, 'percentual'),
                 'titulo' => 'Distribuição por Classificação EBIA',
                 'chaveOriginal' => $colEbiaClassificacao
             ];
         }
    }

    // --- 3. NOVO: Gráfico EBIA Respostas (Stacked Bar Percentual) ---
    $colsEbiaRespostas = [];
    $ebiaLabels = [];
    for ($i = 1; $i <= 8; $i++) {
        $col = 'ebia_resposta' . $i;
        if (in_array($col, $colunasDisponiveis)) {
            $colsEbiaRespostas[] = $col;
            $ebiaLabels[] = 'Q' . $i; // Label curto para o eixo X
        }
    }

    if (!empty($colsEbiaRespostas)) {
        $dataSimPct = [];
        $dataNaoPct = [];
        $originalKeysEbia = []; // Para saber qual coluna foi clicada

        foreach ($colsEbiaRespostas as $col) {
            $valores = array_column($dadosRelatorio, $col);
            $validos = array_filter($valores, fn($v) => !is_null($v) && $v !== ''); // Conta nulos/vazios? Aqui não.
            $totalValidos = count($validos);
            $contagem = !empty($validos) ? array_count_values($validos) : [1 => 0, 0 => 0];

            $simCount = $contagem[1] ?? $contagem['1'] ?? 0;
            $naoCount = $contagem[0] ?? $contagem['0'] ?? 0;

            // Se não houver respostas válidas para a pergunta, considera 0%
            $simPct = ($totalValidos > 0) ? round(($simCount / $totalValidos) * 100, 1) : 0;
            $naoPct = ($totalValidos > 0) ? round(($naoCount / $totalValidos) * 100, 1) : 0;

            // Garante que a soma seja ~100% em caso de arredondamento
             if ($totalValidos > 0 && abs(($simPct + $naoPct) - 100) > 0.1) {
                // Ajuste simples: atribui o restante ao 'Não' (ou 'Sim', dependendo da lógica preferida)
                $naoPct = 100 - $simPct;
             } elseif ($totalValidos == 0) {
                $naoPct = 100; // Se não há dados, mostra 100% Não (ou outra convenção)
             }


            $dataSimPct[] = $simPct;
            $dataNaoPct[] = $naoPct;
            $originalKeysEbia[] = $col; // Guarda a chave original (ex: 'ebia_resposta1')
        }

        $graficos['ebia_respostas_stacked'] = [
            'labels' => $ebiaLabels, // ['Q1', 'Q2', ...]
            'datasets' => [
                ['label' => 'Sim (%)', 'data' => $dataSimPct, 'originalValue' => 1], // Valor clicado é 1
                ['label' => 'Não (%)', 'data' => $dataNaoPct, 'originalValue' => 0]  // Valor clicado é 0
            ],
            'originalKeys' => $originalKeysEbia, // Array de chaves originais ['ebia_resposta1', ...]
            'titulo' => 'Percentual de Respostas "Sim" por Pergunta EBIA',
            // Não tem uma única 'chaveOriginal' aqui, é tratado no JS
        ];
    }

    // --- 4. NOVO: Gráficos Consumo Alimentar (Barras Percentuais Separadas) ---
    $mapConsumoItens = [
        'recomendados' => [
            'consumo_feijao' => 'Feijão',
            'consumo_frutas_frescas' => 'Frutas Frescas',
            'consumo_verduras_legumes' => 'Verduras/Legumes',
        ],
        'ultraprocessados' => [
            'consumo_hamburguer_embutidos' => 'Hamb./Embutidos',
            'consumo_bebidas_adocadas' => 'Beb. Adoçadas',
            'consumo_macarrao_instantaneo' => 'Macarrão Inst./Salgad.',
            'consumo_biscoitos_recheados' => 'Bisc. Recheados/Doces',
        ]
    ];

    foreach ($mapConsumoItens as $grupoKey => $itens) {
        $labelsConsumo = [];
        $dataConsumoPct = [];
        $originalKeysConsumo = [];
        $hasData = false;

        foreach ($itens as $col => $label) {
            if (in_array($col, $colunasDisponiveis)) {
                $valores = array_column($dadosRelatorio, $col);
                $validos = array_filter($valores, fn($v) => !is_null($v) && $v !== '');
                $totalValidos = count($validos); // Total que respondeu (não o total geral)
                $contagem = !empty($validos) ? array_count_values($validos) : [1 => 0];

                $simCount = $contagem[1] ?? $contagem['1'] ?? 0;
                // Percentual em relação ao TOTAL de registros no relatório, não só quem respondeu
                $simPct = ($totalRegistros > 0) ? round(($simCount / $totalRegistros) * 100, 1) : 0;

                $labelsConsumo[] = $label;
                $dataConsumoPct[] = $simPct;
                $originalKeysConsumo[] = $col;
                if ($simPct > 0) $hasData = true;
            }
        }

        if ($hasData) {
            $tituloConsumo = ($grupoKey === 'recomendados')
                ? 'Consumo de Alimentos Recomendados (%)'
                : 'Consumo de Alimentos Ultraprocessados (%)';

            $graficos['consumo_' . $grupoKey] = [
                'labels' => $labelsConsumo,
                'data' => $dataConsumoPct,
                'originalLabels' => $labelsConsumo, // Label formatado pode ser usado aqui
                'originalKeys' => $originalKeysConsumo, // Chaves originais das colunas
                'percentuais' => $dataConsumoPct, // Reutiliza os dados percentuais
                'titulo' => $tituloConsumo,
                'chaveOriginal' => null // Não aplicável diretamente, tratado no JS
            ];
        }
    }

    // --- 5. Gráfico Refeições e Uso Dispositivos (Mantidos como antes, se necessário) ---
    $colRefeicoes = 'consumo_refeicoes';
    if (in_array($colRefeicoes, $colunasDisponiveis)) {
         $dadosGraficoRef = $gerarContagemSimples($colRefeicoes); // Usa a função genérica
         if ($dadosGraficoRef && !empty($dadosGraficoRef['labels'])) {
              // Processamento especial para refeições (contar ocorrências de cada item)
              $contagemRef = [];
              $nomesRefeicoes = ["Café da manhã", "Lanche da manhã", "Almoço", "Lanche da tarde", "Jantar", "Ceia/lanche da noite"];
              foreach($nomesRefeicoes as $nr) $contagemRef[$nr] = 0;

              foreach($dadosRelatorio as $linha) {
                  if(isset($linha[$colRefeicoes]) && is_string($linha[$colRefeicoes])) {
                      $refs = explode(',', $linha[$colRefeicoes]);
                      foreach($refs as $r) {
                          $rLimp = trim($r);
                          if(isset($contagemRef[$rLimp])) $contagemRef[$rLimp]++;
                      }
                  }
              }
              $contagemRef = array_filter($contagemRef); // Remove refeições com 0 ocorrências
              if(!empty($contagemRef)) {
                  arsort($contagemRef);
                  $totalOcorrencias = array_sum($contagemRef);
                  $percentuaisRef = $totalOcorrencias > 0 ? array_map(fn($c)=> round(($c/$totalOcorrencias)*100, 1), $contagemRef) : [];
                  $graficos['refeicoes'] = [
                      'labels' => array_keys($contagemRef),
                      'data' => array_values($contagemRef),
                      'originalLabels' => array_keys($contagemRef), // Valor original é o próprio nome da refeição
                      'percentuais' => $percentuaisRef,
                      'titulo' => 'Refeições Realizadas (Frequência de Menção)',
                      'chaveOriginal' => $colRefeicoes
                  ];
              }
         }
    }
    $colUsaDisp = 'consumo_usa_dispositivos';
    if (in_array($colUsaDisp, $colunasDisponiveis)) {
        $dadosGraficoDisp = $gerarContagemSimples($colUsaDisp);
         if ($dadosGraficoDisp && !empty($dadosGraficoDisp['labels'])) {
            // Garante labels Sim/Não
            $countSim = 0; $countNao = 0;
            foreach($dadosGraficoDisp['originalLabels'] as $idx => $origLabel) {
                if(in_array($origLabel, [1, '1', true], true)) $countSim += $dadosGraficoDisp['data'][$idx];
                if(in_array($origLabel, [0, '0', false], true)) $countNao += $dadosGraficoDisp['data'][$idx];
            }
            $totalDisp = $countSim + $countNao;
            if ($totalDisp > 0) {
                 $labelsDisp = []; $dataDisp = []; $origLabelsDisp = []; $pctsDisp = [];
                 if($countSim > 0) { $labelsDisp[]='Sim'; $dataDisp[]=$countSim; $origLabelsDisp[]=1; $pctsDisp[]=round(($countSim/$totalDisp)*100,1); }
                 if($countNao > 0) { $labelsDisp[]='Não'; $dataDisp[]=$countNao; $origLabelsDisp[]=0; $pctsDisp[]=round(($countNao/$totalDisp)*100,1); }
                 if(!empty($labelsDisp)){
                     $graficos['usa_dispositivos'] = [
                        'labels' => $labelsDisp, 'data' => $dataDisp, 'originalLabels' => $origLabelsDisp, 'percentuais' => $pctsDisp,
                        'titulo' => 'Uso de Dispositivos na Refeição', 'chaveOriginal' => $colUsaDisp
                     ];
                 }
            }
        }
    }


    return $graficos;
}


?>