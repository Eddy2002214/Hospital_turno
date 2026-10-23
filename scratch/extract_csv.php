<?php

$transcriptFile = 'C:/Users/HP/.gemini/antigravity-ide/brain/6ec3b17e-919d-480a-8ccd-14aa2048f648/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptFile, 'r');
$csvFound = false;

while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'codigo_funcionario') !== false) {
        $data = json_decode($line, true);
        if (isset($data['content']) && strpos($data['content'], '"codigo_funcionario"') !== false) {
            $pos = strpos($data['content'], '"codigo_funcionario"');
            $csv = substr($data['content'], $pos);
            file_put_contents(__DIR__.'/../database/personal_hospital.csv', $csv);
            echo 'CSV extraido con éxito. Tamaño: '.strlen($csv)." bytes\n";
            $csvFound = true;
            break;
        }
    }
}
fclose($handle);

if (! $csvFound) {
    echo "No se encontró el texto del CSV en el transcript.\n";
}
