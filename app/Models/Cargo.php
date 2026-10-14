<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    protected $table = 'Cargos';

    protected $primaryKey = 'id_cargo';

    public $timestamps = false;

    protected $fillable = [
        'nombre_cargo',
    ];

    public function empleados()
    {
        return $this->hasMany(Empleado::class, 'id_cargo', 'id_cargo');
    }
}
