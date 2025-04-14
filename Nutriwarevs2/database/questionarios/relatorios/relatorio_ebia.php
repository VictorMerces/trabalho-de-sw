<?php
session_start();

// Incluir arquivos necessários
include '../../config/conexao.php';
include '../../config/banco.php'; // Onde está gerar_relatorio_inseguranca_alimentar
include '../../config/error_handler.php';

// --- Inicialização ---
$relatorioEbia = [];
$chartData = [];
$filtrosAplicados = [];
$erro = '';
$colunasReais = [];
$modo_relatorio = 'filtrado'; // Manter a opção de filtro

// --- MODIFICADO: Colunas Desejadas para este Relatório Específico ---
$colunasDesejadas = [
    // Substituído 'id' por 'nome'
    'nome',
    // Manter colunas EBIA
    'resposta1', 'resposta2', 'resposta3', 'resposta4',
    'resposta5', 'resposta6', 'resposta7', 'resposta8',
    'pontuacao_total', 'classificacao'
    // Pode adicionar 'idade', 'genero', etc., de volta se quiser mais contexto na tabela
];
// --- FIM DA MODIFICAÇÃO ---

// --- Processamento do Formulário (para filtros) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $modo_relatorio = $_POST['modo_relatorio'] ?? 'filtrado'; // Pode ser 'filtrado' ou 'geral'

    // --- Montar Filtros ---
    $filtros = [];
    if ($modo_relatorio === 'filtrado') {
        // Filtros por Idade (mantidos)
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

        // Outros filtros que ainda fazem sentido (adapte conforme necessário)
        if (!empty($_POST['genero'])) $filtros['genero'] = trim(htmlspecialchars($_POST['genero']));
        if (!empty($_POST['raca'])) $filtros['raca'] = trim(htmlspecialchars($_POST['raca']));
        if (!empty($_POST['escolaridade'])) $filtros['escolaridade'] = trim(htmlspecialchars($_POST['escolaridade']));
        if (!empty($_POST['estado_civil'])) $filtros['estado_civil'] = trim(htmlspecialchars($_POST['estado_civil']));
        if (!empty($_POST['situacao_emprego'])) $filtros['situacao_emprego'] = trim(htmlspecialchars($_POST['situacao_emprego']));
        if (!empty($_POST['religiao'])) $filtros['religiao'] = trim(htmlspecialchars($_POST['religiao']));

        $filtrosAplicados = $filtros;
    }

    // --- Buscar Dados ---
    if (empty($erro)) {
        try {
             // Chama a função que agora buscará 'nome' em vez de 'id'
             $relatorioEbia = gerar_relatorio_inseguranca_alimentar($conexao, $filtros, $colunasDesejadas);

             if (!empty($relatorioEbia)) {
                 $colunasReais = array_keys($relatorioEbia[0]); // Pegará 'nome' e as colunas EBIA
             } else {
                 $colunasReais = [];
             }

        } catch (PDOException $e) {
            $erro = "Erro ao buscar dados EBIA no banco de dados.";
            error_log("Erro DB Relatorio EBIA: " . $e->getMessage());
            $relatorioEbia = []; $colunasReais = [];
        } catch (Exception $e) {
             $erro = "Erro ao gerar relatório EBIA: " . $e->getMessage();
             error_log("Erro Geral Relatorio EBIA: " . $e->getMessage());
             $relatorioEbia = []; $colunasReais = [];
        }
    }

    // --- Exportação CSV (irá incluir 'nome' automaticamente) ---
    if (isset($_POST['exportar']) && $_POST['exportar'] == 'csv' && empty($erro) && !empty($relatorioEbia)) {
         header('Content-Type: text/csv; charset=utf-8');
         header('Content-Disposition: attachment; filename=nutriware_relatorio_ebia_' . date('YmdHis') . '.csv');
         $output = fopen('php://output', 'w');
         fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
         // Cabeçalho CSV - O mapeamento genérico tratará 'nome' corretamente
         $headerNomes = array_map(function($col) {
              $ebiaHeaders = [ /* ... mapeamentos EBIA ... */
                   'resposta1' => 'EBIA 1: Preocupação falta alim.', 'resposta2' => 'EBIA 2: Alim. acabaram (sem $$)',
                   'resposta3' => 'EBIA 3: Sem $$ alim. saudável',   'resposta4' => 'EBIA 4: Comeu poucos tipos (sem $$)',
                   'resposta5' => 'EBIA 5: Adulto pulou refeição',   'resposta6' => 'EBIA 6: Adulto comeu menos',
                   'resposta7' => 'EBIA 7: Adulto sentiu fome',      'resposta8' => 'EBIA 8: Adulto 1 ref./dia ou 0',
                   'pontuacao_total' => 'EBIA Pontuação',             'classificacao' => 'EBIA Classificação',
              ];
              if (isset($ebiaHeaders[$col])) return $ebiaHeaders[$col];
              return ucfirst(str_replace('_', ' ', $col)); // 'nome' vira 'Nome'
         }, $colunasReais);
         fputcsv($output, $headerNomes);
         // Dados CSV (lógica mantida)
         foreach ($relatorioEbia as $linha) {
             $linhaExport = [];
             foreach ($colunasReais as $col) {
                 $valor = $linha[$col] ?? '';
                  if (preg_match('/^resposta[1-8]$/', $col) && ($valor === 1 || $valor === 0 || $valor === '1' || $valor === '0')) {
                      $linhaExport[] = ($valor == 1) ? 'Sim' : 'Não';
                  } elseif (is_bool($valor)) { $linhaExport[] = $valor ? 'Sim' : 'Não';
                  } elseif (is_null($valor)) { $linhaExport[] = '';
                  } else { $linhaExport[] = (string)$valor; }
             }
             fputcsv($output, $linhaExport);
         }
         fclose($output);
         exit;
    }


    // --- Preparação de Dados para Gráficos ---
    if (empty($erro) && !empty($relatorioEbia)) {
        // A função preparar_dados_graficos_ebia não será afetada, pois não usava 'id'
        $chartData = preparar_dados_graficos_ebia($relatorioEbia, $colunasReais, $filtrosAplicados);
    }
}

/**
 * Função preparar_dados_graficos_ebia (SEM ALTERAÇÕES NESTA ETAPA)
 * Continua a mesma da versão anterior (sem gráficos demográficos, com títulos completos).
 */
function preparar_dados_graficos_ebia(array $dadosRelatorio, array $colunasDisponiveis, array $filtros): array {
    $graficos = [];
    $totalRegistros = count($dadosRelatorio);
    if ($totalRegistros === 0) return [];

    // Função interna para gerar contagem (mantida)
    $gerarContagem = function($coluna) use ($dadosRelatorio, $totalRegistros) {
         if (!isset($dadosRelatorio[0][$coluna])) return null;
         $valoresColuna = array_column($dadosRelatorio, $coluna);
         $valoresValidos = array_filter($valoresColuna, function($val) { return $val !== null && $val !== ''; });
         if (empty($valoresValidos)) return null;
         $contagem = array_count_values($valoresValidos);
         $isBoolLike = true;
         foreach(array_keys($contagem) as $k) { if (!in_array($k, [0, 1, '0', '1', true, false], true)) { $isBoolLike = false; break; } }
         $labels = array_keys($contagem); $data = array_values($contagem);
         if ($isBoolLike && count($labels) <= 2 ) {
             $newLabels = []; $newData = []; $mapSim = [1, '1', true]; $mapNao = [0, '0', false];
             $countSim = 0; $countNao = 0;
             foreach($labels as $i => $lbl) {
                 if(in_array($lbl, $mapSim, true)) { $countSim = $data[$i]; }
                 if(in_array($lbl, $mapNao, true)) { $countNao = $data[$i]; }
             }
             if ($countSim >= 0) { $newLabels[] = 'Sim'; $newData[] = $countSim; }
             if ($countNao >= 0) { $newLabels[] = 'Não'; $newData[] = $countNao; }
             if(empty(array_filter($newData))) { $labels = []; $data = []; } else { if (count($newData) == 2 && $newLabels[0] === 'Não') { $labels = array_reverse($newLabels); $data = array_reverse($newData); } else { $labels = $newLabels; $data = $newData; } }
         } else { arsort($contagem); $labels = array_keys($contagem); $data = array_values($contagem); }
         $totalValidos = array_sum($data);
         $percentuais = $totalValidos > 0 ? array_map(function($count) use ($totalValidos) { return round(($count / $totalValidos) * 100, 1); }, $data) : array_fill(0, count($data), 0.0);
         if(empty($labels) || empty($data)) return null;
         return ['labels' => $labels, 'data' => $data, 'percentuais' => $percentuais];
    };

    // Gráficos Demográficos Removidos

    // Gráfico de Classificação EBIA (Mantido)
    if (in_array('classificacao', $colunasDisponiveis)) {
         $dadosGrafico = $gerarContagem('classificacao');
         if ($dadosGrafico) {
               $order = ['seguranca_alimentar' => 1, 'inseguranca_leve' => 2, 'inseguranca_moderada' => 3, 'inseguranca_grave' => 4];
               $valoresClassificacao = array_column($dadosRelatorio, 'classificacao');
               $valoresValidos = array_filter($valoresClassificacao, function($val){ return $val!==null && $val!=='';});
               if(!empty($valoresValidos)){
                    $contagemOriginal = array_count_values($valoresValidos);
                    uksort($contagemOriginal, function($a, $b) use ($order) { return ($order[$a] ?? 99) <=> ($order[$b] ?? 99); });
                    $labelsOrdenados = array_keys($contagemOriginal);
                    $dataOrdenada = array_values($contagemOriginal);
                    $totalContagem = array_sum($dataOrdenada);
                    $percentuaisOrdenados = $totalContagem > 0 ? array_map(function($count) use ($totalContagem) { return round(($count / $totalContagem) * 100, 1); }, $dataOrdenada) : array_fill(0, count($dataOrdenada), 0.0);
                    $graficos['classificacao_ebia'] = [
                        'labels' => $labelsOrdenados, 'data' => $dataOrdenada, 'percentuais' => $percentuaisOrdenados,
                        'titulo' => 'Distribuição por Classificação EBIA'
                    ];
               }
         }
    }

    // Gráficos para Respostas EBIA Individuais com Títulos Completos (Mantido)
    $ebiaPerguntasTextos = [
        'resposta1' => '1. Os moradores deste domicílio tiveram a preocupação de que os alimentos acabassem antes de poderem comprar ou receber mais comida?',
        'resposta2' => '2. Os alimentos acabaram antes que os moradores deste domicílio tivessem dinheiro para comprar mais comida?',
        'resposta3' => '3. Os moradores deste domicílio ficaram sem dinheiro para ter uma alimentação saudável e variada?',
        'resposta4' => '4. Os moradores deste domicílio comeram apenas alguns poucos tipos de alimentos que ainda tinham, porque o dinheiro acabou?',
        'resposta5' => '5. Algum/a morador/a de 18 anos ou mais de idade deixou de fazer alguma refeição, porque não havia dinheiro para comprar comida?',
        'resposta6' => '6. Algum/a morador/a de 18 anos ou mais de idade, alguma vez, comeu menos do que achou que devia, porque não havia dinheiro para comprar comida?',
        'resposta7' => '7. Algum/a morador/a de 18 anos ou mais de idade, alguma vez, sentiu fome, mas não comeu, porque não havia dinheiro para comprar comida?',
        'resposta8' => '8. Algum/a morador/a de 18 anos ou mais de idade, alguma vez, fez apenas uma refeição ao dia ou ficou um dia inteiro sem comer porque não havia dinheiro para comprar comida?',
    ];
    for ($i = 1; $i <= 8; $i++) {
        $colEbia = 'resposta' . $i;
        if (in_array($colEbia, $colunasDisponiveis) && isset($ebiaPerguntasTextos[$colEbia])) {
             $dadosGrafico = $gerarContagem($colEbia);
             if ($dadosGrafico && !empty($dadosGrafico['labels']) && !empty($dadosGrafico['data'])) {
                 $graficos['ebia_q' . $i] = $dadosGrafico + ['titulo' => $ebiaPerguntasTextos[$colEbia]]; // Usa pergunta completa
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
  <title>Relatório EBIA Detalhado - Nutriware</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* Estilos mantidos da versão anterior */
    .chart-container { position: relative; margin: auto; height: 40vh; width: 100%; max-width: 450px; margin-bottom: 40px; }
    th, td { white-space: normal; word-wrap: break-word; font-size: 0.85rem; vertical-align: top; }
    .table-responsive { max-height: 600px; overflow: auto; }
    .sticky-top { position: sticky; top: 0; background-color: #f8f9fa; z-index: 1020; }
    .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
    .card-body ul { padding-left: 20px; }
    .graficos-ebia .card-header { font-size: 0.8rem; line-height: 1.2; min-height: 60px; word-wrap: break-word; text-align: left; padding: 0.5rem 0.75rem; }
    .badge.seguranca-alimentar { background-color: #28a745; color: white; }
    .badge.inseguranca-leve { background-color: #ffc107; color: #212529; }
    .badge.inseguranca-moderada { background-color: #fd7e14; color: white; }
    .badge.inseguranca-grave { background-color: #dc3545; color: white; }
  </style>
</head>
<body class="container mt-4 mb-5">
  <h1 class="mb-4">Relatório Detalhado EBIA</h1>

  <a href="relatorios.html" class="btn btn-secondary mb-4">&laquo; Voltar à Seleção</a>

  <?php if (!empty($erro)): ?>
    <div class="alert alert-danger">
      <strong>Erro:</strong> <?php echo htmlspecialchars($erro); ?>
    </div>
  <?php endif; ?>

  <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($erro)): ?>
    <?php if (empty($relatorioEbia)): ?>
      <div class="alert alert-info">
        Nenhum dado EBIA encontrado para os critérios selecionados.
      </div>
    <?php else: ?>
      <div class="card mb-4">
         <div class="card-header">Resumo da Geração</div>
         <div class="card-body">
              <p><strong>Tipo de Relatório:</strong> Insegurança Alimentar (EBIA Detalhado)</p>
              <p><strong>Modo:</strong> <?php echo htmlspecialchars(ucfirst($modo_relatorio)); ?></p>
              <?php if ($modo_relatorio === 'filtrado' && !empty($filtrosAplicados)): ?>
                  <p><strong>Filtros Aplicados:</strong></p>
                  <ul> <?php /* ... loop de filtros ... */ ?>
                      <?php foreach ($filtrosAplicados as $key => $value): ?>
                          <li><strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                      <?php endforeach; ?>
                  </ul>
              <?php elseif ($modo_relatorio === 'filtrado' && empty($filtrosAplicados)): ?>
                   <p><strong>Filtros Aplicados:</strong> Nenhum filtro específico selecionado.</p>
              <?php endif; ?>
              <p><strong>Total de Registros Encontrados:</strong> <?php echo count($relatorioEbia); ?></p>
         </div>
      </div>

      <h2 class="mt-5">Dados Detalhados EBIA</h2>
      <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped table-hover table-sm">
          <thead class="thead-light sticky-top">
             <tr>
                 <?php // Cabeçalho da Tabela - Gerado dinamicamente a partir de $colunasReais ?>
                 <?php
                    $ebiaPerguntasTooltips = [ /* ... tooltips ... */
                        'resposta1' => 'Os moradores deste domicílio tiveram a preocupação de que os alimentos acabassem antes de poderem comprar ou receber mais comida?',
                        'resposta2' => 'Os alimentos acabaram antes que os moradores deste domicílio tivessem dinheiro para comprar mais comida?',
                        'resposta3' => 'Os moradores deste domicílio ficaram sem dinheiro para ter uma alimentação saudável e variada?',
                        'resposta4' => 'Os moradores deste domicílio comeram apenas alguns poucos tipos de alimentos que ainda tinham, porque o dinheiro acabou?',
                        'resposta5' => 'Algum/a morador/a de 18 anos ou mais de idade deixou de fazer alguma refeição, porque não havia dinheiro para comprar comida?',
                        'resposta6' => 'Algum/a morador/a de 18 anos ou mais de idade, alguma vez, comeu menos do que achou que devia, porque não havia dinheiro para comprar comida?',
                        'resposta7' => 'Algum/a morador/a de 18 anos ou mais de idade, alguma vez, sentiu fome, mas não comeu, porque não havia dinheiro para comprar comida?',
                        'resposta8' => 'Algum/a morador/a de 18 anos ou mais de idade, alguma vez, fez apenas uma refeição ao dia ou ficou um dia inteiro sem comer porque não havia dinheiro para comprar comida?',
                    ];
                    $ebiaHeadersTabela = [ /* ... headers curtos ... */
                        'resposta1' => 'Q1', 'resposta2' => 'Q2', 'resposta3' => 'Q3', 'resposta4' => 'Q4',
                        'resposta5' => 'Q5', 'resposta6' => 'Q6', 'resposta7' => 'Q7', 'resposta8' => 'Q8',
                        'pontuacao_total' => 'Pontos', 'classificacao' => 'Classif.'
                    ];
                 ?>
                 <?php foreach ($colunasReais as $coluna): ?>
                      <?php
                         // Define o cabeçalho: Usa 'Nome' para a coluna 'nome', 'Qx' para 'respostaX', 'Pontos'/'Classif.' ou o nome padrão
                         $nomeCabecalho = ($coluna === 'nome') ? 'Nome' : ($ebiaHeadersTabela[$coluna] ?? ucfirst(str_replace('_', ' ', $coluna)));
                         $tooltip = htmlspecialchars($ebiaPerguntasTooltips[$coluna] ?? $nomeCabecalho);
                      ?>
                     <th title="<?php echo $tooltip; ?>">
                         <?php echo htmlspecialchars($nomeCabecalho); ?>
                     </th>
                 <?php endforeach; ?>
             </tr>
          </thead>
          <tbody>
            <?php // Corpo da tabela (lógica inalterada) ?>
            <?php foreach ($relatorioEbia as $linha): ?>
            <tr>
              <?php foreach ($colunasReais as $coluna): ?>
                <td>
                   <?php
                      $valor = $linha[$coluna] ?? null;
                      $isEbiaResponse = preg_match('/^resposta[1-8]$/', $coluna);

                      if ($isEbiaResponse && ($valor === 1 || $valor === 0 || $valor === '1' || $valor === '0')) {
                          echo ($valor == 1) ? 'Sim' : 'Não';
                      } elseif ($coluna === 'classificacao' && !empty($valor)) {
                          $cssClass = str_replace('_', '-', $valor);
                          echo '<span class="badge badge-pill ' . $cssClass . '">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $valor))) . '</span>';
                      } elseif (is_bool($valor)) {
                          echo $valor ? 'Sim' : 'Não';
                      } elseif (is_null($valor) || $valor === '') {
                           echo '-';
                      } else {
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

      <form action="relatorio_ebia.php" method="POST" class="mb-5" target="_blank">
          <?php /* ... Campos hidden para exportação ... */ ?>
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

      <h2 class="mt-5 mb-4">Gráficos Resumo EBIA</h2>
      <div class="row">
          <?php // Loop para exibir gráficos (lógica inalterada) ?>
          <?php if (!empty($chartData)): ?>
              <?php foreach ($chartData as $key => $grafico): ?>
                   <?php $isGraficoEbiaQ = strpos($key, 'ebia_q') === 0; ?>
                  <div class="col-lg-6 col-md-6 mb-4 <?php echo $isGraficoEbiaQ ? 'graficos-ebia' : ''; ?>">
                       <div class="card h-100">
                           <div class="card-header text-center">
                               <?php echo htmlspecialchars($grafico['titulo']); ?>
                           </div>
                           <div class="card-body d-flex align-items-center justify-content-center">
                               <?php
                                $isClassification = $key === 'classificacao_ebia';
                                $usePie = $isGraficoEbiaQ || $isClassification;
                               ?>
                               <div class="chart-container">
                                   <canvas id="chart-<?php echo htmlspecialchars(str_replace(['[',']', '.'], '', $key)); ?>"></canvas>
                               </div>
                           </div>
                       </div>
                  </div>
              <?php endforeach; ?>
          <?php else: ?>
              <div class="col-12">
                  <div class="alert alert-warning">Não há dados agregados suficientes para gerar gráficos.</div>
              </div>
          <?php endif; ?>
      </div>

    <?php endif; ?>
  <?php endif; ?>

  <?php // Scripts JS (lógica inalterada) ?>
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

  <?php if (!empty($chartData)): ?>
  <script>
    const chartDataJS = JSON.parse('<?php echo json_encode($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>');
    const backgroundColors = ['#69A8E6','#FFB1C1','#A6E8D8','#FFD6A5','#CBAACB','#FFFFB5','#C4D7ED','#FFDAC1','#E2F0CB','#FFC8A2','#B5EAD7','#EBC7E6'];
    const borderColors = backgroundColors.map(color => color.replace('0.7', '1'));

    function formatLabel(label) { /* ... (mesma função formatLabel de antes) ... */
        if (typeof label !== 'string') return String(label);
        let cleanLabel = label.replace(/^(participante_|consumo_|ebia_)/, '');
        const map = {
            'seguranca_alimentar': 'Segurança Alimentar', 'inseguranca_leve': 'Insegurança Leve',
            'inseguranca_moderada': 'Insegurança Moderada', 'inseguranca_grave': 'Insegurança Grave',
        };
        if (map[cleanLabel]) return map[cleanLabel];
        if(label === 'Sim' || label === 'Não') return label;
        return cleanLabel.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    document.addEventListener('DOMContentLoaded', () => { /* ... (mesmo JS de renderização de antes) ... */
      let chartInstances = {};
      for (const key in chartDataJS) {
        if (chartDataJS.hasOwnProperty(key)) {
          const grafico = chartDataJS[key];
          const safeKey = key.replace(/[^a-zA-Z0-9-_]/g, '');
          const canvasId = `chart-${safeKey}`;
          const ctx = document.getElementById(canvasId)?.getContext('2d');

          if (ctx && grafico.labels && grafico.labels.length > 0 && grafico.data && grafico.data.length > 0) {
             if (chartInstances[canvasId]) { chartInstances[canvasId].destroy(); }
             const isEbiaQuestion = key.startsWith('ebia_q');
             const isClassification = key === 'classificacao_ebia';
             const usePieChart = isEbiaQuestion || isClassification;
             const chartType = usePieChart ? 'pie' : 'bar';
             let specificBackgrounds = backgroundColors.slice(0, grafico.labels.length);
             if (isClassification) {
                 const colorMap = { 'Segurança Alimentar': 'rgba(75, 192, 192, 0.7)', 'Insegurança Leve': 'rgba(255, 206, 86, 0.7)', 'Insegurança Moderada': 'rgba(255, 159, 64, 0.7)', 'Insegurança Grave': 'rgba(255, 99, 132, 0.7)' };
                 specificBackgrounds = grafico.labels.map(label => colorMap[formatLabel(label)] || '#cccccc');
             } else if (isEbiaQuestion && grafico.labels.length <= 2 ) {
                 const simColor = 'rgba(54, 162, 235, 0.7)'; const naoColor = 'rgba(255, 99, 132, 0.7)';
                 specificBackgrounds = grafico.labels.map(label => (label === 'Sim' ? simColor : naoColor));
                 if (grafico.labels.length === 2 && grafico.labels[0] === 'Não') { specificBackgrounds = [naoColor, simColor]; }
                 else if (grafico.labels.length === 1 && grafico.labels[0] === 'Não'){ specificBackgrounds = [naoColor]; }
                 else if (grafico.labels.length === 1 && grafico.labels[0] === 'Sim'){ specificBackgrounds = [simColor]; }
             }
             let specificBorders = specificBackgrounds.map(color => color.replace('0.7', '1'));
             chartInstances[canvasId] = new Chart(ctx, {
              type: chartType,
              data: { labels: isClassification ? grafico.labels.map(formatLabel) : grafico.labels, datasets: [{ label: 'Contagem', data: grafico.data, backgroundColor: specificBackgrounds, borderColor: specificBorders, borderWidth: 1 }] },
              options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', display: true, labels: { boxWidth: 15, font: { size: 10 } } }, tooltip: { callbacks: { label: function(context) { let label = context.chart.data.labels[context.dataIndex] || ''; let value = context.formattedValue; const percentage = grafico.percentuais ? grafico.percentuais[context.dataIndex] : null; let output = `${label}: ${value}`; if (percentage !== null && context.chart.config.type === 'pie') { output += ` (${percentage}%)`; } return output; } } }, title: { display: false } }, scales: chartType === 'bar' ? { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { autoSkip: grafico.labels.length > 10, maxRotation: 45, minRotation: 0, font: { size: 10 } } } } : {} }
            });
          } else if (ctx) {
             ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
             const container = ctx.canvas.parentNode;
             if (container) container.innerHTML = '<p class="text-muted text-center small p-3">Sem dados para este gráfico.</p>';
          }
        }
      }
    });
  </script>
  <?php endif; ?>

</body>
</html>