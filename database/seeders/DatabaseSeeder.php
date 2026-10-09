<?php

namespace Database\Seeders;

use App\Models\Cargo;
use App\Models\DepartamentoServicio;
use App\Models\Empleado;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear Cargos
        $cargoJefe = Cargo::firstOrCreate(['nombre_cargo' => 'Jefe de Servicio']);
        $cargoEmpleado = Cargo::firstOrCreate(['nombre_cargo' => 'Operador / Especialista']);
        $cargoAdmin = Cargo::firstOrCreate(['nombre_cargo' => 'Administrador de Sistema']);

        // 2. Crear Departamentos
        $dept1 = DepartamentoServicio::firstOrCreate(['nombre_departmento' => 'Tecnología de la Información']);
        $dept2 = DepartamentoServicio::firstOrCreate(['nombre_departmento' => 'Enfermería']);

        // 3. Crear Turnos Básicos
        $turnos = [
            ['codigo_turno' => 'M', 'nombre_turno' => 'Mañana', 'hora_entrada' => '07:30', 'hora_salida' => '13:30'],
            ['codigo_turno' => 'M*', 'nombre_turno' => 'Mañana extendido', 'hora_entrada' => '07:30', 'hora_salida' => '15:30'],
            ['codigo_turno' => 'T', 'nombre_turno' => 'Tarde', 'hora_entrada' => '13:30', 'hora_salida' => '19:30'],
            ['codigo_turno' => 'N', 'nombre_turno' => 'Noche', 'hora_entrada' => '19:30', 'hora_salida' => '07:30'],
            ['codigo_turno' => 'L', 'nombre_turno' => 'Libre', 'hora_entrada' => null, 'hora_salida' => null],
        ];
        foreach ($turnos as $turno) {
            Turno::firstOrCreate(['codigo_turno' => $turno['codigo_turno']], $turno);
        }

        // 4. Crear Empleados y Usuarios

        // --- ADMIN ---
        $empAdmin = Empleado::firstOrCreate(
            ['codigo_biometrico' => 'ADM-001'],
            ['nombre_completo' => 'Super Admin', 'id_cargo' => $cargoAdmin->id_cargo, 'id_departamento' => null, 'item_id' => '100', 'telefono' => '111111']
        );
        User::firstOrCreate(
            ['correo' => 'admin@hospital.com'],
            ['nombre_usuario' => 'admin', 'password_hash' => Hash::make('password'), 'rol' => 'Administrador', 'id_empleado' => $empAdmin->id_empleado]
        );

        // --- JEFES ---
        // Jefe TI
        $empJefeTI = Empleado::firstOrCreate(
            ['codigo_biometrico' => 'JEF-001'],
            ['nombre_completo' => 'Jefe de Sistemas', 'id_cargo' => $cargoJefe->id_cargo, 'id_departamento' => $dept1->id_departamento, 'item_id' => '101', 'telefono' => '222222']
        );
        User::firstOrCreate(
            ['correo' => 'jefeti@hospital.com'],
            ['nombre_usuario' => 'jefeti', 'password_hash' => Hash::make('password'), 'rol' => 'Jefe de Area', 'id_empleado' => $empJefeTI->id_empleado]
        );

        // Jefe Enfermería
        $empJefeEnf = Empleado::firstOrCreate(
            ['codigo_biometrico' => 'JEF-002'],
            ['nombre_completo' => 'Jefe de Enfermería', 'id_cargo' => $cargoJefe->id_cargo, 'id_departamento' => $dept2->id_departamento, 'item_id' => '102', 'telefono' => '333333']
        );
        User::firstOrCreate(
            ['correo' => 'jefeenf@hospital.com'],
            ['nombre_usuario' => 'jefeenf', 'password_hash' => Hash::make('password'), 'rol' => 'Jefe de Area', 'id_empleado' => $empJefeEnf->id_empleado]
        );

        // --- USUARIOS (7 en total: 3 TI, 4 Enfermería) ---
        // 3 Usuarios TI
        for ($i = 1; $i <= 3; $i++) {
            $emp = Empleado::firstOrCreate(
                ['codigo_biometrico' => 'USR-TI-00'.$i],
                ['nombre_completo' => 'Usuario TI '.$i, 'id_cargo' => $cargoEmpleado->id_cargo, 'id_departamento' => $dept1->id_departamento, 'item_id' => '20'.$i, 'telefono' => '4444'.$i]
            );
            User::firstOrCreate(
                ['correo' => "user_ti_$i@hospital.com"],
                ['nombre_usuario' => "userti$i", 'password_hash' => Hash::make('password'), 'rol' => 'Operador', 'id_empleado' => $emp->id_empleado]
            );
        }

        // 4 Usuarios Enfermería
        for ($i = 1; $i <= 4; $i++) {
            $emp = Empleado::firstOrCreate(
                ['codigo_biometrico' => 'USR-ENF-00'.$i],
                ['nombre_completo' => 'Usuario Enfermería '.$i, 'id_cargo' => $cargoEmpleado->id_cargo, 'id_departamento' => $dept2->id_departamento, 'item_id' => '30'.$i, 'telefono' => '5555'.$i]
            );
            User::firstOrCreate(
                ['correo' => "user_enf_$i@hospital.com"],
                ['nombre_usuario' => "userenf$i", 'password_hash' => Hash::make('password'), 'rol' => 'Operador', 'id_empleado' => $emp->id_empleado]
            );
        }

        // --- TERCER DEPARTAMENTO: EMERGENCIAS ---
        $dept3 = DepartamentoServicio::firstOrCreate(['nombre_departmento' => 'Emergencias']);

        // Jefe Emergencias
        $empJefeEmerg = Empleado::firstOrCreate(
            ['codigo_biometrico' => 'JEF-003'],
            ['nombre_completo' => 'Jefe de Emergencias', 'id_cargo' => $cargoJefe->id_cargo, 'id_departamento' => $dept3->id_departamento, 'item_id' => '103', 'telefono' => '666666']
        );
        User::firstOrCreate(
            ['correo' => 'jefeemerg@hospital.com'],
            ['nombre_usuario' => 'jefeemerg', 'password_hash' => Hash::make('password'), 'rol' => 'Jefe de Area', 'id_empleado' => $empJefeEmerg->id_empleado]
        );

        // 4 Usuarios Emergencias
        for ($i = 1; $i <= 4; $i++) {
            $emp = Empleado::firstOrCreate(
                ['codigo_biometrico' => 'USR-EMG-00'.$i],
                ['nombre_completo' => 'Usuario Emergencias '.$i, 'id_cargo' => $cargoEmpleado->id_cargo, 'id_departamento' => $dept3->id_departamento, 'item_id' => '40'.$i, 'telefono' => '7777'.$i]
            );
            User::firstOrCreate(
                ['correo' => "user_emg_$i@hospital.com"],
                ['nombre_usuario' => "useremg$i", 'password_hash' => Hash::make('password'), 'rol' => 'Operador', 'id_empleado' => $emp->id_empleado]
            );
        }

        // --- 4 EMPLEADOS NUEVOS SIN JEFE (id_departamento = null) ---
        for ($i = 1; $i <= 4; $i++) {
            $emp = Empleado::firstOrCreate(
                ['codigo_biometrico' => 'USR-NUEVO-00'.$i],
                ['nombre_completo' => 'Empleado Nuevo '.$i, 'id_cargo' => $cargoEmpleado->id_cargo, 'id_departamento' => null, 'item_id' => '90'.$i, 'telefono' => '8888'.$i]
            );
            User::firstOrCreate(
                ['correo' => "nuevo_$i@hospital.com"],
                ['nombre_usuario' => "nuevo$i", 'password_hash' => Hash::make('password'), 'rol' => 'Operador', 'id_empleado' => $emp->id_empleado]
            );
        }
    }
}
