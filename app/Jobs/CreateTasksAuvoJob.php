<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Models\Task;
use Illuminate\Support\Facades\Http;

class CreateTasksAuvoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Collection $colaboradores;
    protected int $customerId;
    protected int $idOficina;
    protected string $accessToken;
    protected string $orientation;

    public function __construct(Collection $colaboradores, int $customerId, int $idOficina, string $accessToken, string $orientation)
    {
        $this->colaboradores = $colaboradores;
        $this->customerId = $customerId;
        $this->idOficina = $idOficina;
        $this->accessToken = $accessToken;
        $this->orientation = $orientation;
    }

    public function handle(): void
    {
        Log::info("Handling CreateTasksAuvoJob for customer: {$this->customerId}");

        $client = $this->configureHttpClient();

        $oficina = $this->getOficinaById($this->idOficina);
        if (!$oficina) {
            Log::error("Oficina with ID {$this->idOficina} not found.");
            return;
        }

        $validTaskType = 153103;
        $validQuestionnaireId = 173499;

        foreach ($this->colaboradores as $colaborador) {
            if (!isset($colaborador['id'])) {
                Log::error("Colaborador ID is not set.");
                continue;
            }

            // Verificar se o colaborador é responsável pela oficina específica
            if (!$this->isColaboradorResponsavelPelaOficina($colaborador, $this->idOficina)) {
                Log::info("Colaborador {$colaborador['id']} não é responsável pela oficina {$this->idOficina}.");
                continue;
            }

            $this->createTasksForColaborador($client, $colaborador, $oficina, $validTaskType, $validQuestionnaireId);
        }
    }

    private function configureHttpClient()
    {
        return Http::baseUrl(env('AUVO_API_URL'))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->retry(3, 100);
    }

    private function createTasksForColaborador($client, $colaborador, $oficina, $validTaskType, $validQuestionnaireId)
    {
        for ($i = 0; $i < 60; $i++) {
            $currentDate = new \DateTime("now", new \DateTimeZone('America/Sao_Paulo'));
            $currentDate->modify("+{$i} days");
            $dayOfWeek = $currentDate->format('l');

            if (in_array($dayOfWeek, $oficina['diasSemana'])) {
                $taskDate = $this->getDateTimeForDay($currentDate, $oficina['horaInicio']);
                $existingTask = Task::where('auvo_id_task', $this->customerId)->first();

                if ($existingTask) {
                    Log::info("Task for customer {$this->customerId} already exists with ID {$existingTask->auvo_id_task}.");
                    continue;
                }

                $taskData = $this->buildTaskData($colaborador['id'], $taskDate, $oficina['endereco'], $validTaskType, $validQuestionnaireId);

                $this->sendTaskData($client, $taskData, $taskDate);
            }
        }
    }

    private function buildTaskData($colaboradorId, $taskDate, $address, $taskType, $questionnaireId)
    {
        return [
            'taskType' => $taskType,
            'idUserFrom' => 163489,
            'idUserTo' => $colaboradorId,
            'taskDate' => $taskDate,
            'address' => $address,
            'orientation' => $this->orientation,
            'priority' => 3,
            'questionnaireId' => $questionnaireId,
            'customerExternalId' => "{$this->customerId}",
            'checkinType' => 1
        ];
    }

    private function sendTaskData($client, $taskData, $taskDate)
    {
        Log::info("Sending task data: " . json_encode($taskData));

        try {
            $response = $client->put('tasks', $taskData);
            Log::info("API response status: {$response->status()}");

            if (in_array($response->status(), [200, 201])) {
                Log::info("Task created for customer {$this->customerId} on date: {$taskDate}");
                $this->processSuccessfulResponse($response);
            } else {
                Log::error("Error creating task for customer {$this->customerId} on date: {$taskDate}: {$response->body()}");
            }
        } catch (\Exception $e) {
            Log::error("Exception creating task for customer {$this->customerId} on date: {$taskDate}: " . $e->getMessage());
        }
    }

    private function processSuccessfulResponse($response)
    {
        $responseData = $response->json();
        if (isset($responseData['result']['taskID'])) {
            Log::info("Task ID: {$responseData['result']['taskID']}");
            Task::create([
                'auvo_id_task' => $responseData['result']['taskID'],
            ]);
        } else {
            Log::error("Task ID not found in response: " . json_encode($responseData));
        }
    }

    private function getOficinaById(int $idOficina): ?array
    {
        foreach ($this->colaboradores as $colaborador) {
            if (isset($colaborador['ids_oficina'])) {
                foreach ($colaborador['ids_oficina'] as $oficina) {
                    if (isset($oficina['id']) && $oficina['id'] == $idOficina) {
                        return $oficina;
                    }
                }
            }
        }

        return null;
    }

    private function getDateTimeForDay(\DateTime $date, string $horaInicio): string
    {
        list($hours, $minutes) = explode(':', $horaInicio);
        $date->setTime((int)$hours, (int)$minutes);

        return $date->format('Y-m-d\TH:i:s');
    }

    private function isColaboradorResponsavelPelaOficina($colaborador, $idOficina): bool
    {
        if (isset($colaborador['ids_oficina'])) {
            foreach ($colaborador['ids_oficina'] as $oficina) {
                if (isset($oficina['id']) && $oficina['id'] == $idOficina) {
                    return true;
                }
            }
        }
        return false;
    }
}
