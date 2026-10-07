<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolMensual extends Model
{
    protected $table = 'Roles_Mensuales';
    protected $primaryKey = 'id_rol_mensual';
    public $timestamps = false;

    protected $fillable = [
        'mes',
        'anio',
        'id_departamento',
        'fecha_recepcion',
    ];

    public function departamento()
    {
        return $this->belongsTo(DepartamentoServicio::class, 'id_departamento', 'id_departamento');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionTurnoDiario::class, 'id_rol_mensual', 'id_rol_mensual');
    }
}
