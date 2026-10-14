<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartamentoServicio extends Model
{
    protected $table = 'Departamentos_Servicios';

    protected $primaryKey = 'id_departamento';

    public $timestamps = false;

    protected $fillable = [
        'nombre_departmento',
    ];

    public function empleados()
    {
        return $this->hasMany(Empleado::class, 'id_departamento', 'id_departamento');
    }

    public function rolesMensuales()
    {
        return $this->hasMany(RolMensual::class, 'id_departamento', 'id_departamento');
    }
}
