<?php

namespace App\Models\Ileva;

use App\Models\Call;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class IlevaAssociateVehicle extends Model
{
    use HasFactory;

    protected $connection = 'ileva';
    protected $table = 'hbrd_asc_veiculo';

    protected $guarded = [];

    public function ilevaAssociate()
    {
        return $this->belongsTo(IlevaAssociate::class, 'id_associado');
    }

    public function ilevaModel(): HasOne
    {
        return $this->hasOne(IlevaAssociateVehicleModel::class, 'id', 'id_modelo');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class, 'ileva_associate_vehicle_id');
    }

    public function ilevaColor(): HasOne
    {
        return $this->hasOne(IlevaAssociateVehicleColor::class, 'id', 'id_cor');
    }

    public static function getVehiclesForFieldControl(): array
    {
        return DB::connection('ileva')
            ->select("
            SELECT
            CONCAT(hav.id, ' / ', hap.nome, ' / ', hav.placa, ' / ', IF(habv.id_beneficio IN (118,122,130,174,234), 'PROTECT', 'LOCALIZO')) name,
            hap.cpf,
            hav.id code,
            hap.logradouro,
            hap.numero,
            hap.bairro,
            hap.complemento,
            hap.cep,
            hmuc.cidade,
            hmus.uf
            FROM
            hbrd_asc_veiculo hav
            INNER JOIN hbrd_asc_beneficio_veiculo habv on hav.id = habv.id_veiculo
            INNER JOIN hbrd_asc_associado haa on haa.id = hav.id_associado
            INNER JOIN hbrd_asc_pessoa hap on hap.id = haa.id_pessoa
            INNER JOIN hbrd_adm_benefit hab on hab.id = habv.id_beneficio
            LEFT JOIN hbrd_main_util_city hmuc ON hmuc.id = hap.id_cidade
            LEFT JOIN hbrd_main_util_state hmus ON hmus.id = hmuc.id_estado
            WHERE hab.id in (118,122,130,174,234,181,123,119,31)
            ");
    }
}
