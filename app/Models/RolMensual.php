<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'estado',
        'aprobado_por',
        'fecha_aprobacion',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_recepcion' => 'date',
            'fecha_aprobacion' => 'datetime',
        ];
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(DepartamentoServicio::class, 'id_departamento', 'id_departamento');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por', 'id_usuario');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionTurnoDiario::class, 'id_rol_mensual', 'id_rol_mensual');
    }
}
