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

class CreateTasksAuvoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Collection $colaboradores;
    protected int $customerId;
    protected int $idOficina;

    public function __construct(Collection $colaboradores, int $customerId, int $idOficina)
    {
        $this->colaboradores = $colaboradores;
        $this->customerId = $customerId;
        $this->idOficina = $idOficina;
    }

    public function handle(PendingRequest $client): void
    {
        $diasDaSemana = [
            'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'
        ];

        foreach ($this->colaboradores as $colaborador) {
            foreach ($colaborador['ids_oficina'] as $oficina) {
                if ($oficina['id'] === $this->idOficina) {
                    $startDate = $oficina['dataContrato'] ?? now();

                    $this->createTasksUntilDate($client, $colaborador, $oficina, $startDate, $diasDaSemana);
                }
            }
        }
    }

    private function taskExists(PendingRequest $client, int $userId, string $day): bool
    {
        $response = $client->get("tasks", [
            'customerId' => $this->customerId,
            'idUserTo' => $userId,
            'taskDate' => now()->next($day)->format('Y-m-d')
        ]);

        return $response->status() === 200 && !empty($response->json());
    }

    private function createTask(PendingRequest $client, array $colaborador, array $oficina, string $day): void
    {
        $response = $client->post(
            'tasks',
            [
                'taskType' => 1,
                'idUserFrom' => 163489, // ID do usuário que está criando a tarefa (Thais)
                'idUserTo' => $colaborador['id'],
                'taskDate' => now()->next($day)->format('Y-m-d\TH:i:s'),
                'address' => $oficina['endereco'],
                'priority' => 1,
                'questionnaireId' => 3,
                'customerId' => $this->customerId,
                'checkinType' => 1,
                'keyWords' => [1]
            ]
        );

        if (!in_array($response->status(), [200, 201])) {
            Log::error("Error creating task for {$colaborador['nome']} on {$day}: {$response->body()}");
        }
    }

    private function createTasksUntilDate(PendingRequest $client, array $colaborador, array $oficina, string $startDate, array $diasDaSemana): void
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end = $start->copy()->addDays(60);

        while ($start->lessThanOrEqualTo($end)) {
            if (in_array($start->format('l'), $diasDaSemana)) {
                $this->createTask($client, $colaborador, $oficina, $start->format('l'));
            }
            $start->addDay(); // Avançar para o próximo dia
        }
    }
}
