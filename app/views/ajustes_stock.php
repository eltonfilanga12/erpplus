<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Ajustes de Stock
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
                            Ajustes de Stock
                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <form id="formAjusteStock">

            <div class="card">

                <div class="card-header">

                    <h5 class="card-title mb-0">
                        Dados do Ajuste
                    </h5>

                </div>

                <div class="card-body">

                    <div class="form-row">

                        <div class="form-group col-md-6">

                            <label for="ajusteArmazemId">
                                Armazém
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                class="form-control"
                                id="ajusteArmazemId"
                                name="armazem_id"
                                required>
                                <option value="">
                                    Selecione o armazém
                                </option>
                            </select>

                        </div>


                        <div class="form-group col-md-6">

                            <label for="ajusteData">
                                Data do Ajuste
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="datetime-local"
                                class="form-control"
                                id="ajusteData"
                                name="data_movimento"
                                required>

                        </div>


                        <div class="form-group col-md-12">

                            <label for="ajusteObservacoes">
                                Motivo / Observações
                                <span class="text-danger">*</span>
                            </label>

                            <textarea
                                class="form-control"
                                id="ajusteObservacoes"
                                name="observacoes"
                                rows="3"
                                maxlength="1000"
                                required
                                placeholder="Ex.: Diferença encontrada durante contagem física..."></textarea>

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
                                Contagem Física
                            </h5>

                        </div>

                        <div class="col-auto">

                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                id="btnAdicionarProdutoAjuste">
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
                            id="ajusteProdutosTable">

                            <thead>

                                <tr>
                                    <th style="min-width:280px;">
                                        Produto
                                    </th>

                                    <th style="width:120px;">
                                        Unidade
                                    </th>

                                    <th style="width:150px;">
                                        Stock Sistema
                                    </th>

                                    <th style="width:150px;">
                                        Stock Físico
                                    </th>

                                    <th style="width:150px;">
                                        Diferença
                                    </th>

                                    <th style="width:170px;">
                                        Custo Unitário
                                    </th>

                                    <th style="width:170px;">
                                        Impacto
                                    </th>

                                    <th style="width:60px;"></th>
                                </tr>

                            </thead>


                            <tbody id="ajusteProdutosBody"></tbody>


                            <tfoot>

                                <tr>

                                    <th colspan="6" class="text-right">
                                        Impacto Total
                                    </th>

                                    <th id="ajusteImpactoTotal">
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
                        id="btnLimparAjuste">
                        <i class="fa fa-eraser"></i>
                        Limpar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-warning"
                        id="btnGuardarAjuste">
                        <i class="fa fa-wrench"></i>
                        Registar Ajuste
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>