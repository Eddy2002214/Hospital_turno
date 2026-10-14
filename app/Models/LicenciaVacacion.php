<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenciaVacacion extends Model
{
    protected $table = 'licencias_vacaciones';

    protected $primaryKey = 'id_licencia';

    protected $fillable = [
        'id_empleado',
        'tipo_licencia',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'documento_respaldo',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }
}
