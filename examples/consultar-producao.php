<?php

declare(strict_types=1);

use Cesarlopes\NFSePHP\Domain\Helpers\NfseHelper;
use Cesarlopes\NFSePHP\Facades\NfseFacade;
use Cesarlopes\NFSePHP\Infrastructure\Common\RestBase;
use Cesarlopes\NFSePHP\Infrastructure\Http\CurlClient;
use NFePHP\Common\Certificate;

require __DIR__ . '/../vendor/autoload.php';

// Exibição de erros (opcional)
error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('America/Sao_Paulo');

try {

    $pfxContent = file_get_contents(__DIR__ . '/../certificates/certificate.pfx');
    $password   = '';

    $cert = Certificate::readPfx($pfxContent, $password);

    $restBase = new RestBase($cert);

    $client = new CurlClient($restBase);

    $nfse = new NfseFacade($client, 1);

    // Consulta NFSe por chave (exemplo fictício)
    $chave = '000...';
    $resultado = $nfse->consultarNfseChave($chave);

    if (is_array($resultado) && isset($resultado['nfseXmlGZipB64'])) {
        $xml = NfseHelper::decompressBase64($resultado['nfseXmlGZipB64']);
        var_dump($xml);
    } else {
        var_dump($resultado);
    }

} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . PHP_EOL;
}
