<?php

declare(strict_types=1);

namespace Cesarlopes\NFSePHP\Infrastructure\Environment;

/**
 * Fornece URLs ou configurações para produção/homologação (ou outros ambientes).
 */
class EnvironmentConfig
{
    public const URL_PRODUCAO = 'https://sefin.nfse.gov.br/sefinnacional';
    public const URL_HOMOLOGACAO = 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional';

    /**
     * Retorna a URL base do ambiente.
     *
     * @param int $ambiente 1=Produção, 2=Homologação
     * @return string
     */
    public static function getBaseUrl(int $ambiente): string
    {
        if ($ambiente === 1) {
            return self::URL_PRODUCAO;
        }
        return self::URL_HOMOLOGACAO;
    }
}
