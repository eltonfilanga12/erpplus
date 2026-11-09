<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <h3 class="page-title">Obrigações Fiscais e Sociais</h3>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="abasObrigacoes">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#abaInss" data-tipo="INSS">INSS</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#abaIrps" data-tipo="IRPS">IRPS</a></li>
                </ul>
                <div class="row mt-3">
                    <div class="col-md-3"><label>Ano</label><select id="obrigacaoAno" class="form-control"></select></div>
                    <div class="col-md-3"><label>Filial</label><select id="obrigacaoFilial" class="form-control">
                            <option value="">Todas as filiais</option>
                        </select></div>
                    <div class="col-md-2 d-flex align-items-end"><button id="btnFiltrarObrigacoes" class="btn btn-primary btn-block"><i class="fa fa-search"></i> Filtrar</button></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body"><small id="rotuloTrabalhador">Trabalhador</small>
                        <h4 id="totalTrabalhador">0,00 MTn</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3" id="cardPatronal">
                <div class="card">
                    <div class="card-body"><small>Patronal</small>
                        <h4 id="totalPatronal">0,00 MTn</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body"><small>Total apurado</small>
                        <h4 id="totalApurado">0,00 MTn</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body"><small>Saldo pendente</small>
                        <h4 id="totalPendente">0,00 MTn</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Apuramentos por período salarial</h5>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Período</th>
                            <th>Trabalhador</th>
                            <th>Patronal</th>
                            <th>Apurado</th>
                            <th>Pago</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="obrigacoesBody">
                        <tr>
                            <td colspan="8" class="text-center">A carregar...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPagarObrigacao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formPagarObrigacao">
                <div class="modal-header">
                    <h5 class="modal-title">Registar pagamento</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="pagFolhaId"><input type="hidden" id="pagTipo"><input type="hidden" id="pagSaldoMaximo">
                    <div class="alert alert-info">Saldo: <strong id="pagSaldoTexto">0,00 MTn</strong></div>
                    <div class="form-group"><label>Fonte</label><select id="pagFonte" class="form-control" required>
                            <option value="BANCO">Banco</option>
                            <option value="CAIXA">Caixa</option>
                        </select></div>
                    <div class="form-group" id="grupoBanco"><label>Conta bancária</label><select id="pagBanco" class="form-control"></select></div>
                    <div class="form-group d-none" id="grupoCaixa"><label>Caixa aberto</label><select id="pagCaixa" class="form-control"></select></div>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label>Valor</label><input id="pagValor" type="number" min="0.01" step="0.01" class="form-control" required></div>
                        <div class="form-group col-md-6"><label>Data</label><input id="pagData" type="datetime-local" class="form-control" required></div>
                    </div>
                    <div class="form-group">
                        <label for="pagCentroCusto">Centro de custo</label>
                        <select id="pagCentroCusto" class="form-control" required>
                            <option value="">Selecione</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Referência da guia</label><input id="pagReferencia" maxlength="100" class="form-control" required></div>
                    <div class="form-group"><label>Documento</label><input id="pagDocumento" maxlength="100" class="form-control"></div>
                    <div class="form-group">
                        <label for="pagComprovativo">Comprovativo de Pagamento (PDF, PNG, JPG)</label>
                        <input type="file" id="pagComprovativo" name="comprovativo" class="form-control-file" accept=".pdf,.png,.jpg,.jpeg">
                    </div>
                    <div class="form-group"><label>Observações</label><textarea id="pagObservacoes" maxlength="1000" class="form-control"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button><button class="btn btn-success" type="submit">Confirmar pagamento</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHistoricoPagamentos" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloHistoricoPagamentos">Histórico de Pagamentos</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Fonte</th>
                                <th>Conta / Caixa</th>
                                <th>Guia / Ref.</th>
                                <th>Valor</th>
                                <th class="text-center">Comprovativo</th>
                            </tr>
                        </thead>
                        <tbody id="historicoPagamentosBody">
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