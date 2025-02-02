<?php

use Illuminate\Support\Carbon;

class FrequenciaController extends ApiCoreController
{
    protected function getTipoPresenca()
    {
        $id = $this->getRequest()->id;

        if (is_numeric($id)) {
            $turma = new clsPmieducarTurma();
            $turma->cod_turma = $id;
            $turma = $turma->detalhe();

            foreach ($turma as $k => $v) {
                if (is_numeric($k)) {
                    unset($turma[$k]);
                }
            }

            if (isset($turma) && !empty($turma)) {
                $db = new clsBanco();
                $sql = "
                        SELECT
                            r.tipo_presenca
                        FROM
                            modules.regra_avaliacao_serie_ano s
                        JOIN modules.regra_avaliacao r
                            ON (s.regra_avaliacao_id = r.id)
                        WHERE s.serie_id = {$turma['ref_ref_cod_serie']}
               ";

                $db->Consulta($sql);

                $db->ProximoRegistro();
                return ['tipo_presenca' => $db->Campo('tipo_presenca')];
            }

            return [];
        }

        return [];
    }

    protected function getQtdAulasQuadroHorario()
    {
        $turmaId = $this->getRequest()->id;
        $qtdFaltas = $this->getRequest()->qtdFaltas;
        $dataFrequencia = $this->getRequest()->data;
        $userId = \Illuminate\Support\Facades\Auth::id();

        if (is_numeric($turmaId)) {
            $clsInstituicao = new clsPmieducarInstituicao();
            $instituicao = $clsInstituicao->primeiraAtiva();

            $utilizaSabadoAlternado = $instituicao['utiliza_sabado_alternado'];
            $checaQtdAulasQuadroHorario = $instituicao['checa_qtd_aulas_quadro_horario'];

            $isOnlyProfessor = Portabilis_Business_Professor::isOnlyProfessor($instituicao['cod_instituicao'], $userId);
            $diaSemana =  Carbon::createFromFormat('d/m/Y', $dataFrequencia)->dayOfWeek;

            $qtdAulas = 0;

            if ($isOnlyProfessor && !$utilizaSabadoAlternado && $checaQtdAulasQuadroHorario) {
                $diaSemanaConvertido = $this->converterDiaSemanaQuadroHorario($diaSemana);

                $quadroHorario = Portabilis_Business_Professor::quadroHorarioAlocado($turmaId, $userId, $diaSemanaConvertido);

                if (count($quadroHorario) > 0) {
                    foreach ($quadroHorario as $horario) {
                        $qtdAulas += (!empty($horario['qtd_aulas']) ? $horario['qtd_aulas'] : 1);
                    }
                }
            }

            $qtdAulasPresente = 0;
            $faltouDia = false;
            $verificaAulas = $qtdFaltas > 0 && $qtdAulas > 0;

            if ($verificaAulas) {
                $qtdAulasPresente = $qtdAulas - $qtdFaltas;

                if ($qtdAulasPresente <= 0) {
                    $qtdAulasPresente = 0;
                    $faltouDia = true;
                }
            }

            return ['isProfessor' => $isOnlyProfessor,
                    'qtdAulas' => $qtdAulasPresente,
                    'faltouDia' => $faltouDia];
        }

        return [];
    }

    protected function getRegistroDiarioQuadroHorario()
    {
        $turmaId = $this->getRequest()->id;
        $dataFrequencia = $this->getRequest()->data;
        $userId = \Illuminate\Support\Facades\Auth::id();

        if (is_numeric($turmaId)) {
            $clsInstituicao = new clsPmieducarInstituicao();
            $instituicao = $clsInstituicao->primeiraAtiva();

            $isOnlyProfessor = Portabilis_Business_Professor::isOnlyProfessor($instituicao['cod_instituicao'], $userId);

            if ($isOnlyProfessor) {
                $componentesCurriculares = [];
                $registraDiarioIndividual = false;

                $quadroHorario = Portabilis_Business_Professor::quadroHorarioAlocado($turmaId, $userId, null, true);

                if (count($quadroHorario) > 0) {
                    $registraDiarioIndividual = true;
                    foreach ($quadroHorario as $horario) {
                        $componentesCurriculares[] = $horario['ref_cod_disciplina'];
                    }
                }

                return ['isProfessor' => true,
                        'registraDiarioIndividual' => $registraDiarioIndividual,
                        'componentesCurriculares' => $componentesCurriculares];
            } else {
                return ['isProfessor' => false,
                        'registraDiarioIndividual' => false]; //admin/coordenador
            }
        }

        return [];
    }

    protected function converterDiaSemanaQuadroHorario(int $diaSemana)
    {
        $arrDiasSemanaIeducar = [
            0 => 1,
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 5,
            5 => 6,
            6 => 7,
        ];

      return $arrDiasSemanaIeducar[$diaSemana];
    }

    public function Gerar()
    {
        if ($this->isRequestFor('get', 'getTipoPresenca')) {
            $this->appendResponse($this->getTipoPresenca());
        } else if ($this->isRequestFor('get', 'getQtdAulasQuadroHorario')) {
            $this->appendResponse($this->getQtdAulasQuadroHorario());
        } else if ($this->isRequestFor('get', 'getRegistroDiarioQuadroHorario')) {
            $this->appendResponse($this->getRegistroDiarioQuadroHorario());
        }
    }
}
