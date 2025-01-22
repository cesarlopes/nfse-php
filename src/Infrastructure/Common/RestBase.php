<?php

declare(strict_types=1);

namespace Cesarlopes\NFSePHP\Infrastructure\Common;

use NFePHP\Common\Certificate;
use NFePHP\Common\Exception\RuntimeException;
use NFePHP\Common\Files;
use NFePHP\Common\Strings;

/**
 * Classe base para manipular o certificado digital e arquivos temporários .pem
 */
class RestBase
{
    protected ?Certificate $certificate = null;
    protected bool $disableCertValidation = false;

    protected string $tempdir = '';   // Caminho completo para a pasta temporária
    protected string $certsdir = '';  // Subpasta onde serão criados os arquivos .pem
    protected ?Files $filesystem = null;

    protected ?string $prifile = null;   // Nome do arquivo .pem com chave privada
    protected ?string $pubfile = null;   // Nome do arquivo .pem com chave pública
    protected ?string $certfile = null;  // Nome do arquivo .pem com chave + certificado

    protected ?string $temppass = null;  // Se quiser exportar privateKey com senha
    public int $waitingTime = 45;        // Tempo (min) para remover arquivos antigos

    public function __construct(?Certificate $certificate = null)
    {
        $this->loadCertificate($certificate);
    }

    /**
     * Seta o certificado e faz checagem de expiração (se habilitado)
     */
    public function loadCertificate(?Certificate $certificate): void
    {
        if ($certificate && !$this->disableCertValidation && $certificate->isExpired()) {
            throw new Certificate\Exception\Expired($certificate);
        }
        $this->certificate = $certificate;
    }

    /**
     * Desativa/ativa validação de expiração
     */
    public function disableCertValidation(bool $flag = true): void
    {
        $this->disableCertValidation = $flag;
    }

    /**
     * Garante que os arquivos .pem existam
     */
    public function ensureKeyFiles(): void
    {
        $this->saveTemporarilyKeyFiles();
    }

    /**
     * Define/Cria a pasta temporária para os .pem
     */
    public function setTemporaryFolder(?string $folderRealPath = null): void
    {
        $mapto = (!empty($this->certificate) && !empty($this->certificate->getCnpj()))
            ? $this->certificate->getCnpj()
            : ($this->certificate ? $this->certificate->getCpf() : '');

        if (empty($mapto)) {
            throw new RuntimeException('Não foi possível determinar CNPJ ou CPF no certificado.');
        }

        if (empty($folderRealPath)) {
            $path = '/nfse-' . $this->uid() . '/' . $mapto . '/';
            $folderRealPath = sys_get_temp_dir() . $path;
        }

        if (!str_ends_with($folderRealPath, '/')) {
            $folderRealPath .= '/';
        }

        $this->tempdir = $folderRealPath;
        $this->filesystem = new Files($this->tempdir);
    }

    /**
     * Salva as chaves do certificado em .pem
     */
    protected function saveTemporarilyKeyFiles(): void
    {
        if (!empty($this->certsdir)) {
            // Já criados
            return;
        }
        if (!$this->certificate) {
            throw new RuntimeException('Certificado não definido.');
        }
        if (!$this->filesystem) {
            $this->setTemporaryFolder();
        }

        // Remove arquivos antigos antes de criar novos
        $this->removeTemporarilyFiles();

        $this->certsdir = 'certs/';
        $this->prifile  = $this->randomName();
        $this->pubfile  = $this->randomName();
        $this->certfile = $this->randomName();

        // A partir de versões recentes do NFePHP, privateKey e publicKey são objetos
        $privateKeyString = (string)$this->certificate->privateKey;
        $publicKeyString  = (string)$this->certificate->publicKey;
        $certificateString = (string)$this->certificate;

        $ret = true;
        $ret &= $this->filesystem->put($this->prifile, $privateKeyString);
        $ret &= $this->filesystem->put($this->pubfile, $publicKeyString);

        // Geralmente, precisamos de um arquivo com (chave privada + certificado)
        $ret &= $this->filesystem->put($this->certfile, $privateKeyString . $certificateString);

        if (!$ret) {
            throw new RuntimeException('Falha ao escrever arquivos PEM na pasta temporária.');
        }
    }

    /**
     * Remove arquivos temporários e antigos
     */
    public function removeTemporarilyFiles(): void
    {
        try {
            if (!$this->filesystem || !$this->certsdir) {
                return;
            }
            // Remove arquivos principais
            if ($this->certfile) {
                $this->filesystem->delete($this->certfile);
            }
            if ($this->prifile) {
                $this->filesystem->delete($this->prifile);
            }
            if ($this->pubfile) {
                $this->filesystem->delete($this->pubfile);
            }

            // Remove arquivos antigos
            $contents = $this->filesystem->listContents($this->certsdir, true);
            $dt = new \DateTime();
            $interval = new \DateInterval('PT' . $this->waitingTime . 'M');
            $interval->invert = 1;
            $limit = $dt->add($interval)->getTimestamp();

            foreach ($contents as $item) {
                if ($item['type'] === 'file') {
                    if ($this->filesystem->has($item['path'])) {
                        $timestamp = $this->filesystem->getTimestamp($item['path']);
                        if ($timestamp < $limit) {
                            $this->filesystem->delete($item['path']);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Em ambientes concorrentes, podemos suprimir ou logar
        }
    }

    protected function randomName(int $size = 10): string
    {
        $name = $this->certsdir . Strings::randomString($size) . '.pem';
        if (!$this->filesystem->has($name)) {
            return $name;
        }
        return $this->randomName($size + 5);
    }

    protected function uid(): string
    {
        return function_exists('posix_getuid') ? (string)posix_getuid() : (string)getmyuid();
    }

    // Getters caso o CurlClient ou outro precise
    public function getCertificate(): ?Certificate
    {
        return $this->certificate;
    }

    public function getTempdir(): string
    {
        return $this->tempdir;
    }

    public function getCertFile(): ?string
    {
        return $this->certfile;
    }

    public function getPriFile(): ?string
    {
        return $this->prifile;
    }

    public function getPubFile(): ?string
    {
        return $this->pubfile;
    }

    public function getTemppass(): ?string
    {
        return $this->temppass;
    }
}
