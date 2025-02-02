<?php

namespace App\Jobs;

use App\Models\LegacyInstitution;
use App\Models\LegacyUserType;
use App\Models\NotificationType;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckInstitutionConfigurationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var string
     */
    private $databaseConnection;

    /**
     * Create a new job instance.
     *
     * @param string $databaseConnection
     */
    public function __construct($databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
    }

    /**
     * Execute the job.
     *
     * @param NotificationService $notificationsService
     *
     * @throws Throwable
     *
     * @return void
     */
    public function handle(NotificationService $notificationsService)
    {
        DB::setDefaultConnection($this->databaseConnection);

        if (!$this->areFilledDateFields()) {
            return;
        }

        $text = 'Bem-vindo a ' . date('Y') . '! Lembre-se de conferir se as datas de troca de turma e deslocamento estão corretas no cadastro da Instituição (Escola > Cadastro > Instituição).';

        $notificationsService->createByUserLevel(LegacyUserType::LEVEL_INSTITUTIONAL, $text, null, NotificationType::OTHER);
    }

    private function areFilledDateFields()
    {
        $institution = app(LegacyInstitution::class);

        return !empty($institution->data_base_transferencia) || !empty($institution->data_base_remanejamento);
    }

    public function tags()
    {
        return [
            $this->databaseConnection,
            'check-institution-configurations',
        ];
    }
}
