<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Models\Task;

class CreateTasksAuvoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Collection $colaboradores;
    protected int $customerId;
    protected int $idOficina;
    protected string $accessToken;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Collection $colaboradores, int $customerId, int $idOficina, string $accessToken)
    {
        $this->colaboradores = $colaboradores;
        $this->customerId = $customerId;
        $this->idOficina = $idOficina;
        $this->accessToken = $accessToken;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(PendingRequest $client): void
    {
        Log::info("Handling CreateTasksAuvoJob for customer: {$this->customerId}");

        $client = $client->baseUrl(env('AUVO_API_URL'))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ]);

        $oficina = $this->getOficinaById($this->idOficina);

        if (!$oficina) {
            Log::error("Oficina with ID {$this->idOficina} not found.");
            return;
        }

        Log::info("Oficina found: " . json_encode($oficina));

        $validTaskType = 153068;
        $validQuestionnaireId = 178968;

        if (!$this->isCustomerIdValid($this->customerId, $client)) {
            Log::error("Customer ID {$this->customerId} not found. Additional action may be required.");
            return;
        }

        Log::info("Customer ID {$this->customerId} is valid.");

        foreach ($this->colaboradores as $colaborador) {
            if (!isset($colaborador['id'])) {
                Log::error("Colaborador ID is not set.");
                continue;
            }

            Log::info("Processing colaborador ID: {$colaborador['id']}");

            foreach ($oficina['diasSemana'] as $diaSemana) {
                $taskDate = $this->getNextDateByDayOfWeek($diaSemana, $oficina['horaInicio']);

                Log::info("Generated task date: {$taskDate} for diaSemana: {$diaSemana}");

                $existingTask = Task::where('auvo_id_task', $this->customerId)->first();

                if ($existingTask) {
                    Log::info("Task for customer {$this->customerId} already exists with ID {$existingTask->auvo_id_task}.");
                    continue;
                }

                $taskData = [
                    'taskType' => $validTaskType,
                    'idUserFrom' => 163489,
                    'idUserTo' => $colaborador['id'],
                    'taskDate' => $taskDate,
                    'address' => $oficina['endereco'],
                    'orientation' => 'INSPEÇÃO DE QUALIDADE',
                    'priority' => 1,
                    'questionnaireId' => $validQuestionnaireId,
                    'customerId' => $this->customerId,
                    'checkinType' => 1,
                    'keyWords' => [1],
                ];

                Log::info("Sending task data: " . json_encode($taskData));

                try {
                    $response = $client->put('tasks', $taskData);

                    Log::info("API response status: {$response->status()}");
                    Log::info("API response body: " . $response->body());

                    if (in_array($response->status(), [200, 201])) {
                        Log::info("Task created for customer {$this->customerId} on date: {$taskDate}");
                        $responseData = $response->json();
                        if (isset($responseData['result']['taskID'])) {
                            Log::info("Task ID: {$responseData['result']['taskID']}");
                            Task::create([
                                'auvo_id_task' => $responseData['result']['taskID'],
                            ]);
                        } else {
                            Log::error("Task ID not found in response: " . json_encode($responseData));
                        }
                    } else {
                        Log::error("Error creating task for customer {$this->customerId} on date: {$taskDate}: {$response->body()}");
                    }
                } catch (\Exception $e) {
                    Log::error("Exception creating task for customer {$this->customerId} on date: {$taskDate}: " . $e->getMessage());
                }
            }
        }
    }

    private function getOficinaById(int $idOficina): ?array
    {
        foreach ($this->colaboradores as $colaborador) {
            Log::info("Checking colaborador: " . json_encode($colaborador));
            if (isset($colaborador['ids_oficina'])) {
                foreach ($colaborador['ids_oficina'] as $oficina) {
                    Log::info("Checking oficina: " . json_encode($oficina));
                    if (isset($oficina['id']) && $oficina['id'] == $idOficina) {
                        return $oficina;
                    }
                }
            }
        }

        return null;
    }

    private function getNextDateByDayOfWeek(string $dayOfWeek, string $horaInicio): string
    {
        $date = new \DateTime("now", new \DateTimeZone('America/Sao_Paulo'));
        $date->modify("next $dayOfWeek");

        list($hours, $minutes) = explode(':', $horaInicio);
        $date->setTime((int)$hours, (int)$minutes);

        return $date->format('Y-m-d\TH:i:s');
    }

    private function isCustomerIdValid(int $customerId, PendingRequest $client): bool
    {
        try {
            $response = $client->get("customers/{$customerId}");
            Log::info("Customer validation request for ID {$customerId}");
            Log::info("Customer validation response status: {$response->status()}");
            Log::info("Customer validation response body: " . $response->body());
            return $response->status() === 200;
        } catch (\Exception $e) {
            Log::error("Error verifying customer ID {$customerId}: {$e->getMessage()}");
            return false;
        }
    }
}
