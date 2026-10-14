<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empleado extends Model
{
    protected $table = 'Empleados';

    protected $primaryKey = 'id_empleado';

    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'codigo_biometrico',
        'ci',
        'telefono',
        'nombre_completo',
        'id_cargo',
        'id_departamento',
        'activo',
        'profesion_especialidad',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'id_cargo', 'id_cargo');
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(DepartamentoServicio::class, 'id_departamento', 'id_departamento');
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class, 'id_empleado', 'id_empleado');
    }

    public function asignacionesTurnos(): HasMany
    {
        return $this->hasMany(AsignacionTurnoDiario::class, 'id_empleado', 'id_empleado');
    }

    public function cambiosSolicitados(): HasMany
    {
        return $this->hasMany(CambioTurno::class, 'id_empleado_solicitante', 'id_empleado');
    }

    public function licencias(): HasMany
    {
        return $this->hasMany(LicenciaVacacion::class, 'id_empleado', 'id_empleado');
    }
}
