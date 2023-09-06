<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 p-5 bg-white">

            <div class="row">
                <div class="col">
                    <h4>Gestão de agentes</h4>
                </div>
                <div class="col text-end">
                    <a href="?ct=admin&mt=new_agent_frm" class="btn btn-secondary"><i class="fa-solid fa-user-plus me-2"></i>Novo agente</a>
                </div>
            </div>

            <hr>

            <?php if (!count($agents)) : ?>
                <p class="my-5 text-center opacity-75">Não existem agentes registados.</p>

                <div class="mb-5 text-center">
                    <a href="?ct=main&mt=index" class="btn btn-secondary px-4"><i class="fa-solid fa-chevron-left me-2"></i>Voltar</a>
                </div>
            <?php else : ?>
                <table class="table table-striped table-bordered" id="tableAgents">
                    <thead class="table-dark">
                        <tr>
                            <th>Nome</th>
                            <th class="text-center">Perfil</th>
                            <th class="text-center">Registado</th>
                            <th class="text-center">Último login</th>
                            <th class="text-center">Data criação</th>
                            <th class="text-center">Atualizado em</th>
                            <th class="text-center">Eliminado em</th>
                            <th width="15%"></th>

                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agents as $agent) : ?>
                            <tr>

                                <td>
                                    <?php if ($agent->profile == 'admin') : ?>
                                        <i class="fa-solid fa-user-tie"></i>
                                    <?php else : ?>
                                        <i class="fa-regular fa-user"></i>
                                    <?php endif; ?>
                                    <span class="ms-3 fw-bold"><?= $agent->name ?></span>
                                </td>
                                <td class="text-center fw-bold"><?= $agent->profile ?></td>
                                <td class="text-center">
                                    <?php if (!empty($agent->passwrd)) : ?>
                                        <i class="fa solid fa-circle-check text-success"></i>
                                    <?php else : ?>
                                        <i class="fa solid fa-circle-xmark text-danger"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= $agent->last_login ?></td>
                                <td class="text-center"><?= $agent->created_at ?></td>
                                <td class="text-center"><?= $agent->updated_at ?></td>
                                <td class="text-center text-danger"><?= $agent->deleted_at ?></td>
                                <td class="text-center">

                                    <?php if ($agent->id != $_SESSION['user']->id) : ?>
                                        <?php if (empty($agent->deleted_at)) : ?>
                                            <a href="?ct=admin&mt=edit_agent&id=<?= aes_encrypt($agent->id) ?>"><i class="fa-regular fa-pen-to-square me-2"></i>Editar</a>
                                            <span class="mx-2 opacity-50">|</span>
                                            <a href="?ct=admin&mt=edit_delete&id=<?= aes_encrypt($agent->id) ?>"><i class="fa-solid fa-trash me-2"></i>Eliminar</a>
                                        <?php else : ?>
                                            <span class="opacity-50"><i class="fa-regular fa-pen-to-square me-2"></i></span>
                                            <span class="mx-2 opacity-50">|</span>
                                            <a href="?ct=admin&mt=edit_recover&id=<?= aes_encrypt($agent->id) ?>"><i class="fa-solid fa-rotate-right me-2"></i>Recuperar</a>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="row mt-3">
                    <div class="col">
                        <p class="mb-5">Total: <strong><?= count($agents) ?></strong></p>
                    </div>
                    <div class="col text-end">
                        <a href="?ct=admin&mt=export_agents_xslx" class="btn btn-secondary px-4"><i class="fa-regular fa-file-excel me-2"></i>Exportar para XLSX</a>
                        <a href="?ct=main&mt=index" class="btn btn-secondary px-4"><i class="fa-solid fa-chevron-left me-2"></i>Voltar</a>
                    </div>
                </div>
            <?php endif; ?>
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
</script>