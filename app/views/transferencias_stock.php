<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Transferência de Stock
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item">
                            Stock
                        </li>

                        <li class="breadcrumb-item active">
                            Transferências
                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <form id="formTransferenciaStock">

            <!-- Dados -->
            <div class="card">

                <div class="card-header">

                    <h5 class="card-title mb-0">
                        Dados da Transferência
                    </h5>

                </div>

                <div class="card-body">

                    <div class="form-row">

                        <!-- Origem -->
                        <div class="form-group col-md-4">

                            <label for="transferenciaArmazemOrigem">
                                Armazém de Origem
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-control"
                                id="transferenciaArmazemOrigem"
                                name="armazem_origem_id"
                                required>
                                <option value="">
                                    Selecione
                                </option>
                            </select>

                        </div>


                        <!-- Destino -->
                        <div class="form-group col-md-4">

                            <label for="transferenciaArmazemDestino">
                                Armazém de Destino
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-control"
                                id="transferenciaArmazemDestino"
                                name="armazem_destino_id"
                                required>
                                <option value="">
                                    Selecione
                                </option>
                            </select>

                        </div>


                        <!-- Data -->
                        <div class="form-group col-md-4">

                            <label for="transferenciaData">
                                Data da Transferência
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="datetime-local"
                                class="form-control"
                                id="transferenciaData"
                                name="data_movimento"
                                required>

                        </div>


                        <!-- Observações -->
                        <div class="form-group col-md-12">

                            <label for="transferenciaObservacoes">
                                Observações
                            </label>

                            <textarea
                                class="form-control"
                                id="transferenciaObservacoes"
                                name="observacoes"
                                rows="3"
                                maxlength="1000"
                                placeholder="Ex.: Transferência para reforço do stock da filial..."></textarea>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Produtos -->
            <div class="card">

                <div class="card-header">

                    <div class="row align-items-center">

                        <div class="col">

                            <h5 class="card-title mb-0">
                                Produtos a Transferir
                            </h5>

                        </div>

                        <div class="col-auto">

                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                id="btnAdicionarProdutoTransferencia">
                                <i class="fa fa-plus"></i>
                                Adicionar Produto
                            </button>

                        </div>

                    </div>

                </div>


                <div class="card-body">

                    <div class="table-responsive">

                        <table
                            class="table table-bordered"
                            id="transferenciaProdutosTable">

                            <thead>

                                <tr>
                                    <th style="min-width:280px;">
                                        Produto
                                    </th>

                                    <th style="width:120px;">
                                        Unidade
                                    </th>

                                    <th style="width:140px;">
                                        Disponível
                                    </th>

                                    <th style="width:150px;">
                                        Quantidade
                                    </th>

                                    <th style="width:180px;">
                                        Custo Unitário
                                    </th>

                                    <th style="width:180px;">
                                        Valor
                                    </th>

                                    <th style="width:60px;"></th>
                                </tr>

                            </thead>

                            <tbody id="transferenciaProdutosBody"></tbody>


                            <tfoot>

                                <tr>

                                    <th colspan="5" class="text-right">
                                        Valor Total
                                    </th>

                                    <th id="transferenciaValorTotal">
                                        0,00 MT
                                    </th>

                                    <th></th>

                                </tr>

                            </tfoot>

                        </table>

                    </div>

                </div>

            </div>


            <!-- Botões -->
            <div class="card">

                <div class="card-body text-right">

                    <button
                        type="button"
                        class="btn btn-light"
                        id="btnLimparTransferencia">
                        <i class="fa fa-eraser"></i>
                        Limpar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-info"
                        id="btnGuardarTransferencia">
                        <i class="fa fa-exchange"></i>
                        Transferir Stock
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>