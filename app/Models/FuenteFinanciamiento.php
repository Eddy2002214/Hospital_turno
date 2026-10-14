<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuenteFinanciamiento extends Model
{
    protected $table = 'Fuentes_Financiamiento';

    protected $primaryKey = 'id_fuente';

    public $timestamps = false;

    protected $fillable = [
        'codigo_fuente',
        'descripcion',
    ];
}
