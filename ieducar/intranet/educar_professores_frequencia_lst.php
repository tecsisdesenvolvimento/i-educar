<?php

return new class extends clsListagem {
    /**
     * Referencia pega da session para o idpes do usuario atual
     *
     * @var int
     */
    public $pessoa_logada;

    /**
     * Titulo no topo da pagina
     *
     * @var int
     */
    public $titulo;

    /**
     * Quantidade de registros a ser apresentada em cada pagina
     *
     * @var int
     */
    public $limite;

    /**
     * Inicio dos registros a serem exibidos (limit)
     *
     * @var int
     */
    public $offset;

    public $data_inicial;
    public $data_final;

    public $etapa;
    public $fase_etapa;

    public function Gerar()
    {
        $this->titulo = 'Frequência - Listagem';

        foreach ($_GET as $var => $val) { // passa todos os valores obtidos no GET para atributos do objeto
            $this->$var = ($val === '') ? null: $val;
        }

        $this->funcAcaoNome = "ComValidacaoCampos";

        $lista_busca = [
            'Data',
            'Turma',
            'Turno',
            'S&eacute;rie',
            'Escola',
            'Etapa',
            'Componente curricular',
            'Aulas',
            'Professor'
        ];

        $this->addCabecalhos($lista_busca);

        if (!isset($_GET['busca'])) {
            $this->ano = date('Y');
        }

        $this->inputsHelper()->dynamic(['ano'], ['required' => false]);
        $this->inputsHelper()->dynamic(['instituicao', 'escola', 'curso', 'serie', 'turma'], ['required' => true]);
        $this->inputsHelper()->turmaTurno(['required' => false, 'label' => 'Turno']);
        $this->inputsHelper()->dynamic('componenteCurricular', ['required' => false]);
        $this->inputsHelper()->dynamic('professorComponente', ['required' => false]);


        $this->campoQuebra();
        $this->campoRotulo('filtros_periodo', '<b>Filtros por período</b>');

        $this->inputsHelper()->dynamic(['dataInicial'], ['required' => false, 'value' => $this->data_inicial]);
        $this->inputsHelper()->dynamic(['dataFinal'], ['required' => false, 'value' => $this->data_final]);

        $this->campoQuebra();
        $this->campoRotulo('filtros_etapa', '<b>Filtros por etapa</b>');

        $this->inputsHelper()->dynamic(['faseEtapa'], ['required' => false, 'label' => 'Etapa']);

        // Paginador
        $this->limite = 20;
        $this->offset = ($_GET["pagina_{$this->nome}"]) ? $_GET["pagina_{$this->nome}"]*$this->limite-$this->limite: 0;

        $obj_turma = new clsModulesFrequencia();
        $obj_turma->setOrderby('data DESC');
        $obj_turma->setLimite($this->limite, $this->offset);

        if ($this->data_inicial && Portabilis_Date_Utils::validaData($this->data_inicial) || !$this->data_inicial) {
            $this->data_inicial = dataToBanco($this->data_inicial);
        } else {
            $temp_data_inicial = new DateTime('now');
            $this->data_inicial = dataToBanco($temp_data_inicial->format('d/m/Y'));
        }

        if ($this->data_final && Portabilis_Date_Utils::validaData($this->data_final) || !$this->data_final) {
            $this->data_final = dataToBanco($this->data_final);
        } else {
            $temp_data_final = new DateTime('now');
            $this->data_final = dataToBanco($temp_data_final->format('d/m/Y'));
        }

        $obj_servidor = new clsPmieducarServidor(
            $this->pessoa_logada,
            null,
            null,
            null,
            null,
            null,
            1,      //  Ativo
            1,      //  Fixado na instituição de ID 1
        );

        $eh_professor = $obj_servidor->isProfessor();

        $escolasUsuario = [];
        $escolasUser = App_Model_IedFinder::getEscolasUser($this->pessoa_logada);

        foreach ($escolasUser as $e) {
            $escolasUsuario[] = $e['ref_cod_escola'];
        }

        $servidor_id = null;

        if ($eh_professor) {
            $servidor_id = $this->pessoa_logada;
        }

        if (!empty($this->ref_cod_professor_componente)) {
            $servidor_id = $this->ref_cod_professor_componente;
        }

        $lista = $obj_turma->lista(
            $this->ano,
            $this->ref_cod_instituicao,
            $this->ref_cod_escola,
            $this->ref_cod_curso,
            $this->ref_cod_serie,
            $this->ref_cod_turma,
            $this->ref_cod_componente_curricular,
            $this->turma_turno_id,
            $this->data_inicial,
            $this->data_final,
            $this->fase_etapa,
            $servidor_id,
            empty($this->ref_cod_escola) ? $escolasUsuario : null
        );

        $total = $obj_turma->_total;
        $totalRemover = 0;

        // monta a lista
        if (is_array($lista) && count($lista)) {
            foreach ($lista as $registro) {
                $data_formatada = dataToBrasil($registro['data']);

                $quadroHorario = Portabilis_Business_Professor::quadroHorarioAlocado($registro['ref_cod_turma'], $this->pessoa_logada, null, true);

                if ($eh_professor && count($quadroHorario) > 0 && $registro['professor_turma'] != $registro['professor_registro']) {
                    $totalRemover++;
                    continue;
                }

                if (!empty($registro['professor_registro']) && $registro['professor_turma'] != $registro['professor_registro'] && $registro['professor_registro'] != 'Administrador' && $registro['professor_registro'] != 'Coordenador') {
                    $totalRemover++;
                    continue;
                }

                $lista_busca = [
                    "<a href=\"educar_professores_frequencia_det.php?id={$registro['id']}\">{$data_formatada}</a>",
                    "<a href=\"educar_professores_frequencia_det.php?id={$registro['id']}\">{$registro['turma']}</a>",
                    "<a href=\"educar_professores_frequencia_det.php?id={$registro['id']}\">{$registro['turno']}</a>",
                    "<a href=\"educar_professores_frequencia_det.php?id={$registro['id']}\">{$registro['serie']}</a>",
                    "<a href=\"educar_professores_frequencia_det.php?id={$registro['id']}\">{$registro['escola']}</a>",
                    "<a href=\"educar_professores_frequencia_det.php?id={$registro['id']}\">{$registro['fase_etapa']}º {$registro['etapa']}</a>"
                ];

                if ($registro['componente_curricular']) {
                    $lista_busca[] = "<a href=\"educar_professores_frequencia_det.php?id={$registro['id']}\">{$registro['componente_curricular']}</a>";
                } else {
                    $lista_busca[] = "<a href=\"educar_professores_frequencia_det.php?id={$registro['id']}\">—</a>";
                }

                if ($registro['ordens_aulas']) {
                    $lista_busca[] = "<a href=\"educar_professores_frequencia_det.php?id={$registro['id']}\">{$registro['ordens_aulas']}</a>";
                } else {
                    $lista_busca[] = "<a href=\"educar_professores_frequencia_det.php?id={$registro['id']}\">—</a>";
                }

                if (!empty($registro['cod_professor_registro'])) {
                    $lista_busca[] = "<a href=\"educar_professores_frequencia_det.php?id={$registro['id']}\">{$registro['professor_registro']}</a>";
                } else {
                    $lista_busca[] = "<a href=\"educar_professores_frequencia_det.php?id={$registro['id']}\">{$registro['professor_turma']}</a>";
                }


                $this->addLinhas($lista_busca);
            }
        }

        $totalRegistrosDuplicidade = 0;
        if ($totalRemover > 0) {
            //TODO: Condição adicionada para resolução imediata do bug de total registros
            // esse bug ocorre devido as condições do laço acima, como, os registros aparecem "duplicados"
            // só alterando o professor e nós não podemos exibir isso em alguns casos, temos que fazer a dedução
            // desse registro do total de registros.

            $totalPaginas = ceil($total / $this->limite);
            $totalRegistrosDuplicidade = $total - ($totalRemover * $totalPaginas);
        }


        $this->addPaginador2('educar_professores_frequencia_lst.php', $total, $_GET, $this->nome, $this->limite, 3, false, 0, false, $totalRegistrosDuplicidade);
        $obj_permissoes = new clsPermissoes();
        if ($obj_permissoes->permissao_cadastra(58, $this->pessoa_logada, 7)) {
            $this->acao = 'go("educar_professores_frequencia_cad.php")';
            $this->nome_acao = 'Novo';
        }
        $this->largura = '100%';

        $this->breadcrumb('Listagem de frequências', [
            url('intranet/educar_professores_index.php') => 'Professores',
        ]);
    }

    public function Formular()
    {
        $this->title = 'Frequência - Listagem';
        $this->processoAp = '58';
    }
};
