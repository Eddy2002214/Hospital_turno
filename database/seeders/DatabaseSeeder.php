<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear Cargos
        $cargoJefe = \App\Models\Cargo::firstOrCreate(['nombre_cargo' => 'Jefe de Servicio']);
        $cargoEmpleado = \App\Models\Cargo::firstOrCreate(['nombre_cargo' => 'Operador / Especialista']);
        $cargoAdmin = \App\Models\Cargo::firstOrCreate(['nombre_cargo' => 'Administrador de Sistema']);

        // 2. Crear Departamentos
        $dept1 = \App\Models\DepartamentoServicio::firstOrCreate(['nombre_departmento' => 'Tecnología de la Información']);
        $dept2 = \App\Models\DepartamentoServicio::firstOrCreate(['nombre_departmento' => 'Enfermería']);

        // 3. Crear Turnos Básicos
        $turnos = [
            ['codigo_turno' => 'M', 'nombre_turno' => 'Mañana', 'hora_entrada' => '07:30', 'hora_salida' => '13:30'],
            ['codigo_turno' => 'M*', 'nombre_turno' => 'Mañana extendido', 'hora_entrada' => '07:30', 'hora_salida' => '15:30'],
            ['codigo_turno' => 'T', 'nombre_turno' => 'Tarde', 'hora_entrada' => '13:30', 'hora_salida' => '19:30'],
            ['codigo_turno' => 'N', 'nombre_turno' => 'Noche', 'hora_entrada' => '19:30', 'hora_salida' => '07:30'],
            ['codigo_turno' => 'L', 'nombre_turno' => 'Libre', 'hora_entrada' => null, 'hora_salida' => null],
        ];
        foreach ($turnos as $turno) {
            \App\Models\Turno::firstOrCreate(['codigo_turno' => $turno['codigo_turno']], $turno);
        }

        // 4. Crear Empleados y Usuarios
        
        // --- ADMIN ---
        $empAdmin = \App\Models\Empleado::firstOrCreate(
            ['codigo_biometrico' => 'ADM-001'],
            ['nombre_completo' => 'Super Admin', 'id_cargo' => $cargoAdmin->id_cargo, 'id_departamento' => null, 'item_id' => '100', 'telefono' => '111111']
        );
        \App\Models\User::firstOrCreate(
            ['correo' => 'admin@hospital.com'],
            ['nombre_usuario' => 'admin', 'password_hash' => \Illuminate\Support\Facades\Hash::make('password'), 'rol' => 'Administrador', 'id_empleado' => $empAdmin->id_empleado]
        );

        // --- JEFES ---
        // Jefe TI
        $empJefeTI = \App\Models\Empleado::firstOrCreate(
            ['codigo_biometrico' => 'JEF-001'],
            ['nombre_completo' => 'Jefe de Sistemas', 'id_cargo' => $cargoJefe->id_cargo, 'id_departamento' => $dept1->id_departamento, 'item_id' => '101', 'telefono' => '222222']
        );
        \App\Models\User::firstOrCreate(
            ['correo' => 'jefeti@hospital.com'],
            ['nombre_usuario' => 'jefeti', 'password_hash' => \Illuminate\Support\Facades\Hash::make('password'), 'rol' => 'Jefe de Area', 'id_empleado' => $empJefeTI->id_empleado]
        );

        // Jefe Enfermería
        $empJefeEnf = \App\Models\Empleado::firstOrCreate(
            ['codigo_biometrico' => 'JEF-002'],
            ['nombre_completo' => 'Jefe de Enfermería', 'id_cargo' => $cargoJefe->id_cargo, 'id_departamento' => $dept2->id_departamento, 'item_id' => '102', 'telefono' => '333333']
        );
        \App\Models\User::firstOrCreate(
            ['correo' => 'jefeenf@hospital.com'],
            ['nombre_usuario' => 'jefeenf', 'password_hash' => \Illuminate\Support\Facades\Hash::make('password'), 'rol' => 'Jefe de Area', 'id_empleado' => $empJefeEnf->id_empleado]
        );

        // --- USUARIOS (7 en total: 3 TI, 4 Enfermería) ---
        // 3 Usuarios TI
        for ($i = 1; $i <= 3; $i++) {
            $emp = \App\Models\Empleado::firstOrCreate(
                ['codigo_biometrico' => 'USR-TI-00' . $i],
                ['nombre_completo' => 'Usuario TI ' . $i, 'id_cargo' => $cargoEmpleado->id_cargo, 'id_departamento' => $dept1->id_departamento, 'item_id' => '20' . $i, 'telefono' => '4444' . $i]
            );
            \App\Models\User::firstOrCreate(
                ['correo' => "user_ti_$i@hospital.com"],
                ['nombre_usuario' => "userti$i", 'password_hash' => \Illuminate\Support\Facades\Hash::make('password'), 'rol' => 'Operador', 'id_empleado' => $emp->id_empleado]
            );
        }

        // 4 Usuarios Enfermería
        for ($i = 1; $i <= 4; $i++) {
            $emp = \App\Models\Empleado::firstOrCreate(
                ['codigo_biometrico' => 'USR-ENF-00' . $i],
                ['nombre_completo' => 'Usuario Enfermería ' . $i, 'id_cargo' => $cargoEmpleado->id_cargo, 'id_departamento' => $dept2->id_departamento, 'item_id' => '30' . $i, 'telefono' => '5555' . $i]
            );
            \App\Models\User::firstOrCreate(
                ['correo' => "user_enf_$i@hospital.com"],
                ['nombre_usuario' => "userenf$i", 'password_hash' => \Illuminate\Support\Facades\Hash::make('password'), 'rol' => 'Operador', 'id_empleado' => $emp->id_empleado]
            );
        }
    }
}
