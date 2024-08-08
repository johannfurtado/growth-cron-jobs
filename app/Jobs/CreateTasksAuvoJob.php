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
use Dompdf\Dompdf;

class CreateTasksAuvoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(
        protected Collection $colaboradores,
        protected $customer,
        protected int $idOficina,
        protected string $accessToken,
    ) {
    }

    public function handle(): void
    {
        Log::info("Handling CreateTasksAuvoJob for customer: {$this->customer->id}");

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
                $existingTask = Task::where('auvo_id_task', $this->customer->id)->first();

                if ($existingTask) {
                    Log::info("Task for customer {$this->customer->id} already exists with ID {$existingTask->auvo_id_task}.");
                    continue;
                }

                $taskData = $this->buildTaskData($colaborador['id'], $taskDate, $oficina['endereco'], $validTaskType, $validQuestionnaireId);

                // Gerar o PDF
                $pdfPath = $this->generatePdf($this->customer);

                // Codificar o PDF em base64
                $pdfBase64 = base64_encode(file_get_contents($pdfPath));

                // Adicionar o PDF aos dados da tarefa
                $taskData['attachments'] = [
                    [
                        'name' => 'ordem_resumo_' . $this->customer->id . '.pdf',
                        'file' => $pdfBase64,
                    ],
                ];

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
            'orientation' => $this->customer->orientation,
            'priority' => 3,
            'questionnaireId' => $questionnaireId,
            'customerExternalId' => "{$this->customer->id}",
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
                Log::info("Task created for customer {$this->customer->id} on date: {$taskDate}");
                $this->processSuccessfulResponse($response);
            } else {
                Log::error("Error creating task for customer {$this->customer->id} on date: {$taskDate}: {$response->body()}");
            }
        } catch (\Exception $e) {
            Log::error("Exception creating task for customer {$this->customer->id} on date: {$taskDate}: " . $e->getMessage());
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

    private function generatePdf($customer): string
    {
        $dompdf = new Dompdf();
        $orderItems = json_decode($customer->order_items, true);
        $orderSummary = json_decode($customer->order_summary, true);

        $html = '
        <h3>Resumo do pedido: ' . $customer->id_order . '</h3>
        <h3>' . $customer->orientation . '</h3>
        <table border="1" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Quantidade</th>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Desconto</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($orderItems as $item) {
            $html .= '
                <tr>
                    <td>' . $item['quantidade'] . '</td>
                    <td>' . $item['descricao'] . '</td>
                    <td>R$ ' . $item['valor'] . '</td>
                    <td>R$ ' . $item['desconto'] . '</td>
                </tr>';
        }

        $html .= '
            </tbody>
        </table>
        <br><br>
        <h3>Valores a cobrar</h3>
        <table border="1" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td>Subtotal</td>
                <td>R$ ' . $orderSummary['subtotal'] . '</td>
            </tr>
            <tr>
                <td>Valor da Mão de Obra</td>
                <td>R$ ' . $orderSummary['valor_maoobra'] . '</td>
            </tr>
            <tr>
                <td>Desconto da Oficina</td>
                <td style="color: red;">R$ -' . $orderSummary['valor_desconto'] . '</td>
            </tr>
            <tr>
                <td>Desconto dos Itens</td>
                <td style="color: red;">R$ -' . $orderSummary['valor_desconto_itens'] . '</td>
            </tr>
            <tr>
                <td>Desconto na Negociação</td>
                <td style="color: red;">R$ -' . $orderSummary['valor_desconto_negociacao'] . '</td>
            </tr>
            <tr>
                <td>Ajuda Participativa</td>
                <td style="color: red;">R$ -' . $orderSummary['ajuda_participativa'] . '</td>
            </tr>
            <tr>
                <td><strong>Valor Total</strong></td>
                <td><strong>R$ ' . $orderSummary['valor_total'] . '</strong></td>
            </tr>
        </table>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();
        $pdfPath = storage_path('app/public/order_' . $customer->id . '.pdf');
        file_put_contents($pdfPath, $output);

        return $pdfPath;
    }
}
