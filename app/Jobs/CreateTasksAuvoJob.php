<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateTasksAuvoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $colaboradores;
    protected int $customerId;
    protected int $idOficina;

    /**
     * Create a new job instance.
     *
     * @param array $colaboradores
     * @param int $customerId
     * @param int $idOficina
     */
    public function __construct(array $colaboradores, int $customerId, int $idOficina)
    {
        $this->colaboradores = $colaboradores;
        $this->customerId = $customerId;
        $this->idOficina = $idOficina;
    }

    /**
     * Execute the job.
     *
     * @param PendingRequest $client
     * @return void
     */
    public function handle(PendingRequest $client): void
    {
        $diasDaSemana = [
            'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'
        ];

        foreach ($this->colaboradores as $colaborador) {
            foreach ($colaborador['ids_oficina'] as $oficina) {
                // Verifica se o ID da oficina corresponde ao ID fornecido
                if ($oficina['id'] === $this->idOficina) {
                    foreach ($diasDaSemana as $day) {
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
                }
            }
        }
    }
}
