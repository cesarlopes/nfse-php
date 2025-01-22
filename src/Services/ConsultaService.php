<?php

declare(strict_types=1);

namespace Cesarlopes\NFSePHP\Services;

use Cesarlopes\NFSePHP\Infrastructure\Environment\EnvironmentConfig;
use Cesarlopes\NFSePHP\Infrastructure\Http\CurlClient;

/**
 * Service focado em consultas de NFSe, DPS, eventos etc.
 */
class ConsultaService
{
    public function __construct(
        private CurlClient $client,
        private int $ambiente
    ) {
        // ...
    }

    /**
     * Exemplo: consulta NFSe por chave
     */
    public function consultarNfseChave(string $chave): array|string|null
    {
        $baseUrl = EnvironmentConfig::getBaseUrl($this->ambiente);
        $url = $baseUrl . '/nfse/' . $chave;

        $response = $this->client->get($url);

        // Tenta decodificar JSON
        $json = json_decode($response, true);
        if (is_array($json)) {
            return $json;
        }
        return $response;
    }

    /**
     * Exemplo: consulta DPS por chave
     */
    public function consultarDpsChave(string $chave): array|string|null
    {
        $baseUrl = EnvironmentConfig::getBaseUrl($this->ambiente);
        $url = $baseUrl . '/dps/' . $chave;

        $response = $this->client->get($url);
        $json = json_decode($response, true);
        if (is_array($json)) {
            return $json;
        }
        return $response;
    }
}
