<?php
$nif = '99999910G';
$numSerie = 'T531';
$fechaExp = '29-04-2026';
$tipo = 'F2';
$cuota = '7.03';
$importe = '40.53';
$prevHuella = '27C60DFDA46C7EE67A099B5763FE5A20E1919715906555909AA4CDC9BDE20E8D';
$fechaHito = '2026-04-29T15:38:16+02:00';

// Hypothesis 1: Plain concatenation (current implementation)
$str1 = $nif . $numSerie . $fechaExp . $tipo . $cuota . $importe . $prevHuella . $fechaHito;
$hash1 = strtoupper(hash('sha256', $str1));
echo "Hash 1 (Plain): $hash1\n";

// Hypothesis 2: Labels and ampersands
$str2 = "IDEmisorFactura=$nif&NumSerieFactura=$numSerie&FechaExpedicionFactura=$fechaExp&TipoFactura=$tipo&CuotaTotal=$cuota&ImporteTotal=$importe&Huella=$prevHuella&FechaHoraHusoGenRegistro=$fechaHito";
$hash2 = strtoupper(hash('sha256', $str2));
echo "Hash 2 (Labels): $hash2\n";

// AEAT expected: 5B3C157498228A17BF6AED429F57C776A70F1A6E6C71D00A879ACC27D241E09B
if ($hash2 === '5B3C157498228A17BF6AED429F57C776A70F1A6E6C71D00A879ACC27D241E09B') {
    echo "Hypothesis 2 MATCHES AEAT!\n";
} else {
    echo "Hypothesis 2 does NOT match.\n";
}
