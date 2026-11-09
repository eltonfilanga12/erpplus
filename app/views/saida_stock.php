<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Saída de Stock
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
                            Saída de Stock
                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <form id="formSaidaStock">

            <div class="card">

                <div class="card-header">

                    <h5 class="card-title mb-0">
                        Dados da Saída
                    </h5>

                </div>

                <div class="card-body">

                    <div class="form-row">

                        <div class="form-group col-md-6">

                            <label for="saidaArmazemId">
                                Armazém de Origem
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-control"
                                id="saidaArmazemId"
                                name="armazem_id"
                                required>
                                <option value="">
                                    A carregar armazéns...
                                </option>
                            </select>

                        </div>


                        <div class="form-group col-md-6">

                            <label for="saidaData">
                                Data da Saída
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="datetime-local"
                                class="form-control"
                                id="saidaData"
                                name="data_movimento"
                                required>

                        </div>


                        <div class="form-group col-md-12">

                            <label for="saidaObservacoes">
                                Observações / Motivo
                            </label>

                            <textarea
                                class="form-control"
                                id="saidaObservacoes"
                                name="observacoes"
                                rows="3"
                                maxlength="1000"
                                placeholder="Ex.: Consumo interno, dano, oferta, saída manual..."></textarea>

                        </div>

                    </div>

                </div>

            </div>


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
                                id="btnAdicionarProdutoSaida">
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
                            id="saidaProdutosTable">

                            <thead>

                                <tr>
                                    <th style="min-width:280px;">Produto</th>
                                    <th style="width:120px;">Unidade</th>
                                    <th style="width:140px;">Disponível</th>
                                    <th style="width:150px;">Quantidade</th>
                                    <th style="width:160px;">Custo Unitário</th>
                                    <th style="width:160px;">Subtotal</th>
                                    <th style="width:60px;"></th>
                                </tr>

                            </thead>

                            <tbody id="saidaProdutosBody"></tbody>

                            <tfoot>

                                <tr>

                                    <th colspan="5" class="text-right">
                                        Total
                                    </th>

                                    <th id="saidaValorTotal">
                                        0,00 MT
                                    </th>

                                    <th></th>

                                </tr>

                            </tfoot>

                        </table>

                    </div>

                </div>

            </div>


            <div class="card">

                <div class="card-body text-right">

                    <button
                        type="button"
                        class="btn btn-light"
                        id="btnLimparSaidaStock">
                        <i class="fa fa-eraser"></i>
                        Limpar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-danger"
                        id="btnGuardarSaidaStock">
                        <i class="fa fa-save"></i>
                        Registar Saída
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>