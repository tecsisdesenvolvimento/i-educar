<?php
use iEducar\Legacy\Model;
use App\Models\TurmaCardapio;

class clsModulesTurmaCardapio extends Model
{
    public $id;
    public $unidade;
    public $descricao;

    public function __construct(
        $id = null,
        $descricao = null
    ) {
        $db = new clsBanco();
        $this->_schema = 'pmieducar.';
        $this->_tabela = "{$this->_schema}merenda_turma";

        $this->_from = "
            pmieducar.merenda_turma as merenda_turma
        ";

        $this->_campos_lista = $this->_todos_campos = '
            merenda_turma.id,
            merenda_turma.descricao
         
        ';

        
        if (is_numeric($id)) {
            $this->id = $id;
        }

      

        if (is_string($descricao)) {
            $this->descricao = $descricao;
        }
    }

   
   

    /**
     * Retorna um array com os dados de um registro
     *
     * @return array
     */
    public function detalhe()
    {
        $data = [];

        if (is_numeric($this->id)) {
            $db = new clsBanco();
            $db->Consulta("
                SELECT
                    {$this->_todos_campos}
                FROM
                    {$this->_from}
                WHERE
                    turma.id = {$this->id}
            ");

            $db->ProximoRegistro();
            $data = $db->Tupla();

            return $data;
        }

        return false;
    }


    /**
     * Retorna uma lista filtrados de acordo com os parametros
     *
     * @return array
     */
    public function lista(
        $int_frequencia = null,
        $int_campo_experiencia = null
    ) {
        if (!is_numeric($int_frequencia))
            return false;

        $sql = "
            WITH select_ as (
                SELECT
                    {$this->_campos_lista}
                FROM
                    {$this->_from}
            )
                SELECT
                    bncc.id,
                    codigo,
                    habilidade,
                    campo_experiencia,
                    unidade_tematica,
                    componente_curricular_id,
                    bncc.serie_id
                FROM
                    pmieducar.frequencia as f
                JOIN pmieducar.turma as t
                    ON (t.cod_turma = f.ref_cod_turma)
                JOIN pmieducar.componente_curricular as cc
                    ON (cc.id = f.ref_componente_curricular OR f.ref_componente_curricular IS NULL)
                JOIN select_ as bncc
                    ON (bncc.serie_id = t.etapa_educacenso
                AND (bncc.componente_curricular_id = cc.codigo_educacenso
                OR bncc.componente_curricular_id IS NULL))
                WHERE f.id = '{$int_frequencia}'
        ";

        $whereAnd = ' AND ';
        $filtros = "";

        if (is_numeric($int_campo_experiencia)) {
            $filtros .= "{$whereAnd} bncc.campo_experiencia = '{$int_campo_experiencia}'";
            $whereAnd = ' AND ';
        }

        $db = new clsBanco();
        $countCampos = count(explode(',', $this->_campos_lista));
        $resultado = [];

        $sql .= $filtros . $this->getOrderby() . $this->getLimite();

        $this->_total = $db->CampoUnico("
            WITH select_ as (
                SELECT
                    {$this->_campos_lista}
                FROM
                    {$this->_from}
            )
                SELECT
                    COUNT(0)
                FROM
                    pmieducar.frequencia as f
                JOIN pmieducar.turma as t
                    ON (t.cod_turma = f.ref_cod_turma)
                JOIN pmieducar.componente_curricular as cc
                    ON (cc.id = f.ref_componente_curricular OR f.ref_componente_curricular IS NULL)
                JOIN select_ as bncc
                    ON (bncc.serie_id = t.etapa_educacenso
                AND (bncc.componente_curricular_id = cc.codigo_educacenso
                OR bncc.componente_curricular_id IS NULL))
                WHERE f.id = '{$int_frequencia}'
                {$filtros}
        ");

        $db->Consulta($sql);

        if ($countCampos > 1) {
            while ($db->ProximoRegistro()) {
                $tupla = $db->Tupla();

                $tupla['_total'] = $this->_total;
                $resultado[] = $tupla;
            }
        } else {
            while ($db->ProximoRegistro()) {
                $tupla = $db->Tupla();
                $resultado[] = $tupla[$this->_campos_lista];
            }
        }
        if (count($resultado)) {
            return $resultado;
        }

        return false;
    }



    public function lista_turmas(  $ref_cod_curso = null,  $ano = null,  $turno_id = null)
    {
        $condicional = "";
        $contador_condicional =0;
        if(is_numeric( $ref_cod_curso)){
            $condicional .= " WHERE ref_cod_curso = ".$ref_cod_curso." ";
            $contador_condicional++;
        }
        if(is_numeric( $ano)){
            if($contador_condicional>0){
                $condicional .= " AND ano = ".$ano." ";
            }else{

                $condicional .= " WHERE ano = ".$ano." ";
            }
            $contador_condicional++;
        }
        if(is_numeric( $turno_id)){
            if($contador_condicional>0){
                $condicional .= " AND turma_turno_id = ".$turno_id." ";
            }else{

                $condicional .= " WHERE turma_turno_id = ".$turno_id." ";
            }
            $contador_condicional++;
        }
       
        
    
        
        
        $sql = "SELECT * FROM pmieducar.turma ".$condicional;
        $db = new clsBanco();
        $countCampos = count(explode(',', $this->_campos_lista));
        $resultado = [];
        $filtros = '  ';

        $sql .= $filtros . $this->getOrderby() . $this->getLimite();

        $this->_total = $db->CampoUnico("SELECT COUNT(0) FROM pmieducar.turma".$condicional);

        $db->Consulta($sql);

       
            while ($db->ProximoRegistro()) {
                $tupla = $db->Tupla();

                $tupla['_total'] = $this->_total;
                $resultado[] = $tupla;
            }
       
        if (count($resultado)) {
            return $resultado;
        }

        return false;
    }

    
  
}
