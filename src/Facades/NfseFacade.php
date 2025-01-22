<?php

declare(strict_types=1);

namespace Cesarlopes\NFSePHP\Facades;

use stdClass;
use Cesarlopes\NFSePHP\Infrastructure\Http\CurlClient;
use Cesarlopes\NFSePHP\Services\ConsultaService;
use Cesarlopes\NFSePHP\Services\EmissaoService;
use Cesarlopes\NFSePHP\Services\EventoService;

/**
 * Facade que reúne operações de NFSe em um único ponto de acesso.
 */
class NfseFacade
{
    private ConsultaService $consulta;
    private EmissaoService $emissao;
    private EventoService $evento;

    public function __construct(
        private CurlClient $client,
        private int $ambiente = 2
    ) {
        $this->consulta = new ConsultaService($client, $ambiente);
        $this->emissao  = new EmissaoService($client, $ambiente);
        $this->evento   = new EventoService($client, $ambiente);
    }

    /**
     * Consulta NFSe pela chave
     */
    public function consultarNfseChave(string $chave): array|string|null
    {
        return $this->consulta->consultarNfseChave($chave);
    }

    /**
     * Emite a DPS (monta e envia)
     */
    public function emitirDps(stdClass $dados): array|string
    {
        return $this->emissao->emitirDps($dados);
    }

    /**
     * Cancela NFSe etc.
     */
    public function cancelaNfse(stdClass $std): array|string
    {
        return $this->evento->cancelaNfse($std);
    }

    // ... e assim por diante para outros métodos
}
