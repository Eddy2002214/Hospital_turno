<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    protected $table = 'Turnos';

    protected $primaryKey = 'id_turno';

    public $timestamps = false;

    protected $fillable = [
        'codigo_turno',
        'nombre_turno',
        'hora_entrada',
        'hora_salida',
        'horas_computables',
        'color_hex',
    ];

    protected function casts(): array
    {
        return [
            'horas_computables' => 'decimal:2',
        ];
    }
}
