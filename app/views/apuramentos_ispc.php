<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Apuramento de ISPC (Imposto Simplificado)</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Apuramento ISPC</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-primary" id="btnNovoApuramentoISPC">
                        <i class="fa fa-calculator"></i> Novo Apuramento ISPC
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="table-responsive">
            <table id="apuramentosISPCTable" class="table table-striped custom-table mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empresa / Filial</th>
                        <th>Exercício</th>
                        <th>Volume de Negócios</th>
                        <th>Taxa Aplicada</th>
                        <th>Imposto Apurado</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div>
</div>

<!-- Modal Novo Apuramento ISPC -->
<div id="modalApuramentoISPC" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <form id="formApuramentoISPC" class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Efetuar Apuramento de ISPC</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="apuramento_filial_id">Filial / Empresa *</label>
                    <select class="form-control" id="apuramento_filial_id" name="filial_id">
                        <option value="">Sede / Matriz</option>
                    </select>
                    <small class="text-muted">Selecione a filial para puxar automaticamente a faturação e o enquadramento fiscal configurado.</small>
                </div>

                <div class="form-group">
                    <label for="exercicio_fiscal">Exercício Fiscal (Ano) *</label>
                    <input type="number" class="form-control" id="exercicio_fiscal" name="exercicio_fiscal" value="<?= date('Y') ?>" required>
                    <small class="text-muted">O sistema vai buscar o volume de negócios estimado ou registado na configuração fiscal deste ano para calcular o imposto.</small>
                </div>

                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> O cálculo do ISPC incide automaticamente sobre o volume de negócios anual com base nos escalões legais configurados no sistema.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarApuramentoISPC">
                    <i class="fa fa-calculator"></i> Calcular e Registar Apuramento
                </button>
            </div>
        </form>
    </div>
</div>