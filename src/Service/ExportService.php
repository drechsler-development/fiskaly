<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Http\AuthenticatedHttpClient;
use Random\RandomException;

final class ExportService extends AbstractService {

	/**
	 * @param AuthenticatedHttpClient $client
	 */
	public function __construct (AuthenticatedHttpClient $client) {
		parent::__construct ($client);
	}

	/**
	 * @param string $tssId
	 * @param string $exportId
	 * @param array  $query
	 *
	 * @return array
	 * @throws RandomException	 */
	public function TriggerExport (string $tssId, string $exportId, array $query = []): array {
		return $this->Json ('PUT', "/tss/$tssId/export/$exportId", [], $query);
	}

	/**
	 * @param string $tssId
	 * @param string $exportId
	 *
	 * @return array
	 * @throws RandomException	 */
	public function RetrieveExport (string $tssId, string $exportId): array {
		return $this->Json ('GET', "/tss/$tssId/export/$exportId");
	}

	/**
	 * @param string $tssId
	 * @param string $exportId
	 *
	 * @return array
	 * @throws RandomException	 */
	public function CancelExport (string $tssId, string $exportId): array {
		return $this->Json ('DELETE', "/tss/$tssId/export/$exportId");
	}

	/**
	 * @param string $tssId
	 * @param array  $query
	 *
	 * @return array
	 * @throws RandomException	 */
	public function ListExports (string $tssId, array $query = []): array {
		return $this->Json ('GET', "/tss/$tssId/export", null, $query);
	}

	/**
	 * @param array $query
	 *
	 * @return array
	 * @throws RandomException	 */
	public function ListAllExports (array $query = []): array {
		return $this->Json ('GET', '/export', null, $query);
	}

	/**
	 * @param string $tssId
	 * @param string $exportId
	 *
	 * @return string
	 * @throws RandomException
	 */
	public function RetrieveExportFile (string $tssId, string $exportId): string {
		$body = $this->httpClient->RequestRaw ('GET', "/tss/$tssId/export/$exportId/file")->GetRawBody ();
		return $body ?? '';
	}

	/**
	 * @param string $tssId
	 * @param string $exportId
	 * @param string $targetFile
	 *
	 * @return string
	 * @throws RandomException
	 */
	public function SaveExportFile (string $tssId, string $exportId, string $targetFile): string {
		$content   = $this->RetrieveExportFile ($tssId, $exportId);
		$directory = dirname ($targetFile);
		if (!is_dir ($directory)) {
			mkdir ($directory, 0775, true);
		}
		file_put_contents ($targetFile, $content);
		return $targetFile;
	}

	/**
	 * @param string $tssId
	 * @param string $exportId
	 *
	 * @return array
	 * @throws RandomException	 */
	public function RetrieveMetadata (string $tssId, string $exportId): array {
		return $this->Json ('GET', "/tss/$tssId/export/$exportId/metadata");
	}

	/**
	 * @param string $tssId
	 * @param string $exportId
	 * @param array  $metadata
	 *
	 * @return array
	 * @throws RandomException	 */
	public function UpdateMetadata (string $tssId, string $exportId, array $metadata): array {
		return $this->Json ('PATCH', "/tss/$tssId/export/$exportId/metadata", ['metadata' => $metadata]);
	}

}
