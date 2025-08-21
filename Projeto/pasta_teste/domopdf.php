<?php 

require 'vendor/autoload.php';

include_once ("db/conexao.php");

$sql = "SELECT * FROM usuarios";
$resultado = $conn->query($sql); 

use Dompdf\Dompdf;

$dompdf = new Dompdf();

// $dompdf = new Dompdf();
// $options = $dompdf->getOptions();
// $options->setDefaultFont('Courier');
// $dompdf->setOptions($options);

$dompdf->loadHtml('hello world');
$lista = '<ul>';
while ($linha = $resultado->fetch_object()) {
    $lista .= "<li>$linha->razao_social</li>";
}
$lista .= '</ul>';

'<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body style="font-family:sens-serif">
<h1> Seu pai desgraçado </h1>
' . $lista . '
    
</body>
</html>';
// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream();

$output = $dompdf -> output();
$dompdf->stream('pdf/arquivo.pdf',['Attachment' => false]);

//$dompdf->$dompdf->output();
//file_put_contents('pdf/arquivo.pdf', $output);