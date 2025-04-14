<?php
// Inclui o arquivo de conexão com o banco de dados
// include __DIR__ . '/conexao.php'; // O include já está no relatorios.php, remover daqui se não for usado em outro lugar

/**
 * Gera relatório de insegurança alimentar com base nos filtros e colunas.
 * MODIFICADO: Prioriza a coluna 'nome' em vez de 'id' como identificador principal.
 *
 * @param PDO $conexao Objeto de conexão PDO.
 * @param array $filtros Filtros a serem aplicados (ex: ['idade_min' => 18]).
 * @param array $colunasVisiveis (Opcional) Array com os nomes das colunas desejadas (ex: ['nome', 'idade', 'classificacao']). Se vazio, usa um padrão.
 * @return array Retorna um array associativo com os resultados.
 * @throws PDOException Em caso de erro na consulta.
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
    // Colunas proibidas de serem retornadas (mesmo se solicitadas)
    $colunasProibidas = ['email', 'senha'];

    // --- Define as colunas a serem selecionadas ---
    $colunasSelecionadas = [];

    // 1. Define as colunas padrão desejadas, priorizando 'nome' sobre 'id'
    $colunasPadraoDefault = [
        'nome', // Identificador padrão
        'idade', 'genero', 'raca', 'escolaridade',
        'pontuacao_total', 'classificacao',
        'resposta1', 'resposta2', 'resposta3', 'resposta4', // Adicionando respostas EBIA ao padrão
        'resposta5', 'resposta6', 'resposta7', 'resposta8',
        'data_preenchimento'
    ];

    if (empty($colunasVisiveis)) {
        // Se nenhuma coluna específica foi pedida, use o padrão
        $colunasSelecionadas = $colunasPadraoDefault;
    } else {
        // Se colunas específicas foram pedidas:
        // Primeiro, remova as proibidas da lista solicitada
        $colunasPermitidas = array_diff($colunasVisiveis, $colunasProibidas);

        // Verifique se 'nome' foi pedido
        $nomePresente = in_array('nome', $colunasPermitidas);

        if ($nomePresente) {
            // Se 'nome' foi pedido, use as colunas permitidas, mas REMOVA 'id' para substituí-lo.
            $colunasSelecionadas = array_diff($colunasPermitidas, ['id']);
             // Garante que 'nome' esteja presente, caso array_diff o remova por engano
             if (!in_array('nome', $colunasSelecionadas) && isset($mapaColunas['nome'])) {
                 array_unshift($colunasSelecionadas, 'nome');
             }
        } else {
            // Se 'nome' NÃO foi pedido:
            // Verifique se 'id' foi pedido
            $idPresente = in_array('id', $colunasPermitidas);
            if ($idPresente) {
                // Se 'id' foi pedido (e 'nome' não), mantenha as colunas permitidas (incluindo 'id')
                $colunasSelecionadas = $colunasPermitidas;
            } else {
                // Se NEM 'nome' NEM 'id' foram pedidos, adicione 'nome' como identificador padrão
                $colunasSelecionadas = $colunasPermitidas;
                // Adiciona 'nome' no início da lista se ele for uma coluna válida
                 if (isset($mapaColunas['nome'])) {
                    array_unshift($colunasSelecionadas, 'nome');
                 } elseif (isset($mapaColunas['id'])) {
                     // Fallback: Se 'nome' não for válido por algum motivo, adiciona 'id'
                     array_unshift($colunasSelecionadas, 'id');
                 }
            }
        }
    }

    // Garante que apenas colunas válidas (existentes no mapa) sejam usadas
    $colunasFinais = array_intersect($colunasSelecionadas, array_keys($mapaColunas));
    $colunasFinais = array_unique($colunasFinais); // Garante que não haja duplicatas

    // Fallback final: se por algum motivo a lista ficar vazia, usa um mínimo seguro com 'nome'
    if (empty($colunasFinais)) {
         // Tenta usar nome, idade, classificacao
         $colunasFinaisFallback = ['nome', 'idade', 'classificacao'];
         $colunasFinais = array_intersect($colunasFinaisFallback, array_keys($mapaColunas));
         // Se ainda assim estiver vazio (improvável), tenta só 'nome'
         if (empty($colunasFinais) && isset($mapaColunas['nome'])) {
             $colunasFinais = ['nome'];
         } elseif (empty($colunasFinais) && isset($mapaColunas['id'])) {
             // Ou só 'id' como último recurso
             $colunasFinais = ['id'];
         }
         // Se nem 'nome' nem 'id' existirem no mapa, $colunasFinais ficará vazio e a query falhará (o que é esperado)
    }


    // --- Monta a cláusula SELECT ---
    $selectParts = [];
    foreach ($colunasFinais as $col) {
        // Verifica novamente se a coluna existe no mapa (redundância segura)
        if (isset($mapaColunas[$col])) {
            $alias = $mapaColunas[$col];
            // Gera a parte do SELECT com alias para a coluna final (ex: p.nome AS nome)
            $selectParts[] = "$alias.$col AS $col";
        }
    }

    // Se $selectParts estiver vazio, lança um erro ou retorna vazio, pois não há colunas válidas para selecionar.
    if (empty($selectParts)) {
        // Ou lance uma exceção: throw new Exception("Nenhuma coluna válida para selecionar.");
        error_log("Relatório EBIA: Nenhuma coluna válida encontrada para seleção.");
        return []; // Retorna array vazio
    }
    $selectClause = implode(", ", $selectParts);


    // --- Monta a Query Base ---
    $query = "SELECT $selectClause
              FROM participantes p
              JOIN questionarios_ebia q ON p.id = q.participante_id
              WHERE 1=1"; // Usa JOIN pois dados EBIA são necessários
    $params = [];

    // Adiciona filtros opcionais (usando as chaves corretas dos filtros e alias da tabela 'p')
    if (isset($filtros['genero']) && $filtros['genero'] !== '') {
        $query .= " AND p.genero = :genero";
        $params[':genero'] = $filtros['genero'];
    }
    if (isset($filtros['idade_min']) && $filtros['idade_min'] !== '' && $filtros['idade_min'] !== null) {
        $query .= " AND p.idade >= :idade_min";
        $params[':idade_min'] = $filtros['idade_min'];
    }
    if (isset($filtros['idade_max']) && $filtros['idade_max'] !== '' && $filtros['idade_max'] !== null) {
        $query .= " AND p.idade <= :idade_max";
        $params[':idade_max'] = $filtros['idade_max'];
    }
    if (isset($filtros['raca']) && $filtros['raca'] !== '') {
        $query .= " AND p.raca = :raca";
        $params[':raca'] = $filtros['raca'];
    }
    if (isset($filtros['escolaridade']) && $filtros['escolaridade'] !== '') {
        $query .= " AND p.escolaridade = :escolaridade";
        $params[':escolaridade'] = $filtros['escolaridade'];
    }
    if (isset($filtros['estado_civil']) && $filtros['estado_civil'] !== '') {
         $query .= " AND p.estado_civil = :estado_civil";
         $params[':estado_civil'] = $filtros['estado_civil'];
     }
    if (isset($filtros['situacao_emprego']) && $filtros['situacao_emprego'] !== '') {
        $query .= " AND p.situacao_emprego = :situacao_emprego";
        $params[':situacao_emprego'] = $filtros['situacao_emprego'];
    }
    if (isset($filtros['religiao']) && $filtros['religiao'] !== '') {
        $query .= " AND p.religiao = :religiao";
        $params[':religiao'] = $filtros['religiao'];
    }

    // --- Prepara e Executa ---
    $stmt = $conexao->prepare($query);

    // Vincula os parâmetros dos filtros (Bind genérico)
    foreach ($params as $key => $value) {
        $type = PDO::PARAM_STR; // Default
        if (strpos($key, 'idade') !== false) { // Se for idade, usa INT
            $type = PDO::PARAM_INT;
        }
        $stmt->bindValue($key, $value, $type);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC); // Retorna array com chaves simples (nome, idade, etc.)
}


/**
 * Gera relatório de consumo alimentar com base nos filtros e colunas.
 * MODIFICADO: Prioriza a coluna 'nome' em vez de 'id' e remove colunas proibidas.
 * Usa aliases explícitos 'AS' no SELECT para retornar chaves simples.
 * @param PDO $conexao
 * @param array $filtros
 * @param array $colunasVisiveis (Opcional) Colunas desejadas (nomes simples)
 * @return array
 */
function gerar_relatorio_consumo_alimentar($conexao, $filtros = [], $colunasVisiveis = []) {
    // Mapeamento de colunas simples para SQL com alias 'AS'
    $mapaColunasSql = [
        // Participantes (p)
        'id' => 'p.id AS id', 'nome' => 'p.nome AS nome', // Incluído 'nome'
        'idade' => 'p.idade AS idade', 'genero' => 'p.genero AS genero',
        'raca' => 'p.raca AS raca', 'escolaridade' => 'p.escolaridade AS escolaridade',
        'estado_civil' => 'p.estado_civil AS estado_civil', 'situacao_emprego' => 'p.situacao_emprego AS situacao_emprego',
        'beneficios_sociais' => 'p.beneficios_sociais AS beneficios_sociais',
        'numero_dependentes' => 'p.numero_dependentes AS numero_dependentes', 'religiao' => 'p.religiao AS religiao',
        // Consumo Alimentar (ca)
        'refeicoes' => 'ca.refeicoes AS refeicoes', 'usa_dispositivos' => 'ca.usa_dispositivos AS usa_dispositivos',
        'feijao' => 'ca.feijao AS feijao', 'frutas_frescas' => 'ca.frutas_frescas AS frutas_frescas',
        'verduras_legumes' => 'ca.verduras_legumes AS verduras_legumes',
        'hamburguer_embutidos' => 'ca.hamburguer_embutidos AS hamburguer_embutidos',
        'bebidas_adocadas' => 'ca.bebidas_adocadas AS bebidas_adocadas',
        'macarrao_instantaneo' => 'ca.macarrao_instantaneo AS macarrao_instantaneo',
        'biscoitos_recheados' => 'ca.biscoitos_recheados AS biscoitos_recheados',
        'data_preenchimento' => 'ca.data_preenchimento AS data_preenchimento'
    ];

    // Colunas padrão seguras (nomes simples) - PRIORIZAR NOME
    $colunasPadrao = [
        'nome', // <-- Prioridade
        'idade', 'genero', 'raca', 'escolaridade', 'refeicoes', 'usa_dispositivos', 'feijao',
        'frutas_frescas', 'verduras_legumes', 'hamburguer_embutidos', 'bebidas_adocadas',
        'macarrao_instantaneo', 'biscoitos_recheados', 'data_preenchimento'
    ];
     // Colunas proibidas (NÃO incluir 'nome' aqui)
    $colunasProibidas = ['email', 'senha', 'genero_outro', 'raca_outro', 'escolaridade_outro', 'situacao_emprego_outro', 'religiao_outro'];


    // Determina as colunas a serem selecionadas
    $colunasSelecionadas = [];
    if (empty($colunasVisiveis)) {
        $colunasSelecionadas = $colunasPadrao; // Usa padrão com 'nome'
    } else {
        // Remove as proibidas da lista solicitada
        $colunasPermitidas = array_diff($colunasVisiveis, $colunasProibidas);
        $nomePresente = in_array('nome', $colunasPermitidas);

        if ($nomePresente) {
            // Se 'nome' foi pedido, remova 'id' para substituí-lo
            $colunasSelecionadas = array_diff($colunasPermitidas, ['id']);
             // Garante que 'nome' está presente (caso array_diff o tenha removido por engano, improvável)
             if (!in_array('nome', $colunasSelecionadas) && isset($mapaColunasSql['nome'])) {
                 array_unshift($colunasSelecionadas, 'nome');
             }
        } else {
            // Se 'nome' não foi pedido, verifica 'id'
            $idPresente = in_array('id', $colunasPermitidas);
            if ($idPresente) {
                // Se 'id' foi pedido, mantém as colunas permitidas (incluindo 'id')
                $colunasSelecionadas = $colunasPermitidas;
            } else {
                // Se nem 'nome' nem 'id' foram pedidos, adiciona 'nome' como padrão
                $colunasSelecionadas = $colunasPermitidas;
                if (isset($mapaColunasSql['nome'])) {
                    array_unshift($colunasSelecionadas, 'nome');
                } elseif (isset($mapaColunasSql['id'])) { // Fallback para id se 'nome' não for válido
                     array_unshift($colunasSelecionadas, 'id');
                }
            }
        }
    }

    // Garante que apenas colunas válidas (existentes no mapa) sejam usadas
    $colunasFinais = array_intersect($colunasSelecionadas, array_keys($mapaColunasSql));
    $colunasFinais = array_unique($colunasFinais);

    // Fallback final
    if (empty($colunasFinais)) {
         $colunasFinaisFallback = ['nome', 'idade', 'refeicoes']; // Fallback com nome
         $colunasFinais = array_intersect($colunasFinaisFallback, array_keys($mapaColunasSql));
          if (empty($colunasFinais) && isset($mapaColunasSql['nome'])) $colunasFinais = ['nome'];
          elseif (empty($colunasFinais) && isset($mapaColunasSql['id'])) $colunasFinais = ['id'];
    }

    // Monta a cláusula SELECT
    $selectParts = [];
    foreach ($colunasFinais as $col) {
        if (isset($mapaColunasSql[$col])) {
             $selectParts[] = $mapaColunasSql[$col];
        }
    }
    if (empty($selectParts)) {
        error_log("Relatório Consumo: Nenhuma coluna válida encontrada para seleção.");
        return [];
    }
    $selectClause = implode(", ", $selectParts);


    // Query base com LEFT JOIN para incluir participantes mesmo sem dados de consumo
    $query = "SELECT $selectClause
              FROM participantes p
              LEFT JOIN consumo_alimentar ca ON p.id = ca.participante_id
              WHERE 1=1";
    $params = [];

    // Adiciona filtros opcionais (demográficos) - Usa alias da tabela (p.)
     if (isset($filtros['genero']) && $filtros['genero'] !== '') {
        $query .= " AND p.genero = :genero";
        $params[':genero'] = $filtros['genero'];
    }
    if (isset($filtros['idade_min']) && $filtros['idade_min'] !== '' && $filtros['idade_min'] !== null) {
        $query .= " AND p.idade >= :idade_min";
        $params[':idade_min'] = $filtros['idade_min'];
    }
    if (isset($filtros['idade_max']) && $filtros['idade_max'] !== '' && $filtros['idade_max'] !== null) {
        $query .= " AND p.idade <= :idade_max";
        $params[':idade_max'] = $filtros['idade_max'];
    }
    if (isset($filtros['raca']) && $filtros['raca'] !== '') {
        $query .= " AND p.raca = :raca";
        $params[':raca'] = $filtros['raca'];
    }
    if (isset($filtros['escolaridade']) && $filtros['escolaridade'] !== '') {
        $query .= " AND p.escolaridade = :escolaridade";
        $params[':escolaridade'] = $filtros['escolaridade'];
    }
     if (isset($filtros['estado_civil']) && $filtros['estado_civil'] !== '') {
         $query .= " AND p.estado_civil = :estado_civil";
         $params[':estado_civil'] = $filtros['estado_civil'];
     }
    if (isset($filtros['situacao_emprego']) && $filtros['situacao_emprego'] !== '') {
        $query .= " AND p.situacao_emprego = :situacao_emprego";
        $params[':situacao_emprego'] = $filtros['situacao_emprego'];
    }
    if (isset($filtros['religiao']) && $filtros['religiao'] !== '') {
        $query .= " AND p.religiao = :religiao";
        $params[':religiao'] = $filtros['religiao'];
    }

    // Prepara e executa
    $stmt = $conexao->prepare($query);
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
    $stmt->execute();
    // Retorna array associativo com chaves SIMPLES devido aos aliases 'AS'
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Gera relatório de perfil dos participantes.
 * @param PDO $conexao
 * @param array $filtros
 * @param array $colunasVisiveis (Opcional) Colunas desejadas (nomes simples)
 * @return array
 */
function gerar_relatorio_perfil($conexao, $filtros = [], $colunasVisiveis = []) {
     // Mapeamento de colunas simples para SQL com alias 'AS'
     // *** MODIFICADO: Adicionado 'nome' ***
     $mapaColunasSql = [
         'id' => 'id AS id',
         'nome' => 'nome AS nome', // <--- ADICIONADO
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

     // Colunas padrão seguras (nomes simples)
     // Opcional: Poderia adicionar 'nome' aqui também, mas deixar que $colunasVisiveis controle é mais flexível
     $colunasPadrao = [
         'id', 'idade', 'genero', 'raca', 'escolaridade', 'estado_civil',
         'situacao_emprego', 'religiao', 'numero_dependentes', 'beneficios_sociais'
     ];
    // Colunas proibidas
    // *** MODIFICADO: Removido 'nome' ***
    $colunasProibidas = ['email', 'senha'];


    // Determina as colunas a serem selecionadas
    $colunasParaSelecionar = [];
    if (empty($colunasVisiveis)) {
        $colunasParaSelecionar = $colunasPadrao;
    } else {
        // Filtra as colunas solicitadas para garantir que são válidas e seguras
        $colunasSolicitadasSeguras = array_diff($colunasVisiveis, $colunasProibidas);
        $colunasSolicitadasSeguras = array_intersect($colunasSolicitadasSeguras, array_keys($mapaColunasSql));

         // Adiciona colunas '_outro' automaticamente se a coluna base estiver presente
         $colsComOutro = ['genero', 'raca', 'escolaridade', 'situacao_emprego', 'religiao'];
         foreach($colsComOutro as $colBase) {
             if (in_array($colBase, $colunasSolicitadasSeguras)) {
                 // Adiciona apenas se o _outro correspondente existe no mapa e não foi incluído ainda
                 $colOutro = $colBase . '_outro';
                 if (isset($mapaColunasSql[$colOutro]) && !in_array($colOutro, $colunasSolicitadasSeguras)) {
                     $colunasSolicitadasSeguras[] = $colOutro;
                 }
             }
         }

        if (empty($colunasSolicitadasSeguras)) {
            // Fallback se o filtro remover tudo - Tenta usar 'nome' se possível
            if (isset($mapaColunasSql['nome'])) {
                 $colunasParaSelecionar = ['nome', 'idade', 'genero'];
            } elseif (isset($mapaColunasSql['id'])) {
                 $colunasParaSelecionar = ['id', 'idade', 'genero']; // Fallback para id
            } else {
                 $colunasParaSelecionar = []; // Sem identificador seguro
            }
        } else {
            $colunasParaSelecionar = $colunasSolicitadasSeguras;
        }
    }

    // Monta a cláusula SELECT usando o mapeamento com 'AS'
    $selectParts = [];
    // Garante que um identificador (nome ou id) esteja sempre presente se possível
    $identificadorPresente = false;
    if (in_array('nome', $colunasParaSelecionar)) $identificadorPresente = true;
    if (!$identificadorPresente && in_array('id', $colunasParaSelecionar)) $identificadorPresente = true;

    if (!$identificadorPresente) {
         if (isset($mapaColunasSql['nome'])) {
              array_unshift($colunasParaSelecionar, 'nome');
         } elseif (isset($mapaColunasSql['id'])) {
              array_unshift($colunasParaSelecionar, 'id');
         }
    }
     $colunasParaSelecionar = array_unique($colunasParaSelecionar); // Remove duplicatas após adicionar identificador

    foreach ($colunasParaSelecionar as $col) {
        if (isset($mapaColunasSql[$col])) {
            $selectParts[] = $mapaColunasSql[$col];
        }
    }

     if (empty($selectParts)) {
         error_log("Relatório Perfil: Nenhuma coluna válida encontrada para seleção.");
         return [];
     } else {
         $selectClause = implode(", ", $selectParts);
     }

    // Query base
    $query = "SELECT $selectClause FROM participantes WHERE 1=1"; // Sem alias de tabela aqui
    $params = [];

    // Adiciona filtros opcionais (não precisa de alias de tabela aqui)
    if (isset($filtros['idade_min']) && $filtros['idade_min'] !== '' && $filtros['idade_min'] !== null) {
        $query .= " AND idade >= :idade_min"; // >=
        $params[':idade_min'] = $filtros['idade_min'];
    }
    if (isset($filtros['idade_max']) && $filtros['idade_max'] !== '' && $filtros['idade_max'] !== null) {
         $query .= " AND idade <= :idade_max"; // <=
        $params[':idade_max'] = $filtros['idade_max'];
    }
    if (isset($filtros['genero']) && $filtros['genero'] !== '') {
        $query .= " AND genero = :genero";
         $params[':genero'] = $filtros['genero'];
    }
    if (isset($filtros['raca']) && $filtros['raca'] !== '') {
        $query .= " AND raca = :raca";
        $params[':raca'] = $filtros['raca'];
    }
    if (isset($filtros['escolaridade']) && $filtros['escolaridade'] !== '') {
        $query .= " AND escolaridade = :escolaridade";
        $params[':escolaridade'] = $filtros['escolaridade'];
    }
    if (isset($filtros['estado_civil']) && $filtros['estado_civil'] !== '') {
        $query .= " AND estado_civil = :estado_civil";
         $params[':estado_civil'] = $filtros['estado_civil'];
    }
    if (isset($filtros['situacao_emprego']) && $filtros['situacao_emprego'] !== '') {
        $query .= " AND situacao_emprego = :situacao_emprego";
         $params[':situacao_emprego'] = $filtros['situacao_emprego'];
    }
    if (isset($filtros['religiao']) && $filtros['religiao'] !== '') {
        $query .= " AND religiao = :religiao";
         $params[':religiao'] = $filtros['religiao'];
    }
    // Adicione aqui filtros para numero_dependentes ou beneficios_sociais se necessário no futuro

    $stmt = $conexao->prepare($query);

    // Bind genérico
    foreach ($params as $key => $value) {
        $type = PDO::PARAM_STR;
        if (strpos($key, 'idade') !== false) {
            $type = PDO::PARAM_INT;
        }
        $stmt->bindValue($key, $value, $type);
    }

    $stmt->execute();
    // Retorna array associativo com chaves SIMPLES devido aos aliases 'AS'
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Gera o relatório completo combinando dados das três tabelas principais.
 * Usa aliases prefixados (participante_, consumo_, ebia_) para evitar colisão de nomes.
 * @param PDO $conexao
 * @param array $filtros Filtros demográficos aplicados à tabela 'participantes'.
 * @return array
 */
function gerar_relatorio_completo($conexao, $filtros = []) {
    // Seleciona colunas de todas as tabelas, usando aliases para clareza e evitar conflitos.
    // Exclua colunas sensíveis como 'senha'. Adapte a lista conforme necessário.
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

    $query = "SELECT $selectClause
              FROM participantes p
              LEFT JOIN consumo_alimentar ca ON p.id = ca.participante_id
              LEFT JOIN questionarios_ebia qe ON p.id = qe.participante_id
              WHERE 1=1"; // Cláusula base para adicionar filtros

    $params = []; // Array para parâmetros de filtro

    // Adiciona filtros opcionais (aplicados à tabela participantes 'p')
    if (isset($filtros['genero']) && $filtros['genero'] !== '') {
        $query .= " AND p.genero = :genero";
        $params[':genero'] = $filtros['genero'];
    }
    if (isset($filtros['idade_min']) && $filtros['idade_min'] !== '' && $filtros['idade_min'] !== null) {
        $query .= " AND p.idade >= :idade_min";
        $params[':idade_min'] = $filtros['idade_min'];
    }
    if (isset($filtros['idade_max']) && $filtros['idade_max'] !== '' && $filtros['idade_max'] !== null) {
        $query .= " AND p.idade <= :idade_max";
        $params[':idade_max'] = $filtros['idade_max'];
    }
    if (isset($filtros['raca']) && $filtros['raca'] !== '') {
        $query .= " AND p.raca = :raca";
        $params[':raca'] = $filtros['raca'];
    }
    if (isset($filtros['escolaridade']) && $filtros['escolaridade'] !== '') {
        $query .= " AND p.escolaridade = :escolaridade";
        $params[':escolaridade'] = $filtros['escolaridade'];
    }
     if (isset($filtros['estado_civil']) && $filtros['estado_civil'] !== '') {
         $query .= " AND p.estado_civil = :estado_civil";
         $params[':estado_civil'] = $filtros['estado_civil'];
     }
    if (isset($filtros['situacao_emprego']) && $filtros['situacao_emprego'] !== '') {
        $query .= " AND p.situacao_emprego = :situacao_emprego";
        $params[':situacao_emprego'] = $filtros['situacao_emprego'];
    }
    if (isset($filtros['religiao']) && $filtros['religiao'] !== '') {
        $query .= " AND p.religiao = :religiao";
        $params[':religiao'] = $filtros['religiao'];
    }
    // Adicione mais filtros demográficos se necessário

    $stmt = $conexao->prepare($query);

    // Vincula os parâmetros dos filtros (Bind genérico)
    foreach ($params as $key => $value) {
        $type = PDO::PARAM_STR; // Default
        if (strpos($key, 'idade') !== false) { // Se for idade, usa INT
            $type = PDO::PARAM_INT;
        }
        $stmt->bindValue($key, $value, $type);
    }

    $stmt->execute();
    // Retorna array associativo com chaves prefixadas (participante_, consumo_, ebia_)
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>