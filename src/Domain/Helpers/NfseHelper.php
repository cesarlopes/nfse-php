<?php

declare(strict_types=1);

namespace Cesarlopes\NFSePHP\Domain\Helpers;

use NFePHP\Common\Certificate;
use NFePHP\Common\Signer;

/**
 * Classe helper para manipular (por exemplo) assinatura de XML,
 * compressão GZip/Base64, etc.
 */
class NfseHelper
{
    /**
     * Assina um XML usando o NFePHP\Common\Signer.
     *
     * @param Certificate $cert
     * @param string $xml Conteúdo XML
     * @param string $tag Nome da tag a ser assinada
     * @param string $mark Nome do atributo que será usado como ID
     * @param string $rootName Nome da tag raiz (usado em Signer::sign)
     * @return string XML assinado
     */
    public static function signXml(
        Certificate $cert,
        string $xml,
        string $tag,
        string $mark,
        string $rootName
    ): string {
        $canonical = [true, false, null, null]; // Exemplo
        return Signer::sign(
            $cert,
            $xml,
            $tag,
            $mark,
            OPENSSL_ALGO_SHA1,
            $canonical,
            $rootName
        );
    }

    /**
     * Compacta e converte em Base64.
     */
    public static function compressBase64(string $content): string
    {
        return base64_encode(gzencode($content));
    }

    /**
     * Decodifica Base64 e descompacta o GZip.
     */
    public static function decompressBase64(string $compressed): string
    {
        return gzdecode(base64_decode($compressed));
    }
}
