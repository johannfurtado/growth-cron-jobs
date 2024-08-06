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
    public function handle(): void
    {
        Log::info("Handling CreateTasksAuvoJob for customer: {$this->customerId}");

        $client = Http::baseUrl(env('AUVO_API_URL'))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->retry(3, 100);

        $oficina = $this->getOficinaById($this->idOficina);

        if (!$oficina) {
            Log::error("Oficina with ID {$this->idOficina} not found.");
            return;
        }

        $validTaskType = 153068;
        $validQuestionnaireId = 178968;

        foreach ($this->colaboradores as $colaborador) {
            if (!isset($colaborador['id'])) {
                Log::error("Colaborador ID is not set.");
                continue;
            }

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

                    $taskData = [
                        'taskType' => $validTaskType,
                        'idUserFrom' => 163489,
                        'idUserTo' => $colaborador['id'],
                        'taskDate' => $taskDate,
                        'address' => $oficina['endereco'],
                        'orientation' => 'INSPEÇÃO DE QUALIDADE',
                        'priority' => 1,
                        'questionnaireId' => $validQuestionnaireId,
                        'customerExternalId' => "{$this->customerId}",
                        'checkinType' => 1,
                        'keyWords' => [1],
                    ];

                    Log::info("Sending task data: " . json_encode($taskData));

                    try {
                        $response = $client->put('tasks', $taskData);

                        Log::info("API response status: {$response->status()}");
                        Log::info("API response: " . $response);

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
}
