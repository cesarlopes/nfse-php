<?php

declare(strict_types=1);

namespace Cesarlopes\NFSePHP\Infrastructure\Http;

use Cesarlopes\NFSePHP\Infrastructure\Common\RestBase;

/**
 * Responsável pelas requisições HTTP usando cURL.
 * Se comunica com o RestBase para obter caminhos .pem e manipular certificado.
 */
class CurlClient
{
    protected RestBase $restBase;

    protected bool $disableSSLVerification = true;
    protected ?string $securityLevel = null;
    protected int $connectionTimeout = 30;
    protected int $timeout = 30;
    protected mixed $httpVersion = CURL_HTTP_VERSION_NONE;

    // Infos da última resposta
    protected string $lastResponseHeaders = '';
    protected string $lastResponseBody    = '';
    protected array $lastInfo             = [];

    public function __construct(RestBase $restBase)
    {
        $this->restBase = $restBase;
    }

    public function setDisableSSLVerification(bool $flag): void
    {
        $this->disableSSLVerification = $flag;
    }

    public function setSecurityLevel(?string $level): void
    {
        $this->securityLevel = $level;
    }

    public function setConnectionTimeout(int $seconds): void
    {
        $this->connectionTimeout = $seconds;
    }

    public function setTimeout(int $seconds): void
    {
        $this->timeout = $seconds;
    }

    public function setHttpVersion(mixed $version): void
    {
        $this->httpVersion = $version;
    }

    /**
     * Retorna o RestBase, se precisar acessar o certificado etc.
     */
    public function getRestBase(): RestBase
    {
        return $this->restBase;
    }

    /**
     * Requisição GET
     */
    public function get(string $url, ?string $data = null): string
    {
        $this->restBase->ensureKeyFiles(); // Gera os .pem se ainda não tiver

        $ch = curl_init($url);
        $this->configureCurl($ch);

        if (!empty($data)) {
            // Se quiser, pode utilizar POST fields ou anexar querystring.
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($ch);
        $this->checkCurlErrors($ch, $response);

        curl_close($ch);
        return $this->lastResponseBody;
    }

    /**
     * Requisição POST
     */
    public function post(string $url, string $data, array $headers = []): string
    {
        $this->restBase->ensureKeyFiles();

        $ch = curl_init($url);
        $this->configureCurl($ch);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $this->checkCurlErrors($ch, $response);

        curl_close($ch);
        return $this->lastResponseBody;
    }

    private function configureCurl($ch): void
    {
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, $this->httpVersion);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);

        if ($this->disableSSLVerification) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        }

        if ($this->securityLevel) {
            curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, $this->securityLevel);
        }

        // Seta arquivo PEM com (chave + cert), ou separado
        $certFile = $this->restBase->getCertFile();
        $priFile  = $this->restBase->getPriFile();

        if ($certFile) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->restBase->getTempdir() . $certFile);
        }
        if ($priFile) {
            curl_setopt($ch, CURLOPT_SSLKEY, $this->restBase->getTempdir() . $priFile);
        }

        $tempPass = $this->restBase->getTemppass();
        if (!empty($tempPass)) {
            curl_setopt($ch, CURLOPT_KEYPASSWD, $tempPass);
        }
    }

    private function checkCurlErrors($ch, string|false $response): void
    {
        if ($response === false) {
            $errorMsg = curl_error($ch);
            $errorNo  = curl_errno($ch);
            throw new RestException("Erro cURL (#{$errorNo}): $errorMsg");
        }

        $info = curl_getinfo($ch);
        $headerSize = $info['header_size'] ?? 0;

        $this->lastInfo = $info;
        $this->lastResponseHeaders = substr($response, 0, $headerSize);
        $this->lastResponseBody    = substr($response, $headerSize);

        $httpCode = $info['http_code'] ?? 0;
        if ($httpCode >= 400) {
            // Inclui o body na mensagem de erro, para debug
            // Se quiser truncar, pode usar substr
            throw new RestException(
                "HTTP $httpCode - Falha na requisição para {$info['url']}\n" .
                "== Response Body ==\n" . $this->lastResponseBody
            );
        }
    }

    public function getLastResponseHeaders(): string
    {
        return $this->lastResponseHeaders;
    }

    public function getLastResponseBody(): string
    {
        return $this->lastResponseBody;
    }

    public function getLastInfo(): array
    {
        return $this->lastInfo;
    }
}
