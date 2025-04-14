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

// --- Inicialização ---
$relatorio = [];
$chartData = [];
$filtrosAplicados = [];
$erro = '';
$colunasReais = [];
$tipo_relatorio = 'perfil'; // Default
$modo_relatorio = 'filtrado'; // Default

// --- Processamento do Formulário ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Obter tipo e modo do relatório
    $tipo_relatorio = $_POST['tipo_relatorio'] ?? 'perfil';
    $modo_relatorio = $_POST['modo_relatorio'] ?? 'filtrado';

    // --- Montar Filtros (apenas se modo='filtrado') ---
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
        // Não adicionamos filtros para dependentes/benefícios aqui, pois não há input no formulário relatorios.html

        $filtrosAplicados = $filtros;
    }

    // --- Buscar Dados ---
    if (empty($erro)) {
        try {
             // --- MODIFICADO: Define colunas desejadas incluindo 'nome' ---
             $colunasDesejadasEbia = ['nome', 'idade', 'genero', 'raca', 'escolaridade', 'pontuacao_total', 'classificacao']; // Exemplo para EBIA (já tinha nome)
             // *** MODIFICADO: Adicionado 'nome' e mantido 'id' (opcional) ***
             $colunasDesejadasPerfil = [
                 'nome', // <-- ADICIONADO
                 'id',   // <-- Pode remover se não precisar mais
                 'idade', 'genero', 'raca', 'escolaridade', 'estado_civil',
                 'situacao_emprego', 'religiao', 'numero_dependentes', 'beneficios_sociais'
             ];
             $colunasDesejadasConsumo = null; // Deixar a função usar o padrão

             switch ($tipo_relatorio) {
                case 'inseguranca':
                    // Se gerar EBIA por aqui, passar as colunas desejadas
                    $relatorio = gerar_relatorio_inseguranca_alimentar($conexao, $filtros, $colunasDesejadasEbia);
                    break;
                case 'consumo':
                    // Se gerar Consumo por aqui, passar as colunas desejadas
                    $relatorio = gerar_relatorio_consumo_alimentar($conexao, $filtros, $colunasDesejadasConsumo);
                    break;
                case 'completo':
                    $relatorio = gerar_relatorio_completo($conexao, $filtros); // Chama a nova função
                    break;
                case 'perfil':
                default:
                    // *** MODIFICADO: Passa as colunas desejadas para o perfil ***
                    $relatorio = gerar_relatorio_perfil($conexao, $filtros, $colunasDesejadasPerfil);
                    $tipo_relatorio = 'perfil'; // Garante o tipo default
                    break;
            }

             // Obter colunas reais DEPOIS de buscar os dados
             if (!empty($relatorio)) {
                 $colunasReais = array_keys($relatorio[0]);
                  // Opcional: Remover colunas que você NUNCA quer mostrar, mesmo que a função retorne
                  $colunasReais = array_diff($colunasReais, ['senha', 'email']); // Exemplo: remover email também
                  // Opcional: Se decidiu remover o ID completamente da exibição:
                  // $colunasReais = array_diff($colunasReais, ['id']);
             } else {
                 $colunasReais = [];
             }

        } catch (PDOException $e) {
            $erro = "Erro ao buscar dados no banco de dados.";
            error_log("Erro DB Relatorio ($tipo_relatorio): " . $e->getMessage());
            $relatorio = [];
            $colunasReais = [];
        } catch (Exception $e) {
             $erro = "Erro ao gerar relatório: " . $e->getMessage();
             error_log("Erro Geral Relatorio ($tipo_relatorio): " . $e->getMessage());
             $relatorio = [];
             $colunasReais = [];
        }
    }

    // --- Exportação CSV ---
    // O código existente para CSV já usa $colunasReais, então deve funcionar.
    // Apenas revise a lógica de tratamento de JSON/Booleanos se necessário para as novas colunas.
    if (isset($_POST['exportar']) && $_POST['exportar'] == 'csv' && empty($erro) && !empty($relatorio)) {
         header('Content-Type: text/csv; charset=utf-8');
         header('Content-Disposition: attachment; filename=nutriware_relatorio_' . $tipo_relatorio . '_' . date('YmdHis') . '.csv');
         $output = fopen('php://output', 'w');

         fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

         // Cabeçalho com nomes amigáveis baseado nas colunas reais
          $headerNomes = array_map(function($col) {
              // *** MODIFICADO: Mapear 'nome' para 'Nome' ***
              if ($col === 'nome') return 'Nome';
              // Remove prefixos comuns e formata
              $col = preg_replace('/^(participante_|consumo_|ebia_)/', '', $col);
              return ucfirst(str_replace('_', ' ', $col));
          }, $colunasReais);
          fputcsv($output, $headerNomes);

         // Dados
         foreach ($relatorio as $linha) {
             $linhaExport = [];
             foreach ($colunasReais as $col) {
                 $valor = $linha[$col] ?? '';
                  // Tratamento para CSV (igual ao anterior, mas agora inclui as novas colunas)
                  if (is_bool($valor)) {
                      $linhaExport[] = $valor ? 'Sim' : 'Não';
                  } elseif (is_string($valor) && ($json = json_decode($valor, true)) !== null && json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                       // Tratamento específico para JSON (beneficios_sociais)
                       if (isset($json['Outros']) && is_string($json['Outros'])) {
                           // Combina itens normais e o texto do 'Outros'
                           $outrosItens = array_diff_key($json, array_flip(['Outros']));
                           $itensNormais = array_filter($json, function($k){ return $k !== 'Outros'; }, ARRAY_FILTER_USE_KEY);
                           $textoFinal = implode('; ', $itensNormais);
                           if (!empty($textoFinal)) $textoFinal .= '; ';
                           $textoFinal .= 'Outros: ' . $json['Outros'];
                           $linhaExport[] = $textoFinal;
                       } else {
                           // Itens normais do JSON (pode ser array simples ou associativo)
                           $itensParaExportar = [];
                           foreach($json as $item) {
                               $itensParaExportar[] = is_scalar($item) ? (string)$item : json_encode($item); // Converte não-escalares para JSON
                           }
                           $linhaExport[] = implode('; ', $itensParaExportar);
                       }
                  } elseif (is_array($valor)){ // Caso raro, mas seguro
                       $linhaExport[] = implode('; ', $valor);
                  } elseif (is_null($valor)) {
                        $linhaExport[] = '';
                  }
                  else {
                      // Trata 0/1 como Não/Sim se for coluna de resposta EBIA ou Consumo
                      if (($valor === 0 || $valor === '0') && preg_match('/^(ebia_resposta|consumo_)/', $col)) {
                            $linhaExport[] = 'Não';
                      } elseif (($valor === 1 || $valor === '1') && preg_match('/^(ebia_resposta|consumo_)/', $col)) {
                            $linhaExport[] = 'Sim';
                      } else {
                            $linhaExport[] = (string)$valor; // Converte para string (incluindo 'nome')
                      }
                  }
             }
             fputcsv($output, $linhaExport);
         }
         fclose($output);
         exit;
    }

    // --- Preparação de Dados para Gráficos ---
    if (empty($erro) && !empty($relatorio)) {
        // Chama a função que agora também processará as novas colunas
        $chartData = preparar_dados_graficos($relatorio, $colunasReais, $filtrosAplicados);
    }

}

/**
 * Prepara dados para gráficos Chart.js.
 * @param array $dadosRelatorio Dados brutos do relatório.
 * @param array $colunasDisponiveis Nomes das colunas presentes nos dados.
 * @param array $filtros Filtros que foram aplicados.
 * @return array Dados formatados para os gráficos.
 */
function preparar_dados_graficos(array $dadosRelatorio, array $colunasDisponiveis, array $filtros): array {
    $graficos = [];
    $totalRegistros = count($dadosRelatorio);
     if ($totalRegistros === 0) return [];

    // Função interna para gerar dados de contagem
    $gerarContagem = function($coluna) use ($dadosRelatorio, $totalRegistros) {
        // Verifica se a coluna existe nos dados
        if (!isset($dadosRelatorio[0][$coluna])) return null;

        $valoresColuna = array_column($dadosRelatorio, $coluna);
        // Filtra valores nulos ou vazios para não contar como categorias
        $valoresValidos = array_filter($valoresColuna, function($val) { return $val !== null && $val !== ''; });

        if (empty($valoresValidos)) return null; // Se não há valores válidos, não gera gráfico

        $contagem = [];
        $isBoolLike = true; // Assume que é booleano até provar o contrário

        foreach ($valoresValidos as $valor) {
             // --- Tratamento para JSON (Benefícios Sociais) ---
             if (is_string($valor) && ($json = json_decode($valor, true)) !== null && json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                 $isBoolLike = false; // JSON não é booleano
                 if (!empty($json)) {
                    // Verifica se tem a chave 'Outros' com texto
                    $textoOutros = null;
                    if (isset($json['Outros']) && is_string($json['Outros']) && !empty(trim($json['Outros']))) {
                        $textoOutros = trim($json['Outros']);
                    }

                    // Conta cada item no JSON (exceto a chave 'Outros')
                    foreach ($json as $key => $item) {
                        if ($key === 'Outros') continue; // Pula a chave 'Outros'

                        // Usa o próprio item como chave de contagem (se for string)
                        // Se não for string, pode ser um array dentro do JSON, tratar como caso especial se necessário
                        $chave = trim(is_string($item) ? $item : json_encode($item));
                        if (!empty($chave)) {
                             $contagem[$chave] = ($contagem[$chave] ?? 0) + 1;
                        }
                    }

                    // Se havia texto em 'Outros', cria uma categoria separada para ele
                    if ($textoOutros !== null) {
                        $chaveOutros = 'Outros: ' . $textoOutros; // Categoria distinta para o texto 'Outros'
                        // Nota: Isso conta a *ocorrência* do campo outros, não cada palavra nele.
                        // Se precisar contar cada palavra, a lógica seria mais complexa.
                        $contagem[$chaveOutros] = ($contagem[$chaveOutros] ?? 0) + 1;
                    }
                 }
             }
             // --- Valor normal (não JSON ou JSON inválido/vazio) ---
             else {
                 // Converte para string ou usa placeholder
                 $chave = trim(is_scalar($valor) ? (string)$valor : 'Valor Complexo');
                 // Trata string vazia após trim como inválida aqui também
                 if ($chave !== '') {
                    $contagem[$chave] = ($contagem[$chave] ?? 0) + 1;
                    // Verifica se NÃO é booleano (0, 1, true, false) - case-insensitive para true/false
                    if (!in_array(strtolower($chave), ['0', '1', 'true', 'false'], true) && !is_bool($valor)) {
                        $isBoolLike = false;
                    }
                 } else {
                    // Se o valor original era '0', não marque como não-booleano
                    if ($valor !== 0 && $valor !== '0') {
                        $isBoolLike = false;
                    }
                 }
             }
         } // Fim foreach $valoresValidos

         if(empty($contagem)) return null; // Se nenhuma chave válida foi contada

         $labels = array_keys($contagem);
         $data = array_values($contagem);

          // Reordena e formata se for booleano (Sim/Não)
         if ($isBoolLike && count($labels) <= 2) {
             $newLabels = []; $newData = [];
             $mapSim = ['1', 'true']; $mapNao = ['0', 'false'];
             $countSim = 0; $countNao = 0;

             foreach ($labels as $i => $lbl) {
                 $lblLower = strtolower((string)$lbl); // Comparação case-insensitive
                 if (in_array($lblLower, $mapSim, true) || $dadosRelatorio[0][$coluna] === true) { // Inclui booleano true
                     $countSim += $data[$i];
                 } elseif (in_array($lblLower, $mapNao, true) || $dadosRelatorio[0][$coluna] === false) { // Inclui booleano false
                     $countNao += $data[$i];
                 }
                 // Se não for nem 0/1 nem true/false, ignora para contagem Sim/Não
             }

             // Adiciona apenas se houver contagem > 0
             if ($countSim > 0) { $newLabels[] = 'Sim'; $newData[] = $countSim; }
             if ($countNao > 0) { $newLabels[] = 'Não'; $newData[] = $countNao; }

             if (empty($newData)) return null; // Se não encontrou Sim nem Não

             // Garante a ordem Sim -> Não, se ambos existirem
             if (count($newData) == 2 && $newLabels[0] === 'Não') {
                 $labels = array_reverse($newLabels);
                 $data = array_reverse($newData);
             } else {
                 $labels = $newLabels;
                 $data = $newData;
             }
         } else {
              // Ordena por contagem (maior para menor) para não-booleanos
              arsort($contagem);
              $labels = array_keys($contagem);
              $data = array_values($contagem);
         }

         // Calcula percentuais
         $totalValidosContados = array_sum($data); // Usa a soma dos dados finais
         $percentuais = $totalValidosContados > 0 ? array_map(function($count) use ($totalValidosContados) {
             return round(($count / $totalValidosContados) * 100, 1);
         }, $data) : array_fill(0, count($data), 0.0);

         // Checagem final antes de retornar
         if (empty($labels) || empty($data)) return null;

        return ['labels' => $labels, 'data' => $data, 'percentuais' => $percentuais];
    };

    // *** MODIFICAÇÃO AQUI: Adicionar as novas colunas à lista a ser processada ***
    $colsParaGraficos = [
        'genero', 'raca', 'escolaridade', 'situacao_emprego', 'religiao',
        'estado_civil', 'numero_dependentes', 'beneficios_sociais' // Adicionadas
    ];

    // --- Loop para gerar gráficos demográficos, de dependentes e benefícios ---
    foreach ($colsParaGraficos as $colGraf) {
        // Define nomes de colunas com e sem prefixo (para compatibilidade com relatório completo)
        $colunaComPrefixo = 'participante_' . $colGraf;
        $colunaSemPrefixo = $colGraf;

        // Determina qual nome de coluna usar baseado no que está disponível nos dados recebidos
        $colunaParaUsar = null;
        if (in_array($colunaComPrefixo, $colunasDisponiveis)) {
            $colunaParaUsar = $colunaComPrefixo;
        } elseif (in_array($colunaSemPrefixo, $colunasDisponiveis)) {
            $colunaParaUsar = $colunaSemPrefixo;
        }

        // Pula se a coluna não foi encontrada nos dados
        if ($colunaParaUsar === null) {
            continue;
        }

        // Gera os dados de contagem para o gráfico
        $dadosGrafico = $gerarContagem($colunaParaUsar);

        if ($dadosGrafico) {
             // Ajusta a geração do título e chave do filtro
             // Usa o nome da coluna SEM prefixo para título e filtro
             $tituloBase = ucfirst(str_replace('_', ' ', $colunaSemPrefixo));
             // Mapeamentos específicos para títulos mais amigáveis
             $mapTitulos = [
                'Numero dependentes' => 'Número de Dependentes',
                'Beneficios sociais' => 'Benefícios Sociais Recebidos'
                // Adicione outros se necessário
             ];
             $titulo = 'Distribuição por ' . ($mapTitulos[$tituloBase] ?? $tituloBase); // Usa mapeamento ou título base

             // Adapta a chave do filtro (deve ser o nome sem prefixo, como vem do form)
             $filtroKey = $colunaSemPrefixo;
             if (!empty($filtros[$filtroKey])) $titulo .= ' (Filtrado)';

             // Usa a coluna SEM prefixo como chave do gráfico para consistência
             $graficos[$colunaSemPrefixo] = $dadosGrafico + ['titulo' => $titulo];
        }
    }

    // --- Gráfico de Classificação EBIA (lógica mantida, só gera se a coluna existir) ---
    $colClassificacao = 'ebia_classificacao';
    if (in_array($colClassificacao, $colunasDisponiveis)) {
         // A lógica existente para classificação EBIA continua a mesma...
         // ... (código de ordenação e contagem) ...
          $dadosGraficoEbia = $gerarContagem($colClassificacao);
          if ($dadosGraficoEbia) {
                // Ordem específica da EBIA
                $order = ['seguranca_alimentar' => 1, 'inseguranca_leve' => 2, 'inseguranca_moderada' => 3, 'inseguranca_grave' => 4];
                // Pega os valores da coluna de classificação dos dados originais
                $valoresClassificacao = array_column($dadosRelatorio, $colClassificacao);
                $valoresValidosClassif = array_filter($valoresClassificacao, function($val){ return $val!==null && $val!=='';});

                if(!empty($valoresValidosClassif)){
                     $contagemOriginal = array_count_values($valoresValidosClassif);
                     // Ordena a contagem usando a ordem definida
                     uksort($contagemOriginal, function($a, $b) use ($order) {
                         return ($order[$a] ?? 99) <=> ($order[$b] ?? 99);
                     });

                     $labelsOrdenados = array_keys($contagemOriginal);
                     $dataOrdenada = array_values($contagemOriginal);
                     $totalContagem = array_sum($dataOrdenada);
                     $percentuaisOrdenados = $totalContagem > 0 ? array_map(function($count) use ($totalContagem) { return round(($count / $totalContagem) * 100, 1); }, $dataOrdenada) : array_fill(0, count($dataOrdenada), 0.0);

                     // Adiciona ao array de gráficos
                     $graficos['ebia_classificacao'] = [ // Chave mantida com prefixo ou identificador único
                         'labels' => $labelsOrdenados,
                         'data' => $dataOrdenada,
                         'percentuais' => $percentuaisOrdenados,
                         'titulo' => 'Distribuição por Classificação EBIA'
                     ];
                }
          }
    }

     // --- Gráficos para colunas BOOLEAN/Checkbox do consumo (lógica mantida) ---
     $colunasConsumoBool = [
        'consumo_usa_dispositivos', 'consumo_feijao', 'consumo_frutas_frescas',
        'consumo_verduras_legumes', 'consumo_hamburguer_embutidos', 'consumo_bebidas_adocadas',
        'consumo_macarrao_instantaneo', 'consumo_biscoitos_recheados'
     ];
     $nomesAmigaveisConsumo = [ /* Mapeamento mantido */
        'consumo_usa_dispositivos' => 'Uso de Dispositivos na Refeição', 'consumo_feijao' => 'Consumo de Feijão',
        'consumo_frutas_frescas' => 'Consumo de Frutas Frescas', 'consumo_verduras_legumes' => 'Consumo de Verduras/Legumes',
        'consumo_hamburguer_embutidos' => 'Consumo de Hambúrguer/Embutidos', 'consumo_bebidas_adocadas' => 'Consumo de Bebidas Adoçadas',
        'consumo_macarrao_instantaneo' => 'Consumo de Macarrão Inst./Salgadinhos', 'consumo_biscoitos_recheados' => 'Consumo de Biscoitos Rech./Doces',
     ];

     foreach ($colunasConsumoBool as $colBool) {
         if (in_array($colBool, $colunasDisponiveis)) {
              $dadosGraficoConsumo = $gerarContagem($colBool);
              if ($dadosGraficoConsumo && !empty($dadosGraficoConsumo['labels'])) { // Verifica se há labels
                   $tituloGrafico = $nomesAmigaveisConsumo[$colBool] ?? ucfirst(str_replace('_', ' ', preg_replace('/^consumo_/', '', $colBool)));
                   $graficos[$colBool] = $dadosGraficoConsumo + ['titulo' => $tituloGrafico];
              }
         }
     }

    return $graficos;
}


// --- Exibição HTML ---
// O restante do seu HTML permanece o mesmo.
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Relatório Nutriware</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .chart-container {
      position: relative; margin: auto; height: 40vh; width: 100%; max-width: 450px; margin-bottom: 40px;
    }
    /* Ajuste para gráficos de barras mais largos (ex: benefícios) */
    .chart-container-bar-wide {
        position: relative; margin: auto; height: 45vh; width: 100%; max-width: 600px; margin-bottom: 40px;
    }
     th, td { white-space: normal; word-wrap: break-word; font-size: 0.85rem; vertical-align: top; }
     .table-responsive { max-height: 600px; overflow: auto; }
     .sticky-top { position: sticky; top: 0; background-color: #f8f9fa; z-index: 1020; }
     .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
     .card-body ul { padding-left: 20px; }
     /* Adiciona estilos para classificação EBIA na tabela */
     .badge.seguranca-alimentar { background-color: #28a745; color: white; }
     .badge.inseguranca-leve { background-color: #ffc107; color: #212529; }
     .badge.inseguranca-moderada { background-color: #fd7e14; color: white; }
     .badge.inseguranca-grave { background-color: #dc3545; color: white; }
  </style>
</head>
<body class="container mt-4 mb-5">
  <h1 class="mb-4">Resultado do Relatório Nutriware</h1>

  <a href="relatorios.html" class="btn btn-secondary mb-4">&laquo; Voltar aos Filtros</a>

  <?php if (!empty($erro)): ?>
    <div class="alert alert-danger">
      <strong>Erro:</strong> <?php echo htmlspecialchars($erro); ?>
    </div>
  <?php endif; ?>

  <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($erro)): ?>
    <?php if (empty($relatorio)): ?>
      <div class="alert alert-info">
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
                  <ul>
                      <?php foreach ($filtrosAplicados as $key => $value): ?>
                          <li><strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                      <?php endforeach; ?>
                  </ul>
              <?php elseif ($modo_relatorio === 'filtrado' && empty($filtrosAplicados)): ?>
                   <p><strong>Filtros Aplicados:</strong> Nenhum filtro específico selecionado.</p>
              <?php endif; ?>
              <p><strong>Total de Registros Encontrados:</strong> <?php echo count($relatorio); ?></p>
         </div>
      </div>

      <h2 class="mt-5">Dados Detalhados</h2>
      <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped table-hover table-sm">
          <thead class="thead-light sticky-top">
             <tr>
                 <?php // Cabeçalho dinâmico usando $colunasReais ?>
                 <?php foreach ($colunasReais as $coluna): ?>
                      <?php
                         // Formata o cabeçalho para ser mais legível
                         $nomeCabecalho = preg_replace('/^(participante_|consumo_|ebia_)/', '', $coluna); // Remove prefixos
                         $nomeCabecalho = ucfirst(str_replace('_', ' ', $nomeCabecalho)); // Capitaliza e substitui _

                         // *** MODIFICADO: Definir cabeçalho para 'nome' ***
                         if ($coluna === 'nome') {
                              $nomeCabecalho = 'Nome';
                         }
                         // Se decidiu remover ID, pode pular a coluna
                         // elseif ($coluna === 'id') {
                         //     continue;
                         // }
                         else {
                             // Mapeamentos específicos para cabeçalhos curtos (ex: EBIA)
                             $ebiaHeadersTabela = [
                                'Resposta1' => 'Q1', 'Resposta2' => 'Q2', 'Resposta3' => 'Q3', 'Resposta4' => 'Q4',
                                'Resposta5' => 'Q5', 'Resposta6' => 'Q6', 'Resposta7' => 'Q7', 'Resposta8' => 'Q8',
                                'Pontuacao total' => 'Pontos', 'Classificacao' => 'Classif.'
                             ];
                             // Mapeamento para cabeçalhos mais amigáveis (opcional)
                             $friendlyHeaders = [
                                'Numero dependentes' => 'Dependentes',
                                'Beneficios sociais' => 'Benefícios Soc.'
                                // Adicione outros se precisar
                             ];
                             if (isset($ebiaHeadersTabela[$nomeCabecalho])) {
                                 $nomeCabecalho = $ebiaHeadersTabela[$nomeCabecalho];
                             } elseif (isset($friendlyHeaders[$nomeCabecalho])) {
                                 $nomeCabecalho = $friendlyHeaders[$nomeCabecalho];
                             }
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
                <?php
                    // Se decidiu remover ID da exibição
                    // if ($coluna === 'id') continue;
                ?>
                <td>
                   <?php
                      $valor = $linha[$coluna] ?? null;
                      $colunaLimpa = preg_replace('/^(participante_|consumo_|ebia_)/', '', $coluna); // Remove prefixo para lógica

                      // --- Tratamento para exibição na tabela ---

                      // Trata arrays/json (ex: beneficios_sociais)
                      if ($colunaLimpa === 'beneficios_sociais' && is_string($valor) && ($json = json_decode($valor, true)) !== null && json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                          if (!empty($json)) {
                                $itensFormatados = [];
                                // Pega o texto do 'Outros' se existir
                                $textoOutros = isset($json['Outros']) && is_string($json['Outros']) ? trim($json['Outros']) : null;
                                // Pega os outros itens
                                foreach ($json as $key => $item) {
                                    if ($key !== 'Outros' && is_string($item)) {
                                        $itensFormatados[] = htmlspecialchars(trim($item));
                                    }
                                }
                                // Junta os itens normais
                                $output = implode(', ', $itensFormatados);
                                // Adiciona o texto do 'Outros'
                                if ($textoOutros) {
                                    if (!empty($output)) $output .= '; '; // Adiciona separador
                                    $output .= 'Outros: ' . htmlspecialchars($textoOutros);
                                }
                                echo $output;
                          } else { echo '-'; } // JSON vazio
                      }
                      // Trata Booleanos e Respostas EBIA/Consumo (0/1 para Não/Sim)
                      elseif (is_bool($valor) || preg_match('/^(ebia_resposta[1-8]|consumo_)/', $coluna)) {
                          // Verifica se é explicitamente 0 ou 1 (string ou int) ou booleano
                          if ($valor === 1 || $valor === '1' || $valor === true) {
                              echo 'Sim';
                          } elseif ($valor === 0 || $valor === '0' || $valor === false) {
                               // Não exibe 'Não' se o valor original for NULL (importante para LEFT JOIN)
                               if ($valor !== null) {
                                  echo 'Não';
                               } else {
                                  echo '-'; // Valor era NULL
                               }
                          } elseif (is_null($valor)) {
                               echo '-'; // Mantém traço para nulos
                          } else {
                               echo htmlspecialchars((string)$valor); // Caso estranho, mostra como string
                          }
                      }
                      // Trata Classificação EBIA com Badges
                      elseif ($coluna === 'ebia_classificacao' && !empty($valor)) {
                          $cssClass = str_replace('_', '-', $valor); // Converte para nome de classe CSS
                          echo '<span class="badge badge-pill ' . $cssClass . '">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $valor))) . '</span>';
                      }
                      // Trata outros valores nulos/vazios
                      elseif (is_null($valor) || $valor === '') {
                           echo '-';
                      }
                      // Exibe outros valores como string (incluindo NOME, numero_dependentes, religiao, etc.)
                      else {
                           echo htmlspecialchars((string)$valor); // Exibirá o nome aqui
                      }
                   ?>
                 </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <form action="relatorio_perfil_participante.php" method="POST" class="mb-5" target="_blank">
          <?php // Campos hidden para reenviar filtros na exportação ?>
          <input type="hidden" name="tipo_relatorio" value="<?php echo htmlspecialchars($tipo_relatorio); ?>">
          <input type="hidden" name="modo_relatorio" value="<?php echo htmlspecialchars($modo_relatorio); ?>">
          <?php if ($modo_relatorio === 'filtrado'): ?>
              <?php foreach ($filtrosAplicados as $key => $value): ?>
                   <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
              <?php endforeach; ?>
          <?php endif; ?>
          <button type="submit" name="exportar" value="csv" class="btn btn-success">
                <i class="fas fa-file-csv"></i> Exportar Tabela para CSV
          </button>
       </form>


      <h2 class="mt-5 mb-4">Gráficos Resumo</h2>
      <div class="row">
          <?php if (!empty($chartData)): ?>
              <?php foreach ($chartData as $key => $grafico): ?>
                  <?php
                     // Ajusta o tamanho da coluna e classe do container baseado na chave
                     $colSize = 'col-lg-4 col-md-6'; // Padrão
                     $containerClass = 'chart-container'; // Padrão
                     if ($key === 'ebia_classificacao') {
                         $colSize = 'col-lg-6 col-md-12'; // Classificação maior
                     } elseif ($key === 'beneficios_sociais' || $key === 'numero_dependentes') {
                         // Gráfico de benefícios ou dependentes pode precisar de mais espaço se houver muitas categorias
                         if (count($grafico['labels']) > 5) {
                             $colSize = 'col-lg-6 col-md-12';
                             $containerClass = 'chart-container-bar-wide'; // Usa classe CSS para container mais largo
                         }
                     }
                     // Remove caracteres inválidos para ID HTML
                     $canvasKey = preg_replace('/[^a-zA-Z0-9-_]/', '', $key);
                  ?>
                  <div class="<?php echo $colSize; ?> mb-4">
                       <div class="card h-100">
                           <div class="card-header text-center">
                               <?php echo htmlspecialchars($grafico['titulo']); ?>
                           </div>
                           <div class="card-body d-flex align-items-center justify-content-center">
                               <div class="<?php echo $containerClass; ?>">
                                   <canvas id="chart-<?php echo htmlspecialchars($canvasKey); ?>"></canvas>
                               </div>
                           </div>
                       </div>
                  </div>
              <?php endforeach; ?>
          <?php else: ?>
              <div class="col-12">
                  <div class="alert alert-warning">Não há dados agregados suficientes ou aplicáveis para gerar gráficos para este relatório.</div>
              </div>
          <?php endif; ?>
      </div>

    <?php endif; // Fim do if (empty($relatorio)) ?>
  <?php endif; // Fim do if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($erro)) ?>


  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
  <?php // FontAwesome (opcional para ícone CSV) ?>
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>


  <?php if (!empty($chartData)): ?>
  <script>
    const chartDataJS = JSON.parse('<?php echo json_encode($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>');
    const backgroundColors = ['rgba(54, 162, 235, 0.7)','rgba(255, 99, 132, 0.7)','rgba(75, 192, 192, 0.7)','rgba(255, 206, 86, 0.7)','rgba(153, 102, 255, 0.7)','rgba(255, 159, 64, 0.7)','rgba(199, 199, 199, 0.7)','rgba(83, 102, 255, 0.7)','rgba(100, 255, 100, 0.7)', 'rgba(210, 130, 190, 0.7)', '#69A8E6','#FFB1C1','#A6E8D8','#FFD6A5','#CBAACB','#FFFFB5']; // Mais cores
    const borderColors = backgroundColors.map(color => color.replace('0.7', '1'));

    // Função para formatar labels (melhorada para remover prefixos comuns e mapear valores)
    function formatLabel(label) {
        if (typeof label !== 'string') return String(label); // Converte não-string para string

         // Mapeamentos EXATOS primeiro (para classificações, etc.)
         const exactMap = {
            'seguranca_alimentar': 'Segurança Alimentar',
            'inseguranca_leve': 'Insegurança Leve',
            'inseguranca_moderada': 'Insegurança Moderada',
            'inseguranca_grave': 'Insegurança Grave',
            // Adicione outros mapeamentos exatos se necessário
        };
        if (exactMap[label]) return exactMap[label];

         // Remove prefixos comuns DEPOIS do mapeamento exato
         let cleanLabel = label.replace(/^(participante_|consumo_|ebia_)/, '', );

         // Mapeamentos específicos (após remover prefixo) - Adapte conforme seus valores reais
         const map = {
            // Consumo
            'usa_dispositivos': 'Usa Dispositivos?', 'hamburguer_embutidos': 'Hambúrguer/Embutidos',
            'bebidas_adocadas': 'Bebidas Adoçadas', 'macarrao_instantaneo': 'Macarrão Inst./Salgadinhos',
            'biscoitos_recheados': 'Biscoitos Rech./Doces',
            // Demográficos
            'nao_binario': 'Não Binário', 'povos_originarios': 'Povos Originários',
            'prefere_nao_dizer': 'Pref. Não Dizer',
            'ensino_fundamental_incompleto': 'Fund. Incompleto', 'ensino_fundamental_completo': 'Fund. Completo',
            'ensino_medio_incompleto': 'Médio Incompleto', 'ensino_medio_completo': 'Médio Completo',
            'graduacao_incompleta': 'Grad. Incompleta', 'graduacao_completa': 'Grad. Completa',
            'meio_periodo': 'Meio Período', 'tempo_integral': 'Tempo Integral', 'incapaz_trabalhar': 'Incapaz de Trabalhar',
            'uniao_estavel': 'União Estável',
            // --- MODIFICADO: Adicionar mapeamentos para dependentes ---
            '0': '0 (Nenhum)',
            '1': '1 Dependente',
            '2': '2 Dependentes',
            '3': '3 Dependentes',
            '4 ou mais': '4 ou Mais Dep.',
            // Adicione outros mapeamentos de valores ENUM/etc. se necessário
        };
        // Aplica mapeamento se existir para a label limpa
        if (map[cleanLabel]) return map[cleanLabel];

        // Formatação genérica para o que sobrou (capitaliza palavras)
        // Trata especificamente o caso 'Outros: texto' vindo do JSON de benefícios
        if (cleanLabel.startsWith('Outros: ')) {
            return 'Outros Benefícios'; // Label genérica para agrupar diferentes 'Outros' no gráfico
        }
        // Trata outros valores "Outro"
        if (cleanLabel.toLowerCase() === 'outro') {
            return 'Outro';
        }

        // Capitaliza palavras normais
        return cleanLabel.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    document.addEventListener('DOMContentLoaded', () => {
      let chartInstances = {};

      for (const key in chartDataJS) {
        if (chartDataJS.hasOwnProperty(key)) {
          const grafico = chartDataJS[key];
           // Garante que key seja um ID válido para HTML
           const safeKey = key.replace(/[^a-zA-Z0-9-_]/g, '');
           const canvasId = `chart-${safeKey}`;
          const ctx = document.getElementById(canvasId)?.getContext('2d');

          if (ctx && grafico.labels && grafico.labels.length > 0 && grafico.data && grafico.data.length > 0) {
             // Destroi gráfico anterior se existir
             if (chartInstances[canvasId]) { chartInstances[canvasId].destroy(); }

            // --- Lógica para Tipo de Gráfico e Cores ---
            let chartType = 'bar'; // Default para barra
            let specificBackgrounds = backgroundColors.slice(0, grafico.labels.length);
            let specificBorders = borderColors.slice(0, grafico.labels.length);
            const isBooleanChart = grafico.labels.length <= 2 && (grafico.labels.includes('Sim') || grafico.labels.includes('Não'));
            const isClassification = key === 'ebia_classificacao';
            const numCategories = grafico.labels.length;

            // Usar Pizza para booleanos (Sim/Não), classificação EBIA e outras colunas com poucas categorias (<= 6)
             if (isBooleanChart || isClassification || (!isBooleanChart && numCategories <= 6)) {
                chartType = 'pie';
                // Cores específicas para classificação EBIA
                if (isClassification) {
                   const colorMap = { /* ... cores EBIA ... */
                        'Segurança Alimentar': 'rgba(75, 192, 192, 0.7)', 'Insegurança Leve': 'rgba(255, 206, 86, 0.7)',
                        'Insegurança Moderada': 'rgba(255, 159, 64, 0.7)', 'Insegurança Grave': 'rgba(255, 99, 132, 0.7)'
                    };
                   specificBackgrounds = grafico.labels.map(label => colorMap[formatLabel(label)] || '#cccccc');
                }
                // Cores específicas para Sim/Não
                else if (isBooleanChart) {
                    const simColor = 'rgba(54, 162, 235, 0.7)'; const naoColor = 'rgba(255, 99, 132, 0.7)';
                    specificBackgrounds = grafico.labels.map(label => (label === 'Sim' ? simColor : naoColor));
                    if (grafico.labels.length === 2 && grafico.labels[0] === 'Não') { specificBackgrounds = [naoColor, simColor]; }
                    else if (grafico.labels.length === 1 && grafico.labels[0] === 'Não'){ specificBackgrounds = [naoColor]; }
                    else if (grafico.labels.length === 1 && grafico.labels[0] === 'Sim'){ specificBackgrounds = [simColor]; }
                }
                // Para outros gráficos de pizza (demográficos com poucas categorias), usa as cores padrão
                specificBorders = specificBackgrounds.map(color => color.replace('0.7', '1'));
             }
             // Para 'beneficios_sociais', forçar barra se tiver muitas categorias
             else if (key === 'beneficios_sociais' && numCategories > 6) {
                 chartType = 'bar';
             }
             // Para 'numero_dependentes', forçar barra se tiver muitas categorias (incluindo "Outro")
             else if (key === 'numero_dependentes' && numCategories > 6) {
                 chartType = 'bar';
             }
             // Outros casos com muitas categorias permanecem como barra (default)


            // Cria o novo gráfico
            chartInstances[canvasId] = new Chart(ctx, {
              type: chartType,
              data: {
                labels: grafico.labels.map(formatLabel), // Formata os labels para exibição
                datasets: [{
                  label: 'Contagem', // Label do dataset
                  data: grafico.data,
                  backgroundColor: specificBackgrounds,
                  borderColor: specificBorders,
                  borderWidth: 1
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                 indexAxis: (chartType === 'bar' && numCategories > 6) ? 'y' : 'x', // Eixo Y para barras com muitas categorias
                plugins: {
                     legend: {
                        position: chartType === 'pie' ? 'bottom' : 'top',
                        display: (chartType === 'pie' || numCategories <= 8), // Esconde legenda se muitas barras
                        labels: { boxWidth: 15, font: { size: 10 } }
                     },
                     tooltip: {
                        callbacks: {
                           label: function(context) {
                                let label = context.label || '';
                                let value = context.raw;
                                const percentage = grafico.percentuais ? grafico.percentuais[context.dataIndex] : null;
                                let output = `${label}: ${value}`;
                                if (percentage !== null && context.chart.config.type === 'pie') {
                                    output += ` (${percentage}%)`;
                                }
                                return output;
                           },
                            title: function(context) {
                                // Para barras horizontais, o label já está no eixo Y, então não precisa de título no tooltip
                                return (context[0].chart.config.type === 'pie' || context[0].chart.options.indexAxis === 'y') ? null : context[0].label;
                           }
                        }
                     },
                     title: { display: false }
                 },
                 scales: chartType === 'bar' ? {
                     // Configuração dos eixos X e Y dependendo da orientação (indexAxis)
                     [chartInstances[canvasId]?.options.indexAxis === 'y' ? 'x' : 'y']: { // Eixo do valor (numérico)
                        beginAtZero: true,
                        ticks: { precision: 0 }
                     },
                     [chartInstances[canvasId]?.options.indexAxis === 'y' ? 'y' : 'x']: { // Eixo da categoria (labels)
                        ticks: {
                            autoSkip: numCategories > 12, // Pula mais se muitas categorias
                            maxRotation: (chartInstances[canvasId]?.options.indexAxis === 'y') ? 0 : 45, // Sem rotação para barras horizontais
                            minRotation: 0,
                            font: { size: 10 }
                        }
                     }
                 } : {} // Sem eixos para gráfico de pizza
              }
            });
          } else if (ctx) {
               // Limpa canvas se não houver dados
               ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
               const container = ctx.canvas.parentNode;
               if (container) container.innerHTML = '<p class="text-muted text-center small p-3">Sem dados suficientes para este gráfico.</p>';
          } else {
              console.warn(`Canvas com ID '${canvasId}' não encontrado.`);
          }
        }
      }
    });
  </script>
  <?php endif; ?>

</body>
</html>