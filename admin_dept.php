<?php
$d = \App\Models\DepartamentoServicio::where('nombre_departmento', 'Administración')->first();
if ($d) {
    \App\Models\Empleado::where('id_departamento', $d->id_departamento)->update(['id_departamento' => null]);
    $d->delete();
    echo "Departamento Administración eliminado y admin restaurado a null.\n";
}
