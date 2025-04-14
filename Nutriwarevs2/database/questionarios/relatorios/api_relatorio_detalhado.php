<?php
session_start();
header('Content-Type: application/json; charset=utf-8'); // Define o tipo de resposta como JSON

// Incluir arquivos necessários
include __DIR__ . '/../../config/conexao.php';
// Incluir banco.php pode não ser necessário se a lógica de query for simples aqui
// include __DIR__ . '/../../config/banco.php'; // <- Gerar relatorio completo está aqui, não precisa incluir
include __DIR__ . '/../../config/error_handler.php';

// Função auxiliar de formatação (versão simplificada para API)
function formatarValorApi($coluna, $valor) {
    if (is_null($valor) || $valor === '') return ''; // Retorna vazio para nulo/vazio no JSON

    $colunaLimpa = preg_replace('/^(participante_|consumo_|ebia_)/', '', $coluna);
    $mapSimNao = [1 => 'Sim', '0' => 'Não', true => 'Sim', false => 'Não'];
    $booleanColumns = ['resposta1','resposta2','resposta3','resposta4','resposta5','resposta6','resposta7','resposta8', 'usa_dispositivos', 'feijao', 'frutas_frescas', 'verduras_legumes', 'hamburguer_embutidos', 'bebidas_adocadas', 'macarrao_instantaneo', 'biscoitos_recheados'];
    $mapClassificacaoEbia = ['seguranca_alimentar' => 'Segurança Alimentar', 'inseguranca_leve' => 'Insegurança Leve', 'inseguranca_moderada' => 'Insegurança Moderada', 'inseguranca_grave' => 'Insegurança Grave'];

    if (in_array($colunaLimpa, $booleanColumns)) {
        if (array_key_exists($valor, $mapSimNao)) return $mapSimNao[$valor];
        if (array_key_exists((string)$valor, $mapSimNao)) return $mapSimNao[(string)$valor];
        return '';
    }
    if ($colunaLimpa === 'classificacao') {
        return $mapClassificacaoEbia[(string)$valor] ?? ucfirst(str_replace('_', ' ', (string)$valor));
    }
    if ($colunaLimpa === 'beneficios_sociais') {
        $json = is_string($valor) ? json_decode($valor, true) : null;
        if ($json !== null && is_array($json)) {
            $itensFormatados = [];
            $textoOutros = isset($json['Outros']) && is_string($json['Outros']) ? trim($json['Outros']) : null;
            foreach ($json as $key => $item) {
                if ($key !== 'Outros' && is_string($item)) $itensFormatados[] = trim($item);
            }
            $output = implode(', ', $itensFormatados);
            if ($textoOutros) $output .= (!empty($output) ? '; ' : '') . 'Outros: ' . $textoOutros;
            return $output;
        }
        return '';
    }
     if (str_ends_with($colunaLimpa, 'data_cadastro') || str_ends_with($colunaLimpa, 'data_preenchimento')) {
         try {
            if (empty($valor) || $valor === '0000-00-00 00:00:00') return '';
            $date = new DateTime($valor);
            return $date->format('d/m/Y H:i');
         } catch (Exception $e) { return (string)$valor; }
    }
    if ($colunaLimpa === 'refeicoes') return str_replace(',', ', ', (string)$valor);

    // Outros ENUMs (simplificado)
    $mapEnum = [
       'genero' => ['masculino' => 'Masculino', 'feminino' => 'Feminino', /*...*/ 'prefere_nao_dizer' => 'Prefere Não Dizer'],
       'raca' => ['branco' => 'Branco', 'preto' => 'Preto', /*...*/ 'prefere_nao_dizer' => 'Prefere Não Dizer'],
       'escolaridade' => ['ensino_medio_completo' => 'Ens. Médio Completo', /*...*/ 'prefere_nao_dizer' => 'Prefere Não Dizer'],
       'estado_civil' => ['solteiro' => 'Solteiro(a)', 'casado' => 'Casado(a)', /*...*/ 'prefere_nao_dizer' => 'Prefere Não Dizer'],
       'situacao_emprego' => ['desempregado' => 'Desempregado', 'autonomo' => 'Autônomo', /*...*/ 'prefere_nao_dizer' => 'Prefere Não Dizer'],
       'religiao' => ['catolico' => 'Católico', 'evangelico' => 'Evangélico', /*...*/ 'prefere_nao_dizer' => 'Prefere Não Dizer'],
    ];
    if(isset($mapEnum[$colunaLimpa])){
       return $mapEnum[$colunaLimpa][(string)$valor] ?? ucfirst(str_replace('_', ' ', (string)$valor));
    }


    return (string)$valor; // Retorna como string por padrão
}


// --- Validação Básica ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// --- Obter Dados da Requisição ---
$input = json_decode(file_get_contents('php://input'), true);

$campoOriginalClicado = filter_var($input['campo_original'] ?? '', FILTER_SANITIZE_STRING); // Nome da coluna original (com prefixo)
$valorClicado = $input['valor_clicado'] ?? null; // Valor original do banco de dados
$filtrosOriginais = $input['filtros_originais'] ?? [];

if (empty($campoOriginalClicado) || is_null($valorClicado)) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos: campo ou valor não fornecido.']);
    exit;
}

// --- Mapeamento reverso e validação do campo clicado ---
// Precisamos garantir que o campo clicado é um campo válido e determinar a tabela correta
$mapAliasCampo = [
    // Participantes
    'participante_genero' => 'p', 'participante_raca' => 'p', 'participante_escolaridade' => 'p',
    'participante_estado_civil' => 'p', 'participante_situacao_emprego' => 'p',
    'participante_numero_dependentes' => 'p', 'participante_religiao' => 'p',
    'participante_beneficios_sociais' => 'p', // JSON - filtro especial
    // Consumo
    'consumo_refeicoes' => 'ca', // TEXT - filtro especial
    'consumo_usa_dispositivos' => 'ca', 'consumo_feijao' => 'ca', 'consumo_frutas_frescas' => 'ca',
    'consumo_verduras_legumes' => 'ca', 'consumo_hamburguer_embutidos' => 'ca',
    'consumo_bebidas_adocadas' => 'ca', 'consumo_macarrao_instantaneo' => 'ca',
    'consumo_biscoitos_recheados' => 'ca',
    // EBIA
    'ebia_classificacao' => 'qe',
    'ebia_resposta1' => 'qe', 'ebia_resposta2' => 'qe', 'ebia_resposta3' => 'qe', 'ebia_resposta4' => 'qe',
    'ebia_resposta5' => 'qe', 'ebia_resposta6' => 'qe', 'ebia_resposta7' => 'qe', 'ebia_resposta8' => 'qe',
];

if (!array_key_exists($campoOriginalClicado, $mapAliasCampo)) {
    echo json_encode(['success' => false, 'message' => 'Campo de filtro inválido.']);
    exit;
}
$aliasTabelaCampoClicado = $mapAliasCampo[$campoOriginalClicado];
$nomeCampoClicadoSemPrefixo = preg_replace('/^(participante_|consumo_|ebia_)/', '', $campoOriginalClicado);

// --- Montar Query SQL ---
// Selecionar colunas desejadas para o modal (ajuste conforme necessário)
$colunasDetalhe = [
    'p.nome AS participante_nome',
    'p.idade AS participante_idade',
    'p.genero AS participante_genero',
    'p.escolaridade AS participante_escolaridade',
    'qe.classificacao AS ebia_classificacao', // Exemplo de dado EBIA
    'ca.feijao AS consumo_feijao', // Exemplo de dado Consumo
    'ca.frutas_frescas AS consumo_frutas_frescas' // Exemplo de dado Consumo
];
$selectDetalhe = implode(', ', $colunasDetalhe);

// ******************************************************
// ***** CORREÇÃO APLICADA AQUI na Query SQL *****
// ******************************************************
// Query MODIFICADA com subconsultas para buscar o ID mais recente (assumindo que ID auto_increment indica o mais recente)
$query = "SELECT {$selectDetalhe}
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
          WHERE 1=1"; // Cláusula base para filtros demográficos

$params = [];

// 1. Adicionar Filtro do Clique
$placeholderClicado = ":valor_clicado"; // Placeholder simples
if ($nomeCampoClicadoSemPrefixo === 'beneficios_sociais') {
     // Filtro especial para JSON: Verifica se o valor clicado existe no array JSON
     // ATENÇÃO: JSON_CONTAINS pode ter sintaxe ligeiramente diferente dependendo da versão do MySQL/MariaDB
     // Esta sintaxe assume que $valorClicado é um dos valores básicos (não 'Outros')
     // E que o JSON é um array de strings simples ou tem a chave 'Outros'.
     // Se clicou em 'Outros Benefícios', o $valorClicado será essa string.
      if ($valorClicado === 'Outros Benefícios (detalhado)') {
           $query .= " AND JSON_EXTRACT(p.beneficios_sociais, '$.Outros') IS NOT NULL AND JSON_EXTRACT(p.beneficios_sociais, '$.Outros') != ''";
      } else {
           // Tenta encontrar o valor como um item no array JSON
            $query .= " AND JSON_CONTAINS(p.beneficios_sociais, JSON_QUOTE(:valor_clicado), '$')";
            $params[$placeholderClicado] = $valorClicado;
      }

} elseif ($nomeCampoClicadoSemPrefixo === 'refeicoes') {
    // Filtro especial para TEXT (string separada por vírgula)
    $query .= " AND FIND_IN_SET(:valor_clicado, ca.refeicoes) > 0"; // Corrigido para apontar para ca.refeicoes
    $params[$placeholderClicado] = $valorClicado;
} else {
    // Filtro padrão para ENUM, INT, BOOLEAN, etc.
    // Aponta para a coluna correta (p., ca. ou qe.) com base no mapeamento
    $query .= " AND {$aliasTabelaCampoClicado}.{$nomeCampoClicadoSemPrefixo} = {$placeholderClicado}";
    // Determina o tipo do parâmetro
    $tipoParam = PDO::PARAM_STR;
    if (is_int($valorClicado) || is_bool($valorClicado) || is_numeric($valorClicado) || in_array($valorClicado, ['0','1'], true)) {
        // Trata booleanos e números como inteiros (ou booleanos se suportado explicitamente)
         if (is_bool($valorClicado)) $tipoParam = PDO::PARAM_BOOL;
         else $tipoParam = PDO::PARAM_INT;
         // Tenta converter para int se for 0 ou 1 string
         if ($valorClicado === '0' || $valorClicado === '1') {
            $valorClicado = (int)$valorClicado;
            $tipoParam = PDO::PARAM_INT;
         }
    }
    $params[$placeholderClicado] = ['value' => $valorClicado, 'type' => $tipoParam];
}


// 2. Adicionar Filtros Originais (aplicados à tabela 'p')
foreach ($filtrosOriginais as $key => $value) {
    // Mapeia a chave do filtro (sem prefixo) para a coluna real no DB (com prefixo 'p.')
    $colunaFiltroOriginal = 'p.' . $key; // Assume que filtros originais são sempre de participantes
    $placeholderOriginal = ":" . $key . "_orig"; // Placeholder único

    // Valida se a coluna do filtro original existe (segurança básica)
    $colunasParticipanteValidas = ['p.idade', 'p.genero', 'p.raca', 'p.escolaridade', 'p.estado_civil', 'p.situacao_emprego', 'p.religiao']; // Adicione outras se necessário
    $colunaIdadeMin = 'p.idade';
    $colunaIdadeMax = 'p.idade';

    // Ignora valores vazios ou nulos nos filtros originais
    if ($value === '' || is_null($value)) {
        continue;
    }

    if ($key === 'idade_min') {
        $query .= " AND {$colunaIdadeMin} >= {$placeholderOriginal}";
        $params[$placeholderOriginal] = ['value' => (int)$value, 'type' => PDO::PARAM_INT];
    } elseif ($key === 'idade_max') {
        $query .= " AND {$colunaIdadeMax} <= {$placeholderOriginal}";
        $params[$placeholderOriginal] = ['value' => (int)$value, 'type' => PDO::PARAM_INT];
    } elseif (in_array($colunaFiltroOriginal, $colunasParticipanteValidas)) {
        $query .= " AND {$colunaFiltroOriginal} = {$placeholderOriginal}";
        $params[$placeholderOriginal] = ['value' => $value, 'type' => PDO::PARAM_STR];
    }
}

// Ordenar resultados (opcional)
$query .= " ORDER BY p.nome ASC";

// --- Executar Query ---
try {
    $stmt = $conexao->prepare($query);

    // Bind dos parâmetros
    foreach ($params as $placeholder => $paramData) {
        // Se for array (com tipo definido), usa bindValue
        if (is_array($paramData) && isset($paramData['value'])) {
            $stmt->bindValue($placeholder, $paramData['value'], $paramData['type'] ?? PDO::PARAM_STR);
        } else {
            // Senão, usa bindValue padrão (caso do valor clicado simples de JSON/TEXT)
             $stmt->bindValue($placeholder, $paramData); // PDO::PARAM_STR é o default
        }
    }

    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatar os resultados antes de enviar
    $resultadosFormatados = [];
    foreach($resultados as $linha) {
        $linhaFormatada = [];
        foreach($linha as $coluna => $valor) {
            $linhaFormatada[$coluna] = formatarValorApi($coluna, $valor);
        }
        $resultadosFormatados[] = $linhaFormatada;
    }


    echo json_encode(['success' => true, 'data' => $resultadosFormatados]);

} catch (PDOException $e) {
    error_log("Erro API Detalhes: " . $e->getMessage() . " Query: " . $query . " Params: " . print_r($params, true));
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar detalhes no banco de dados.']);
} catch (Exception $e) {
     error_log("Erro Geral API Detalhes: " . $e->getMessage());
     echo json_encode(['success' => false, 'message' => 'Ocorreu um erro inesperado.']);
}

?>