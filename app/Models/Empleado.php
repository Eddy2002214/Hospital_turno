<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'Empleados';
    protected $primaryKey = 'id_empleado';
    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'codigo_biometrico',
        'telefono',
        'nombre_completo',
        'id_cargo',
        'id_departamento',
    ];

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'id_cargo', 'id_cargo');
    }

    public function departamento()
    {
        return $this->belongsTo(DepartamentoServicio::class, 'id_departamento', 'id_departamento');
    }

    public function usuario()
    {
        return $this->hasOne(User::class, 'id_empleado', 'id_empleado');
    }
}
