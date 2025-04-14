<?php
session_start();
// Verificação de permissão (opcional, mas recomendado)
// if (!isset($_SESSION['usuario_permitido'])) { header('Location: ../../login/login.php'); exit; }

// Incluir arquivos necessários
include __DIR__ . '/../../config/conexao.php';
include __DIR__ . '/../../config/banco.php';
include __DIR__ . '/../../config/error_handler.php';

// --- Função Auxiliar para Formatar Valores (Mesma versão anterior) ---
function formatarValorCompletoParaExibicao($colunaPrefixo, $valor, $formato = 'html') {
    // ... (código completo da função formatarValorCompletoParaExibicao da versão anterior) ...
        if (is_null($valor) || $valor === '') {
        return ($formato === 'csv') ? '' : '-'; // Vazio no CSV, '-' no HTML
    }

    $colunaLimpa = preg_replace('/^(participante_|consumo_|ebia_)/', '', $colunaPrefixo);

    // Mapeamentos comuns (reutilizáveis)
    $mapSimNao = [1 => 'Sim', '0' => 'Não', true => 'Sim', false => 'Não'];
    $mapBooleanCols = [ // Colunas que representam Sim/Não (1/0)
        'resposta1', 'resposta2', 'resposta3', 'resposta4', 'resposta5', 'resposta6', 'resposta7', 'resposta8', // EBIA
        'usa_dispositivos', 'feijao', 'frutas_frescas', 'verduras_legumes', 'hamburguer_embutidos',
        'bebidas_adocadas', 'macarrao_instantaneo', 'biscoitos_recheados' // Consumo
    ];
    $mapGenero = ['masculino' => 'Masculino', 'feminino' => 'Feminino', 'transgenero' => 'Transgênero', 'nao_binario' => 'Não Binário', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapRaca = ['branco' => 'Branco', 'preto' => 'Preto', 'pardo' => 'Pardo', 'povos_originarios' => 'Povos Originários', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapEscolaridade = ['ensino_fundamental_incompleto' => 'Ens. Fundamental Incompleto', 'ensino_fundamental_completo' => 'Ens. Fundamental Completo', 'ensino_medio_incompleto' => 'Ens. Médio Incompleto', 'ensino_medio_completo' => 'Ens. Médio Completo', 'graduacao_incompleta' => 'Graduação Incompleta', 'graduacao_completa' => 'Graduação Completa', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapEstadoCivil = ['solteiro' => 'Solteiro(a)', 'casado' => 'Casado(a)', 'divorciado' => 'Divorciado(a)', 'viuvo' => 'Viúvo(a)', 'separado' => 'Separado(a)', 'uniao_estavel' => 'União Estável', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapSituacaoEmprego = ['meio_periodo' => 'Meio Período', 'tempo_integral' => 'Tempo Integral', 'autonomo' => 'Autônomo', 'desempregado' => 'Desempregado', 'incapaz_trabalhar' => 'Incapaz de Trabalhar', 'aposentado' => 'Aposentado', 'estudante' => 'Estudante', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapReligiao = ['catolico' => 'Católico', 'evangelico' => 'Evangélico', 'espirita' => 'Espírita', 'umbanda' => 'Umbanda', 'candomble' => 'Candomblé', 'ateu' => 'Ateu', 'nenhum' => 'Nenhuma', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapClassificacaoEbia = ['seguranca_alimentar' => 'Segurança Alimentar', 'inseguranca_leve' => 'Insegurança Leve', 'inseguranca_moderada' => 'Insegurança Moderada', 'inseguranca_grave' => 'Insegurança Grave'];

    $valorStr = is_scalar($valor) ? (string)$valor : ''; // Garante string para chaves de array, vazio se não for escalar

    // 1. Colunas Booleanas (Sim/Não)
    if (in_array($colunaLimpa, $mapBooleanCols)) {
        if (array_key_exists($valor, $mapSimNao)) return $mapSimNao[$valor];
        if (array_key_exists($valorStr, $mapSimNao)) return $mapSimNao[$valorStr];
        return ($formato === 'csv') ? '' : '-';
    }

    // 2. Colunas ENUM/Mapeadas
    switch ($colunaLimpa) {
        case 'genero':           return $mapGenero[$valorStr] ?? ucfirst(str_replace('_', ' ', $valorStr));
        case 'raca':             return $mapRaca[$valorStr] ?? ucfirst(str_replace('_', ' ', $valorStr));
        case 'escolaridade':     return $mapEscolaridade[$valorStr] ?? ucfirst(str_replace('_', ' ', $valorStr));
        case 'estado_civil':     return $mapEstadoCivil[$valorStr] ?? ucfirst(str_replace('_', ' ', $valorStr));
        case 'situacao_emprego': return $mapSituacaoEmprego[$valorStr] ?? ucfirst(str_replace('_', ' ', $valorStr));
        case 'religiao':         return $mapReligiao[$valorStr] ?? ucfirst(str_replace('_', ' ', $valorStr));
        case 'classificacao':    // EBIA Classificação
            if (isset($mapClassificacaoEbia[$valorStr])) {
                 if ($formato === 'html') {
                    $cssClass = str_replace('_', '-', $valorStr);
                    // Certifique-se de que as classes CSS (.seguranca-alimentar, .inseguranca-leve, etc.) existam no seu CSS
                    return '<span class="badge badge-pill ' . htmlspecialchars($cssClass) . '">' . htmlspecialchars($mapClassificacaoEbia[$valorStr]) . '</span>';
                 } else { // CSV
                    return $mapClassificacaoEbia[$valorStr];
                 }
            }
            return ucfirst(str_replace('_', ' ', $valorStr)); // Fallback
    }

    // 3. Tratamento JSON (beneficios_sociais)
    if ($colunaLimpa === 'beneficios_sociais') {
        $json = is_string($valorStr) ? json_decode($valorStr, true) : null;
        if ($json !== null && json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $jsonFiltrado = array_filter($json, function($item) { return !is_null($item) && $item !== ''; });
            if (!empty($jsonFiltrado)) {
                $itensFormatados = [];
                $textoOutros = isset($jsonFiltrado['Outros']) && is_string($jsonFiltrado['Outros']) && trim($jsonFiltrado['Outros']) !== '' ? trim($jsonFiltrado['Outros']) : null;
                foreach ($jsonFiltrado as $key => $item) {
                    if ($key === 'Outros') continue;
                    if (is_string($item)) { $itensFormatados[] = trim($item); }
                }
                $output = implode(', ', $itensFormatados);
                if ($textoOutros !== null) { $output .= (!empty($output) ? '; ' : '') . 'Outros: ' . htmlspecialchars($textoOutros); }
                return !empty($output) ? $output : (($formato === 'csv') ? '' : '-');
            }
        }
        return ($formato === 'csv') ? '' : '-';
    }

    // 4. Colunas com sufixo '_outro'
    if (str_ends_with($colunaLimpa, '_outro')) {
        return !empty($valorStr) ? htmlspecialchars($valorStr) : (($formato === 'csv') ? '' : '-');
    }

    // 5. Datas
    if (str_ends_with($colunaLimpa, 'data_cadastro') || str_ends_with($colunaLimpa, 'data_preenchimento')) {
         try {
            if (empty($valorStr) || $valorStr === '0000-00-00 00:00:00') return ($formato === 'csv') ? '' : '-';
            $date = new DateTime($valorStr);
            return $date->format('d/m/Y H:i');
         } catch (Exception $e) { return !empty($valorStr) ? htmlspecialchars($valorStr) : (($formato === 'csv') ? '' : '-'); }
    }

    // 6. Refeições
    if ($colunaLimpa === 'refeicoes') {
        return !empty($valorStr) ? htmlspecialchars(str_replace(',', ', ', $valorStr)) : (($formato === 'csv') ? '' : '-');
    }

    // 7. Valor padrão
    if (is_array($valor)) return ($formato === 'csv') ? '' : '[Dados complexos]';
    return htmlspecialchars($valorStr);
}
// --- Fim Função Auxiliar ---


// --- Função para Preparar Dados para Gráficos (MODIFICADA para incluir originalLabels) ---
/**
 * Prepara dados agregados para gráficos Chart.js, incluindo labels originais (BD).
 */
function preparar_dados_graficos_completo(array $dadosRelatorio, array $colunasDisponiveis, array $filtros): array {
    $graficos = [];
    $totalRegistros = count($dadosRelatorio);
    if ($totalRegistros === 0) return [];

    // Mapeamentos ENUM (precisamos deles aqui para gerar os labels formatados)
    $mapClassificacaoEbiaText = ['seguranca_alimentar' => 'Segurança Alimentar', 'inseguranca_leve' => 'Insegurança Leve', 'inseguranca_moderada' => 'Insegurança Moderada', 'inseguranca_grave' => 'Insegurança Grave'];
    $mapGeneroText = ['masculino' => 'Masculino', 'feminino' => 'Feminino', 'transgenero' => 'Transgênero', 'nao_binario' => 'Não Binário', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapRacaText = ['branco' => 'Branco', 'preto' => 'Preto', 'pardo' => 'Pardo', 'povos_originarios' => 'Povos Originários', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapEscolaridadeText = ['ensino_fundamental_incompleto' => 'Ens. Fundamental Incompleto', 'ensino_fundamental_completo' => 'Ens. Fundamental Completo', 'ensino_medio_incompleto' => 'Ens. Médio Incompleto', 'ensino_medio_completo' => 'Ens. Médio Completo', 'graduacao_incompleta' => 'Graduação Incompleta', 'graduacao_completa' => 'Graduação Completa', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapEstadoCivilText = ['solteiro' => 'Solteiro(a)', 'casado' => 'Casado(a)', 'divorciado' => 'Divorciado(a)', 'viuvo' => 'Viúvo(a)', 'separado' => 'Separado(a)', 'uniao_estavel' => 'União Estável', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapSituacaoEmpregoText = ['meio_periodo' => 'Meio Período', 'tempo_integral' => 'Tempo Integral', 'autonomo' => 'Autônomo', 'desempregado' => 'Desempregado', 'incapaz_trabalhar' => 'Incapaz de Trabalhar', 'aposentado' => 'Aposentado', 'estudante' => 'Estudante', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapReligiaoText = ['catolico' => 'Católico', 'evangelico' => 'Evangélico', 'espirita' => 'Espírita', 'umbanda' => 'Umbanda', 'candomble' => 'Candomblé', 'ateu' => 'Ateu', 'nenhum' => 'Nenhuma', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];


    $gerarContagem = function($colunaComPrefixo) use ($dadosRelatorio, $mapClassificacaoEbiaText, $mapGeneroText, $mapRacaText, $mapEscolaridadeText, $mapEstadoCivilText, $mapSituacaoEmpregoText, $mapReligiaoText) {
        if (!isset($dadosRelatorio[0][$colunaComPrefixo])) return null;

        $colunaLimpa = preg_replace('/^(participante_|consumo_|ebia_)/', '', $colunaComPrefixo);
        $valoresColuna = array_column($dadosRelatorio, $colunaComPrefixo);
        $valoresValidos = array_filter($valoresColuna, function($val) { return !is_null($val) && $val !== ''; });
        if (empty($valoresValidos)) return null;

        $contagem = [];
        $mapSim = [1, '1', true]; $mapNao = [0, '0', false];
        $booleanColumnsGrafico = ['resposta1', 'resposta2', 'resposta3', 'resposta4', 'resposta5', 'resposta6', 'resposta7', 'resposta8', 'usa_dispositivos', 'feijao', 'frutas_frescas', 'verduras_legumes', 'hamburguer_embutidos', 'bebidas_adocadas', 'macarrao_instantaneo', 'biscoitos_recheados'];
        $isBoolColumn = in_array($colunaLimpa, $booleanColumnsGrafico);

        // Agrega contagens por valor original do BD
        foreach ($valoresValidos as $valor) {
             if ($colunaLimpa === 'beneficios_sociais' && is_string($valor) && ($json = json_decode($valor, true)) !== null && json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                  if (!empty($json)) {
                    $textoOutros = isset($json['Outros']) && is_string($json['Outros']) && !empty(trim($json['Outros'])) ? trim($json['Outros']) : null;
                    $beneficiosIndividuais = 0;
                    foreach ($json as $key => $item) {
                        if ($key === 'Outros') continue;
                        if (is_string($item)) {
                            $chave = trim($item);
                            if (!empty($chave)) { $contagem[$chave] = ($contagem[$chave] ?? 0) + 1; $beneficiosIndividuais++; }
                        }
                    }
                     if ($textoOutros !== null && ($beneficiosIndividuais > 0 || count($json) === 1)) {
                         $chaveOutros = 'Outros Benefícios (detalhado)'; // Chave original para agrupar
                         $contagem[$chaveOutros] = ($contagem[$chaveOutros] ?? 0) + 1;
                     }
                  }
             } elseif ($colunaLimpa === 'refeicoes' && is_string($valor)) {
                 $refeicoesIndividuais = explode(',', $valor);
                 foreach ($refeicoesIndividuais as $ref) {
                     $chave = trim($ref);
                     if (!empty($chave)) { $contagem[$chave] = ($contagem[$chave] ?? 0) + 1; }
                 }
             } else {
                  $chave = is_scalar($valor) ? trim((string)$valor) : '[Valor Não Escalar]';
                  if ($chave !== '') { $contagem[$chave] = ($contagem[$chave] ?? 0) + 1; }
             }
         }
         if(empty($contagem)) return null;

         // Ordena e Prepara dados finais
         $labels = []; $data = []; $originalLabels = []; // <<< Array para labels originais
         if ($isBoolColumn) {
             $countSim = 0; $countNao = 0;
             foreach ($contagem as $chave => $qtd) {
                 if (in_array($chave, $mapSim, true)) $countSim += $qtd;
                 elseif (in_array($chave, $mapNao, true)) $countNao += $qtd;
             }
             if ($countSim > 0) { $labels[] = 'Sim'; $originalLabels[] = 1; $data[] = $countSim; } // Original é 1
             if ($countNao > 0) { $labels[] = 'Não'; $originalLabels[] = 0; $data[] = $countNao; } // Original é 0
             if (count($labels) == 2 && $labels[0] === 'Não') {
                 $labels = array_reverse($labels); $data = array_reverse($data); $originalLabels = array_reverse($originalLabels);
             }
         } elseif ($colunaLimpa === 'classificacao') {
              $order = ['seguranca_alimentar' => 1, 'inseguranca_leve' => 2, 'inseguranca_moderada' => 3, 'inseguranca_grave' => 4];
              uksort($contagem, function($a, $b) use ($order) { return ($order[$a] ?? 99) <=> ($order[$b] ?? 99); });
              $originalLabels = array_keys($contagem);
              $data = array_values($contagem);
              $labels = array_map(function($orig) use ($mapClassificacaoEbiaText) { return $mapClassificacaoEbiaText[$orig] ?? ucfirst(str_replace('_',' ',$orig)); }, $originalLabels);
         } else {
              arsort($contagem); // Ordena pela frequência
              $originalLabels = array_keys($contagem); // Labels originais são as chaves
              $data = array_values($contagem);
              // Mapeia labels originais para labels formatados
              $labels = array_map(function($orig) use ($colunaLimpa, $mapGeneroText, $mapRacaText, $mapEscolaridadeText, $mapEstadoCivilText, $mapSituacaoEmpregoText, $mapReligiaoText) {
                 $map = null;
                 switch($colunaLimpa) {
                     case 'genero': $map = $mapGeneroText; break;
                     case 'raca': $map = $mapRacaText; break;
                     case 'escolaridade': $map = $mapEscolaridadeText; break;
                     case 'estado_civil': $map = $mapEstadoCivilText; break;
                     case 'situacao_emprego': $map = $mapSituacaoEmpregoText; break;
                     case 'religiao': $map = $mapReligiaoText; break;
                 }
                 if ($map && isset($map[$orig])) return $map[$orig];
                 // Formatação fallback
                 if ($orig === 'Outros Benefícios (detalhado)') return 'Outros Benefícios'; // Label formatado
                 if(is_numeric($orig)) return $orig; // Mantém números
                 return ucfirst(str_replace('_',' ',$orig));
              }, $originalLabels);
         }

         if (empty($labels) || empty($data)) return null;
         $totalContagem = array_sum($data);
         $percentuais = $totalContagem > 0 ? array_map(fn($c) => round(($c / $totalContagem) * 100, 1), $data) : [];

         // Retorna incluindo originalLabels
         return ['labels' => $labels, 'data' => $data, 'originalLabels' => $originalLabels, 'percentuais' => $percentuais];
    };
    // --- Fim $gerarContagem ---


    // --- Colunas para Gerar Gráficos ---
    $colsDemograficas = ['participante_genero', 'participante_raca', 'participante_escolaridade', 'participante_situacao_emprego', 'participante_religiao', 'participante_estado_civil', 'participante_numero_dependentes', 'participante_beneficios_sociais'];
    $colsConsumo = ['consumo_refeicoes', 'consumo_usa_dispositivos', 'consumo_feijao', 'consumo_frutas_frescas', 'consumo_verduras_legumes', 'consumo_hamburguer_embutidos', 'consumo_bebidas_adocadas', 'consumo_macarrao_instantaneo', 'consumo_biscoitos_recheados'];
    $colEbiaClassificacao = 'ebia_classificacao';
    $colsEbiaRespostas = ['ebia_resposta1', 'ebia_resposta2', 'ebia_resposta3', 'ebia_resposta4', 'ebia_resposta5', 'ebia_resposta6', 'ebia_resposta7', 'ebia_resposta8'];
    $mapTitulos = [ /* ... Títulos como antes ... */
        'genero' => 'Distribuição por Gênero', 'raca' => 'Distribuição por Raça/Cor',
        'escolaridade' => 'Distribuição por Escolaridade', 'situacao_emprego' => 'Distribuição por Situação de Emprego',
        'religiao' => 'Distribuição por Religião', 'estado_civil' => 'Distribuição por Estado Civil',
        'numero_dependentes' => 'Distribuição por Nº de Dependentes',
        'beneficios_sociais' => 'Benefícios Sociais Recebidos (Frequência)',
        'refeicoes' => 'Refeições Realizadas (Frequência)',
        'usa_dispositivos' => 'Uso de Dispositivos na Refeição', 'feijao' => 'Consumo de Feijão (Ontem)',
        'frutas_frescas' => 'Consumo de Frutas Frescas (Ontem)', 'verduras_legumes' => 'Consumo de Verduras/Legumes (Ontem)',
        'hamburguer_embutidos' => 'Consumo de Hambúrguer/Embutidos (Ontem)', 'bebidas_adocadas' => 'Consumo de Bebidas Adoçadas (Ontem)',
        'macarrao_instantaneo' => 'Consumo de Macarrão Inst./Salgadinhos (Ontem)', 'biscoitos_recheados' => 'Consumo de Biscoitos Rech./Doces (Ontem)',
        'classificacao' => 'Distribuição por Classificação EBIA',
        'resposta1' => 'EBIA: Preocupação falta alim.?', 'resposta2' => 'EBIA: Alim. acabaram (sem $$)?',
        'resposta3' => 'EBIA: Sem $$ alim. saudável?',   'resposta4' => 'EBIA: Comeu poucos tipos (sem $$)?',
        'resposta5' => 'EBIA: Adulto pulou refeição?',   'resposta6' => 'EBIA: Adulto comeu menos?',
        'resposta7' => 'EBIA: Adulto sentiu fome?',      'resposta8' => 'EBIA: Adulto 1 ref./dia ou 0?',
    ];

    // --- Loop para gerar gráficos ---
    $colsParaLoop = array_merge($colsDemograficas, $colsConsumo, $colsEbiaRespostas);
     if(in_array($colEbiaClassificacao, $colunasDisponiveis)) $colsParaLoop[] = $colEbiaClassificacao; // Adiciona classificação se disponível

    foreach (array_unique($colsParaLoop) as $colGraf) { // Usa array_unique para evitar processar duas vezes
        if (in_array($colGraf, $colunasDisponiveis)) {
            $dadosGrafico = $gerarContagem($colGraf);
            if ($dadosGrafico && !empty($dadosGrafico['labels'])) {
                $colunaLimpa = preg_replace('/^(participante_|consumo_|ebia_)/', '', $colGraf);
                $titulo = $mapTitulos[$colunaLimpa] ?? 'Distribuição por ' . ucfirst(str_replace('_', ' ', $colunaLimpa));
                $filtroKey = $colunaLimpa;
                if (!empty($filtros[$filtroKey])) $titulo .= ' (Filtrado)';
                $graficos[$colunaLimpa] = $dadosGrafico + ['titulo' => $titulo, 'chaveOriginal' => $colGraf]; // Adiciona chaveOriginal
            }
        }
    }

    return $graficos;
}
// --- Fim Função Gráficos ---

// --- Inicialização e Processamento do Formulário (Como na versão anterior) ---
$relatorioCompleto = [];
$chartDataCompleto = [];
$filtrosAplicados = [];
$erro = '';
$colunasReais = [];
$modo_relatorio = 'filtrado';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $modo_relatorio = $_POST['modo_relatorio'] ?? 'filtrado';
    $tipo_relatorio_selecionado = $_POST['tipo_relatorio'] ?? 'completo';
    $filtros = [];
     if ($modo_relatorio === 'filtrado') {
        $idade_min_raw = filter_input(INPUT_POST, 'idade_min', FILTER_SANITIZE_NUMBER_INT);
        $idade_max_raw = filter_input(INPUT_POST, 'idade_max', FILTER_SANITIZE_NUMBER_INT);
        $idade_min = ($idade_min_raw !== '' && filter_var($idade_min_raw, FILTER_VALIDATE_INT) !== false && $idade_min_raw >= 0) ? (int)$idade_min_raw : null;
        $idade_max = ($idade_max_raw !== '' && filter_var($idade_max_raw, FILTER_VALIDATE_INT) !== false && $idade_max_raw >= 0) ? (int)$idade_max_raw : null;
        if ($idade_min !== null) $filtros['idade_min'] = $idade_min;
        if ($idade_max !== null) $filtros['idade_max'] = $idade_max;
        if (isset($filtros['idade_min']) && isset($filtros['idade_max']) && $filtros['idade_min'] > $filtros['idade_max']) { $erro = "A idade mínima não pode ser maior que a idade máxima."; unset($filtros['idade_min'], $filtros['idade_max']); }
        if (!empty($_POST['genero'])) $filtros['genero'] = trim(htmlspecialchars($_POST['genero']));
        if (!empty($_POST['raca'])) $filtros['raca'] = trim(htmlspecialchars($_POST['raca']));
        if (!empty($_POST['escolaridade'])) $filtros['escolaridade'] = trim(htmlspecialchars($_POST['escolaridade']));
        if (!empty($_POST['estado_civil'])) $filtros['estado_civil'] = trim(htmlspecialchars($_POST['estado_civil']));
        if (!empty($_POST['situacao_emprego'])) $filtros['situacao_emprego'] = trim(htmlspecialchars($_POST['situacao_emprego']));
        if (!empty($_POST['religiao'])) $filtros['religiao'] = trim(htmlspecialchars($_POST['religiao']));
        $filtrosAplicados = $filtros;
    }

    if (empty($erro)) {
        try {
             $relatorioCompleto = gerar_relatorio_completo($conexao, $filtros);
             if (!empty($relatorioCompleto)) { $colunasReais = array_keys($relatorioCompleto[0]); }
             else { $colunasReais = []; }
        } catch (PDOException $e) { $erro = "Erro DB"; error_log("Erro DB Relatorio Completo: " . $e->getMessage()); $relatorioCompleto = []; $colunasReais = []; $chartDataCompleto = []; }
          catch (Exception $e) { $erro = "Erro Geral"; error_log("Erro Geral Relatorio Completo: " . $e->getMessage()); $relatorioCompleto = []; $colunasReais = []; $chartDataCompleto = []; }
    }

    // --- Exportação CSV (Sem alterações) ---
    if (isset($_POST['exportar']) && $_POST['exportar'] == 'csv' && empty($erro) && !empty($relatorioCompleto)) {
        // ... (código da exportação CSV igual ao anterior) ...
         header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=nutriware_relatorio_completo_' . date('YmdHis') . '.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
        $headerMapCompleto = [
            'participante_id' => 'ID Part.', 'participante_nome' => 'Nome', 'participante_email' => 'Email',
            'participante_idade' => 'Idade', 'participante_genero' => 'Gênero', 'participante_genero_outro' => 'Gênero (Outro)',
            'participante_raca' => 'Raça/Cor', 'participante_raca_outro' => 'Raça/Cor (Outro)',
            'participante_escolaridade' => 'Escolaridade', 'participante_escolaridade_outro' => 'Escolaridade (Outro)',
            'participante_estado_civil' => 'Estado Civil', 'participante_situacao_emprego' => 'Situação Emprego',
            'participante_situacao_emprego_outro' => 'Situação Emprego (Outro)', 'participante_beneficios_sociais' => 'Benefícios Sociais',
            'participante_numero_dependentes' => 'Nº Dependentes', 'participante_religiao' => 'Religião',
            'participante_religiao_outro' => 'Religião (Outro)', 'participante_data_cadastro' => 'Data Cadastro',
            'consumo_refeicoes' => 'Cons. Refeições', 'consumo_usa_dispositivos' => 'Cons. Usa Disp.?',
            'consumo_feijao' => 'Cons. Feijão?', 'consumo_frutas_frescas' => 'Cons. Frutas Frescas?',
            'consumo_verduras_legumes' => 'Cons. Verd/Leg?', 'consumo_hamburguer_embutidos' => 'Cons. Hamb/Emb?',
            'consumo_bebidas_adocadas' => 'Cons. Beb. Adoç?', 'consumo_macarrao_instantaneo' => 'Cons. Mac. Inst?',
            'consumo_biscoitos_recheados' => 'Cons. Bisc. Rech?', 'consumo_data_preenchimento' => 'Data Consumo',
            'ebia_resposta1' => 'EBIA Q1', 'ebia_resposta2' => 'EBIA Q2', 'ebia_resposta3' => 'EBIA Q3',
            'ebia_resposta4' => 'EBIA Q4', 'ebia_resposta5' => 'EBIA Q5', 'ebia_resposta6' => 'EBIA Q6',
            'ebia_resposta7' => 'EBIA Q7', 'ebia_resposta8' => 'EBIA Q8',
            'ebia_pontuacao_total' => 'EBIA Pontos', 'ebia_classificacao' => 'EBIA Classif.',
            'ebia_data_preenchimento' => 'Data EBIA' ];
        $headerNomesCsv = array_map(function($col) use ($headerMapCompleto) { return $headerMapCompleto[$col] ?? ucfirst(str_replace(['participante_', 'consumo_', 'ebia_', '_'], ['', '', '', ' '], $col)); }, $colunasReais);
        fputcsv($output, $headerNomesCsv);
        foreach ($relatorioCompleto as $linha) {
            $linhaExport = []; foreach ($colunasReais as $col) { $linhaExport[] = formatarValorCompletoParaExibicao($col, $linha[$col] ?? null, 'csv'); } fputcsv($output, $linhaExport);
        } fclose($output); exit;
    }

    // --- PREPARAÇÃO DE DADOS PARA GRÁFICOS ---
    if (empty($erro) && !empty($relatorioCompleto)) {
        try { $chartDataCompleto = preparar_dados_graficos_completo($relatorioCompleto, $colunasReais, $filtrosAplicados); }
        catch (Exception $e) { error_log("Erro ao preparar dados para gráficos: " . $e->getMessage()); $chartDataCompleto = []; }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Relatório Completo Interativo - Nutriware</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
  <style>
    /* Estilos gerais */
    body { padding-bottom: 50px; }
    h1, h2 { text-align: center; margin-top: 1.5rem; margin-bottom: 1rem; }

    /* Tabela */
    .table-responsive { max-height: 65vh; /* Reduzido para dar espaço aos gráficos */ overflow: auto; margin-top: 1rem; border: 1px solid #dee2e6; }
    .table th, .table td { white-space: nowrap; font-size: 0.75rem; /* Menor */ vertical-align: middle; padding: 0.25rem 0.4rem; /* Menor */ }
    .table th { text-align: center; background-color: #e9ecef; position: sticky; top: 0; z-index: 10; }
    .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
    .table-hover tbody tr:hover { background-color: #f1f1f1; }
    .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(0,0,0,.03); }

    /* Card de Resumo */
    .card-body ul { padding-left: 20px; margin-bottom: 0; }

    /* Badges de Classificação EBIA */
    .badge { font-size: 0.75em; font-weight: bold; } /* Menor */
    .badge.seguranca-alimentar { background-color: #28a745 !important; color: white !important; }
    .badge.inseguranca-leve { background-color: #ffc107 !important; color: #212529 !important; }
    .badge.inseguranca-moderada { background-color: #fd7e14 !important; color: white !important; }
    .badge.inseguranca-grave { background-color: #dc3545 !important; color: white !important; }

    /* Gráficos */
    .grafico-container { cursor: pointer; /* Indica que é clicável */ }
    .chart-container { position: relative; margin: auto; height: 35vh; /* Menor */ width: 100%; max-width: 400px; /* Menor */ margin-bottom: 20px; }
    .chart-container-bar-wide { position: relative; margin: auto; height: 40vh; /* Menor */ width: 100%; max-width: 550px; margin-bottom: 20px; }
    .grafico-card { margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .grafico-card .card-header { font-size: 0.85rem; /* Menor */ font-weight: bold; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; }
    .grafico-card .card-body { padding: 0.5rem; } /* Menor */
    .no-data-message { color: #6c757d; font-style: italic; }

    /* Modal */
    .modal-dialog { max-width: 80%; /* Modal mais largo */ }
    .modal-body { max-height: 70vh; overflow-y: auto; }
    #tabelaDetalhes td, #tabelaDetalhes th { font-size: 0.8rem; white-space: normal; } /* Permite quebra de linha no modal */
    .loading-spinner { font-size: 2rem; color: #007bff; }
  </style>
</head>
<body class="container-fluid mt-4">
  <h1 class="mb-3">Relatório Completo Interativo</h1>

  <div class="text-center mb-4">
      <a href="relatorios.html" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar aos Filtros</a>
      <a href="../../login/menu/menu.php" class="btn btn-light">Voltar ao Menu Principal</a>
  </div>

  <?php if (!empty($erro)): ?>
    <div class="alert alert-danger text-center"> <strong>Erro:</strong> <?php echo htmlspecialchars($erro); ?> </div>
  <?php endif; ?>

  <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($erro)): ?>
    <?php if (empty($relatorioCompleto)): ?>
      <div class="alert alert-info text-center"> Nenhum participante encontrado para os critérios selecionados. </div>
    <?php else: ?>
      <div class="card mb-4">
         <div class="card-header">Resumo da Geração</div>
         <div class="card-body">
              <p><strong>Tipo:</strong> Completo Interativo</p>
              <p><strong>Modo:</strong> <?php echo htmlspecialchars(ucfirst($modo_relatorio)); ?></p>
              <?php if ($modo_relatorio === 'filtrado' && !empty($filtrosAplicados)): ?>
                  <p><strong>Filtros Aplicados:</strong></p>
                  <ul> <?php foreach ($filtrosAplicados as $key => $value): ?> <li><strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?>:</strong> <?php echo htmlspecialchars($value); ?></li> <?php endforeach; ?> </ul>
              <?php endif; ?>
              <p class="mb-0"><strong>Total de Registros:</strong> <?php echo count($relatorioCompleto); ?></p>
         </div>
      </div>

      <h2 class="mt-4">Dados Detalhados (Visão Geral)</h2>
       <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped table-hover table-sm">
          <thead>
             <tr>
                 <?php $headerMapHtml = $headerMapCompleto; // Reuse mapping from CSV ?>
                 <?php foreach ($colunasReais as $coluna): ?>
                     <?php
                        $nomeCabecalhoHtml = $headerMapHtml[$coluna] ?? ucfirst(str_replace(['participante_', 'consumo_', 'ebia_', '_'], ['', '', '', ' '], $coluna));
                        $mapTooltipsEbia = ['ebia_resposta1' => 'Preoc. falta alim.?', /* ... outros ... */ 'ebia_resposta8' => '1 ref./dia ou 0?'];
                        $tooltip = isset($mapTooltipsEbia[$coluna]) ? ' title="' . htmlspecialchars($mapTooltipsEbia[$coluna]) . '"' : '';
                     ?>
                     <th<?php echo $tooltip; ?>><?php echo htmlspecialchars($nomeCabecalhoHtml); ?></th>
                 <?php endforeach; ?>
             </tr>
          </thead>
          <tbody>
            <?php foreach ($relatorioCompleto as $linha): ?>
            <tr>
              <?php foreach ($colunasReais as $coluna): ?>
                <td><?php echo formatarValorCompletoParaExibicao($coluna, $linha[$coluna] ?? null, 'html'); ?></td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <form action="relatorio_completo.php" method="POST" class="mb-5 text-center" target="_blank">
           <input type="hidden" name="modo_relatorio" value="<?php echo htmlspecialchars($modo_relatorio); ?>">
           <input type="hidden" name="tipo_relatorio" value="completo">
           <?php if ($modo_relatorio === 'filtrado'): ?>
               <?php foreach ($filtrosAplicados as $key => $value): ?> <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>"> <?php endforeach; ?>
           <?php endif; ?>
           <button type="submit" name="exportar" value="csv" class="btn btn-success"> <i class="fas fa-file-csv"></i> Exportar Tabela Completa para CSV </button>
       </form>

      <?php if (!empty($chartDataCompleto)): ?>
          <hr class="my-4">
          <h2 class="mt-4 mb-3">Gráficos Interativos <small class="text-muted">(Clique nas barras/fatias para ver detalhes)</small></h2>
          <div class="row justify-content-center">
              <?php foreach ($chartDataCompleto as $keyGrafico => $grafico): ?>
                  <?php
                     $colSize = 'col-lg-4 col-md-6'; $containerClass = 'chart-container';
                     if ($keyGrafico === 'classificacao') { $colSize = 'col-lg-5 col-md-6'; }
                     elseif (($keyGrafico === 'beneficios_sociais' || $keyGrafico === 'numero_dependentes' || $keyGrafico === 'refeicoes') && count($grafico['labels']) > 6) { $colSize = 'col-lg-6 col-md-12'; $containerClass = 'chart-container-bar-wide'; }
                     $canvasId = 'chart-' . preg_replace('/[^a-zA-Z0-9-_]/', '', $keyGrafico);
                     // Armazena a chave original (com prefixo) e a chave limpa no container do gráfico
                     $dataAttributes = 'data-chart-key="' . htmlspecialchars($keyGrafico) . '" data-original-key="' . htmlspecialchars($grafico['chaveOriginal'] ?? $keyGrafico) . '"';
                  ?>
                  <div class="<?php echo $colSize; ?>">
                       <div class="card grafico-card">
                           <div class="card-header text-center"> <?php echo htmlspecialchars($grafico['titulo']); ?> </div>
                           <div class="card-body d-flex align-items-center justify-content-center grafico-container" <?php echo $dataAttributes; ?>>
                               <div class="<?php echo $containerClass; ?>">
                                   <canvas id="<?php echo htmlspecialchars($canvasId); ?>"></canvas>
                               </div>
                           </div>
                       </div>
                  </div>
              <?php endforeach; ?>
          </div>
      <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
          <div class="alert alert-warning text-center"> Não há dados agregados para gerar gráficos. </div>
      <?php endif; ?>
    <?php endif; ?>
  <?php endif; ?>

  <div class="modal fade" id="modalDetalhes" tabindex="-1" role="dialog" aria-labelledby="modalDetalhesLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document"> <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalDetalhesLabel">Detalhes do Grupo</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="modalLoading" class="text-center" style="display: none;">
            <i class="fas fa-spinner fa-spin loading-spinner"></i> Carregando detalhes...
          </div>
          <div id="modalError" class="alert alert-danger" style="display: none;"></div>
          <div id="modalContent" style="display: none;">
             <p>Exibindo detalhes para participantes com o critério selecionado:</p>
             <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped" id="tabelaDetalhes">
                    <thead>
                        </thead>
                    <tbody>
                        </tbody>
                </table>
             </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>


  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

  <?php if (!empty($chartDataCompleto)): ?>
  <script>
    // Passa os dados PHP (incluindo filtros e dados dos gráficos com originalLabels) para JavaScript
    const chartDataJS = JSON.parse('<?php echo json_encode($chartDataCompleto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>');
    const originalFiltersJS = JSON.parse('<?php echo json_encode($filtrosAplicados, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>');

    // Paleta de cores
    const backgroundColors = ['rgba(54, 162, 235, 0.7)','rgba(255, 99, 132, 0.7)','rgba(75, 192, 192, 0.7)','rgba(255, 206, 86, 0.7)','rgba(153, 102, 255, 0.7)','rgba(255, 159, 64, 0.7)','rgba(199, 199, 199, 0.7)','rgba(83, 102, 255, 0.7)','rgba(100, 255, 100, 0.7)', 'rgba(210, 130, 190, 0.7)', '#69A8E6','#FFB1C1','#A6E8D8','#FFD6A5','#CBAACB','#FFFFB5'];
    const borderColors = backgroundColors.map(color => color.replace('0.7', '1'));

    // Função para formatar labels (mantida da versão anterior)
    function formatLabel(labelKey, originalLabel) {
        // ... (código da função formatLabel da versão anterior) ...
         if (typeof originalLabel !== 'string') return String(originalLabel);
        const mapLabels = {
            'seguranca_alimentar': 'Segurança Alimentar', 'inseguranca_leve': 'Insegurança Leve', 'inseguranca_moderada': 'Insegurança Moderada', 'inseguranca_grave': 'Insegurança Grave',
            'ensino_fundamental_incompleto': 'Fund. Inc.', 'ensino_fundamental_completo': 'Fund. Comp.', 'ensino_medio_incompleto': 'Médio Inc.', 'ensino_medio_completo': 'Médio Comp.', 'graduacao_incompleta': 'Grad. Inc.', 'graduacao_completa': 'Grad. Comp.',
            'meio_periodo': 'Meio Período', 'tempo_integral': 'Tempo Integral', 'incapaz_trabalhar': 'Incapaz Trab.',
            'nao_binario': 'Não Binário', 'povos_originarios': 'Povos Originários', 'prefere_nao_dizer': 'Pref. Não Dizer', 'uniao_estavel': 'União Estável',
            'hamburguer_embutidos': 'Hamb./Emb.', 'bebidas_adocadas': 'Beb. Adoç.', 'macarrao_instantaneo': 'Mac. Inst./Salg.', 'biscoitos_recheados': 'Bisc. Rech./Doces',
            'usa_dispositivos': 'Usa Disp.?', 'Outros Benefícios (detalhado)': 'Outros Benef.',
        };
        if (mapLabels[originalLabel]) return mapLabels[originalLabel];
        if(originalLabel === 'Sim') return 'Sim'; if(originalLabel === 'Não') return 'Não';
        if (/^\d+(\s*ou\s*mais)?$/.test(originalLabel)) return originalLabel;
        return originalLabel.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    // Armazena instâncias de gráficos para destruição/atualização se necessário
    let chartInstances = {};

    // --- Função para lidar com o clique no gráfico ---
    function handleChartClick(evt, chartInstance) {
        const points = chartInstance.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);

        if (points.length) {
            const firstPoint = points[0];
            const clickedIndex = firstPoint.index;
            const chartKey = chartInstance.canvas.parentNode.parentNode.dataset.chartKey; // Pega do card-body
            const originalKey = chartInstance.canvas.parentNode.parentNode.dataset.originalKey; // Pega do card-body

            if (!chartDataJS[chartKey] || !chartDataJS[chartKey].originalLabels) {
                console.error("Dados do gráfico ou labels originais não encontrados para a chave:", chartKey);
                return;
            }

            const clickedOriginalValue = chartDataJS[chartKey].originalLabels[clickedIndex];
            const clickedFormattedLabel = chartInstance.data.labels[clickedIndex];
            const chartTitle = chartDataJS[chartKey].titulo.replace(' (Filtrado)',''); // Título base do gráfico

            // --- Preparar para mostrar o Modal ---
            $('#modalDetalhesLabel').text(`Detalhes para ${chartTitle}: ${clickedFormattedLabel}`);
            $('#modalContent').hide();
            $('#modalError').hide();
            $('#modalLoading').show();
            $('#modalDetalhes').modal('show');

            // --- Fazer a requisição AJAX ---
            fetch('api_relatorio_detalhado.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                     // Adicionar header CSRF se necessário: 'X-CSRF-TOKEN': 'seu_token_aqui'
                },
                body: JSON.stringify({
                    campo_original: originalKey, // Envia a chave original com prefixo
                    valor_clicado: clickedOriginalValue,
                    filtros_originais: originalFiltersJS
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                $('#modalLoading').hide();
                if (data.success && data.data) {
                    populateModalTable(data.data); // Função para preencher a tabela
                    $('#modalContent').show();
                } else {
                    $('#modalError').text(data.message || 'Erro ao buscar detalhes.').show();
                }
            })
            .catch(error => {
                console.error('Erro na requisição AJAX:', error);
                $('#modalLoading').hide();
                $('#modalError').text('Erro ao conectar com o servidor para buscar detalhes. Verifique a conexão.').show();
            });
        }
    }

    // --- Função para popular a tabela no Modal ---
    function populateModalTable(detailData) {
        const tableHead = $('#tabelaDetalhes > thead');
        const tableBody = $('#tabelaDetalhes > tbody');
        tableHead.empty();
        tableBody.empty();

        if (!detailData || detailData.length === 0) {
            tableBody.append('<tr><td colspan="100%" class="text-center text-muted">Nenhum participante encontrado para este critério.</td></tr>');
            return;
        }

        // Criar cabeçalho da tabela baseado nas chaves do primeiro objeto
        const headers = Object.keys(detailData[0]);
        let headerRow = '<tr>';
        headers.forEach(header => {
            // Formata o nome da coluna para exibição (remove prefixo, Title Case)
            let cleanHeader = header.replace(/^(participante_|consumo_|ebia_)/, '');
            let displayHeader = ucfirst(cleanHeader.replace(/_/g, ' '));
             if (header === 'participante_nome') displayHeader = 'Nome';
             if (header === 'participante_idade') displayHeader = 'Idade';
             if (header === 'ebia_classificacao') displayHeader = 'Classif. EBIA';
             // Adicionar mais mapeamentos se necessário
            headerRow += `<th>${escapeHtml(displayHeader)}</th>`;
        });
        headerRow += '</tr>';
        tableHead.append(headerRow);

        // Preencher corpo da tabela
        detailData.forEach(row => {
            let tableRow = '<tr>';
            headers.forEach(header => {
                // Usa o valor formatado diretamente da API (já tratou nulos, etc.)
                let value = row[header] !== null ? row[header] : '-';
                 // Aplica badge para classificação EBIA também no modal
                 if (header === 'ebia_classificacao' && value !== '-' && value !== '') {
                     let originalValue = value.toLowerCase().replace(/ /g, '_'); // Tenta reverter para o valor original
                     let cssClass = originalValue.replace(/_/g, '-');
                     tableRow += `<td><span class="badge badge-pill ${escapeHtml(cssClass)}">${escapeHtml(value)}</span></td>`;
                 } else {
                     tableRow += `<td>${escapeHtml(String(value))}</td>`;
                 }
            });
            tableRow += '</tr>';
            tableBody.append(tableRow);
        });
    }

     // Função simples para escapar HTML
     function escapeHtml(unsafe) {
         if (typeof unsafe !== 'string') return unsafe;
         return unsafe
              .replace(/&/g, "&amp;")
              .replace(/</g, "&lt;")
              .replace(/>/g, "&gt;")
              .replace(/"/g, "&quot;")
              .replace(/'/g, "&#039;");
     }
     function ucfirst(string) { return string.charAt(0).toUpperCase() + string.slice(1); }

    // --- Lógica de Renderização dos Gráficos Iniciais ---
    document.addEventListener('DOMContentLoaded', () => {
      Chart.register(ChartDataLabels); // Registra o plugin datalabels (se for usar)
      Chart.defaults.set('plugins.datalabels', { // Configurações padrão datalabels
        display: false // Desabilita por padrão
      });

      for (const key in chartDataJS) {
        if (chartDataJS.hasOwnProperty(key)) {
          const grafico = chartDataJS[key];
          const safeKey = key.replace(/[^a-zA-Z0-9-_]/g, '');
          const canvasId = `chart-${safeKey}`;
          const ctx = document.getElementById(canvasId)?.getContext('2d');

          if (ctx && grafico.labels && grafico.labels.length > 0 && grafico.data && grafico.data.length > 0) {
             if (chartInstances[canvasId]) { chartInstances[canvasId].destroy(); }

             let chartType = 'bar'; let indexAxis = 'x'; let specificBackgrounds = backgroundColors.slice(0, grafico.labels.length); let specificBorders = borderColors.slice(0, grafico.labels.length);
             const numCategories = grafico.labels.length;
             const isBooleanChart = numCategories <= 2 && (grafico.labels.includes('Sim') || grafico.labels.includes('Não'));
             const isClassification = key === 'classificacao'; const fewCategories = !isBooleanChart && !isClassification && numCategories <= 5;

             // Escolha do tipo de gráfico (como antes)
              if (key === 'refeicoes' || key === 'beneficios_sociais') { chartType = 'bar'; if (numCategories > 6) { indexAxis = 'y'; } }
              else if (isBooleanChart || isClassification || fewCategories) { chartType = 'pie'; /* Cores específicas */ if (isClassification) { const colorMapEbia = { 'Segurança Alimentar': 'rgba(75, 192, 192, 0.8)', 'Insegurança Leve': 'rgba(255, 206, 86, 0.8)', 'Insegurança Moderada': 'rgba(255, 159, 64, 0.8)', 'Insegurança Grave': 'rgba(255, 99, 132, 0.8)' }; specificBackgrounds = grafico.labels.map(label => colorMapEbia[label] || '#cccccc'); } else if (isBooleanChart) { const simColor = 'rgba(54, 162, 235, 0.8)'; const naoColor = 'rgba(255, 99, 132, 0.8)'; specificBackgrounds = grafico.labels.map(label => (label === 'Sim' ? simColor : naoColor)); if (grafico.labels.length === 2 && grafico.labels[0] === 'Não') specificBackgrounds = [naoColor, simColor]; else if (grafico.labels.length === 1 && grafico.labels[0] === 'Não') specificBackgrounds = [naoColor]; else if (grafico.labels.length === 1 && grafico.labels[0] === 'Sim') specificBackgrounds = [simColor]; } specificBorders = specificBackgrounds.map(color => color.replace('0.8', '1')); }
              else if (numCategories > 7) { chartType = 'bar'; indexAxis = 'y'; }

             // Opções do Gráfico (Adicionando onClick)
             let currentOptions = {
                responsive: true, maintainAspectRatio: false, indexAxis: indexAxis,
                onClick: (evt, elements, chart) => { // *** ADICIONADO onClick ***
                    if (elements.length > 0) {
                        handleChartClick(evt, chart); // Chama a função que faz o AJAX
                    }
                },
                plugins: {
                    legend: { position: chartType === 'pie' ? 'bottom' : 'top', display: (chartType === 'pie' || numCategories <= 10), labels: { boxWidth: 12, padding: 15, font: { size: 11 } } },
                    tooltip: { callbacks: { label: function(context) { let label = context.label || ''; let value = context.raw || 0; const percentage = grafico.percentuais ? grafico.percentuais[context.dataIndex] : null; let output = `${label}: ${value}`; if (percentage !== null) { output += ` (${percentage}%)`; } return output; }, title: function(context) { return chartType === 'pie' ? null : context[0]?.label; } } },
                    title: { display: false },
                     datalabels: { // Configuração datalabels (opcional)
                        display: (context) => { return chartType === 'pie' && context.dataset.data[context.dataIndex] > 0; }, // Mostrar em pizza
                        color: '#fff',
                        anchor: 'end', // Posição (start, center, end)
                        align: 'start', // Posição (start, center, end)
                        offset: -10, // Afastamento da borda
                        formatter: (value, context) => {
                             const percentage = grafico.percentuais ? grafico.percentuais[context.dataIndex] : null;
                             return percentage ? `${percentage}%` : '';
                        },
                        font: { weight: 'bold', size: 10 }
                     }
                },
                scales: {}
             };

             // Configura escalas para barras (como antes)
             if (chartType === 'bar') {
                 if (indexAxis === 'y') { currentOptions.scales = { x: { beginAtZero: true, title: { display: true, text: 'Contagem', font: { size: 11 } }, ticks: { precision: 0, font: {size: 10} } }, y: { ticks: { font: { size: (numCategories > 15 ? 9 : 11) } } } }; }
                 else { currentOptions.scales = { y: { beginAtZero: true, title: { display: true, text: 'Contagem', font: { size: 11 } }, ticks: { precision: 0, font: {size: 10} } }, x: { ticks: { autoSkip: numCategories > 12, maxRotation: 45, minRotation: 0, font: { size: (numCategories > 10 ? 9 : 11) } } } }; }
                 // Desabilitar datalabels para barras (pode poluir)
                 currentOptions.plugins.datalabels = { display: false };
             } else { delete currentOptions.scales; }

             // Cria o Gráfico
             chartInstances[canvasId] = new Chart(ctx, {
                type: chartType,
                data: { labels: grafico.labels, datasets: [{ label: 'Contagem', data: grafico.data, backgroundColor: specificBackgrounds, borderColor: specificBorders, borderWidth: 1 }] },
                options: currentOptions
             });

          } else if (ctx) {
             ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
             const container = ctx.canvas.parentNode;
             if (container) container.innerHTML = '<p class="text-muted text-center small p-3 no-data-message">Sem dados suficientes para este gráfico.</p>';
          } else { console.warn(`Canvas com ID '${canvasId}' não encontrado para o gráfico '${key}'.`); }
        }
      }
    });
  </script>
  <?php endif; ?>
  </body>
</html>