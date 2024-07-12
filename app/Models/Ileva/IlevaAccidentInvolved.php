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
    CONCAT(par.id, ' / ', par.nome, ' / ', par.placa)`name`,
    status.id_pai,
    tipe.id_participant,
    CONCAT(DATE_FORMAT(status.create_at, '%d/%m/%y'), ' ', DATEDIFF(NOW(), status.create_at), ' dia(s)') note,
    par.nome,
    par.placa,
    par.cpf_cnpj cpfCnpj,
        CONCAT(IFNULL(has.nome, ''), ' / ', IFNULL(has.endereco, ''), ' / ', IFNULL(city.cidade, ''), ' - ', IFNULL(state.uf, '')) address,
    par.telefone phone,
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
    state.uf AS estado_associado
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
  AND (tipe.id_tipo = '14' OR tipe.id_tipo = '8')
  AND COALESCE(
        (
            SELECT MIN(status_history.create_at)
            FROM hbrd_adm_sinister_participant_status_history status_history
            WHERE status_history.create_at > status.create_at
              AND status.id_pai = status_history.id_pai
        ),
        status.leave_at
    ) IS NULL
GROUP BY tipe.id_participant
        ");
    }

    public static function getAccidentInvolvedForAuvoToMotoclub(string $databaseConnection): array
    {
        return DB::connection($databaseConnection)
            ->select("
            SELECT DISTINCT
	 par.id,
    CONCAT(par.id, ' / ', par.nome, ' / ', par.placa)`name`,
    status.id_pai,
    tipe.id_participant,
    CONCAT(DATE_FORMAT(status.create_at, '%d/%m/%y'), ' ', DATEDIFF(NOW(), status.create_at), ' dia(s)') note,
    par.nome,
    par.placa,
    par.cpf_cnpj cpfCnpj,
        CONCAT(IFNULL(has.nome, ''), ' / ', IFNULL(has.endereco, ''), ' / ', IFNULL(city.cidade, ''), ' - ', IFNULL(state.uf, '')) address,
    par.telefone phone,
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
    state.uf AS estado_associado
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
  AND (tipe.id_tipo = '8')
  AND COALESCE(
        (
            SELECT MIN(status_history.create_at)
            FROM hbrd_adm_sinister_participant_status_history status_history
            WHERE status_history.create_at > status.create_at
              AND status.id_pai = status_history.id_pai
        ),
        status.leave_at
    ) IS NULL
GROUP BY tipe.id_participant
        ");
    }
}
