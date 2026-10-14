<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CambioTurno extends Model
{
    protected $table = 'cambios_turnos';

    protected $primaryKey = 'id_cambio';

    protected $fillable = [
        'id_empleado_solicitante',
        'id_empleado_reemplazo',
        'fecha_turno',
        'id_turno_original',
        'id_turno_nuevo',
        'motivo',
        'estado',
        'aprobado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_turno' => 'date',
        ];
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado_solicitante', 'id_empleado');
    }

    public function reemplazo(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado_reemplazo', 'id_empleado');
    }

    public function turnoOriginal(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'id_turno_original', 'id_turno');
    }

    public function turnoNuevo(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'id_turno_nuevo', 'id_turno');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por', 'id_usuario');
    }
}
