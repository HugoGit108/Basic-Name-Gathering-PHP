<div class="container-fluid mb-5 bg-white">
    <div class="row justify-content-center pb-5">
        <div class="col-12 p-4">

            <div class="row">
                <div class="col">
                    <h4><strong>Dados estatísticos</strong></h4>
                </div>
                <div class="col text-end">
                    <a href="?ct=main&mt=index" class="btn btn-secondary px-4"><i class="fa-solid fa-chevron-left me-2"></i>Voltar</a>
                </div>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-sm-6 col-12 p-1">
                    <div class="card p-3">
                        <h4><i class="fa-solid fa-users me-2"></i>Clientes dos agentes</h4>
                        <?php if (count($agents) == 0) : ?>
                            <p class="text-center">Não foram encontrados dados.</p>
                        <?php else : ?>
                            <table class="table table-striped table-bordered" id="tableAgents">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Agente</th>
                                        <th class="text-center">Clientes registados</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($agents as $agent) : ?>
                                        <tr>
                                            <td><?= $agent->agente ?></td>
                                            <td class="text-center"><?= $agent->total_clientes ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                    </div>
                </div>
                <div class="col-sm-6 col-12 p-1">
                    <div class="card p-3">
                        <h4><i class="fa-solid fa-users me-2"></i>Gráfico</h4>
                        <!-- Bar graph -->
                        <canvas id="chartJsChart" height="300px"></canvas>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col p-1">

                    <div class="card p-3">
                        <h4><i class="fa-solid fa-list-ul me-2"></i>Dados estatísticos globais</h4>
                        <div class="row justify-content-center">
                            <div class="col-5">
                                <table class="table table-striped" style="font-size: 110%;">
                                    <tr>
                                        <td class="text-start">Numero total de agentes:</td>
                                        <td class="text-start"><strong><?= $globalStats['totalAgents']->value ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start">Numero total de clientes:</td>
                                        <td class="text-start"><strong><?= $globalStats['totalClients']->value?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start">Numero total de clientes inativos:</td>
                                        <td class="text-start"><strong><?= $globalStats['totalInactiveClients']->value ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start">Numero média de clientes por agente:</td>
                                        <td class="text-start"><strong><?= sprintf("%d", $globalStats['averageClientsPerAgent']->value) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start">Idade do cliente mais novo:</td>
                                        <td class="text-start"><strong><?= empty($globalStats['youngerClient']->value) ? '0' : $globalStats['youngerClient']->value ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start">Idade do cliente mais velho:</td>
                                        <td class="text-start"><strong><?= empty($globalStats['oldestClient']->value) ? '0' : $globalStats['oldestClient']->value ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start">Média de cliente mais velho:</td>
                                        <td class="text-start"><strong><?= sprintf("%d",$globalStats['averageAge']->value) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start">Percentagem de clientes homens:</td>
                                        <td class="text-start"><strong><?= sprintf("%d",$globalStats['percentageMales']->value) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start">Percentagem de clientes mulheres:</td>
                                        <td class="text-start"><strong><?= sprintf("%d",$globalStats['percentageFemales']->value) ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="text-center">
                            <a href="?ct=admin&mt=create_pdf_report" target="_blank" class="btn btn-secondary px-4"><i class="fa-solid fa-file me-2"></i>Criar relatório em pdf</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
</div>


<script>
    // datatables
    $(document).ready(function() {
        $('#tableAgents').DataTable({
            pageLenght: 10,
            pagingType: 'full_numbers',
            language: {
                "emptyTable": "Não foi encontrado nenhum registo",
                "loadingRecords": "A carregar...",
                "processing": "A processar...",
                "lengthMenu": "Mostrar _MENU_ registos",
                "zeroRecords": "Não foram encontrados resultados",
                "info": "Mostrando _START_ até _END_ de _TOTAL_ registos",
                "search": "Procurar:",
                "paginate": {
                    "first": "Primeiro",
                    "previous": "Anterior",
                    "next": "Seguinte",
                    "last": "Último"
                },
                "aria": {
                    "sortAscending": ": Ordenar colunas de forma ascendente",
                    "sortDescending": ": Ordenar colunas de forma descendente"
                }
            }
        });
    });

    // chart js 
    <?php if (count($agents) !== 0) : ?>
        new Chart (
            document.querySelector('#chartJsChart'), {
                type: "bar",
                data: {
                    labels: <?= $chartLabels ?>,
                    datasets: [{
                        label: "Total de clientes por agente",
                        data: <?= $chartTotals ?>,  
                        backgroundColor: 'rgb(50,100,200)',
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                },
            }
        );
    <?php endif; ?>
</script>