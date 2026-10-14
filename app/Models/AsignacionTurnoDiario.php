<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionTurnoDiario extends Model
{
    protected $table = 'Asignacion_Turnos_Diarios';

    protected $primaryKey = 'id_asignacion';

    public $timestamps = false;

    protected $fillable = [
        'id_rol_mensual',
        'id_empleado',
        'id_fuente',
        'fecha',
        'id_turno',
    ];

    public function rolMensual()
    {
        return $this->belongsTo(RolMensual::class, 'id_rol_mensual', 'id_rol_mensual');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }

    public function fuente()
    {
        return $this->belongsTo(FuenteFinanciamiento::class, 'id_fuente', 'id_fuente');
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class, 'id_turno', 'id_turno');
    }
}
