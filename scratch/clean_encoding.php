<?php

$file = __DIR__.'/../esquema_base_de_datos.sql';
$content = file_get_contents($file);

$replacements = [
    '├║' => 'ú',
    '├│' => 'ó',
    '├¡' => 'í',
    '├®' => 'é',
    '├í' => 'á',
    '├▒' => 'ñ',
    '├Ü' => 'Ú',
    '├ü' => 'Á',
];

$content = str_replace(array_keys($replacements), array_values($replacements), $content);
file_put_contents($file, $content);
echo "Mojibake corregido en esquema_base_de_datos.sql\n";
