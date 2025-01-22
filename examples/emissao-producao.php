<?php

declare(strict_types=1);

use Cesarlopes\NFSePHP\Facades\NfseFacade;
use Cesarlopes\NFSePHP\Infrastructure\Common\RestBase;
use Cesarlopes\NFSePHP\Infrastructure\Http\CurlClient;
use NFePHP\Common\Certificate;

require __DIR__ . '/../vendor/autoload.php';

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

    $std = new stdClass();
    $std->infDPS = new stdClass();

    $std->infDPS->tpAmb = 1;

    $std->infDPS->dhEmi = date('Y-m-d\TH:i:sP');
    $std->infDPS->verAplic = 'EmissorWeb_1.3.0.0';
    $std->infDPS->serie = '901';
    $std->infDPS->nDPS = '2';
    $std->infDPS->dCompet = date('Y-m-d');
    $std->infDPS->tpEmit = 1;
    $std->infDPS->cLocEmi = '';


    $std->infDPS->prest = new stdClass();
    $std->infDPS->prest->xNome = '';
    $std->infDPS->prest->CNPJ = '';
    $std->infDPS->prest->IM = '';
    $std->infDPS->prest->fone = '';
    $std->infDPS->prest->email = '';


    $std->infDPS->prest->regTrib = new stdClass();
    $std->infDPS->prest->regTrib->opSimpNac = 2;
    $std->infDPS->prest->regTrib->regEspTrib = 0;


    $std->infDPS->toma = new stdClass();
    $std->infDPS->toma->CNPJ = '';
    $std->infDPS->toma->IM = '';
    $std->infDPS->toma->xNome = '';
    $std->infDPS->toma->fone = '';
    $std->infDPS->toma->email = '';

    $std->infDPS->toma->end = new stdClass();
    $std->infDPS->toma->end->xLgr = '';
    $std->infDPS->toma->end->nro = '';
    $std->infDPS->toma->end->xBairro = '';
    $std->infDPS->toma->end->endNac = new stdClass();
    $std->infDPS->toma->end->endNac->cMun = '';
    $std->infDPS->toma->end->endNac->CEP = '';


    $std->infDPS->serv = new stdClass();
    $std->infDPS->serv->locPrest = new stdClass();
    $std->infDPS->serv->locPrest->cLocPrestacao = '';


    $std->infDPS->serv->cServ = new stdClass();
    $std->infDPS->serv->cServ->cTribNac = '';
    $std->infDPS->serv->cServ->xDescServ = '';


    $std->infDPS->valores = new stdClass();
    $std->infDPS->valores->vServPrest = new stdClass();
    $std->infDPS->valores->vServPrest->vServ = number_format(10.0, 2, '.', '');

    $std->infDPS->valores->trib = new stdClass();
    $std->infDPS->valores->trib->tribMun = new stdClass();
    $std->infDPS->valores->trib->tribMun->tribISSQN = 1;
    $std->infDPS->valores->trib->tribMun->tpRetISSQN = 1;

    $std->infDPS->valores->trib->totTrib = new stdClass();
    $std->infDPS->valores->trib->totTrib->indTotTrib = 0;


    $response = $nfse->emitirDps($std);

    // Se vier nfseXmlGZipB64, podemos extrair o XML final
    if (is_array($response) && isset($response['nfseXmlGZipB64'])) {
        $xml = gzdecode(base64_decode($response['nfseXmlGZipB64']));
        var_dump($xml);
    } else {
        var_dump($response);
    }
} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . PHP_EOL;
    // var_dump($e);
}
