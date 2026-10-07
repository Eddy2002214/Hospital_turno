<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumoComedor extends Model
{
    protected $table = 'consumos_comedor';
    protected $primaryKey = 'id_consumo';
    protected $fillable = [
        'id_empleado',
        'fecha',
        'hora'
    ];
}
