<?php
session_start();
// Verifica se o usuário está logado (se aplicável ao seu sistema de relatórios)
// if (!isset($_SESSION['participante_id'])) { // Usar a sessão correta, se necessário
//     header("Location: ../../login/login.php");
//     exit;
// }

// Incluir arquivos necessários
include '../../config/conexao.php';
include '../../config/banco.php'; // Onde as funções de busca de dados estão
include '../../config/error_handler.php'; // Incluir o handler de erro

// --- Função Auxiliar para Formatar Valores para Exibição na Tabela ---
/**
 * Formata um valor para exibição na tabela de detalhes.
 * Converte snake_case para Title Case para colunas específicas.
 * Trata nulos, booleanos e casos especiais.
 *
 * @param string $coluna O nome da coluna original (pode ter prefixo).
 * @param mixed $valor O valor da coluna.
 * @return string O valor formatado para exibição.
 */
function formatarValorPerfilParaExibicao($coluna, $valor) {
    if (is_null($valor) || $valor === '') {
        return '-'; // Retorna '-' para nulo ou vazio
    }

    // Lista de colunas categóricas que devem ser formatadas (snake_case to Title Case)
    $colunasParaFormatar = [
        'genero', 'raca', 'escolaridade', 'estado_civil',
        'situacao_emprego', 'religiao'
        // Adicionar outras colunas do perfil se necessário
    ];
    // Colunas que são booleanas/SimNao (já tratadas antes na lógica principal)
    $colunasSimNao = [
        'ebia_resposta1', 'ebia_resposta2', 'ebia_resposta3', 'ebia_resposta4',
        'ebia_resposta5', 'ebia_resposta6', 'ebia_resposta7', 'ebia_resposta8',
        'consumo_usa_dispositivos', 'consumo_feijao', 'consumo_frutas_frescas',
        'consumo_verduras_legumes', 'consumo_hamburguer_embutidos', 'consumo_bebidas_adocadas',
        'consumo_macarrao_instantaneo', 'consumo_biscoitos_recheados'
    ];
    // Colunas com tratamento especial (JSON, Badges)
    $colunasEspeciais = ['beneficios_sociais', 'ebia_classificacao'];

    // Remove prefixos (participante_, consumo_, ebia_) para aplicar a lógica
    $colunaLimpa = preg_replace('/^(participante_|consumo_|ebia_)/', '', $coluna);

    // 1. Tratamento JSON (ex: beneficios_sociais)
    if ($colunaLimpa === 'beneficios_sociais' && is_string($valor) && ($json = json_decode($valor, true)) !== null && json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        if (!empty($json)) {
             $itensFormatados = [];
             $textoOutros = isset($json['Outros']) && is_string($json['Outros']) ? trim($json['Outros']) : null;
             foreach ($json as $key => $item) {
                 // Pula a chave 'Outros' e itens não-string
                 if ($key !== 'Outros' && is_string($item)) {
                     $itensFormatados[] = trim($item); // Usa o valor diretamente
                 }
             }
             $output = implode(', ', $itensFormatados); // Separa por vírgula e espaço
             if ($textoOutros) {
                 if (!empty($output)) $output .= '; '; // Separador antes de 'Outros'
                 $output .= 'Outros: ' . $textoOutros;
             }
             return $output; // Retorna string formatada
        } else { return '-'; } // JSON vazio
    }

    // 2. Tratamento Boolean/SimNao (para EBIA/Consumo se incluídos no relatório)
    if (is_bool($valor) || in_array($coluna, $colunasSimNao)) {
        if ($valor === 1 || $valor === '1' || $valor === true) {
            return 'Sim';
        } elseif ($valor === 0 || $valor === '0' || $valor === false) {
             return 'Não'; // Só retorna "Não" se o valor for explicitamente 0 ou false
        } else {
            return '-'; // Era null ou outro valor inesperado
        }
    }

    // 3. Tratamento Classificação EBIA (retorna o valor bruto para a lógica de badge)
    if ($coluna === 'ebia_classificacao' && !empty($valor)) {
        return (string)$valor; // Deixa a lógica da tabela criar o badge
    }

    // 4. Formatação snake_case para Title Case (APENAS para colunas definidas)
    if (in_array($colunaLimpa, $colunasParaFormatar)) {
        // Tratamento de casos especiais com múltiplas palavras ou acentos
        $mapValores = [
            'prefere_nao_dizer' => 'Prefere Não Dizer',
            'uniao_estavel' => 'União Estável',
            'nao_binario' => 'Não Binário',
            'povos_originarios' => 'Povos Originários',
            'ensino_fundamental_incompleto' => 'Ensino Fundamental Incompleto',
            'ensino_fundamental_completo' => 'Ensino Fundamental Completo',
            'ensino_medio_incompleto' => 'Ensino Médio Incompleto',
            'ensino_medio_completo' => 'Ensino Médio Completo',
            'graduacao_incompleta' => 'Graduação Incompleta',
            'graduacao_completa' => 'Graduação Completa',
            'meio_periodo' => 'Meio Período',
            'tempo_integral' => 'Tempo Integral',
            'incapaz_trabalhar' => 'Incapaz de Trabalhar',
            // Adicione outros mapeamentos específicos se necessário
        ];
        if (isset($mapValores[(string)$valor])) {
            return $mapValores[(string)$valor];
        }
        // Formatação genérica (Capitaliza palavras separadas por _)
        return ucwords(str_replace('_', ' ', (string)$valor));
    }

    // 5. Valor padrão (retorna como string, ex: nome, idade, número dependentes)
    return (string)$valor;
}
// --- Fim Função Auxiliar ---


// --- Inicialização (código existente) ---
$relatorio = [];
$chartData = [];
$filtrosAplicados = [];
$erro = '';
$colunasReais = [];
$tipo_relatorio = 'perfil'; // Default
$modo_relatorio = 'filtrado'; // Default

// --- Processamento do Formulário (código existente) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Obter tipo e modo do relatório
    $tipo_relatorio = $_POST['tipo_relatorio'] ?? 'perfil';
    $modo_relatorio = $_POST['modo_relatorio'] ?? 'filtrado';

    // --- Montar Filtros (apenas se modo='filtrado') (código existente) ---
    $filtros = [];
    if ($modo_relatorio === 'filtrado') {
        // Idade
        $idade_min_raw = filter_input(INPUT_POST, 'idade_min', FILTER_SANITIZE_NUMBER_INT);
        $idade_max_raw = filter_input(INPUT_POST, 'idade_max', FILTER_SANITIZE_NUMBER_INT);
        $idade_min = ($idade_min_raw !== '' && filter_var($idade_min_raw, FILTER_VALIDATE_INT) !== false && $idade_min_raw >= 0) ? (int)$idade_min_raw : null;
        $idade_max = ($idade_max_raw !== '' && filter_var($idade_max_raw, FILTER_VALIDATE_INT) !== false && $idade_max_raw >= 0) ? (int)$idade_max_raw : null;

        if ($idade_min !== null) $filtros['idade_min'] = $idade_min;
        if ($idade_max !== null) $filtros['idade_max'] = $idade_max;

        if (isset($filtros['idade_min']) && isset($filtros['idade_max']) && $filtros['idade_min'] > $filtros['idade_max']) {
             $erro = "A idade mínima não pode ser maior que a idade máxima.";
             unset($filtros['idade_min'], $filtros['idade_max']);
        }

        // Outros filtros (Gênero, Raça, Escolaridade, etc.) - Adapte os nomes se necessário
        if (!empty($_POST['genero'])) $filtros['genero'] = trim(htmlspecialchars($_POST['genero']));
        if (!empty($_POST['raca'])) $filtros['raca'] = trim(htmlspecialchars($_POST['raca']));
        if (!empty($_POST['escolaridade'])) $filtros['escolaridade'] = trim(htmlspecialchars($_POST['escolaridade']));
        if (!empty($_POST['estado_civil'])) $filtros['estado_civil'] = trim(htmlspecialchars($_POST['estado_civil']));
        if (!empty($_POST['situacao_emprego'])) $filtros['situacao_emprego'] = trim(htmlspecialchars($_POST['situacao_emprego']));
        if (!empty($_POST['religiao'])) $filtros['religiao'] = trim(htmlspecialchars($_POST['religiao']));

        $filtrosAplicados = $filtros;
    }

    // --- Buscar Dados (código existente com modificações anteriores para incluir 'nome') ---
    if (empty($erro)) {
        try {
             $colunasDesejadasEbia = ['nome', 'idade', 'genero', 'raca', 'escolaridade', 'pontuacao_total', 'classificacao'];
             $colunasDesejadasPerfil = [
                 'nome', 'id', 'idade', 'genero', 'raca', 'escolaridade', 'estado_civil',
                 'situacao_emprego', 'religiao', 'numero_dependentes', 'beneficios_sociais',
                 // Adicione _outro aqui se quiser vê-los na tabela (não recomendado para a formatação pedida)
                 // 'genero_outro', 'raca_outro', 'escolaridade_outro', 'situacao_emprego_outro', 'religiao_outro'
             ];
             $colunasDesejadasConsumo = null; // Usa padrão da função

             switch ($tipo_relatorio) {
                case 'inseguranca':
                    $relatorio = gerar_relatorio_inseguranca_alimentar($conexao, $filtros, $colunasDesejadasEbia);
                    break;
                case 'consumo':
                    $relatorio = gerar_relatorio_consumo_alimentar($conexao, $filtros, $colunasDesejadasConsumo);
                    break;
                case 'completo':
                    $relatorio = gerar_relatorio_completo($conexao, $filtros); // Chama a nova função
                    break;
                case 'perfil':
                default:
                    $relatorio = gerar_relatorio_perfil($conexao, $filtros, $colunasDesejadasPerfil);
                    $tipo_relatorio = 'perfil'; // Garante o tipo default
                    break;
            }

             if (!empty($relatorio)) {
                 $colunasReais = array_keys($relatorio[0]);
                 // Remove colunas que não devem aparecer na tabela final
                 $colunasReais = array_diff($colunasReais, ['senha', 'email']);
                 // Opcional: Remover colunas '_outro' da exibição da tabela
                 $colunasReais = array_filter($colunasReais, function($col) {
                    return !preg_match('/_outro$/', $col);
                 });
                 // Opcional: Remover ID se não for mais necessário
                 // $colunasReais = array_diff($colunasReais, ['id']);
             } else {
                 $colunasReais = [];
             }

        } catch (PDOException $e) {
            $erro = "Erro ao buscar dados no banco de dados.";
            error_log("Erro DB Relatorio ($tipo_relatorio): " . $e->getMessage());
            $relatorio = []; $colunasReais = [];
        } catch (Exception $e) {
             $erro = "Erro ao gerar relatório: " . $e->getMessage();
             error_log("Erro Geral Relatorio ($tipo_relatorio): " . $e->getMessage());
             $relatorio = []; $colunasReais = [];
        }
    }

    // --- Exportação CSV (código existente) ---
    if (isset($_POST['exportar']) && $_POST['exportar'] == 'csv' && empty($erro) && !empty($relatorio)) {
         header('Content-Type: text/csv; charset=utf-8');
         header('Content-Disposition: attachment; filename=nutriware_relatorio_' . $tipo_relatorio . '_' . date('YmdHis') . '.csv');
         $output = fopen('php://output', 'w');
         fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

         // Cabeçalho (lógica existente adaptada)
          $headerNomes = array_map(function($col) {
              if ($col === 'nome') return 'Nome';
              // Remove prefixos e formata
              $colLimpa = preg_replace('/^(participante_|consumo_|ebia_)/', '', $col);
              // Aplica a mesma lógica da função de formatação para o cabeçalho se for coluna formatável
              $colunasParaFormatarHeader = [
                    'genero', 'raca', 'escolaridade', 'estado_civil',
                    'situacao_emprego', 'religiao'
              ];
              if(in_array($colLimpa, $colunasParaFormatarHeader)){
                 return ucwords(str_replace('_', ' ', $colLimpa));
              }
               // Cabeçalhos específicos
              $mapHeader = [
                'id' => 'ID', 'idade' => 'Idade', 'numero_dependentes' => 'Dependentes',
                'beneficios_sociais' => 'Benefícios Sociais',
                'ebia_resposta1' => 'EBIA Q1', 'ebia_resposta2' => 'EBIA Q2', /*...*/ 'ebia_pontuacao_total' => 'EBIA Pontos', 'ebia_classificacao' => 'EBIA Classif.',
                'consumo_refeicoes' => 'Refeições', 'consumo_usa_dispositivos' => 'Usa Disp.?', /*...*/
              ];
               if (isset($mapHeader[$col])) return $mapHeader[$col];

              return ucfirst(str_replace('_', ' ', $colLimpa)); // Fallback
          }, $colunasReais);
          fputcsv($output, $headerNomes);

         // Dados (Usa a função de formatação para consistência)
         foreach ($relatorio as $linha) {
             $linhaExport = [];
             foreach ($colunasReais as $col) {
                 $valorOriginal = $linha[$col] ?? null;
                 // Chama a função formatarValorPerfilParaExibicao para obter o valor legível
                 $valorFormatadoParaCsv = formatarValorPerfilParaExibicao($col, $valorOriginal);
                 // No CSV, não queremos o badge HTML da classificação, usamos o valor formatado
                 if ($col === 'ebia_classificacao') {
                    $linhaExport[] = $valorFormatadoParaCsv === '-' ? '' : ucfirst(str_replace('_', ' ', $valorOriginal)); // Nome completo da classificação
                 } else {
                    $linhaExport[] = ($valorFormatadoParaCsv === '-') ? '' : $valorFormatadoParaCsv; // Usa valor formatado, mas converte '-' para vazio
                 }
             }
             fputcsv($output, $linhaExport);
         }
         fclose($output);
         exit;
    }

    // --- Preparação de Dados para Gráficos (código existente) ---
    if (empty($erro) && !empty($relatorio)) {
        // A função preparar_dados_graficos já deve lidar com as novas colunas
        $chartData = preparar_dados_graficos($relatorio, $colunasReais, $filtrosAplicados);
    }

} // Fim if ($_SERVER["REQUEST_METHOD"] == "POST")

/**
 * Prepara dados para gráficos Chart.js (código existente).
 * Adaptações para tratar as novas colunas foram incluídas na versão anterior.
 */
function preparar_dados_graficos(array $dadosRelatorio, array $colunasDisponiveis, array $filtros): array {
    $graficos = [];
    $totalRegistros = count($dadosRelatorio);
     if ($totalRegistros === 0) return [];

    // Função interna para gerar dados de contagem (adaptada anteriormente)
    $gerarContagem = function($coluna) use ($dadosRelatorio, $totalRegistros) {
        if (!isset($dadosRelatorio[0][$coluna])) return null;
        $valoresColuna = array_column($dadosRelatorio, $coluna);
        $valoresValidos = array_filter($valoresColuna, function($val) { return $val !== null && $val !== ''; });
        if (empty($valoresValidos)) return null;
        $contagem = [];
        $isBoolLike = true;

        foreach ($valoresValidos as $valor) {
             if (is_string($valor) && ($json = json_decode($valor, true)) !== null && json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                 $isBoolLike = false;
                 if (!empty($json)) {
                    $textoOutros = null;
                    if (isset($json['Outros']) && is_string($json['Outros']) && !empty(trim($json['Outros']))) {
                        $textoOutros = trim($json['Outros']);
                    }
                    foreach ($json as $key => $item) {
                        if ($key === 'Outros') continue;
                        $chave = trim(is_string($item) ? $item : json_encode($item));
                        if (!empty($chave)) {
                             $contagem[$chave] = ($contagem[$chave] ?? 0) + 1;
                        }
                    }
                    if ($textoOutros !== null) {
                        // Agrupa diferentes textos de 'Outros' em uma única categoria no gráfico
                        $chaveOutros = 'Outros Benefícios';
                        $contagem[$chaveOutros] = ($contagem[$chaveOutros] ?? 0) + 1;
                    }
                 }
             } else {
                 $chave = trim(is_scalar($valor) ? (string)$valor : 'Valor Complexo');
                 if ($chave !== '') {
                    $contagem[$chave] = ($contagem[$chave] ?? 0) + 1;
                    if (!in_array(strtolower($chave), ['0', '1', 'true', 'false'], true) && !is_bool($valor)) {
                        $isBoolLike = false;
                    }
                 } else {
                    if ($valor !== 0 && $valor !== '0') { $isBoolLike = false; }
                 }
             }
         }
         if(empty($contagem)) return null;
         $labels = array_keys($contagem); $data = array_values($contagem);
         if ($isBoolLike && count($labels) <= 2) {
             $newLabels = []; $newData = []; $mapSim = ['1', 'true']; $mapNao = ['0', 'false'];
             $countSim = 0; $countNao = 0;
             foreach ($labels as $i => $lbl) {
                 $lblLower = strtolower((string)$lbl);
                 if (in_array($lblLower, $mapSim, true) || (isset($dadosRelatorio[0][$coluna]) && $dadosRelatorio[0][$coluna] === true) ) { $countSim += $data[$i]; }
                 elseif (in_array($lblLower, $mapNao, true) || (isset($dadosRelatorio[0][$coluna]) && $dadosRelatorio[0][$coluna] === false) ) { $countNao += $data[$i]; }
             }
             if ($countSim > 0) { $newLabels[] = 'Sim'; $newData[] = $countSim; }
             if ($countNao > 0) { $newLabels[] = 'Não'; $newData[] = $countNao; }
             if (empty($newData)) return null;
             if (count($newData) == 2 && $newLabels[0] === 'Não') { $labels = array_reverse($newLabels); $data = array_reverse($newData); }
             else { $labels = $newLabels; $data = $newData; }
         } else { arsort($contagem); $labels = array_keys($contagem); $data = array_values($contagem); }
         $totalValidosContados = array_sum($data);
         $percentuais = $totalValidosContados > 0 ? array_map(function($count) use ($totalValidosContados) { return round(($count / $totalValidosContados) * 100, 1); }, $data) : array_fill(0, count($data), 0.0);
         if (empty($labels) || empty($data)) return null;
        return ['labels' => $labels, 'data' => $data, 'percentuais' => $percentuais];
    };

    // Colunas para gerar gráficos demográficos e outros
    $colsParaGraficos = [
        'genero', 'raca', 'escolaridade', 'situacao_emprego', 'religiao',
        'estado_civil', 'numero_dependentes', 'beneficios_sociais'
    ];

    // Loop para gerar gráficos (lógica existente adaptada)
    foreach ($colsParaGraficos as $colGraf) {
        $colunaComPrefixo = 'participante_' . $colGraf; $colunaSemPrefixo = $colGraf;
        $colunaParaUsar = null;
        if (in_array($colunaComPrefixo, $colunasDisponiveis)) { $colunaParaUsar = $colunaComPrefixo; }
        elseif (in_array($colunaSemPrefixo, $colunasDisponiveis)) { $colunaParaUsar = $colunaSemPrefixo; }
        if ($colunaParaUsar === null) continue;
        $dadosGrafico = $gerarContagem($colunaParaUsar);
        if ($dadosGrafico) {
             $tituloBase = ucfirst(str_replace('_', ' ', $colunaSemPrefixo));
             $mapTitulos = [ 'Numero dependentes' => 'Número de Dependentes', 'Beneficios sociais' => 'Benefícios Sociais Recebidos' ];
             $titulo = 'Distribuição por ' . ($mapTitulos[$tituloBase] ?? $tituloBase);
             $filtroKey = $colunaSemPrefixo;
             if (!empty($filtros[$filtroKey])) $titulo .= ' (Filtrado)';
             $graficos[$colunaSemPrefixo] = $dadosGrafico + ['titulo' => $titulo];
        }
    }

    // Gráfico de Classificação EBIA (lógica existente)
    $colClassificacao = 'ebia_classificacao';
    if (in_array($colClassificacao, $colunasDisponiveis)) {
          $dadosGraficoEbia = $gerarContagem($colClassificacao);
          if ($dadosGraficoEbia) {
                $order = ['seguranca_alimentar' => 1, 'inseguranca_leve' => 2, 'inseguranca_moderada' => 3, 'inseguranca_grave' => 4];
                $valoresClassificacao = array_column($dadosRelatorio, $colClassificacao);
                $valoresValidosClassif = array_filter($valoresClassificacao, function($val){ return $val!==null && $val!=='';});
                if(!empty($valoresValidosClassif)){
                     $contagemOriginal = array_count_values($valoresValidosClassif);
                     uksort($contagemOriginal, function($a, $b) use ($order) { return ($order[$a] ?? 99) <=> ($order[$b] ?? 99); });
                     $labelsOrdenados = array_keys($contagemOriginal); $dataOrdenada = array_values($contagemOriginal);
                     $totalContagem = array_sum($dataOrdenada);
                     $percentuaisOrdenados = $totalContagem > 0 ? array_map(function($count) use ($totalContagem) { return round(($count / $totalContagem) * 100, 1); }, $dataOrdenada) : array_fill(0, count($dataOrdenada), 0.0);
                     $graficos['ebia_classificacao'] = [ 'labels' => $labelsOrdenados, 'data' => $dataOrdenada, 'percentuais' => $percentuaisOrdenados, 'titulo' => 'Distribuição por Classificação EBIA' ];
                }
          }
    }

     // Gráficos para colunas BOOLEAN/Checkbox do consumo (lógica existente)
     $colunasConsumoBool = [
        'consumo_usa_dispositivos', 'consumo_feijao', 'consumo_frutas_frescas', 'consumo_verduras_legumes',
        'consumo_hamburguer_embutidos', 'consumo_bebidas_adocadas', 'consumo_macarrao_instantaneo', 'consumo_biscoitos_recheados'
     ];
     $nomesAmigaveisConsumo = [
        'consumo_usa_dispositivos' => 'Uso de Dispositivos na Refeição', 'consumo_feijao' => 'Consumo de Feijão (Ontem)',
        'consumo_frutas_frescas' => 'Consumo de Frutas Frescas (Ontem)', 'consumo_verduras_legumes' => 'Consumo de Verduras/Legumes (Ontem)',
        'consumo_hamburguer_embutidos' => 'Consumo de Hambúrguer/Embutidos (Ontem)', 'consumo_bebidas_adocadas' => 'Consumo de Bebidas Adoçadas (Ontem)',
        'consumo_macarrao_instantaneo' => 'Consumo de Macarrão Inst./Salgadinhos (Ontem)', 'consumo_biscoitos_recheados' => 'Consumo de Biscoitos Rech./Doces (Ontem)',
     ];
     foreach ($colunasConsumoBool as $colBool) {
         if (in_array($colBool, $colunasDisponiveis)) {
              $dadosGraficoConsumo = $gerarContagem($colBool);
              if ($dadosGraficoConsumo && !empty($dadosGraficoConsumo['labels'])) {
                   $tituloGrafico = $nomesAmigaveisConsumo[$colBool] ?? ucfirst(str_replace('_', ' ', preg_replace('/^consumo_/', '', $colBool)));
                   $graficos[$colBool] = $dadosGraficoConsumo + ['titulo' => $tituloGrafico];
              }
         }
     }

    return $graficos;
}

// --- Exibição HTML ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Relatório Nutriware</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .chart-container { position: relative; margin: auto; height: 40vh; width: 100%; max-width: 450px; margin-bottom: 40px; }
    .chart-container-bar-wide { position: relative; margin: auto; height: 45vh; width: 100%; max-width: 600px; margin-bottom: 40px; }
     /* Estilos da Tabela mantidos do relatório completo para consistência */
     #tabela-perfil th { background-color: #e9ecef; text-align: center; } /* ID correto */
     #tabela-perfil td { font-size: 0.85rem; vertical-align: middle; text-align: left; padding: 0.4rem;} /* ID correto */
     .table-responsive { margin-top: 1rem; }
     /* Remover sticky-top do thead se DataTables gerenciar o cabeçalho */
     .card-body ul { padding-left: 20px; margin-bottom: 0; }
     .badge { font-size: 0.8em; }
     .badge.seguranca-alimentar { background-color: #28a745; color: white; }
     .badge.inseguranca-leve { background-color: #ffc107; color: #212529; }
     .badge.inseguranca-moderada { background-color: #fd7e14; color: white; }
     .badge.inseguranca-grave { background-color: #dc3545; color: white; }
     h1, h2 { text-align: center; }
  </style>
</head>
<body class="container mt-4 mb-5">
  <h1 class="mb-4">Resultado do Relatório Nutriware</h1>

  <div class="text-center mb-4">
      <a href="relatorios.html" class="btn btn-secondary">&laquo; Voltar aos Filtros</a>
      <a href="../../login/menu/menu.php" class="btn btn-light">Voltar ao Menu Principal</a>
  </div>


  <?php if (!empty($erro)): ?>
    <div class="alert alert-danger text-center">
      <strong>Erro:</strong> <?php echo htmlspecialchars($erro); ?>
    </div>
  <?php endif; ?>

  <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($erro)): ?>
    <?php if (empty($relatorio)): ?>
      <div class="alert alert-info text-center">
        Nenhum dado encontrado para os critérios selecionados.
      </div>
    <?php else: ?>
      <div class="card mb-4">
         <div class="card-header">Resumo da Geração</div>
         <div class="card-body">
              <p><strong>Tipo de Relatório:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $tipo_relatorio))); ?></p>
              <p><strong>Modo:</strong> <?php echo htmlspecialchars(ucfirst($modo_relatorio)); ?></p>
              <?php if ($modo_relatorio === 'filtrado' && !empty($filtrosAplicados)): ?>
                  <p><strong>Filtros Aplicados:</strong></p>
                  <ul> <?php foreach ($filtrosAplicados as $key => $value): ?> <li><strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?>:</strong> <?php echo htmlspecialchars($value); ?></li> <?php endforeach; ?> </ul>
              <?php elseif ($modo_relatorio === 'filtrado' && empty($filtrosAplicados)): ?>
                   <p><strong>Filtros Aplicados:</strong> Nenhum filtro específico selecionado.</p>
              <?php endif; ?>
              <p class="mb-0"><strong>Total de Registros Encontrados:</strong> <?php echo count($relatorio); ?></p>
         </div>
      </div>

      <h2 class="mt-5">Dados Detalhados</h2>
      <div class="table-responsive mb-4">
        <table id="tabela-perfil" class="table table-bordered table-striped table-hover table-sm" style="width:100%">
          <thead>
             <tr>
                 <?php // Cabeçalho dinâmico (lógica existente adaptada) ?>
                 <?php foreach ($colunasReais as $coluna): ?>
                      <?php
                         $nomeCabecalho = preg_replace('/^(participante_|consumo_|ebia_)/', '', $coluna);
                         $nomeCabecalho = ucfirst(str_replace('_', ' ', $nomeCabecalho));
                         if ($coluna === 'nome') { $nomeCabecalho = 'Nome'; }
                         else {
                             $ebiaHeadersTabela = [ 'Resposta1' => 'Q1', 'Resposta2' => 'Q2', /*...*/ 'Pontuacao total' => 'Pontos', 'Classificacao' => 'Classif.' ];
                             $friendlyHeaders = [ 'Numero dependentes' => 'Dep.', 'Beneficios sociais' => 'Benef.', 'Situacao emprego' => 'Sit. Emprego', /*...*/];
                             if (isset($ebiaHeadersTabela[$nomeCabecalho])) { $nomeCabecalho = $ebiaHeadersTabela[$nomeCabecalho]; }
                             elseif (isset($friendlyHeaders[$nomeCabecalho])) { $nomeCabecalho = $friendlyHeaders[$nomeCabecalho]; }
                         }
                      ?>
                     <th><?php echo htmlspecialchars($nomeCabecalho); ?></th>
                 <?php endforeach; ?>
             </tr>
          </thead>
          <tbody>
            <?php foreach ($relatorio as $linha): ?>
            <tr>
              <?php // Dados dinâmicos usando $colunasReais ?>
              <?php foreach ($colunasReais as $coluna): ?>
                <td>
                   <?php
                      $valorOriginal = $linha[$coluna] ?? null;
                      // --- MODIFICAÇÃO PRINCIPAL: Usa a nova função de formatação ---
                      $valorFormatado = formatarValorPerfilParaExibicao($coluna, $valorOriginal);

                      // Lógica especial para badges (precisa do valor original sem formatação Title Case)
                      if ($coluna === 'ebia_classificacao' && !empty($valorOriginal)) {
                          $cssClass = str_replace('_', '-', $valorOriginal);
                          // Usa ucfirst na label do badge para ficar 'Seguranca Alimentar', etc.
                          echo '<span class="badge badge-pill ' . $cssClass . '">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $valorOriginal))) . '</span>';
                      }
                      // Para outros casos, usa o valor formatado pela função
                      else {
                          echo htmlspecialchars($valorFormatado);
                      }
                   ?>
                 </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <form action="relatorio_perfil_participante.php" method="POST" class="mb-5 text-center" target="_blank">
          <?php // Campos hidden para reenviar filtros na exportação (código existente) ?>
          <input type="hidden" name="tipo_relatorio" value="<?php echo htmlspecialchars($tipo_relatorio); ?>">
          <input type="hidden" name="modo_relatorio" value="<?php echo htmlspecialchars($modo_relatorio); ?>">
          <?php if ($modo_relatorio === 'filtrado'): ?>
              <?php foreach ($filtrosAplicados as $key => $value): ?> <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>"> <?php endforeach; ?>
          <?php endif; ?>
          <button type="submit" name="exportar" value="csv" class="btn btn-success">
                <i class="fas fa-file-csv"></i> Exportar Tabela para CSV
          </button>
       </form>


      <h2 class="mt-5 mb-4">Gráficos Resumo</h2>
      <div class="row justify-content-center">
          <?php // Geração de Gráficos (código existente) ?>
          <?php if (!empty($chartData)): ?>
              <?php foreach ($chartData as $key => $grafico): ?>
                  <?php
                     $colSize = 'col-lg-4 col-md-6'; // Padrão
                     $containerClass = 'chart-container'; // Padrão
                     if ($key === 'ebia_classificacao') { $colSize = 'col-lg-6 col-md-12'; }
                     elseif (($key === 'beneficios_sociais' || $key === 'numero_dependentes') && count($grafico['labels']) > 5) {
                         $colSize = 'col-lg-6 col-md-12'; $containerClass = 'chart-container-bar-wide';
                     }
                     $canvasKey = preg_replace('/[^a-zA-Z0-9-_]/', '', $key);
                  ?>
                  <div class="<?php echo $colSize; ?> mb-4">
                       <div class="card h-100">
                           <div class="card-header text-center"> <?php echo htmlspecialchars($grafico['titulo']); ?> </div>
                           <div class="card-body d-flex align-items-center justify-content-center"> <div class="<?php echo $containerClass; ?>"> <canvas id="chart-<?php echo htmlspecialchars($canvasKey); ?>"></canvas> </div> </div>
                       </div>
                  </div>
              <?php endforeach; ?>
          <?php else: ?>
              <div class="col-12"> <div class="alert alert-warning text-center">Não há dados agregados suficientes ou aplicáveis para gerar gráficos para este relatório.</div> </div>
          <?php endif; ?>
      </div>

    <?php endif; // Fim do if (empty($relatorio)) ?>
  <?php endif; // Fim do if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($erro)) ?>

  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
  <?php // FontAwesome (opcional para ícone CSV) ?>
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

  <script>
    $(document).ready(function() {
        try {
            $('#tabela-perfil').DataTable({ // Seleciona a tabela pelo ID correto
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' // Tradução
                },
                responsive: true // Habilita responsividade
                // Outras opções podem ser adicionadas aqui, se necessário
            });
        } catch(e) {
            console.error("Erro ao inicializar DataTables:", e);
        }
    });
  </script>

  <?php if (!empty($chartData)): ?>
  <script>
    // Seu código JavaScript existente para renderizar os gráficos Chart.js permanece aqui...
    const chartDataJS = JSON.parse('<?php echo json_encode($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>');
    const backgroundColors = ['rgba(54, 162, 235, 0.7)','rgba(255, 99, 132, 0.7)','rgba(75, 192, 192, 0.7)','rgba(255, 206, 86, 0.7)','rgba(153, 102, 255, 0.7)','rgba(255, 159, 64, 0.7)','rgba(199, 199, 199, 0.7)','rgba(83, 102, 255, 0.7)','rgba(100, 255, 100, 0.7)', 'rgba(210, 130, 190, 0.7)', '#69A8E6','#FFB1C1','#A6E8D8','#FFD6A5','#CBAACB','#FFFFB5']; // Mais cores
    const borderColors = backgroundColors.map(color => color.replace('0.7', '1'));

    // Função formatLabel (adaptada anteriormente)
    function formatLabel(label) {
        if (typeof label !== 'string') return String(label);
        const exactMap = { 'seguranca_alimentar': 'Segurança Alimentar', 'inseguranca_leve': 'Insegurança Leve', 'inseguranca_moderada': 'Insegurança Moderada', 'inseguranca_grave': 'Insegurança Grave', };
        if (exactMap[label]) return exactMap[label];
        let cleanLabel = label.replace(/^(participante_|consumo_|ebia_)/, '', );
        const map = {
            'usa_dispositivos': 'Usa Disp.?', 'hamburguer_embutidos': 'Hamb./Emb.', 'bebidas_adocadas': 'Beb. Adoç.', 'macarrao_instantaneo': 'Mac. Inst.', 'biscoitos_recheados': 'Bisc. Rech.',
            'nao_binario': 'Não Binário', 'povos_originarios': 'Povos Originários', 'prefere_nao_dizer': 'Pref. Não Dizer',
            'ensino_fundamental_incompleto': 'Fund. Inc.', 'ensino_fundamental_completo': 'Fund. Comp.', 'ensino_medio_incompleto': 'Médio Inc.', 'ensino_medio_completo': 'Médio Comp.',
            'graduacao_incompleta': 'Grad. Inc.', 'graduacao_completa': 'Grad. Comp.', 'meio_periodo': 'Meio Período', 'tempo_integral': 'Tempo Integral', 'incapaz_trabalhar': 'Incapaz Trab.',
            'uniao_estavel': 'União Estável', '0': '0', '1': '1', '2': '2', '3': '3', '4 ou mais': '4+',
            'Outros Benefícios': 'Outros Benef.' // Label para gráfico
        };
        if (map[cleanLabel]) return map[cleanLabel];
        if (cleanLabel.startsWith('Outros: ')) { return 'Outros Benef.'; }
        if (cleanLabel.toLowerCase() === 'outro') { return 'Outro'; }
        return cleanLabel.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    // Lógica de renderização dos gráficos (adaptada anteriormente)
    document.addEventListener('DOMContentLoaded', () => {
      let chartInstances = {};
      for (const key in chartDataJS) {
        if (chartDataJS.hasOwnProperty(key)) {
          const grafico = chartDataJS[key];
          const safeKey = key.replace(/[^a-zA-Z0-9-_]/g, '');
          const canvasId = `chart-${safeKey}`;
          const ctx = document.getElementById(canvasId)?.getContext('2d');
          if (ctx && grafico.labels && grafico.labels.length > 0 && grafico.data && grafico.data.length > 0) {
             if (chartInstances[canvasId]) { chartInstances[canvasId].destroy(); }
             let chartType = 'bar'; let specificBackgrounds = backgroundColors.slice(0, grafico.labels.length); let specificBorders = borderColors.slice(0, grafico.labels.length);
             const isBooleanChart = grafico.labels.length <= 2 && (grafico.labels.includes('Sim') || grafico.labels.includes('Não'));
             const isClassification = key === 'ebia_classificacao'; const numCategories = grafico.labels.length;
             if (isBooleanChart || isClassification || (!isBooleanChart && numCategories <= 6)) {
                chartType = 'pie';
                if (isClassification) { const colorMap = { 'Segurança Alimentar': 'rgba(75, 192, 192, 0.7)', 'Insegurança Leve': 'rgba(255, 206, 86, 0.7)', 'Insegurança Moderada': 'rgba(255, 159, 64, 0.7)', 'Insegurança Grave': 'rgba(255, 99, 132, 0.7)' }; specificBackgrounds = grafico.labels.map(label => colorMap[formatLabel(label)] || '#cccccc'); }
                else if (isBooleanChart) { const simColor = 'rgba(54, 162, 235, 0.7)'; const naoColor = 'rgba(255, 99, 132, 0.7)'; specificBackgrounds = grafico.labels.map(label => (label === 'Sim' ? simColor : naoColor)); if (grafico.labels.length === 2 && grafico.labels[0] === 'Não') { specificBackgrounds = [naoColor, simColor]; } else if (grafico.labels.length === 1 && grafico.labels[0] === 'Não'){ specificBackgrounds = [naoColor]; } else if (grafico.labels.length === 1 && grafico.labels[0] === 'Sim'){ specificBackgrounds = [simColor]; } }
                specificBorders = specificBackgrounds.map(color => color.replace('0.7', '1'));
             }
             else if ((key === 'beneficios_sociais' || key === 'numero_dependentes') && numCategories > 6) { chartType = 'bar'; }

             let currentOptions = { responsive: true, maintainAspectRatio: false, indexAxis: (chartType === 'bar' && numCategories > 6) ? 'y' : 'x', plugins: { legend: { position: chartType === 'pie' ? 'bottom' : 'top', display: (chartType === 'pie' || numCategories <= 8), labels: { boxWidth: 15, font: { size: 10 } } }, tooltip: { callbacks: { label: function(context) { let label = context.label || ''; let value = context.raw; const percentage = grafico.percentuais ? grafico.percentuais[context.dataIndex] : null; let output = `${label}: ${value}`; if (percentage !== null && context.chart.config.type === 'pie') { output += ` (${percentage}%)`; } return output; }, title: function(context) { return (context[0].chart.config.type === 'pie' || context[0].chart.options.indexAxis === 'y') ? null : context[0].label; } } }, title: { display: false } }, scales: chartType === 'bar' ? { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { autoSkip: numCategories > 12, maxRotation: (chartType === 'bar' && numCategories > 6) ? 0 : 45, minRotation: 0, font: { size: 10 } } } } : {} };
              // Ajuste do eixo X e Y para barras horizontais
             if (chartType === 'bar' && currentOptions.indexAxis === 'y') {
                let temp = currentOptions.scales.x; currentOptions.scales.x = currentOptions.scales.y; currentOptions.scales.y = temp;
             }

             chartInstances[canvasId] = new Chart(ctx, { type: chartType, data: { labels: grafico.labels.map(formatLabel), datasets: [{ label: 'Contagem', data: grafico.data, backgroundColor: specificBackgrounds, borderColor: specificBorders, borderWidth: 1 }] }, options: currentOptions });
          } else if (ctx) { ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height); const container = ctx.canvas.parentNode; if (container) container.innerHTML = '<p class="text-muted text-center small p-3">Sem dados suficientes para este gráfico.</p>'; }
          else { console.warn(`Canvas com ID '${canvasId}' não encontrado.`); }
        }
      }
    });
  </script>
  <?php endif; ?>

</body>
</html>