<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Dompdf\Dompdf;

class GeneratePdfAuvoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected $customer,
        protected array $taskData
    ) {
    }

    public function handle(): void
    {
        $pdfPath = $this->generatePdf($this->customer);

        $pdfBase64 = base64_encode(file_get_contents($pdfPath));
        $this->taskData['attachments'] = [
            [
                'name' => 'ordem_resumo_' . $this->customer->id . '.pdf',
                'file' => $pdfBase64,
            ],
        ];

        dispatch(new SendTaskDataAuvoJob($this->taskData, $this->customer, $pdfPath));
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
