<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Configurações Fiscais e Tributárias</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Configuração Fiscal</li>
                    </ul>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-primary" id="btnNovaConfigFiscal">
                        <i class="fa fa-plus"></i> Nova Configuração
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="table-responsive">
            <table id="configFiscalTable" class="table table-striped custom-table mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empresa / Filial</th>
                        <th>Regime</th>
                        <th>Tipo de Atividade</th>
                        <th>IVA</th>
                        <th>Faturação Anual Prevista</th>
                        <th>Exercício</th>
                        <th>Status</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div>
</div>

<!-- Modal Configuração Fiscal -->
<div id="modalConfigFiscal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formConfigFiscal" class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tituloModalConfigFiscal">Nova Configuração Fiscal</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="config_fiscal_id" name="id">

                <div class="form-row">
                    <!-- Filial -->
                    <div class="form-group col-md-6">
                        <label for="config_filial_id">Filial</label>
                        <select class="form-control" id="config_filial_id" name="filial_id">
                            <option value="">Sede / Matriz</option>
                        </select>
                    </div>

                    <!-- Estado / Status -->
                    <div class="form-group col-md-6">
                        <label for="config_status_id">Status *</label>
                        <select class="form-control" id="config_status_id" name="estado_id" required>
                            <option value="">Selecione</option>
                        </select>
                    </div>

                    <!-- Regime Tributário -->
                    <div class="form-group col-md-6">
                        <label for="regime_tributario">Regime Tributário *</label>
                        <select class="form-control" id="regime_tributario" name="regime_tributario" required>
                            <option value="ISPC">ISPC (Imposto Simplificado p/ Pequenos Contribuintes)</option>
                            <option value="IRPC">IRPC (Imposto sobre Rendimento das Pessoas Colectivas)</option>
                            <option value="IRPS">IRPS (Imposto sobre Rendimento das Pessoas Singulares)</option>
                            <option value="OUTRO">Outro</option>
                        </select>
                    </div>

                    <!-- Tipo de Atividade -->
                    <div class="form-group col-md-6">
                        <label for="tipo_atividade">Tipo de Atividade *</label>
                        <select class="form-control" id="tipo_atividade" name="tipo_atividade" required>
                            <option value="">Selecione</option>
                            <option value="Comercial/Industrial">Comercial / Industrial</option>
                            <option value="Prestação de Serviços">Prestação de Serviços Técnicos Gerais</option>
                            <option value="Profissão Liberal">Profissão Liberal</option>
                        </select>
                    </div>

                    <!-- Sujeito Passivo de IVA -->
                    <div class="form-group col-md-6">
                        <label for="sujeito_passivo_iva">Enquadramento de IVA *</label>
                        <select class="form-control" id="sujeito_passivo_iva" name="sujeito_passivo_iva" required>
                            <option value="0">Isento (Contribuinte ISPC)</option>
                            <option value="1">Regime Geral de IVA (16%)</option>
                        </select>
                    </div>

                    <!-- Número de Trabalhadores -->
                    <div class="form-group col-md-6">
                        <label for="numero_trabalhadores">Número de Trabalhadores *</label>
                        <input type="number" class="form-control" id="numero_trabalhadores" name="numero_trabalhadores" min="1" value="1" required>
                    </div>

                    <!-- Previsão de Faturação Anual -->
                    <div class="form-group col-md-6">
                        <label for="previsao_faturacao_anual">Previsão de Faturação Anual (MT) *</label>
                        <input type="number" step="0.01" class="form-control" id="previsao_faturacao_anual" name="previsao_faturacao_anual" min="0" value="0.00" required>
                    </div>

                    <!-- Exercício Fiscal -->
                    <div class="form-group col-md-6">
                        <label for="exercicio_fiscal">Exercício Fiscal (Ano) *</label>
                        <input type="number" class="form-control" id="exercicio_fiscal" name="exercicio_fiscal" value="<?= date('Y') ?>" required>
                    </div>

                    <!-- Visualização Rápida da Taxa Calculada -->
                    <div class="col-md-12 alert alert-secondary mt-2">
                        <strong>Taxa ISPC Estimada pelo Sistema: </strong>
                        <span id="taxa_imposto_preview" class="font-weight-bold text-primary">-</span>
                        <br><small class="text-muted">Calculada automaticamente com base no volume de negócios e escalões legais de Moçambique.</small>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarConfigFiscal">
                    <i class="fa fa-save"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>