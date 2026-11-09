<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Pagamento de IVA</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item">Contabilidade</li>
                        <li class="breadcrumb-item active">Pagamento de IVA</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Apuramento do IVA</h5>
            </div>
            <div class="card-body">
                <form id="formApuramentoIva">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="ivaPeriodicidade">Periodicidade</label>
                            <select id="ivaPeriodicidade" class="form-control" required>
                                <option value="MENSAL">Mensal</option>
                                <option value="TRIMESTRAL">Trimestral</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="ivaPeriodoId">Período</label>
                            <select id="ivaPeriodoId" class="form-control" required>
                                <option value="">Selecione</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ivaDataInicio">Data inicial</label>
                            <input id="ivaDataInicio" type="date" class="form-control" required readonly>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ivaDataFim">Data final</label>
                            <input id="ivaDataFim" type="date" class="form-control" required readonly>
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end">
                            <button type="button" id="btnCalcularIva" class="btn btn-primary btn-block">
                                <i class="fa fa-calculator"></i> Calcular
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body"><small>IVA liquidado</small>
                        <h4 id="ivaLiquidado">0,00 MTn</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body"><small>IVA dedutível</small>
                        <h4 id="ivaDedutivel">0,00 MTn</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body"><small>IVA a pagar</small>
                        <h4 id="ivaPagar">0,00 MTn</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Apuramentos registados</h5>
                <button type="button" id="btnApurarIva" class="btn btn-success" disabled>
                    <i class="fa fa-check"></i> Confirmar apuramento
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Limite</th>
                                <th>Liquidado</th>
                                <th>Dedutível</th>
                                <th>A pagar</th>
                                <th>Pago</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="ivaApuramentosBody">
                            <tr>
                                <td colspan="9" class="text-center">A carregar...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPagamentoIva" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formPagamentoIva">
                <div class="modal-header">
                    <h5 class="modal-title">Registar pagamento do IVA</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="pagamentoIvaApuramentoId">
                    <div class="alert alert-info">Saldo pendente: <strong id="pagamentoIvaSaldo">0,00 MTn</strong></div>
                    <div class="form-group"><label>Fonte</label><select id="pagamentoIvaFonte" class="form-control" required>
                            <option value="BANCO">Banco</option>
                            <option value="CAIXA">Caixa</option>
                        </select></div>
                    <div class="form-group" id="grupoPagamentoIvaBanco"><label>Conta bancária</label><select id="pagamentoIvaBanco" class="form-control"></select></div>
                    <div class="form-group d-none" id="grupoPagamentoIvaCaixa"><label>Caixa aberto</label><select id="pagamentoIvaCaixa" class="form-control"></select></div>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label>Valor</label><input id="pagamentoIvaValor" type="number" min="0.01" step="0.01" class="form-control" required></div>
                        <div class="form-group col-md-6"><label>Data</label><input id="pagamentoIvaData" type="datetime-local" class="form-control" required></div>
                    </div>
                    <div class="form-group">
                        <label>Comprovativo (PDF, PNG, JPG)</label>
                        <input type="file" id="pagamentoIvaComprovativo" name="comprovativo" class="form-control-file" accept=".pdf,.png,.jpg,.jpeg">
                    </div>
                    <div class="form-group"><label>Referência da guia</label><input id="pagamentoIvaReferencia" maxlength="100" class="form-control" required></div>
                    <div class="form-group"><label>Documento</label><input id="pagamentoIvaDocumento" maxlength="100" class="form-control"></div>
                    <div class="form-group"><label>Observações</label><textarea id="pagamentoIvaObservacoes" maxlength="1000" class="form-control"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Confirmar pagamento</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHistoricoPagamentosIva" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Histórico de Pagamentos</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Fonte</th>
                                <th>Conta / Caixa</th>
                                <th>Referência</th>
                                <th>Valor</th>
                                <th>Comprovativo</th>
                            </tr>
                        </thead>
                        <tbody id="historicoPagamentosIvaBody">
                            <tr>
                                <td colspan="6" class="text-center">A carregar...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>