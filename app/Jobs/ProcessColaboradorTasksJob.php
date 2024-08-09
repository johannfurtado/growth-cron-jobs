<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Task;

class ProcessColaboradorTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected $colaborador,
        protected $customer,
        protected array $oficina,
        protected string $accessToken
    ) {
    }

    public function handle(): void
    {
        $taskCount = 1; // Iniciar o contador

        for ($i = 0; $i < 60; $i++) {
            $currentDate = new \DateTime("now", new \DateTimeZone('America/Sao_Paulo'));
            $currentDate->modify("+{$i} days");
            $dayOfWeek = $currentDate->format('l');

            if (in_array($dayOfWeek, $this->oficina['diasSemana'])) {
                $taskDate = $this->getDateTimeForDay($currentDate, $this->oficina['horaInicio']);
                $existingTask = Task::where('auvo_id_task', $this->customer->id)->first();

                if ($existingTask) {
                    Log::info("Task for customer {$this->customer->id} already exists with ID {$existingTask->auvo_id_task}.");
                    continue;
                }

                $taskData = $this->buildTaskData($this->colaborador['id'], $taskDate, $this->oficina['endereco'], 153103, 173499, $taskCount);

                dispatch(new GeneratePdfAuvoJob($this->customer, $taskData, $this->accessToken));

                $taskCount++;
            }
        }
    }

    private function buildTaskData($colaboradorId, $taskDate, $address, $taskType, $questionnaireId, $taskCount)
    {
        // Gerar o externalId combinando customerId e contador
        $externalId = "{$this->customer->id}_{$taskCount}";

        return [
            'externalId' => $externalId,
            'taskType' => $taskType,
            'idUserFrom' => 163489,
            'idUserTo' => $colaboradorId,
            'taskDate' => $taskDate,
            'address' => $address,
            'orientation' => $this->customer->orientation,
            'priority' => 3,
            'questionnaireId' => $questionnaireId,
            'customerExternalId' => $externalId,
            'checkinType' => 1
        ];
    }

    private function getDateTimeForDay(\DateTime $date, string $horaInicio): string
    {
        list($hours, $minutes) = explode(':', $horaInicio);
        $date->setTime((int)$hours, (int)$minutes);
        return $date->format('Y-m-d\TH:i:s');
    }
}
