<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'Usuarios';

    protected $primaryKey = 'id_usuario';

    public $timestamps = false;

    protected $fillable = [
        'nombre_usuario',
        'correo',
        'password_hash',
        'rol',
        'id_empleado',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthIdentifierName()
    {
        return 'id_usuario';
    }

    public function getEmailForPasswordReset()
    {
        return $this->correo;
    }

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
        ];
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }
}
