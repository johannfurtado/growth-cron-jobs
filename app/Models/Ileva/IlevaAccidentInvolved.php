<?php

namespace App\Models\Ileva;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class IlevaAccidentInvolved extends Model
{
    use HasFactory;

    protected $connection = 'ileva';
    protected $table = 'hbrd_adm_sinister_participant';
    protected $guarded = [];
    public static function getAccidentInvolvedForAuvoToSolidy(string $databaseConnection): array
    {
        return DB::connection($databaseConnection)
            ->select("
              SELECT DISTINCT
    par.id,
    CONCAT(par.id, ' / ', par.nome, ' / ', par.placa) AS `name`,
    status.id_pai,
    tipe.id_participant,
    (
        SELECT haso_inner.id
        FROM hbrd_adm_sinister_order haso_inner
        WHERE haso_inner.id_participant = par.id
          AND (haso_inner.id_tipo = 1 OR haso_inner.id_tipo = 13)
        ORDER BY FIELD(haso_inner.id_tipo, 1, 13)
        LIMIT 1
    ) AS id_order,
    DATE_FORMAT(status.create_at, '%d/%m/%y') AS dataContrato,
    CONCAT(DATE_FORMAT(status.create_at, '%d/%m/%y'), ' ', DATEDIFF(NOW(), status.create_at), ' dia(s)') AS note,
    par.nome,
    par.placa,
    par.cpf_cnpj AS cpfCnpj,
    has.id AS id_oficina,
    CONCAT(IFNULL(has.nome, ''), ' / ', IFNULL(has.endereco, ''), ' / ', IFNULL(city.cidade, ''), ' - ', IFNULL(state.uf, '')) AS address,
    CONCAT(IFNULL(has.nome, ''), ' / Placa: ', IFNULL(par.placa, ''), ' / Veículo: ', IFNULL(par.modelo_veiculo, '')) AS orientation,
    par.telefone AS phone,
    par.email,
    par.id_sinister,
    status.id_status,
    tipe.id_tipo,
    par.status,
    status.create_at AS dt_criacao,
    par.create_at AS data_criacao,
    has.endereco,
    city.cidade,
    state.uf,
    COALESCE(
        (
            SELECT MIN(status_history.create_at)
            FROM hbrd_adm_sinister_participant_status_history status_history
            WHERE status_history.create_at > status.create_at
              AND status.id_pai = status_history.id_pai
        ),
        status.leave_at
    ) AS data_da_proxima_etapa,
    status.leave_at,
    s.nome,
    city.cidade AS cidade_associado,
    state.uf AS estado_associado,
    (
        SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'quantidade', oi.quantidade,
                'descricao', oi.descricao,
                'valor', oi.valor,
                'desconto', oi.desconto,
                'observacao', oi.observacao,
                'troca', oi.troca,
                'lanternagem', oi.lanternagemn,
                'pintura', oi.pintura
            )
        )
        FROM hbrd_adm_sinister_order_item oi
        WHERE oi.id_order = (
            SELECT haso_inner.id
            FROM hbrd_adm_sinister_order haso_inner
            WHERE haso_inner.id_participant = par.id
              AND (haso_inner.id_tipo = 1 OR haso_inner.id_tipo = 13)
            ORDER BY FIELD(haso_inner.id_tipo, 1, 13)
            LIMIT 1
        )
    ) AS order_items,
    (
        SELECT JSON_OBJECT(
            'valor_maoobra', haso.valor_maoobra,
            'valor_desconto', haso.valor_desconto,
            'valor_desconto_itens', haso.valor_desconto_itens,
            'valor_desconto_negociacao', haso.valor_desconto_negociacao,
            'subtotal', haso.subtotal,
            'valor_total', haso.valor_total,
            'ajuda_participativa', haso.ajuda_participativa
        )
        FROM hbrd_adm_sinister_order haso
        WHERE haso.id = (
            SELECT haso_inner.id
            FROM hbrd_adm_sinister_order haso_inner
            WHERE haso_inner.id_participant = par.id
              AND (haso_inner.id_tipo = 1 OR haso_inner.id_tipo = 13)
            ORDER BY FIELD(haso_inner.id_tipo, 1, 13)
            LIMIT 1
        )
    ) AS order_summary
FROM hbrd_adm_sinister_participant_status_history status
LEFT JOIN hbrd_adm_sinister_participant_type_history tipe ON status.id_pai = tipe.id
LEFT JOIN hbrd_adm_sinister_status s ON status.id_status = s.id
LEFT JOIN hbrd_adm_sinister_participant par ON par.id = tipe.id_participant
LEFT JOIN hbrd_adm_sinister_history sh ON sh.id_sinister = par.id_sinister
LEFT JOIN hbrd_adm_sinister_order haso ON haso.id_participant = par.id
LEFT JOIN hbrd_adm_store has ON has.id = haso.id_store
LEFT JOIN hbrd_main_util_city city ON city.id = par.id_cidade
LEFT JOIN hbrd_main_util_state state ON state.id = par.id_estado
WHERE status.id_status = 6
  AND par.status = 'Ativo'
  AND (tipe.id_tipo = '8' OR tipe.id_tipo = '14')
  AND COALESCE(
        (
            SELECT MIN(status_history.create_at)
            FROM hbrd_adm_sinister_participant_status_history status_history
            WHERE status_history.create_at > status.create_at
              AND status.id_pai = status_history.id_pai
        ),
        status.leave_at
    ) IS NULL
  AND (has.id IS NULL OR has.id != 2670)
  AND (haso.id_store IS NULL OR haso.id_store != 2670)
GROUP BY tipe.id_participant;
        ");
    }

    public static function getAccidentInvolvedForAuvoToMotoclub(string $databaseConnection): array
    {
        return DB::connection($databaseConnection)
            ->select("
              SELECT DISTINCT
    par.id,
    CONCAT(par.id, ' / ', par.nome, ' / ', par.placa) AS `name`,
    status.id_pai,
    tipe.id_participant,
    (
        SELECT haso_inner.id
        FROM hbrd_adm_sinister_order haso_inner
        WHERE haso_inner.id_participant = par.id
          AND (haso_inner.id_tipo = 1 OR haso_inner.id_tipo = 13)
        ORDER BY FIELD(haso_inner.id_tipo, 1, 13)
        LIMIT 1
    ) AS id_order,
    DATE_FORMAT(status.create_at, '%d/%m/%y') AS dataContrato,
    CONCAT(DATE_FORMAT(status.create_at, '%d/%m/%y'), ' ', DATEDIFF(NOW(), status.create_at), ' dia(s)') AS note,
    par.nome,
    par.placa,
    par.cpf_cnpj AS cpfCnpj,
    has.id AS id_oficina,
    CONCAT(IFNULL(has.nome, ''), ' / ', IFNULL(has.endereco, ''), ' / ', IFNULL(city.cidade, ''), ' - ', IFNULL(state.uf, '')) AS address,
    CONCAT(IFNULL(has.nome, ''), ' / Placa: ', IFNULL(par.placa, ''), ' / Veículo: ', IFNULL(par.modelo_veiculo, '')) AS orientation,
    par.telefone AS phone,
    par.email,
    par.id_sinister,
    status.id_status,
    tipe.id_tipo,
    par.status,
    status.create_at AS dt_criacao,
    par.create_at AS data_criacao,
    has.endereco,
    city.cidade,
    state.uf,
    COALESCE(
        (
            SELECT MIN(status_history.create_at)
            FROM hbrd_adm_sinister_participant_status_history status_history
            WHERE status_history.create_at > status.create_at
              AND status.id_pai = status_history.id_pai
        ),
        status.leave_at
    ) AS data_da_proxima_etapa,
    status.leave_at,
    s.nome,
    city.cidade AS cidade_associado,
    state.uf AS estado_associado,
    (
        SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'quantidade', oi.quantidade,
                'descricao', oi.descricao,
                'valor', oi.valor,
                'desconto', oi.desconto,
                'observacao', oi.observacao,
                'troca', oi.troca,
                'lanternagem', oi.lanternagemn,
                'pintura', oi.pintura
            )
        )
        FROM hbrd_adm_sinister_order_item oi
        WHERE oi.id_order = (
            SELECT haso_inner.id
            FROM hbrd_adm_sinister_order haso_inner
            WHERE haso_inner.id_participant = par.id
              AND (haso_inner.id_tipo = 1 OR haso_inner.id_tipo = 13)
            ORDER BY FIELD(haso_inner.id_tipo, 1, 13)
            LIMIT 1
        )
    ) AS order_items,
    (
        SELECT JSON_OBJECT(
            'valor_maoobra', haso.valor_maoobra,
            'valor_desconto', haso.valor_desconto,
            'valor_desconto_itens', haso.valor_desconto_itens,
            'valor_desconto_negociacao', haso.valor_desconto_negociacao,
            'subtotal', haso.subtotal,
            'valor_total', haso.valor_total,
            'ajuda_participativa', haso.ajuda_participativa
        )
        FROM hbrd_adm_sinister_order haso
        WHERE haso.id = (
            SELECT haso_inner.id
            FROM hbrd_adm_sinister_order haso_inner
            WHERE haso_inner.id_participant = par.id
              AND (haso_inner.id_tipo = 1 OR haso_inner.id_tipo = 13)
            ORDER BY FIELD(haso_inner.id_tipo, 1, 13)
            LIMIT 1
        )
    ) AS order_summary
FROM hbrd_adm_sinister_participant_status_history status
LEFT JOIN hbrd_adm_sinister_participant_type_history tipe ON status.id_pai = tipe.id
LEFT JOIN hbrd_adm_sinister_status s ON status.id_status = s.id
LEFT JOIN hbrd_adm_sinister_participant par ON par.id = tipe.id_participant
LEFT JOIN hbrd_adm_sinister_history sh ON sh.id_sinister = par.id_sinister
LEFT JOIN hbrd_adm_sinister_order haso ON haso.id_participant = par.id
LEFT JOIN hbrd_adm_store has ON has.id = haso.id_store
LEFT JOIN hbrd_main_util_city city ON city.id = par.id_cidade
LEFT JOIN hbrd_main_util_state state ON state.id = par.id_estado
WHERE status.id_status = 6
  AND par.status = 'Ativo'
  AND (tipe.id_tipo = '8' OR tipe.id_tipo = '14')
  AND COALESCE(
        (
            SELECT MIN(status_history.create_at)
            FROM hbrd_adm_sinister_participant_status_history status_history
            WHERE status_history.create_at > status.create_at
              AND status.id_pai = status_history.id_pai
        ),
        status.leave_at
    ) IS NULL
  AND (has.id IS NULL OR has.id != 2670)
  AND (haso.id_store IS NULL OR haso.id_store != 2670)
GROUP BY tipe.id_participant;
        ");
    }

    public static function getAccidentInvolvedForAuvoToNova(string $databaseConnection): array
    {
        return DB::connection($databaseConnection)
            ->select("
            SELECT DISTINCT
    par.id,
    CONCAT(par.id, ' / ', par.nome, ' / ', par.placa) AS `name`,
    status.id_pai,
    tipe.id_participant,
    (
        SELECT haso_inner.id
        FROM hbrd_adm_sinister_order haso_inner
        WHERE haso_inner.id_participant = par.id
          AND (haso_inner.id_tipo = 1 OR haso_inner.id_tipo = 13)
        ORDER BY FIELD(haso_inner.id_tipo, 1, 13)
        LIMIT 1
    ) AS id_order,
    DATE_FORMAT(status.create_at, '%d/%m/%y') AS dataContrato,
    CONCAT(DATE_FORMAT(status.create_at, '%d/%m/%y'), ' ', DATEDIFF(NOW(), status.create_at), ' dia(s)') AS note,
    par.nome,
    par.placa,
    par.cpf_cnpj AS cpfCnpj,
    has.id AS id_oficina,
    CONCAT(IFNULL(has.nome, ''), ' / ', IFNULL(has.endereco, ''), ' / ', IFNULL(city.cidade, ''), ' - ', IFNULL(state.uf, '')) AS address,
    CONCAT(IFNULL(has.nome, ''), ' / Placa: ', IFNULL(par.placa, ''), ' / Veículo: ', IFNULL(par.modelo_veiculo, '')) AS orientation,
    par.telefone AS phone,
    par.email,
    par.id_sinister,
    status.id_status,
    tipe.id_tipo,
    par.status,
    status.create_at AS dt_criacao,
    par.create_at AS data_criacao,
    has.endereco,
    city.cidade,
    state.uf,
    COALESCE(
        (
            SELECT MIN(status_history.create_at)
            FROM hbrd_adm_sinister_participant_status_history status_history
            WHERE status_history.create_at > status.create_at
              AND status.id_pai = status_history.id_pai
        ),
        status.leave_at
    ) AS data_da_proxima_etapa,
    status.leave_at,
    s.nome,
    city.cidade AS cidade_associado,
    state.uf AS estado_associado,
    (
        SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'quantidade', oi.quantidade,
                'descricao', oi.descricao,
                'valor', oi.valor,
                'desconto', oi.desconto,
                'observacao', oi.observacao,
                'troca', oi.troca,
                'lanternagem', oi.lanternagemn,
                'pintura', oi.pintura
            )
        )
        FROM hbrd_adm_sinister_order_item oi
        WHERE oi.id_order = (
            SELECT haso_inner.id
            FROM hbrd_adm_sinister_order haso_inner
            WHERE haso_inner.id_participant = par.id
              AND (haso_inner.id_tipo = 1 OR haso_inner.id_tipo = 13)
            ORDER BY FIELD(haso_inner.id_tipo, 1, 13)
            LIMIT 1
        )
    ) AS order_items,
    (
        SELECT JSON_OBJECT(
            'valor_maoobra', haso.valor_maoobra,
            'valor_desconto', haso.valor_desconto,
            'valor_desconto_itens', haso.valor_desconto_itens,
            'valor_desconto_negociacao', haso.valor_desconto_negociacao,
            'subtotal', haso.subtotal,
            'valor_total', haso.valor_total,
            'ajuda_participativa', haso.ajuda_participativa
        )
        FROM hbrd_adm_sinister_order haso
        WHERE haso.id = (
            SELECT haso_inner.id
            FROM hbrd_adm_sinister_order haso_inner
            WHERE haso_inner.id_participant = par.id
              AND (haso_inner.id_tipo = 1 OR haso_inner.id_tipo = 13)
            ORDER BY FIELD(haso_inner.id_tipo, 1, 13)
            LIMIT 1
        )
    ) AS order_summary
FROM hbrd_adm_sinister_participant_status_history status
LEFT JOIN hbrd_adm_sinister_participant_type_history tipe ON status.id_pai = tipe.id
LEFT JOIN hbrd_adm_sinister_status s ON status.id_status = s.id
LEFT JOIN hbrd_adm_sinister_participant par ON par.id = tipe.id_participant
LEFT JOIN hbrd_adm_sinister_history sh ON sh.id_sinister = par.id_sinister
LEFT JOIN hbrd_adm_sinister_order haso ON haso.id_participant = par.id
LEFT JOIN hbrd_adm_store has ON has.id = haso.id_store
LEFT JOIN hbrd_main_util_city city ON city.id = par.id_cidade
LEFT JOIN hbrd_main_util_state state ON state.id = par.id_estado
WHERE status.id_status = 6
  AND par.status = 'Ativo'
  AND (tipe.id_tipo = '8' OR tipe.id_tipo = '14')
  AND COALESCE(
        (
            SELECT MIN(status_history.create_at)
            FROM hbrd_adm_sinister_participant_status_history status_history
            WHERE status_history.create_at > status.create_at
              AND status.id_pai = status_history.id_pai
        ),
        status.leave_at
    ) IS NULL
  AND (has.id IS NULL OR has.id != 2670)
  AND (haso.id_store IS NULL OR haso.id_store != 2670)
GROUP BY tipe.id_participant;
        ");
    }
}
