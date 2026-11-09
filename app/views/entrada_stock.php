<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Entrada de Stock
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
                            Entrada de Stock
                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <form id="formEntradaStock">

            <div class="card">

                <div class="card-header">

                    <h5 class="card-title mb-0">
                        Dados da Entrada
                    </h5>

                </div>


                <div class="card-body">

                    <div class="form-row">

                        <!-- Armazém -->
                        <div class="form-group col-md-3">

                            <label for="entradaArmazemId">
                                Armazém de Destino
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-control"
                                id="entradaArmazemId"
                                name="armazem_id"
                                required>
                                <option value="">
                                    A carregar armazéns...
                                </option>
                            </select>

                        </div>


                        <!-- Data -->
                        <div class="form-group col-md-3">

                            <label for="entradaData">
                                Data da Entrada
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="datetime-local"
                                class="form-control"
                                id="entradaData"
                                name="data_movimento"
                                required>

                        </div>

                        <!-- Origem contabilística -->
                        <div class="form-group col-md-3">

                            <label for="entradaOrigemContabilistica">
                                Origem contabilística
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-control"
                                id="entradaOrigemContabilistica"
                                name="origem_contabilistica"
                                required>

                                <option value="">
                                    Selecione a origem
                                </option>

                                <option value="STOCK_INICIAL">
                                    Stock inicial — Resultados transitados
                                </option>

                                <option value="CAPITAL_SOCIOS">
                                    Entrada dos sócios — Capital
                                </option>

                                <option value="AJUSTE_POSITIVO">
                                    Ajuste positivo de inventário
                                </option>

                            </select>

                            <small class="form-text text-muted">
                                Compras devem ser registadas no módulo de Compras.
                            </small>

                        </div>

                        <div class="form-group col-md-3">

                            <label>
                                Estado
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="Confirmado"
                                readonly>

                        </div>


                        <!-- Observações -->
                        <div class="form-group col-md-12">

                            <label for="entradaObservacoes">
                                Observações
                            </label>

                            <textarea
                                class="form-control"
                                id="entradaObservacoes"
                                name="observacoes"
                                rows="3"
                                maxlength="1000"
                                placeholder="Ex.: Entrada manual de stock inicial..."></textarea>

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
                                Produtos
                            </h5>

                        </div>


                        <div class="col-auto">

                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                id="btnAdicionarProdutoEntrada">
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
                            id="entradaProdutosTable">

                            <thead>

                                <tr>
                                    <th style="min-width:280px;">
                                        Produto
                                    </th>

                                    <th style="width:130px;">
                                        Unidade
                                    </th>

                                    <th style="width:150px;">
                                        Quantidade
                                    </th>

                                    <th style="width:180px;">
                                        Custo Unitário
                                    </th>

                                    <th style="width:180px;">
                                        Subtotal
                                    </th>

                                    <th style="width:60px;"></th>
                                </tr>

                            </thead>


                            <tbody id="entradaProdutosBody"></tbody>


                            <tfoot>

                                <tr>

                                    <th colspan="4" class="text-right">
                                        Total
                                    </th>

                                    <th id="entradaValorTotal">
                                        0,00 MT
                                    </th>

                                    <th></th>

                                </tr>

                            </tfoot>

                        </table>

                    </div>

                </div>

            </div>


            <!-- Buttons -->
            <div class="card">

                <div class="card-body text-right">

                    <button
                        type="button"
                        class="btn btn-light"
                        id="btnLimparEntradaStock">
                        <i class="fa fa-eraser"></i>
                        Limpar
                    </button>


                    <button
                        type="submit"
                        class="btn btn-success"
                        id="btnGuardarEntradaStock">
                        <i class="fa fa-save"></i>
                        Registar Entrada
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>