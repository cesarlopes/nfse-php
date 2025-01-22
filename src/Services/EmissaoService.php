<?php

declare(strict_types=1);

namespace Cesarlopes\NFSePHP\Services;

use stdClass;
use Cesarlopes\NFSePHP\Domain\Models\Dps;
use Cesarlopes\NFSePHP\Domain\Helpers\NfseHelper;
use Cesarlopes\NFSePHP\Infrastructure\Environment\EnvironmentConfig;
use Cesarlopes\NFSePHP\Infrastructure\Http\CurlClient;

/**
 * Service focado em emissão/envio de NFSe/DPS.
 * Responsável por:
 * - Montar o XML (usando Dps)
 * - Assinar
 * - Comprimir
 * - Enviar via cURL
 */
class EmissaoService
{
    public function __construct(
        private CurlClient $client,
        private int $ambiente
    ) {
    }

    /**
     * Emitir/enviar DPS
     * Recebe $dados (stdClass), constrói o XML via classe Dps,
     * Assina e faz POST no endpoint /nfse do provedor.
     */
    public function emitirDps(stdClass $dados): array|string
    {
        // 1) Gera o XML a partir do stdClass
        $dps = new Dps($dados);
        $xml = $dps->render(); // DPS em formato XML

        // 2) Assinar o XML
        $cert = $this->client->getRestBase()->getCertificate();
        if (!$cert) {
            throw new \RuntimeException('Certificado não definido em RestBase.');
        }

        // Tag a assinar: 'infDPS'
        // Atributo de referência: 'Id'
        // Root: 'DPS'
        $xmlAssinado = NfseHelper::signXml($cert, $xml, 'infDPS', 'Id', 'DPS');

        // 3) Prefixar a declaração <?xml
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlAssinado;

        //DEBUG
        file_put_contents(__DIR__ . '/../../debug-xml-assinado.xml', $xmlAssinado);

        // 4) Compactar + base64
        $gzB64 = NfseHelper::compressBase64($xmlAssinado);

        // 5) Montar JSON p/ envio
        $payload = json_encode(['dpsXmlGZipB64' => $gzB64]);

        // 6) Determinar URL do ambiente
        $baseUrl = EnvironmentConfig::getBaseUrl($this->ambiente);
        $url = $baseUrl . '/nfse';

        // 7) Cabeçalhos
        $headers = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
        ];

        // 8) POST
        $response = $this->client->post($url, $payload, $headers);

        // 9) Decodifica JSON
        $json = json_decode($response, true);
        return $json ?: $response;
    }
}
