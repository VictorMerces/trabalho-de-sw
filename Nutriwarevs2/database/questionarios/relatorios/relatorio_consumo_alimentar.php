<?php
session_start();
// TODO: Adicionar verificação de sessão se o relatório for restrito

// Incluir arquivos necessários
include __DIR__ . '/../../config/conexao.php';
include __DIR__ . '/../../config/banco.php';
include __DIR__ . '/../../config/error_handler.php';

// --- Função Auxiliar para Formatar Valores para Exibição (HTML e CSV) ---
function formatarValorExibicao($coluna, $valor) {
    if (is_null($valor) || $valor === '') {
        return '-'; // Retorna '-' para nulo ou vazio
    }

    // Mapeamentos de códigos para texto legível
    $mapEscolaridade = [
        'ensino_fundamental_incompleto' => 'Ensino Fundamental Incompleto',
        'ensino_fundamental_completo' => 'Ensino Fundamental Completo',
        'ensino_medio_incompleto' => 'Ensino Médio Incompleto',
        'ensino_medio_completo' => 'Ensino Médio Completo',
        'graduacao_incompleta' => 'Graduação Incompleta',
        'graduacao_completa' => 'Graduação Completa',
        'outro' => 'Outro',
        'prefere_nao_dizer' => 'Prefere Não Dizer'
    ];
    $mapGenero = [
        'masculino' => 'Masculino', 'feminino' => 'Feminino', 'transgenero' => 'Transgênero',
        'nao_binario' => 'Não Binário', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'
    ];
    $mapRaca = [
        'branco' => 'Branco', 'preto' => 'Preto', 'pardo' => 'Pardo',
        'povos_originarios' => 'Povos Originários', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'
    ];
    $mapEstadoCivil = [
        'solteiro' => 'Solteiro(a)', 'casado' => 'Casado(a)', 'divorciado' => 'Divorciado(a)',
        'viuvo' => 'Viúvo(a)', 'separado' => 'Separado(a)', 'uniao_estavel' => 'União Estável',
        'prefere_nao_dizer' => 'Prefere Não Dizer'
    ];
    $mapSituacaoEmprego = [
        'meio_periodo' => 'Meio Período', 'tempo_integral' => 'Tempo Integral', 'autonomo' => 'Autônomo',
        'desempregado' => 'Desempregado', 'incapaz_trabalhar' => 'Incapaz de Trabalhar',
        'aposentado' => 'Aposentado', 'estudante' => 'Estudante', 'outro' => 'Outro',
        'prefere_nao_dizer' => 'Prefere Não Dizer'
    ];
    // Colunas que representam Sim/Não
    $booleanColumns = [
        'usa_dispositivos', 'feijao', 'frutas_frescas', 'verduras_legumes',
        'hamburguer_embutidos', 'bebidas_adocadas', 'macarrao_instantaneo',
        'biscoitos_recheados'
    ];

    // Aplica o mapeamento ou formatação apropriada
    $valorOriginal = (string) $valor; // Garante que temos uma string para usar no fallback
    switch ($coluna) {
        case 'escolaridade':
            return $mapEscolaridade[$valor] ?? ucfirst(str_replace('_', ' ', $valorOriginal));
        case 'genero':
            return $mapGenero[$valor] ?? ucfirst(str_replace('_', ' ', $valorOriginal));
        case 'raca':
            return $mapRaca[$valor] ?? ucfirst(str_replace('_', ' ', $valorOriginal));
        case 'estado_civil':
            return $mapEstadoCivil[$valor] ?? ucfirst(str_replace('_', ' ', $valorOriginal));
        case 'situacao_emprego':
            return $mapSituacaoEmprego[$valor] ?? ucfirst(str_replace('_', ' ', $valorOriginal));
        case 'refeicoes':
             // Troca vírgula por vírgula + espaço para melhor leitura
            return str_replace(',', ', ', $valorOriginal);
        default:
            // Verifica colunas booleanas
            if (in_array($coluna, $booleanColumns)) {
                return ($valor == 1 || $valor === true || $valor === '1') ? 'Sim' : 'Não';
            }
            // Para outras colunas (nome, idade, data, etc.), retorna o valor como string
            // NÂO usar htmlspecialchars aqui para funcionar no CSV também
            return $valorOriginal;
    }
}
// --- Fim Função Auxiliar ---


// --- Inicialização ---
$dadosConsumo = [];
$chartData = [];
$erro = '';
$totalRegistros = 0;
$colunasReaisConsumo = [];
$filtrosAplicados = [];

// --- Processamento do Formulário (APENAS para Exportação neste caso) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['exportar']) && $_POST['exportar'] == 'csv') {
        try {
            // Recuperar filtros do POST se implementado no futuro
            // $filtrosPost = [];
            $dadosParaExportar = gerar_relatorio_consumo_alimentar($conexao /*, $filtrosPost ?? [] */);

            if (!empty($dadosParaExportar)) {
                $colunasExport = array_keys($dadosParaExportar[0]);

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=nutriware_relatorio_consumo_' . date('YmdHis') . '.csv');
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

                // Mapeamento para nomes de cabeçalho no CSV (REUTILIZADO ABAIXO)
                $headerMap = [
                    'nome' => 'Participante', // Mudou de ID para Nome
                    'idade' => 'Idade', 'genero' => 'Gênero', 'raca' => 'Raça/Cor',
                    'escolaridade' => 'Escolaridade', 'estado_civil' => 'Estado Civil', 'situacao_emprego' => 'Sit. Emprego',
                    'refeicoes' => 'Refeições Realizadas', 'usa_dispositivos' => 'Usa Dispositivos',
                    'feijao' => 'Comeu Feijão', 'frutas_frescas' => 'Comeu Frutas Frescas',
                    'verduras_legumes' => 'Comeu Verduras/Legumes', 'hamburguer_embutidos' => 'Comeu Hambúrguer/Embutidos',
                    'bebidas_adocadas' => 'Comeu Bebidas Adoçadas', 'macarrao_instantaneo' => 'Comeu Macarrão Inst./Salgadinhos',
                    'biscoitos_recheados' => 'Comeu Biscoitos Rech./Doces', 'data_preenchimento' => 'Data Preenchimento'
                ];
                $headerNomes = array_map(function($col) use ($headerMap) {
                    return $headerMap[$col] ?? ucfirst(str_replace('_', ' ', $col));
                }, $colunasExport);
                fputcsv($output, $headerNomes);

                // Dados para o CSV, usando a função de formatação
                foreach ($dadosParaExportar as $linha) {
                    $linhaExport = [];
                    foreach ($colunasExport as $col) {
                        $valor = $linha[$col] ?? null;
                        // Chama a função de formatação para obter o valor legível
                        $linhaExport[] = formatarValorExibicao($col, $valor);
                    }
                    fputcsv($output, $linhaExport);
                }
                fclose($output);
                exit;

            } else {
                 $erro = "Não foi possível gerar o CSV: Nenhum dado encontrado.";
            }
        } catch (Exception $e) {
            $erro = "Erro ao gerar o arquivo CSV: " . $e->getMessage();
            error_log("Erro CSV Export Consumo: " . $e->getMessage());
        }
    }
}

// --- Buscar Dados de Consumo Alimentar (para exibição na página) ---
if (!isset($_POST['exportar']) || !empty($erro)) {
    try {
        $dadosConsumo = gerar_relatorio_consumo_alimentar($conexao, $filtrosAplicados);
        $totalRegistros = count($dadosConsumo);
        if ($totalRegistros > 0) {
            $colunasReaisConsumo = array_keys($dadosConsumo[0]);
            // Opcional: remover colunas da exibição HTML (ex: 'id' se 'nome' está presente)
             if (in_array('nome', $colunasReaisConsumo)) {
                $colunasReaisConsumo = array_diff($colunasReaisConsumo, ['id']);
             }
        }
    } catch (PDOException $e) {
        $erro = "Erro ao buscar dados de consumo alimentar."; /* Mensagem mais genérica */
        error_log("Erro DB Relatorio Consumo: " . $e->getMessage());
        // Zera variáveis em caso de erro
        $dadosConsumo = []; $totalRegistros = 0; $colunasReaisConsumo = [];
    } catch (Exception $e) {
        $erro = "Erro inesperado ao gerar relatório de consumo."; /* Mensagem mais genérica */
        error_log("Erro Geral Relatorio Consumo: " . $e->getMessage());
         $dadosConsumo = []; $totalRegistros = 0; $colunasReaisConsumo = [];
    }
}


// --- Preparação de Dados para Gráficos ---
// (Código de preparação dos gráficos existente permanece aqui)
if (empty($erro) && $totalRegistros > 0) {
    // ... (código existente para $chartData) ...
    $contagemRefeicoes = [];
    $contagemDispositivos = ['Sim' => 0, 'Não' => 0, 'Não Informado' => 0];
    $contagemAlimentos = [
        'Feijão' => 0, 'Frutas Frescas' => 0, 'Verduras/Legumes' => 0,
        'Hambúrguer/Embutidos' => 0, 'Bebidas Adoçadas' => 0,
        'Macarrão Inst./Salgadinhos' => 0, 'Biscoitos Rech./Doces' => 0,
    ];
    $mapAlimentosColunas = [
        'Feijão' => 'feijao', 'Frutas Frescas' => 'frutas_frescas',
        'Verduras/Legumes' => 'verduras_legumes', 'Hambúrguer/Embutidos' => 'hamburguer_embutidos',
        'Bebidas Adoçadas' => 'bebidas_adocadas', 'Macarrão Inst./Salgadinhos' => 'macarrao_instantaneo',
        'Biscoitos Rech./Doces' => 'biscoitos_recheados',
    ];
    $nomesRefeicoes = [ "Café da manhã", "Lanche da manhã", "Almoço", "Lanche da tarde", "Jantar", "Ceia/lanche da noite" ];
    foreach ($nomesRefeicoes as $nomeRef) { $contagemRefeicoes[$nomeRef] = 0; }

    foreach ($dadosConsumo as $linha) {
        // 1. Contagem de Refeições
        if (!empty($linha['refeicoes'])) {
            $refeicoesIndividuais = explode(',', $linha['refeicoes']);
            foreach ($refeicoesIndividuais as $refeicao) {
                $refeicaoLimpa = trim($refeicao);
                if (in_array($refeicaoLimpa, $nomesRefeicoes)) {
                    $contagemRefeicoes[$refeicaoLimpa]++;
                }
            }
        }
        // 2. Contagem de Uso de Dispositivos
        if (isset($linha['usa_dispositivos'])) {
             if ($linha['usa_dispositivos'] === 1 || $linha['usa_dispositivos'] === true || $linha['usa_dispositivos'] === '1') { $contagemDispositivos['Sim']++; }
             elseif ($linha['usa_dispositivos'] === 0 || $linha['usa_dispositivos'] === false || $linha['usa_dispositivos'] === '0') { $contagemDispositivos['Não']++; }
             else { $contagemDispositivos['Não Informado']++; }
        } else { $contagemDispositivos['Não Informado']++; }
        // 3. Contagem de Alimentos Consumidos Ontem
        foreach ($mapAlimentosColunas as $nomeAmigavel => $colunaDb) {
            if (isset($linha[$colunaDb])) {
                 if ($linha[$colunaDb] === 1 || $linha[$colunaDb] === true || $linha[$colunaDb] === '1') { $contagemAlimentos[$nomeAmigavel]++; }
            }
        }
    }
    // Organiza dados para Chart.js
    $chartData = [];
    $contagemRefeicoesFiltrada = array_filter($contagemRefeicoes, function($count) { return $count > 0; });
    if (!empty($contagemRefeicoesFiltrada)) { arsort($contagemRefeicoesFiltrada); $chartData['refeicoes'] = ['labels' => array_keys($contagemRefeicoesFiltrada), 'data' => array_values($contagemRefeicoesFiltrada), 'titulo' => 'Refeições Realizadas (Frequência)']; }
    if($contagemDispositivos['Não Informado'] === 0) { unset($contagemDispositivos['Não Informado']); }
    if ($contagemDispositivos['Sim'] > 0 || $contagemDispositivos['Não'] > 0) { $chartData['dispositivos'] = ['labels' => array_keys($contagemDispositivos), 'data' => array_values($contagemDispositivos), 'titulo' => 'Uso de Celular/Computador Durante Refeições']; }
    if (array_sum($contagemAlimentos) > 0) { $chartData['alimentos_ontem'] = ['labels' => array_keys($contagemAlimentos), 'data' => array_values($contagemAlimentos), 'titulo' => 'Consumo de Alimentos Específicos no Dia Anterior']; }
} // Fim da preparação de dados do gráfico

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Relatório de Consumo Alimentar - Nutriware</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  <style>
    /* Estilos consistentes */
    .chart-container { position: relative; margin: auto; height: 50vh; width: 100%; max-width: 550px; margin-bottom: 40px; }
    .chart-container-pie { position: relative; margin: auto; height: 45vh; width: 100%; max-width: 450px; margin-bottom: 40px; }
    h1, h2 { text-align: center; }
    .card { min-height: 500px; display: flex; flex-direction: column; }
    .card-body { flex-grow: 1; display: flex; align-items: center; justify-content: center; }
    #tabela-consumo th { background-color: #e9ecef; text-align: center; } /* ID correto */
    #tabela-consumo td { font-size: 0.85rem; vertical-align: middle; text-align: left; padding: 0.4rem; } /* ID correto */
    .table-responsive { margin-top: 1rem; }
    /* Remover sticky-top se DataTables gerencia */
    .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
    .table-hover tbody tr:hover { background-color: #f1f1f1; }
    .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(0,0,0,.03); }
  </style>
</head>
<body class="container mt-4 mb-5">
  <h1 class="mb-4">Relatório de Consumo Alimentar</h1>

  <div class="text-center mb-4">
     <a href="relatorios.html" class="btn btn-secondary">&laquo; Voltar para Seleção</a>
     <a href="../../login/menu/menu.php" class="btn btn-light">Voltar ao Menu Principal</a>
  </div>

  <?php if (!empty($erro)): ?>
    <div class="alert alert-danger text-center">
      <strong>Erro:</strong> <?php echo htmlspecialchars($erro); ?>
    </div>
  <?php endif; ?>

  <?php // Exibe conteúdo principal apenas se não houver erro e tiver dados ?>
  <?php if (empty($erro) && $totalRegistros > 0): ?>
      <div class="alert alert-success text-center">
          Relatório gerado com base em <strong><?php echo $totalRegistros; ?></strong> registro(s) de consumo alimentar.
      </div>
      <hr>

      <h2 class="mt-5">Dados Detalhados de Consumo</h2>
      <?php if (empty($colunasReaisConsumo)): ?>
          <div class="alert alert-warning text-center">Nenhuma coluna de dados encontrada para exibir a tabela.</div>
      <?php else: ?>
          <div class="table-responsive mb-4">
              <table id="tabela-consumo" class="table table-bordered table-striped table-hover table-sm" style="width:100%">
                  <thead>
                      <tr>
                          <?php
                              // Mapeamento para nomes de cabeçalho (REUTILIZADO ACIMA)
                              $headerMap = [
                                    'nome' => 'Participante', 'idade' => 'Idade', 'genero' => 'Gênero', 'raca' => 'Raça/Cor',
                                    'escolaridade' => 'Escolaridade', 'estado_civil' => 'Estado Civil', 'situacao_emprego' => 'Sit. Emprego',
                                    'refeicoes' => 'Refeições', 'usa_dispositivos' => 'Usa Disp.?', 'feijao' => 'Feijão?',
                                    'frutas_frescas' => 'Frutas?', 'verduras_legumes' => 'Verd/Leg?', 'hamburguer_embutidos' => 'Hamb/Emb?',
                                    'bebidas_adocadas' => 'Beb. Adoç?', 'macarrao_instantaneo' => 'Mac. Inst?',
                                    'biscoitos_recheados' => 'Bisc. Rech?', 'data_preenchimento' => 'Data'
                              ];
                              foreach ($colunasReaisConsumo as $coluna):
                                  $nomeCabecalho = $headerMap[$coluna] ?? ucfirst(str_replace('_', ' ', $coluna));
                          ?>
                              <th><?php echo htmlspecialchars($nomeCabecalho); ?></th>
                          <?php endforeach; ?>
                      </tr>
                  </thead>
                  <tbody>
                      <?php foreach ($dadosConsumo as $linha): ?>
                      <tr>
                          <?php foreach ($colunasReaisConsumo as $coluna): ?>
                              <td>
                                  <?php
                                      $valor = $linha[$coluna] ?? null;
                                      // Chama a função de formatação, escapando o resultado para HTML
                                      echo htmlspecialchars(formatarValorExibicao($coluna, $valor));
                                  ?>
                              </td>
                          <?php endforeach; ?>
                      </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>

          <form action="relatorio_consumo_alimentar.php" method="POST" class="mb-5 text-center" target="_blank">
              <?php /* Adicionar campos hidden para filtros aqui se forem implementados */ ?>
              <button type="submit" name="exportar" value="csv" class="btn btn-success">
                  <i class="fas fa-file-csv"></i> Exportar Tabela para CSV
              </button>
          </form>

      <?php endif; // Fim do if (!empty($colunasReaisConsumo)) ?>
      <?php if (!empty($chartData)): ?>
          <h2 class="mt-5 mb-4">Visualização Gráfica</h2>
          <div class="row justify-content-center">
              <?php // Código PHP para gerar os divs dos gráficos (sem alterações) ?>
              <?php if (isset($chartData['refeicoes'])): ?>
                  <div class="col-lg-6 col-md-12 mb-4"> <div class="card"> <div class="card-header text-center"><?php echo htmlspecialchars($chartData['refeicoes']['titulo']); ?></div> <div class="card-body"> <div class="chart-container"> <canvas id="chart-refeicoes"></canvas> </div> </div> </div> </div>
              <?php endif; ?>
              <?php if (isset($chartData['dispositivos'])): ?>
                  <div class="col-lg-6 col-md-12 mb-4"> <div class="card"> <div class="card-header text-center"><?php echo htmlspecialchars($chartData['dispositivos']['titulo']); ?></div> <div class="card-body"> <div class="chart-container-pie"> <canvas id="chart-dispositivos"></canvas> </div> </div> </div> </div>
              <?php endif; ?>
              <?php if (isset($chartData['alimentos_ontem'])): ?>
                   <div class="col-lg-10 col-md-12 mb-4 mt-4"> <div class="card"> <div class="card-header text-center"><?php echo htmlspecialchars($chartData['alimentos_ontem']['titulo']); ?></div> <div class="card-body"> <div class="chart-container" style="max-width: 800px;"> <canvas id="chart-alimentos-ontem"></canvas> </div> </div> </div> </div>
              <?php endif; ?>
          </div>
       <?php elseif(empty($erro)): // Mensagem se HÁ dados mas NENHUM gráfico pode ser gerado ?>
           <div class="alert alert-warning text-center mt-5">
                Não há dados agregados suficientes ou aplicáveis para gerar gráficos.
            </div>
       <?php endif; // Fim if/elseif gráficos ?>
       <?php // Mensagem se não houve erro, mas nenhum registro foi encontrado ?>
  <?php elseif (empty($erro) && $totalRegistros === 0): ?>
      <div class="alert alert-info text-center">
          Nenhum dado de consumo alimentar encontrado para gerar o relatório.
      </div>
  <?php endif; // Fim do if principal de exibição de conteúdo ?>

  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

  <script>
    $(document).ready(function() {
        try {
            $('#tabela-consumo').DataTable({ // Seleciona a tabela pelo ID correto
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' // Tradução
                },
                responsive: true // Habilita responsividade
            });
        } catch(e) {
            console.error("Erro ao inicializar DataTables:", e);
        }
    });
  </script>

  <?php // Script JavaScript para renderizar os gráficos (sem alterações) ?>
  <?php if (!empty($chartData)): ?>
  <script>
    // ... (código JavaScript existente para os gráficos) ...
    const chartDataJS = JSON.parse('<?php echo json_encode($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>');
    const backgroundColors = ['#69A8E6','#FFB1C1','#A6E8D8','#FFD6A5','#CBAACB','#FFFFB5','#C4D7ED','#FFDAC1','#E2F0CB','#FFC8A2','#B5EAD7','#EBC7E6'];
    const borderColors = backgroundColors.map(color => { let [r, g, b] = [parseInt(color.slice(1, 3), 16), parseInt(color.slice(3, 5), 16), parseInt(color.slice(5, 7), 16)]; return `rgb(${Math.max(0, r - 30)}, ${Math.max(0, g - 30)}, ${Math.max(0, b - 30)})`; });
    function createChart(canvasId, chartConfig) { const ctx = document.getElementById(canvasId)?.getContext('2d'); if(ctx){ const chart = Chart.getChart(canvasId); if(chart){ chart.destroy(); } new Chart(ctx, chartConfig); } else { console.error(`Canvas ${canvasId} not found.`); } }
    document.addEventListener('DOMContentLoaded', () => {
        if (chartDataJS.refeicoes?.labels?.length) { createChart('chart-refeicoes', { type: 'bar', data: { labels: chartDataJS.refeicoes.labels, datasets: [{ label: 'Nº de Ocorrências', data: chartDataJS.refeicoes.data, backgroundColor: backgroundColors.slice(0, chartDataJS.refeicoes.labels.length), borderColor: borderColors.slice(0, chartDataJS.refeicoes.labels.length), borderWidth: 1 }] }, options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, title: { display: false }, tooltip: { callbacks: { label: (ctx) => ` ${ctx.raw} ocorrências` } } }, scales: { x: { beginAtZero: true, title: { display: true, text: 'Número de Respostas' } }, y: { title: { display: true, text: 'Refeição' } } } } }); }
        if (chartDataJS.dispositivos?.labels?.length) { createChart('chart-dispositivos', { type: 'pie', data: { labels: chartDataJS.dispositivos.labels, datasets: [{ data: chartDataJS.dispositivos.data, backgroundColor: backgroundColors.slice(0, chartDataJS.dispositivos.labels.length), borderColor: borderColors.slice(0, chartDataJS.dispositivos.labels.length), borderWidth: 1 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, title: { display: false }, tooltip: { callbacks: { label: (ctx) => { let total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0); let perc = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0; return `${ctx.label}: ${ctx.raw} (${perc}%)`; } } } } } }); }
        if (chartDataJS.alimentos_ontem?.labels?.length) { createChart('chart-alimentos-ontem', { type: 'bar', data: { labels: chartDataJS.alimentos_ontem.labels.map(l => l.replace('Inst./Salgadinhos', 'Inst.').replace('Rech./Doces', 'Rech.')), datasets: [{ label: 'Nº de Participantes', data: chartDataJS.alimentos_ontem.data, backgroundColor: backgroundColors.slice(0, chartDataJS.alimentos_ontem.labels.length), borderColor: borderColors.slice(0, chartDataJS.alimentos_ontem.labels.length), borderWidth: 1 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, title: { display: false }, tooltip: { callbacks: { title: (items) => chartDataJS.alimentos_ontem.labels[items[0].dataIndex], label: (ctx) => `${ctx.raw} participantes` } } }, scales: { y: { beginAtZero: true, title: { display: true, text: 'Número de Participantes' } }, x: { title: { display: true, text: 'Alimento' }, ticks: { autoSkip: false, maxRotation: 35, minRotation: 0 } } } } }); }
    });
  </script>
  <?php endif; // Fim do script JS dos gráficos ?>

</body>
</html>