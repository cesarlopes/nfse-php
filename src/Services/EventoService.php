<?php

declare(strict_types=1);

namespace Cesarlopes\NFSePHP\Services;

use Cesarlopes\NFSePHP\Domain\Helpers\NfseHelper;
use Cesarlopes\NFSePHP\Domain\Models\Dps;
use Cesarlopes\NFSePHP\Infrastructure\Environment\EnvironmentConfig;
use Cesarlopes\NFSePHP\Infrastructure\Http\CurlClient;

/**
 * Service focado em eventos da NFSe (ex.: cancelamento).
 */
class EventoService
{
    public function __construct(
        private CurlClient $client,
        private int $ambiente
    ) {
    }

    /**
     * Exemplo: Cancelar NFSe
     */
    public function cancelaNfse(object $std): array|string
    {
        // Gera o XML de evento
        $dps = new Dps($std);
        $xml = $dps->renderEvento();

        // Assina
        $cert = $this->client->getRestBase()->getCertificate();
        if (!$cert) {
            throw new \RuntimeException('Certificado não definido.');
        }

        $xmlAssinado = NfseHelper::signXml($cert, $xml, 'infPedReg', 'Id', 'pedRegEvento');
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlAssinado;

        // GZip + Base64
        $payloadB64 = NfseHelper::compressBase64($xmlAssinado);

        // Monta JSON
        $payload = json_encode(['pedidoRegistroEventoXmlGZipB64' => $payloadB64]);

        $chNFSe = $std->infPedReg->chNFSe ?? '';
        $baseUrl = EnvironmentConfig::getBaseUrl($this->ambiente);
        $url = $baseUrl . "/nfse/{$chNFSe}/eventos";

        $headers = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
        ];

        $response = $this->client->post($url, $payload, $headers);
        $json = json_decode($response, true);
        return $json ?: $response;
    }
}
