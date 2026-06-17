<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Http\AuthenticatedHttpClient;
use Random\RandomException;
use DD\Fiskaly\Http\Response;

final class ExportServiceDsfinv extends AbstractService {

    public function __construct (AuthenticatedHttpClient $client) {
        parent::__construct ($client);
    }

    /**
     * @param string $cashRegisterId
     * @param string $exportId
     * @param array $query
     * @return array
     * @throws RandomException
     */
    public function TriggerExport (string $cashRegisterId, string $exportId, array $query = []): array {
        return $this->Json ('POST', $this->Path('/cash_registers/{id}/export/{exportId}', ['id' => $cashRegisterId, 'exportId' => $exportId]), null, $this->BuildQueryString($query));
    }

    /**
     * @param string $cashRegisterId
     * @param string $exportId
     * @return array
     * @throws RandomException
     */
    public function RetrieveExport (string $cashRegisterId, string $exportId): array {
        return $this->Json ('GET', $this->Path('/cash_registers/{id}/export/{exportId}', ['id' => $cashRegisterId, 'exportId' => $exportId]));
    }

    /**
     * Cancel an ongoing export
     *
     * @param string $cashRegisterId
     * @param string $exportId
     * @return array
     * @throws RandomException
     */
    public function CancelExport (string $cashRegisterId, string $exportId): array {
        return $this->Json('POST', $this->Path('/cash_registers/{id}/export/{exportId}/cancel', ['id' => $cashRegisterId, 'exportId' => $exportId]));
    }

    /**
     * Retrieve export metadata
     *
     * @param string $cashRegisterId
     * @param string $exportId
     * @return array
     * @throws RandomException
     */
    public function RetrieveMetadata (string $cashRegisterId, string $exportId): array {
        return $this->Json('GET', $this->Path('/cash_registers/{id}/export/{exportId}/metadata', ['id' => $cashRegisterId, 'exportId' => $exportId]));
    }

    /**
     * Update export metadata
     *
     * @param string $cashRegisterId
     * @param string $exportId
     * @param array $metadata
     * @return array
     * @throws RandomException
     */
    public function UpdateMetadata (string $cashRegisterId, string $exportId, array $metadata): array {
        return $this->Json('PATCH', $this->Path('/cash_registers/{id}/export/{exportId}/metadata', ['id' => $cashRegisterId, 'exportId' => $exportId]), ['metadata' => $metadata]);
    }

    /**
     * @param string $cashRegisterId
     * @param array $query
     * @return array
     * @throws RandomException
     */
    public function ListExports (string $cashRegisterId, array $query = []): array {
        return $this->Json ('GET', $this->Path('/cash_registers/{id}/export', ['id' => $cashRegisterId]), null, $this->BuildQueryString($query));
    }

    /**
     * Save export file to target path
     *
     * @param string $cashRegisterId
     * @param string $exportId
     * @param string $targetFile
     * @return string
     * @throws RandomException
     */
    public function SaveExportFile (string $cashRegisterId, string $exportId, string $targetFile): string {

        $response = $this->SendHttpRequest ('GET', $this->Path('/cash_registers/{id}/export/{exportId}/file', ['id' => $cashRegisterId, 'exportId' => $exportId]));

        $raw = $response->GetRawBody ();
        if ($raw === null) {
            return '';
        }

        // Write raw body to file
        file_put_contents ($targetFile, $raw);

        return $targetFile;
    }

}
