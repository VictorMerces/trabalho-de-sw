<?php
session_start();
// Verificação de permissão (opcional, mas recomendado)
// if (!isset($_SESSION['usuario_permitido'])) { header('Location: ../../login/login.php'); exit; }

// Incluir arquivos necessários
include __DIR__ . '/../../config/conexao.php';
include __DIR__ . '/../../config/banco.php'; // Contém preparar_dados_graficos_completo MODIFICADO
include __DIR__ . '/../../config/error_handler.php';

// --- Função Auxiliar para Formatar Valores (Mesma versão anterior) ---
function formatarValorCompletoParaExibicao($colunaPrefixo, $valor, $formato = 'html') {
    // ... (código completo da função formatarValorCompletoParaExibicao da versão anterior, sem alterações) ...
    if (is_null($valor) || $valor === '') {
        return ($formato === 'csv') ? '' : '-'; // Vazio no CSV, '-' no HTML
    }
    $colunaLimpa = preg_replace('/^(participante_|consumo_|ebia_)/', '', $colunaPrefixo);
    $mapSimNao = [1 => 'Sim', '0' => 'Não', true => 'Sim', false => 'Não'];
    $mapBooleanCols = [ 'resposta1', 'resposta2', 'resposta3', 'resposta4', 'resposta5', 'resposta6', 'resposta7', 'resposta8', 'usa_dispositivos', 'feijao', 'frutas_frescas', 'verduras_legumes', 'hamburguer_embutidos', 'bebidas_adocadas', 'macarrao_instantaneo', 'biscoitos_recheados'];
    $mapGenero = ['masculino' => 'Masculino', 'feminino' => 'Feminino', 'transgenero' => 'Transgênero', 'nao_binario' => 'Não Binário', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapRaca = ['branco' => 'Branco', 'preto' => 'Preto', 'pardo' => 'Pardo', 'povos_originarios' => 'Povos Originários', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapEscolaridade = ['ensino_fundamental_incompleto' => 'Ens. Fundamental Incompleto', 'ensino_fundamental_completo' => 'Ens. Fundamental Completo', 'ensino_medio_incompleto' => 'Ens. Médio Incompleto', 'ensino_medio_completo' => 'Ens. Médio Completo', 'graduacao_incompleta' => 'Graduação Incompleta', 'graduacao_completa' => 'Graduação Completa', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapEstadoCivil = ['solteiro' => 'Solteiro(a)', 'casado' => 'Casado(a)', 'divorciado' => 'Divorciado(a)', 'viuvo' => 'Viúvo(a)', 'separado' => 'Separado(a)', 'uniao_estavel' => 'União Estável', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapSituacaoEmprego = ['meio_periodo' => 'Meio Período', 'tempo_integral' => 'Tempo Integral', 'autonomo' => 'Autônomo', 'desempregado' => 'Desempregado', 'incapaz_trabalhar' => 'Incapaz de Trabalhar', 'aposentado' => 'Aposentado', 'estudante' => 'Estudante', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapReligiao = ['catolico' => 'Católico', 'evangelico' => 'Evangélico', 'espirita' => 'Espírita', 'umbanda' => 'Umbanda', 'candomble' => 'Candomblé', 'ateu' => 'Ateu', 'nenhum' => 'Nenhuma', 'outro' => 'Outro', 'prefere_nao_dizer' => 'Prefere Não Dizer'];
    $mapClassificacaoEbia = ['seguranca_alimentar' => 'Segurança Alimentar', 'inseguranca_leve' => 'Insegurança Leve', 'inseguranca_moderada' => 'Insegurança Moderada', 'inseguranca_grave' => 'Insegurança Grave'];
    $valorStr = is_scalar($valor) ? (string)$valor : '';
    if (in_array($colunaLimpa, $mapBooleanCols)) { if (array_key_exists($valor, $mapSimNao)) return $mapSimNao[$valor]; if (array_key_exists($valorStr, $mapSimNao)) return $mapSimNao[$valorStr]; return ($formato === 'csv') ? '' : '-'; }
    switch ($colunaLimpa) {
        case 'genero': return $mapGenero[$valorStr] ?? ucfirst(str_replace('_', ' ', $valorStr));
        case 'raca': return $mapRaca[$valorStr] ?? ucfirst(str_replace('_', ' ', $valorStr));
        case 'escolaridade': return $mapEscolaridade[$valorStr] ?? ucfirst(str_replace('_', ' ', $valorStr));
        case 'estado_civil': return $mapEstadoCivil[$valorStr] ?? ucfirst(str_replace('_', ' ', $valorStr));
        case 'situacao_emprego': return $mapSituacaoEmprego[$valorStr] ?? ucfirst(str_replace('_', ' ', $valorStr));
        case 'religiao': return $mapReligiao[$valorStr] ?? ucfirst(str_replace('_', ' ', $valorStr));
        case 'classificacao':
            if (isset($mapClassificacaoEbia[$valorStr])) {
                 if ($formato === 'html') { $cssClass = str_replace('_', '-', $valorStr); return '<span class="badge badge-pill ' . htmlspecialchars($cssClass) . '">' . htmlspecialchars($mapClassificacaoEbia[$valorStr]) . '</span>'; }
                 else { return $mapClassificacaoEbia[$valorStr]; }
            } return ucfirst(str_replace('_', ' ', $valorStr));
    }
    if ($colunaLimpa === 'beneficios_sociais') {
        $json = is_string($valorStr) ? json_decode($valorStr, true) : null;
        if ($json !== null && json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $jsonFiltrado = array_filter($json, fn($item) => !is_null($item) && $item !== '');
            if (!empty($jsonFiltrado)) {
                $itensFormatados = []; $textoOutros = isset($jsonFiltrado['Outros']) && is_string($jsonFiltrado['Outros']) && trim($jsonFiltrado['Outros']) !== '' ? trim($jsonFiltrado['Outros']) : null;
                foreach ($jsonFiltrado as $key => $item) { if ($key === 'Outros') continue; if (is_string($item)) { $itensFormatados[] = trim($item); } }
                $output = implode(', ', $itensFormatados); if ($textoOutros !== null) { $output .= (!empty($output) ? '; ' : '') . 'Outros: ' . htmlspecialchars($textoOutros); }
                return !empty($output) ? $output : (($formato === 'csv') ? '' : '-');
            }
        } return ($formato === 'csv') ? '' : '-';
    }
    if (str_ends_with($colunaLimpa, '_outro')) { return !empty($valorStr) ? htmlspecialchars($valorStr) : (($formato === 'csv') ? '' : '-'); }
    if (str_ends_with($colunaLimpa, 'data_cadastro') || str_ends_with($colunaLimpa, 'data_preenchimento')) {
         try { if (empty($valorStr) || $valorStr === '0000-00-00 00:00:00') return ($formato === 'csv') ? '' : '-'; $date = new DateTime($valorStr); return $date->format('d/m/Y H:i'); }
         catch (Exception $e) { return !empty($valorStr) ? htmlspecialchars($valorStr) : (($formato === 'csv') ? '' : '-'); }
    }
    if ($colunaLimpa === 'refeicoes') { return !empty($valorStr) ? htmlspecialchars(str_replace(',', ', ', $valorStr)) : (($formato === 'csv') ? '' : '-'); }
    if (is_array($valor)) return ($formato === 'csv') ? '' : '[Dados complexos]';
    return htmlspecialchars($valorStr);
}
// --- Fim Função Auxiliar ---

// --- Inicialização e Processamento do Formulário (Como na versão anterior) ---
$relatorioCompleto = [];
$chartDataCompleto = [];
$filtrosAplicados = [];
$erro = '';
$colunasReais = [];
$modo_relatorio = 'filtrado';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $modo_relatorio = $_POST['modo_relatorio'] ?? 'filtrado';
    $tipo_relatorio_selecionado = $_POST['tipo_relatorio'] ?? 'completo'; // Garantir que é 'completo'
    $filtros = [];
     if ($modo_relatorio === 'filtrado') {
        // --- Obtenção de filtros (sem alterações) ---
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
             $relatorioCompleto = gerar_relatorio_completo($conexao, $filtros); // Busca dados
             if (!empty($relatorioCompleto)) {
                 $colunasReais = array_keys($relatorioCompleto[0]);
                 // --- PREPARAÇÃO DE DADOS PARA GRÁFICOS (CHAMA A FUNÇÃO MODIFICADA) ---
                 try {
                     $chartDataCompleto = preparar_dados_graficos_completo($relatorioCompleto, $colunasReais, $filtrosAplicados);
                 } catch (Exception $e) {
                     error_log("Erro ao preparar dados para gráficos completos: " . $e->getMessage());
                     $chartDataCompleto = []; // Continua sem gráficos se houver erro aqui
                 }
             } else {
                 $colunasReais = [];
                 $chartDataCompleto = [];
             }
        } catch (PDOException $e) { $erro = "Erro ao buscar dados do relatório completo."; error_log("Erro DB Relatorio Completo: " . $e->getMessage()); $relatorioCompleto = []; $colunasReais = []; $chartDataCompleto = []; }
          catch (Exception $e) { $erro = "Erro geral ao gerar relatório completo."; error_log("Erro Geral Relatorio Completo: " . $e->getMessage()); $relatorioCompleto = []; $colunasReais = []; $chartDataCompleto = []; }
    }

    // --- Exportação CSV (Sem alterações na lógica, usa a função formatarValorCompletoParaExibicao) ---
    if (isset($_POST['exportar']) && $_POST['exportar'] == 'csv' && empty($erro) && !empty($relatorioCompleto)) {
         header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=nutriware_relatorio_completo_' . date('YmdHis') . '.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        $headerMapCompleto = [ /* ... (mapeamento completo como antes) ... */
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

} // Fim do POST

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Relatório Completo Consolidado - Nutriware</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
  <style>
    /* Estilos gerais */
    body { padding-bottom: 50px; }
    h1, h2 { text-align: center; margin-top: 1.5rem; margin-bottom: 1rem; }

    /* Tabela Detalhada (agora com DataTables) */
    .table-responsive { margin-top: 1rem; } /* Não precisa mais de max-height */
    #tabela-detalhada th { background-color: #e9ecef; position: sticky; top: 0; z-index: 10; text-align: center;}
    #tabela-detalhada td { font-size: 0.78rem; vertical-align: middle; }
    #tabela-detalhada .badge { font-size: 0.75em; font-weight: bold; }

    /* Card de Resumo */
    .card-body ul { padding-left: 20px; margin-bottom: 0; }

    /* Badges de Classificação EBIA */
    .badge.seguranca-alimentar { background-color: #28a745 !important; color: white !important; }
    .badge.inseguranca-leve { background-color: #ffc107 !important; color: #212529 !important; }
    .badge.inseguranca-moderada { background-color: #fd7e14 !important; color: white !important; }
    .badge.inseguranca-grave { background-color: #dc3545 !important; color: white !important; }

    /* Gráficos */
    .grafico-container { cursor: pointer; }
    /* Ajustar alturas e larguras conforme necessário */
    .chart-container-pie { position: relative; margin: auto; height: 38vh; width: 100%; max-width: 420px; margin-bottom: 20px; }
    .chart-container-bar { position: relative; margin: auto; height: 40vh; width: 100%; max-width: 550px; margin-bottom: 20px; }
    .chart-container-bar-wide { position: relative; margin: auto; height: 45vh; width: 100%; max-width: 700px; margin-bottom: 20px; }
    .grafico-card { margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .grafico-card .card-header { font-size: 0.9rem; font-weight: bold; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; }
    .grafico-card .card-body { padding: 0.7rem; }
    .no-data-message { color: #6c757d; font-style: italic; }

    /* Modal */
    .modal-dialog { max-width: 85%; }
    .modal-body { max-height: 75vh; overflow-y: auto; }
    #tabelaDetalhes td, #tabelaDetalhes th { font-size: 0.8rem; white-space: normal; }
    .loading-spinner { font-size: 2rem; color: #007bff; }
  </style>
</head>
<body class="container-fluid mt-4">
  <h1 class="mb-3">Relatório Completo Consolidado</h1>

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
              <p><strong>Tipo:</strong> Completo Consolidado</p>
              <p><strong>Modo:</strong> <?php echo htmlspecialchars(ucfirst($modo_relatorio)); ?></p>
              <?php if ($modo_relatorio === 'filtrado' && !empty($filtrosAplicados)): ?>
                  <p><strong>Filtros Aplicados:</strong></p>
                  <ul> <?php foreach ($filtrosAplicados as $key => $value): ?> <li><strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?>:</strong> <?php echo htmlspecialchars($value); ?></li> <?php endforeach; ?> </ul>
              <?php endif; ?>
              <p class="mb-0"><strong>Total de Registros:</strong> <?php echo count($relatorioCompleto); ?></p>
         </div>
      </div>

      <h2 class="mt-4">Dados Detalhados</h2>
       <div class="table-responsive mb-4">
           <table id="tabela-detalhada" class="table table-bordered table-striped table-hover table-sm" style="width:100%">
               <thead>
                   <tr>
                       <?php $headerMapHtml = $headerMapCompleto; // Reuse mapping ?>
                       <?php foreach ($colunasReais as $coluna): ?>
                           <?php
                              $nomeCabecalhoHtml = $headerMapHtml[$coluna] ?? ucfirst(str_replace(['participante_', 'consumo_', 'ebia_', '_'], ['', '', '', ' '], $coluna));
                              $mapTooltipsEbia = ['ebia_resposta1' => 'Preoc. falta alim.?', /*...*/ 'ebia_resposta8' => '1 ref./dia ou 0?'];
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
          <h2 class="mt-4 mb-3">Gráficos Resumo <small class="text-muted">(Clique nos gráficos para ver detalhes)</small></h2>
          <div class="row justify-content-center">

              <?php
                  // Ordem desejada dos gráficos
                  $ordemGraficos = [
                      'classificacao', 'ebia_respostas_stacked', 'consumo_recomendados', 'consumo_ultraprocessados',
                      'escolaridade', 'raca', 'genero', 'situacao_emprego', 'numero_dependentes',
                      'beneficios_sociais', 'refeicoes', 'usa_dispositivos',
                  ];
                  $graficosParaExibir = array_intersect_key($chartDataCompleto, array_flip($ordemGraficos));
                  $graficosRestantes = array_diff_key($chartDataCompleto, $graficosParaExibir);
                  $graficosParaExibir = array_merge($graficosParaExibir, $graficosRestantes);
              ?>

              <?php foreach ($graficosParaExibir as $keyGrafico => $grafico): ?>
                  <?php
                     $colSize = 'col-lg-4 col-md-6'; $containerClass = 'chart-container-pie';
                     $canvasId = 'chart-' . preg_replace('/[^a-zA-Z0-9-_]/', '', $keyGrafico);
                     if ($keyGrafico === 'ebia_respostas_stacked') { $colSize = 'col-lg-8 col-md-12'; $containerClass = 'chart-container-bar-wide'; }
                     elseif ($keyGrafico === 'consumo_recomendados' || $keyGrafico === 'consumo_ultraprocessados') { $colSize = 'col-lg-6 col-md-6'; $containerClass = 'chart-container-bar'; }
                     elseif ($keyGrafico === 'classificacao') { $colSize = 'col-lg-5 col-md-6'; $containerClass = 'chart-container-pie'; }
                     elseif (($keyGrafico === 'beneficios_sociais' || $keyGrafico === 'numero_dependentes' || $keyGrafico === 'refeicoes') && isset($grafico['labels']) && count($grafico['labels']) > 6) { $colSize = 'col-lg-6 col-md-12'; $containerClass = 'chart-container-bar-wide'; }
                     elseif (in_array($keyGrafico, ['genero', 'raca', 'estado_civil', 'religiao', 'situacao_emprego', 'usa_dispositivos'])) { $isPie = (isset($grafico['labels']) && count($grafico['labels']) <= 5) || $keyGrafico === 'usa_dispositivos'; $containerClass = $isPie ? 'chart-container-pie' : 'chart-container-bar'; }
                     $dataAttributes = 'data-chart-key="' . htmlspecialchars($keyGrafico) . '" ';
                     if (isset($grafico['chaveOriginal'])) { $dataAttributes .= 'data-original-key="' . htmlspecialchars($grafico['chaveOriginal']) . '" '; }
                     if ($keyGrafico === 'ebia_respostas_stacked' || strpos($keyGrafico, 'consumo_') === 0) { $dataAttributes .= 'data-is-multi-key="true" '; }
                     else { $dataAttributes .= 'data-is-multi-key="false" '; }
                  ?>
                  <div class="<?php echo $colSize; ?>">
                       <div class="card grafico-card">
                           <div class="card-header text-center"> <?php echo htmlspecialchars($grafico['titulo']); ?> </div>
                           <div class="card-body d-flex align-items-center justify-content-center grafico-container" <?php echo $dataAttributes; ?>>
                               <?php if (empty($grafico['labels']) || empty($grafico['data'] ?? $grafico['datasets'])): ?>
                                   <p class="text-muted text-center small p-3 no-data-message">Sem dados suficientes para este gráfico.</p>
                               <?php else: ?>
                                   <div class="<?php echo $containerClass; ?>">
                                       <canvas id="<?php echo htmlspecialchars($canvasId); ?>"></canvas>
                                   </div>
                               <?php endif; ?>
                           </div>
                       </div>
                  </div>
              <?php endforeach; ?>
          </div>
      <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
          <div class="alert alert-warning text-center"> Não há dados agregados para gerar gráficos. </div>
      <?php endif; ?>

    <?php endif; // Fim else (empty($relatorioCompleto)) ?>
  <?php endif; // Fim if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($erro)) ?>

  <div class="modal fade" id="modalDetalhes" tabindex="-1" role="dialog" aria-labelledby="modalDetalhesLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document"> <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalDetalhesLabel">Detalhes do Grupo</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <div class="modal-body">
          <div id="modalLoading" class="text-center" style="display: none;"> <i class="fas fa-spinner fa-spin loading-spinner"></i> Carregando detalhes... </div>
          <div id="modalError" class="alert alert-danger" style="display: none;"></div>
          <div id="modalContent" style="display: none;">
             <p>Exibindo detalhes para participantes com o critério selecionado:</p>
             <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped" id="tabelaDetalhes">
                    <thead></thead>
                    <tbody></tbody>
                </table>
             </div>
          </div>
        </div>
        <div class="modal-footer"> <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button> </div>
      </div>
    </div>
  </div>


  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
  <script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

  <?php if (!empty($chartDataCompleto)): ?>

    <?php
        // // Verifica se $chartDataCompleto existe e não está vazio antes de tentar encodar
        $jsonOutput = "{}"; // Default para JSON vazio se não houver dados
        if (!empty($chartDataCompleto)) {
            // Loga antes de encodar
            error_log("DEBUG: \$chartDataCompleto ANTES de json_encode: " . print_r($chartDataCompleto, true));
            $encodedJson = json_encode($chartDataCompleto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
            if ($encodedJson === false) {
                // Loga o erro do json_encode
                error_log("DEBUG: json_encode FALHOU. Erro: " . json_last_error_msg());
            } else {
                $jsonOutput = $encodedJson;
            }
        } else {
             error_log("DEBUG: \$chartDataCompleto está VAZIO ou não definido.");
        }

        echo "\n\n";
        echo "<textarea id='debug-json' style='width: 100%; height: 150px; font-family: monospace; font-size: 10px;' readonly>";
        echo htmlspecialchars($jsonOutput, ENT_QUOTES, 'UTF-8'); // Imprime o JSON (ou '{}' se vazio/erro)
        echo "</textarea>";
        echo "\n\n\n";
        // ?>

    <script>
    // Passa os dados PHP para JavaScript
    // Adiciona um tratamento de erro básico aqui para o JSON.parse
    let chartDataJS = {}; // Default como objeto vazio
    const rawJsonString = document.getElementById('debug-json').value; // Pega do textarea
    const originalFiltersJS = JSON.parse('<?php echo json_encode($filtrosAplicados, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>');

    try {
        chartDataJS = JSON.parse(rawJsonString); // Tenta parsear o JSON do textarea
        console.log("JSON parsed successfully:", chartDataJS);
    } catch (e) {
        console.error("Failed to parse JSON data:", e);
        console.error("Raw JSON string was:", rawJsonString);
        // Opcional: Mostrar uma mensagem de erro para o usuário na página
        // document.body.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger">Erro ao carregar dados dos gráficos. Verifique o console para detalhes.</div>');
    }


    // Paleta de cores (Ajustada para ter mais variações)
    const backgroundColors = ['rgba(54, 162, 235, 0.7)','rgba(255, 99, 132, 0.7)','rgba(75, 192, 192, 0.7)','rgba(255, 206, 86, 0.7)','rgba(153, 102, 255, 0.7)','rgba(255, 159, 64, 0.7)', 'rgba(201, 203, 207, 0.7)', 'rgba(255, 99, 71, 0.7)', 'rgba(60, 179, 113, 0.7)', 'rgba(106, 90, 205, 0.7)', 'rgba(218, 165, 32, 0.7)', 'rgba(72, 209, 204, 0.7)'];
    const borderColors = backgroundColors.map(color => color.replace('0.7', '1'));
    // Cores específicas para Sim/Não e Classificação
    const simColor = 'rgba(54, 162, 235, 0.7)'; const naoColor = 'rgba(255, 99, 132, 0.7)';
    const colorMapEbia = { 'Segurança Alimentar': 'rgba(75, 192, 192, 0.8)', 'Insegurança Leve': 'rgba(255, 206, 86, 0.8)', 'Insegurança Moderada': 'rgba(255, 159, 64, 0.8)', 'Insegurança Grave': 'rgba(255, 99, 132, 0.8)' };

    // Armazena instâncias de gráficos
    let chartInstances = {};

    // --- Função handleChartClick (AJUSTADA para novos gráficos) ---
    function handleChartClick(evt, chartInstance) {
        const points = chartInstance.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);

        if (points.length) {
            const firstPoint = points[0];
            const clickedIndex = firstPoint.index; // Índice do label/barra clicado
            const datasetIndex = firstPoint.datasetIndex; // Índice do dataset (relevante p/ stacked/grouped)

            const chartContainer = chartInstance.canvas.closest('.grafico-container');
            // Verifica se chartContainer foi encontrado antes de acessar dataset
             if (!chartContainer) {
                 console.error("Não foi possível encontrar o container do gráfico pai.");
                 return;
             }
            const chartKey = chartContainer.dataset.chartKey;
            const isMultiKey = chartContainer.dataset.isMultiKey === 'true';

            if (!chartDataJS || !chartDataJS[chartKey]) { console.error("Dados do gráfico não encontrados para a chave:", chartKey); return; }

            let originalKey = '';
            let clickedOriginalValue = null;
            let clickedFormattedLabel = '';
            let chartTitle = chartDataJS[chartKey].titulo.replace(' (Filtrado)','');

            if (isMultiKey) {
                // Lógica para gráficos com múltiplas chaves originais (EBIA Stacked, Consumo)
                if (chartKey === 'ebia_respostas_stacked') {
                     // Verifica se as estruturas existem antes de acessá-las
                     if (!chartDataJS[chartKey].originalKeys || !chartDataJS[chartKey].datasets || !chartDataJS[chartKey].datasets[datasetIndex] || !chartDataJS[chartKey].labels) {
                          console.error("Estrutura de dados incompleta para ebia_respostas_stacked:", chartDataJS[chartKey]); return;
                     }
                    originalKey = chartDataJS[chartKey].originalKeys[clickedIndex]; // Ex: 'ebia_resposta3'
                    clickedOriginalValue = chartDataJS[chartKey].datasets[datasetIndex].originalValue; // 1 para Sim, 0 para Não
                    const questionLabel = chartDataJS[chartKey].labels[clickedIndex]; // Ex: 'Q3'
                    const answerLabel = chartDataJS[chartKey].datasets[datasetIndex].label.includes('Sim') ? 'Sim' : 'Não';
                    clickedFormattedLabel = `${questionLabel}: ${answerLabel}`;
                    chartTitle = "Respostas EBIA"; // Título genérico para o modal
                } else if (chartKey.startsWith('consumo_')) {
                     if (!chartDataJS[chartKey].originalKeys || !chartDataJS[chartKey].labels) {
                          console.error(`Estrutura de dados incompleta para ${chartKey}:`, chartDataJS[chartKey]); return;
                     }
                    originalKey = chartDataJS[chartKey].originalKeys[clickedIndex]; // Ex: 'consumo_feijao'
                    clickedOriginalValue = 1; // Clicar na barra de consumo significa 'Sim' (valor 1)
                    clickedFormattedLabel = chartDataJS[chartKey].labels[clickedIndex]; // Nome do alimento
                } else {
                    console.error("Lógica multi-key não definida para:", chartKey); return;
                }
            } else {
                // Lógica para gráficos com chave única (Demográficos, Classificação)
                 if (!chartDataJS[chartKey].originalLabels || !chartInstance.data || !chartInstance.data.labels) {
                     console.error(`Estrutura de dados incompleta para ${chartKey}:`, chartDataJS[chartKey]); return;
                 }
                 // Verifica se chartContainer.dataset.originalKey existe
                 if (!chartContainer.dataset.originalKey) {
                     console.error("Atributo data-original-key não encontrado para:", chartKey); return;
                 }
                originalKey = chartContainer.dataset.originalKey; // Pega do atributo data-original-key
                 // Verifica se o índice clicado é válido para originalLabels
                 if (clickedIndex >= chartDataJS[chartKey].originalLabels.length) {
                     console.error(`Índice clicado (${clickedIndex}) fora dos limites para originalLabels de ${chartKey}`); return;
                 }
                clickedOriginalValue = chartDataJS[chartKey].originalLabels[clickedIndex];
                 // Verifica se o índice clicado é válido para os labels do gráfico
                 if (clickedIndex >= chartInstance.data.labels.length) {
                      console.error(`Índice clicado (${clickedIndex}) fora dos limites para labels de ${chartKey}`); return;
                 }
                clickedFormattedLabel = chartInstance.data.labels[clickedIndex];
            }

             // Certifica que temos valores válidos antes de prosseguir
             if (originalKey === '' || clickedOriginalValue === null) {
                 console.error("Não foi possível determinar originalKey ou clickedOriginalValue.");
                 return;
             }


            // --- Preparar e Mostrar Modal (Igual antes) ---
            $('#modalDetalhesLabel').text(`Detalhes para ${chartTitle}: ${escapeHtml(String(clickedFormattedLabel))}`);
            $('#modalContent').hide();
            $('#modalError').hide();
            $('#modalLoading').show();
            $('#modalDetalhes').modal('show');

            // --- Requisição AJAX (Igual antes) ---
             console.log("Enviando para API:", { campo_original: originalKey, valor_clicado: clickedOriginalValue, filtros_originais: originalFiltersJS });
            fetch('api_relatorio_detalhado.php', { /* ... config igual ... */
                 method: 'POST',
                 headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', /* 'X-CSRF-TOKEN': '...' */ },
                 body: JSON.stringify({ campo_original: originalKey, valor_clicado: clickedOriginalValue, filtros_originais: originalFiltersJS })
             })
            .then(response => response.ok ? response.json() : Promise.reject(`Erro HTTP: ${response.status}`))
            .then(data => { /* ... tratamento igual ... */
                 $('#modalLoading').hide();
                 if (data.success && data.data) { populateModalTable(data.data); $('#modalContent').show(); }
                 else { $('#modalError').text(data.message || 'Erro ao buscar detalhes.').show(); }
            })
            .catch(error => { /* ... tratamento igual ... */
                 console.error('Erro na requisição AJAX:', error);
                 $('#modalLoading').hide();
                 $('#modalError').text('Erro ao conectar com o servidor para buscar detalhes. Verifique a conexão e os logs do PHP.').show();
            });
        }
    }

    // --- Funções Auxiliares JS (populateModalTable, escapeHtml, ucfirst - Iguais antes) ---
    function populateModalTable(detailData) { /* ... código igual ... */
        const tableHead = $('#tabelaDetalhes > thead'); const tableBody = $('#tabelaDetalhes > tbody'); tableHead.empty(); tableBody.empty();
        if (!detailData || detailData.length === 0) { tableBody.append('<tr><td colspan="100%" class="text-center text-muted">Nenhum participante encontrado para este critério.</td></tr>'); return; }
        const headers = Object.keys(detailData[0]); let headerRow = '<tr>';
        headers.forEach(header => { let cleanHeader = header.replace(/^(participante_|consumo_|ebia_)/, ''); let displayHeader = ucfirst(cleanHeader.replace(/_/g, ' ')); if (header === 'participante_nome') displayHeader = 'Nome'; if (header === 'participante_idade') displayHeader = 'Idade'; if (header === 'ebia_classificacao') displayHeader = 'Classif. EBIA'; headerRow += `<th>${escapeHtml(displayHeader)}</th>`; });
        headerRow += '</tr>'; tableHead.append(headerRow);
        detailData.forEach(row => { let tableRow = '<tr>'; headers.forEach(header => { let value = row[header] !== null ? row[header] : '-'; if (header === 'ebia_classificacao' && value !== '-' && value !== '') { let originalValue = value.toLowerCase().replace(/ /g, '_'); let cssClass = originalValue.replace(/_/g, '-'); tableRow += `<td><span class="badge badge-pill ${escapeHtml(cssClass)}">${escapeHtml(value)}</span></td>`; } else { tableRow += `<td>${escapeHtml(String(value))}</td>`; } }); tableRow += '</tr>'; tableBody.append(tableRow); });
    }
    function escapeHtml(unsafe) { if (typeof unsafe !== 'string') return unsafe; return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;"); }
    function ucfirst(string) { return string.charAt(0).toUpperCase() + string.slice(1); }


    // --- Lógica de Renderização dos Gráficos (MODIFICADA) ---
    document.addEventListener('DOMContentLoaded', () => {

        // Inicializar DataTables na tabela detalhada
        // Adiciona um try-catch para o caso de jQuery ou DataTables não carregarem
        try {
             $('#tabela-detalhada').DataTable({
                 language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' },
                 responsive: true,
             });
        } catch(e) {
             console.error("Erro ao inicializar DataTables:", e);
        }


        // --- Renderizar Gráficos ---
         // Verifica se chartDataJS é um objeto e tem chaves antes de iterar
         if (chartDataJS && typeof chartDataJS === 'object' && Object.keys(chartDataJS).length > 0) {
            for (const keyGrafico in chartDataJS) {
                if (chartDataJS.hasOwnProperty(keyGrafico)) {
                    const grafico = chartDataJS[keyGrafico];
                    const canvasId = `chart-${keyGrafico.replace(/[^a-zA-Z0-9-_]/g, '')}`;
                    console.log(`Processing chart: ${keyGrafico}, Canvas ID: ${canvasId}`); // Log

                    const ctx = document.getElementById(canvasId)?.getContext('2d');

                    if (ctx) { // Verifica se o contexto do canvas existe
                         console.log(`  Canvas context FOUND for ${canvasId}`);
                         // Verifica se a estrutura de dados básica para o gráfico existe
                        if (grafico && ((grafico.labels && (grafico.data || grafico.datasets)) || grafico.datasets)) {
                            console.log(`  Data FOUND for ${keyGrafico}:`, grafico);

                            if (chartInstances[canvasId]) { chartInstances[canvasId].destroy(); } // Limpa gráfico anterior

                            let chartType = 'pie'; // Default
                            let chartOptions = {
                                responsive: true, maintainAspectRatio: false,
                                onClick: (evt, elements, chart) => handleChartClick(evt, chart),
                                plugins: { legend: { position: 'bottom', display: true, labels:{padding: 15, boxWidth: 12} }, tooltip: { }, title: { display: false } },
                                scales: {}
                            };
                             // Callback padrão para tooltip (pode ser sobrescrito)
                             chartOptions.plugins.tooltip.callbacks = {
                                 label: function(context) {
                                     let label = context.label || ''; let value = context.raw || 0;
                                      // Acessa 'grafico' do escopo externo que ainda deve estar correto
                                     const currentChartData = chartDataJS[keyGrafico];
                                     const percentage = currentChartData?.percentuais ? currentChartData.percentuais[context.dataIndex] : null;
                                     let output = `${label}: ${value}`;
                                     // Adiciona percentual apenas se existir e não for o gráfico stacked (já mostra %)
                                     if (percentage !== null && keyGrafico !== 'ebia_respostas_stacked') {
                                          output += ` (${percentage}%)`;
                                     }
                                     return output;
                                 }
                             };

                            let chartConfigData = {};

                            // --- Configurações Específicas por Gráfico ---
                            try { // Adiciona try-catch em volta da criação do gráfico
                                if (keyGrafico === 'ebia_respostas_stacked') {
                                     if (!grafico.datasets || grafico.datasets.length < 2) { throw new Error("Dados incompletos para gráfico EBIA stacked."); }
                                    chartType = 'bar';
                                    chartConfigData = { labels: grafico.labels, datasets: [ { label: grafico.datasets[0].label, data: grafico.datasets[0].data, backgroundColor: simColor }, { label: grafico.datasets[1].label, data: grafico.datasets[1].data, backgroundColor: naoColor } ] };
                                    chartOptions.scales = { x: { stacked: true, ticks:{ font:{size:11} } }, y: { stacked: true, beginAtZero: true, max: 100, title: { display: true, text: '%' }, ticks:{ font:{size:10} } } };
                                    chartOptions.plugins.tooltip = { mode: 'index', intersect: false }; // Sobrescreve tooltip callback

                                } else if (keyGrafico === 'consumo_recomendados' || keyGrafico === 'consumo_ultraprocessados') {
                                     if (!grafico.labels || !grafico.data) { throw new Error(`Dados incompletos para gráfico ${keyGrafico}.`); }
                                    chartType = 'bar';
                                    chartConfigData = { labels: grafico.labels, datasets: [{ label: '% de Participantes', data: grafico.data, backgroundColor: backgroundColors.slice(0, grafico.labels.length), borderColor: borderColors.slice(0, grafico.labels.length), borderWidth: 1 }] };
                                    chartOptions.scales = { y: { beginAtZero: true, max: 100, title: { display: true, text: '%' } }, x: { ticks: {font:{size:11}} } };
                                    chartOptions.plugins.legend.display = false;

                                } else if (keyGrafico === 'classificacao') {
                                     if (!grafico.labels || !grafico.data) { throw new Error("Dados incompletos para gráfico Classificação."); }
                                    chartType = 'pie';
                                    chartConfigData = { labels: grafico.labels, datasets: [{ data: grafico.data, backgroundColor: grafico.labels.map(label => colorMapEbia[label] || '#cccccc'), borderColor: '#fff', borderWidth: 1 }] };
                                    // Tooltip padrão com percentual já configurado

                                } else if (keyGrafico === 'refeicoes') {
                                     if (!grafico.labels || !grafico.data) { throw new Error("Dados incompletos para gráfico Refeições."); }
                                     chartType = 'bar';
                                     chartConfigData = { labels: grafico.labels, datasets: [{ label: 'Ocorrências', data: grafico.data, backgroundColor: backgroundColors.slice(0, grafico.labels.length) }] };
                                     chartOptions.indexAxis = 'y'; chartOptions.scales = { x: { beginAtZero: true } }; chartOptions.plugins.legend.display = false;

                                } else if (keyGrafico === 'usa_dispositivos') {
                                      if (!grafico.labels || !grafico.data) { throw new Error("Dados incompletos para gráfico Usa Dispositivos."); }
                                     chartType = 'pie';
                                     chartConfigData = { labels: grafico.labels, datasets: [{ data: grafico.data, backgroundColor: [simColor, naoColor].slice(0, grafico.labels.length) }] };
                                     // Tooltip padrão com percentual já configurado

                                } else { // Gráficos demográficos restantes
                                     if (!grafico.labels || !grafico.data) { throw new Error(`Dados incompletos para gráfico ${keyGrafico}.`); }
                                    const numCategories = grafico.labels.length;
                                    chartType = (numCategories <= 5) ? 'pie' : 'bar';
                                    chartConfigData = { labels: grafico.labels, datasets: [{ data: grafico.data, backgroundColor: backgroundColors.slice(0, numCategories), borderColor: chartType === 'pie' ? '#fff' : borderColors.slice(0, numCategories), borderWidth: 1, label: 'Contagem' }] };
                                    if (chartType === 'pie') { /* Tooltip padrão com % ok */ }
                                    else { chartOptions.indexAxis = (numCategories > 7) ? 'y' : 'x'; chartOptions.scales = (chartOptions.indexAxis === 'y') ? { x: { beginAtZero: true }, y: { ticks: { font:{size: (numCategories > 15 ? 9:11)} } } } : { y: { beginAtZero: true }, x: { ticks: { font:{size: (numCategories > 10 ? 9:11)} } } }; chartOptions.plugins.legend.display = false; }
                                }

                                // Cria o gráfico
                                chartInstances[canvasId] = new Chart(ctx, { type: chartType, data: chartConfigData, options: chartOptions });
                                console.log(`  Chart CREATED for ${keyGrafico}`);

                             } catch (e) {
                                  console.error(`  Error CREATING chart ${keyGrafico}:`, e);
                                   // Opcional: Exibir erro no lugar do canvas
                                   ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                                   ctx.fillStyle = '#ccc'; ctx.textAlign = 'center'; ctx.fillText('Erro ao criar gráfico', ctx.canvas.width/2, ctx.canvas.height/2);
                             }

                        } else {
                             console.warn(`  Data structure INVALID or empty for ${keyGrafico}`);
                             // Limpa canvas e mostra mensagem 'sem dados'
                             ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                             const container = ctx.canvas.parentNode;
                             if (container && !container.querySelector('.no-data-message')) {
                                container.innerHTML = '<p class="text-muted text-center small p-3 no-data-message">Sem dados suficientes para este gráfico.</p>';
                             }
                         }
                    } else {
                         console.warn(`  Canvas context NOT FOUND for ${canvasId}`);
                    }
                }
            }
        } else {
            console.warn("chartDataJS is empty or not an object. No charts will be rendered.");
             // Opcional: esconder toda a seção de gráficos se não houver dados
            // document.querySelector('.row.justify-content-center').style.display = 'none';
        }
    });
    </script>
  <?php endif; ?>
  </body>
</html>