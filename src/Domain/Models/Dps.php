<?php

declare(strict_types=1);

namespace Cesarlopes\NFSePHP\Domain\Models;

use DOMException;
use DOMNode;
use NFePHP\Common\DOMImproved as Dom;
use stdClass;

/**
 * Classe que constrói o XML da DPS a partir de um stdClass,
 * reproduzindo a lógica do seu projeto original.
 *
 * Exemplo de uso:
 *    $dps = new Dps($std);
 *    $xml = $dps->render(); // retorna string XML
 */
class Dps
{
    /**
     * @var stdClass|null
     */
    public $std;

    /**
     * @var DOMNode
     */
    protected $dps;

    /**
     * @var DOMNode
     */
    protected $evento;

    /**
     * @var Dom
     */
    protected $dom;

    private string $dpsId;
    private string $preId;

    /**
     * @param stdClass|null $std
     * @throws DOMException
     */
    public function __construct(stdClass $std = null)
    {
        $this->init($std);
        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
    }

    /**
     * Renderiza o XML principal da DPS.
     * @param stdClass|null $std Se quiser sobrescrever o std original, passar aqui
     * @return string XML gerado
     */
    public function render(stdClass $std = null): string
    {
        // Reinicia DOM se já tinha nós
        if ($this->dom->hasChildNodes()) {
            $this->dom = new Dom('1.0', 'UTF-8');
            $this->dom->preserveWhiteSpace = false;
            $this->dom->formatOutput = false;
        }
        $this->init($std);

        // Cria elemento root <DPS ...>
        $this->dps = $this->dom->createElement('DPS');
        $this->dps->setAttribute('versao', '1.00');
        $this->dps->setAttribute('xmlns', 'http://www.sped.fazenda.gov.br/nfse');

        // <infDPS ...>
        $infdps_inner = $this->dom->createElement('infDPS');
        $infdps_inner->setAttribute('Id', $this->generateId());

        // Popula tags (exemplos: tpAmb, dhEmi etc.)
        $this->dom->addChild(
            $infdps_inner,
            'tpAmb',
            $this->std->infdps->tpamb,
            true
        );
        $this->dom->addChild(
            $infdps_inner,
            'dhEmi',
            $this->std->infdps->dhemi,
            true
        );
        $this->dom->addChild(
            $infdps_inner,
            'verAplic',
            $this->std->infdps->veraplic,
            true
        );
        $this->dom->addChild(
            $infdps_inner,
            'serie',
            $this->std->infdps->serie,
            true
        );
        $this->dom->addChild(
            $infdps_inner,
            'nDPS',
            $this->std->infdps->ndps,
            true
        );
        $this->dom->addChild(
            $infdps_inner,
            'dCompet',
            $this->std->infdps->dcompet,
            true
        );
        $this->dom->addChild(
            $infdps_inner,
            'tpEmit',
            $this->std->infdps->tpemit,
            true
        );
        $this->dom->addChild(
            $infdps_inner,
            'cLocEmi',
            $this->std->infdps->clocemi,
            true
        );

        // Substituição (subst)
        if (isset($this->std->infdps->subst)) {
            $subst_inner = $this->dom->createElement('subst');
            $infdps_inner->appendChild($subst_inner);
            $this->dom->addChild(
                $subst_inner,
                'chSubstda',
                $this->std->infdps->subst->chsubstda,
                true
            );
            $this->dom->addChild(
                $subst_inner,
                'cMotivo',
                $this->std->infdps->subst->cmotivo,
                true
            );
            $this->dom->addChild(
                $subst_inner,
                'xMotivo',
                $this->std->infdps->subst->xmotivo,
                true
            );
        }

        // Prestador (prest)
        if (isset($this->std->infdps->prest)) {
            $prest_inner = $this->dom->createElement('prest');
            $infdps_inner->appendChild($prest_inner);

            if (isset($this->std->infdps->prest->cnpj)) {
                $this->dom->addChild(
                    $prest_inner,
                    'CNPJ',
                    $this->std->infdps->prest->cnpj,
                    true
                );
            }
            if (isset($this->std->infdps->prest->cpf)) {
                $this->dom->addChild(
                    $prest_inner,
                    'CPF',
                    $this->std->infdps->prest->cpf,
                    true
                );
            }
            // ... e assim por diante

            if (isset($this->std->infdps->prest->end)) {
                $end_inner = $this->dom->createElement('end');
                $prest_inner->appendChild($end_inner);

                if (isset($this->std->infdps->prest->end->endnac)) {
                    $endnac_inner = $this->dom->createElement('endNac');
                    $end_inner->appendChild($endnac_inner);
                    $this->dom->addChild(
                        $endnac_inner,
                        'cMun',
                        $this->std->infdps->prest->end->endnac->cmun,
                        true
                    );
                    $this->dom->addChild(
                        $endnac_inner,
                        'CEP',
                        $this->std->infdps->prest->end->endnac->cep,
                        true
                    );
                } elseif (isset($this->std->infdps->prest->end->endext)) {
                    // ... cPais, cEndPost ...
                }

                $this->dom->addChild(
                    $end_inner,
                    'xLgr',
                    $this->std->infdps->prest->end->xlgr,
                    true
                );
                $this->dom->addChild(
                    $end_inner,
                    'nro',
                    $this->std->infdps->prest->end->nro,
                    true
                );
                if (isset($this->std->infdps->prest->end->xcpl)) {
                    $this->dom->addChild(
                        $end_inner,
                        'xCpl',
                        $this->std->infdps->prest->end->xcpl,
                        false
                    );
                }
                $this->dom->addChild(
                    $end_inner,
                    'xBairro',
                    $this->std->infdps->prest->end->xbairro,
                    true
                );
            }

            $this->dom->addChild(
                $prest_inner,
                'fone',
                $this->std->infdps->prest->fone ?? '',
                false
            );
            $this->dom->addChild(
                $prest_inner,
                'email',
                $this->std->infdps->prest->email ?? '',
                false
            );

            // <regTrib>
            $regtrib_inner = $this->dom->createElement('regTrib');
            $prest_inner->appendChild($regtrib_inner);
            $this->dom->addChild(
                $regtrib_inner,
                'opSimpNac',
                $this->std->infdps->prest->regtrib->opsimpnac,
                true
            );
            $this->dom->addChild(
                $regtrib_inner,
                'regEspTrib',
                $this->std->infdps->prest->regtrib->regesptrib,
                true
            );
            if (isset($this->std->infdps->prest->regtrib->regaptribsn)) {
                $this->dom->addChild(
                    $regtrib_inner,
                    'regApTribSN',
                    $this->std->infdps->prest->regtrib->regaptribsn,
                    false
                );
            }
        }

        // Tomador <toma>
        if (isset($this->std->infdps->toma)) {
            $toma_inner = $this->dom->createElement('toma');
            $infdps_inner->appendChild($toma_inner);
            if (isset($this->std->infdps->toma->cnpj)) {
                $this->dom->addChild($toma_inner, 'CNPJ', $this->std->infdps->toma->cnpj, true);
            }
            if (isset($this->std->infdps->toma->cpf)) {
                $this->dom->addChild($toma_inner, 'CPF', $this->std->infdps->toma->cpf, true);
            }
            // ...
            $this->dom->addChild(
                $toma_inner,
                'xNome',
                $this->std->infdps->toma->xnome,
                true
            );

            if (isset($this->std->infdps->toma->end)) {
                $end_inner = $this->dom->createElement('end');
                $toma_inner->appendChild($end_inner);

                if (isset($this->std->infdps->toma->end->endnac)) {
                    $endnac_inner = $this->dom->createElement('endNac');
                    $end_inner->appendChild($endnac_inner);
                    $this->dom->addChild(
                        $endnac_inner,
                        'cMun',
                        $this->std->infdps->toma->end->endnac->cmun,
                        true
                    );
                    $this->dom->addChild(
                        $endnac_inner,
                        'CEP',
                        $this->std->infdps->toma->end->endnac->cep,
                        true
                    );
                } elseif (isset($this->std->infdps->toma->end->endext)) {
                    // ...
                }

                $this->dom->addChild(
                    $end_inner,
                    'xLgr',
                    $this->std->infdps->toma->end->xlgr,
                    true
                );
                $this->dom->addChild(
                    $end_inner,
                    'nro',
                    $this->std->infdps->toma->end->nro,
                    true
                );
                if (isset($this->std->infdps->toma->end->xcpl)) {
                    $this->dom->addChild(
                        $end_inner,
                        'xCpl',
                        $this->std->infdps->toma->end->xcpl,
                        false
                    );
                }
                $this->dom->addChild(
                    $end_inner,
                    'xBairro',
                    $this->std->infdps->toma->end->xbairro,
                    true
                );
            }

            $this->dom->addChild(
                $toma_inner,
                'fone',
                $this->std->infdps->toma->fone ?? '',
                false
            );
            $this->dom->addChild(
                $toma_inner,
                'email',
                $this->std->infdps->toma->email ?? '',
                false
            );
        }

        // <serv>
        $serv_inner = $this->dom->createElement('serv');
        $infdps_inner->appendChild($serv_inner);

        // <locPrest>
        $locprest_inner = $this->dom->createElement('locPrest');
        $serv_inner->appendChild($locprest_inner);
        $this->dom->addChild(
            $locprest_inner,
            'cLocPrestacao',
            $this->std->infdps->serv->locprest->clocprestacao,
            true
        );

        // <cServ>
        $cserv_inner = $this->dom->createElement('cServ');
        $serv_inner->appendChild($cserv_inner);
        $this->dom->addChild(
            $cserv_inner,
            'cTribNac',
            $this->std->infdps->serv->cserv->ctribnac,
            true
        );
        if (isset($this->std->infdps->serv->cserv->ctribmun)) {
            $this->dom->addChild(
                $cserv_inner,
                'cTribMun',
                $this->std->infdps->serv->cserv->ctribmun,
                true
            );
        }
        $this->dom->addChild(
            $cserv_inner,
            'xDescServ',
            $this->std->infdps->serv->cserv->xdescserv,
            true
        );
        if (isset($this->std->infdps->serv->cserv->cintcontrib)) {
            $this->dom->addChild(
                $cserv_inner,
                'cIntContrib',
                $this->std->infdps->serv->cserv->cintcontrib,
                true
            );
        }

        // <valores>
        $valores_inner = $this->dom->createElement('valores');
        $infdps_inner->appendChild($valores_inner);

        // <vServPrest>
        $vservprest_inner = $this->dom->createElement('vServPrest');
        $valores_inner->appendChild($vservprest_inner);
        $this->dom->addChild(
            $vservprest_inner,
            'vServ',
            $this->std->infdps->valores->vservprest->vserv,
            true
        );

        // <trib>
        $trib_inner = $this->dom->createElement('trib');
        $valores_inner->appendChild($trib_inner);

        $tribmun_inner = $this->dom->createElement('tribMun');
        $trib_inner->appendChild($tribmun_inner);

        $this->dom->addChild(
            $tribmun_inner,
            'tribISSQN',
            $this->std->infdps->valores->trib->tribmun->tribissqn,
            true
        );
        if (isset($this->std->infdps->valores->trib->tribmun->tpretissqn)) {
            $this->dom->addChild(
                $tribmun_inner,
                'tpRetISSQN',
                $this->std->infdps->valores->trib->tribmun->tpretissqn,
                true
            );
        }

        // <totTrib>
        $tottrib_inner = $this->dom->createElement('totTrib');
        $trib_inner->appendChild($tottrib_inner);
        $this->dom->addChild(
            $tottrib_inner,
            'indTotTrib',
            $this->std->infdps->valores->trib->tottrib->indtottrib,
            true
        );

        // Junta tudo no root <DPS>
        $this->dps->appendChild($infdps_inner);
        $this->dom->appendChild($this->dps);

        // Retorna como string
        return $this->dom->saveXML();
    }

    /**
     * Renderiza evento (ex.: cancelamento).
     * Mantido caso seu código também use para cancelamento etc.
     */
    public function renderEvento(stdClass $std = null): string
    {
        if ($this->dom->hasChildNodes()) {
            $this->dom = new Dom('1.0', 'UTF-8');
            $this->dom->preserveWhiteSpace = false;
            $this->dom->formatOutput = false;
        }

        $this->init($std);
        $this->evento = $this->dom->createElement('pedRegEvento');
        $this->evento->setAttribute('versao', '1.00');
        $this->evento->setAttribute('xmlns', 'http://www.sped.fazenda.gov.br/nfse');

        $infpedreg_inner = $this->dom->createElement('infPedReg');
        $infpedreg_inner->setAttribute('Id', $this->generatePre());

        $this->dom->addChild(
            $infpedreg_inner,
            'tpAmb',
            $this->std->infpedreg->tpamb,
            true
        );
        $this->dom->addChild(
            $infpedreg_inner,
            'verAplic',
            $this->std->infpedreg->veraplic,
            true
        );
        $this->dom->addChild(
            $infpedreg_inner,
            'dhEvento',
            $this->std->infpedreg->dhevento,
            true
        );
        if (isset($this->std->infpedreg->cnpjautor)) {
            $this->dom->addChild(
                $infpedreg_inner,
                'CNPJAutor',
                $this->std->infpedreg->cnpjautor,
                true
            );
        }
        if (isset($this->std->infpedreg->cpfautor)) {
            $this->dom->addChild(
                $infpedreg_inner,
                'CPFAutor',
                $this->std->infpedreg->cpfautor,
                true
            );
        }
        $this->dom->addChild(
            $infpedreg_inner,
            'chNFSe',
            $this->std->infpedreg->chnfse,
            true
        );
        $this->dom->addChild(
            $infpedreg_inner,
            'nPedRegEvento',
            $this->std->npedregevento,
            true
        );

        // Se houver e101101 etc.
        if (isset($this->std->infpedreg->e101101)) {
            $e101101_inner = $this->dom->createElement('e101101');
            $infpedreg_inner->appendChild($e101101_inner);
            $this->dom->addChild($e101101_inner, 'xDesc', $this->std->infpedreg->e101101->xdesc, true);
            $this->dom->addChild($e101101_inner, 'cMotivo', $this->std->infpedreg->e101101->cmotivo, true);
            $this->dom->addChild($e101101_inner, 'xMotivo', $this->std->infpedreg->e101101->xmotivo, true);
        }

        $this->evento->appendChild($infpedreg_inner);
        $this->dom->appendChild($this->evento);

        return $this->dom->saveXML();
    }

    public function setFormatOutput(bool $formatOutput): void
    {
        $this->dom->formatOutput = $formatOutput;
    }

    public function setStd(stdClass $std): void
    {
        $this->init($std);
    }

    // ~~~~~ Métodos privados ~~~~~ //

    private function init(stdClass $dps = null): void
    {
        if (!empty($dps)) {
            $this->std = $this->propertiesToLower($dps);
            if (empty($this->std->version)) {
                $this->std->version = '1.00';
            }
        }
    }

    /**
     * Muda todas propriedades da stdClass para minúsculas
     */
    public static function propertiesToLower(stdClass $data): stdClass
    {
        $properties = get_object_vars($data);
        $clone = new stdClass();
        foreach ($properties as $key => $value) {
            if ($value instanceof stdClass) {
                $value = self::propertiesToLower($value);
            }
            $newKey = strtolower($key);
            $clone->{$newKey} = $value;
        }
        return $clone;
    }

    private function generateId(): string
    {
        // Exemplo de construção de ID
        $string = 'DPS';
        $string .= substr($this->std->infdps->clocemi ?? '0000000', 0, 7);
        // Se tiver CNPJ, anexa '2', senão '1'
        $string .= isset($this->std->infdps->prest->cnpj) ? 2 : 1;
        // Joga a inscrição, completando
        $inscricao = $this->std->infdps->prest->cnpj ?? $this->std->infdps->prest->cpf ?? '00000000000000';
        $string .= str_pad($inscricao, 14, '0', STR_PAD_LEFT);
        $string .= str_pad((string)$this->std->infdps->serie, 5, '0', STR_PAD_LEFT);
        $string .= str_pad((string)$this->std->infdps->ndps, 15, '0', STR_PAD_LEFT);

        $this->dpsId = $string;
        return $string;
    }

    private function generatePre(): string
    {
        $string = 'PRE';
        $string .= $this->std->infpedreg->chnfse;
        $string .= $this->codigoEvento();
        $string .= str_pad($this->std->npedregevento, 3, '0', STR_PAD_LEFT);

        $this->preId = $string;
        return $string;
    }

    private function codigoEvento(): string
    {
        $codigo = '000000';
        if (isset($this->std->infpedreg->e101101)) {
            $codigo = '101101';
        } elseif (isset($this->std->infpedreg->e105102)) {
            $codigo = '105102';
        }
        return $codigo;
    }

    public function getDpsId(): string
    {
        return $this->dpsId;
    }

    public function getEventoId(): string
    {
        return $this->preId;
    }
}
