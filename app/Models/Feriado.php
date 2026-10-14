<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feriado extends Model
{
    protected $table = 'feriados';

    protected $primaryKey = 'id_feriado';

    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'descripcion',
        'tipo',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }
}
