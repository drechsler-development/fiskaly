<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Http\AuthenticatedHttpClient;
use Random\RandomException;

final class TssService extends AbstractService {
	/**
	 * @param AuthenticatedHttpClient $client
	 */
	public function __construct (AuthenticatedHttpClient $client) {
		parent::__construct ($client);
	}

	/**
	 * @param string $tssId
	 * @param array  $metadata
	 *
	 * @return array
	 * @throws RandomException	 */
	public function CreateTss (string $tssId, array $metadata = []): array {
		$body = empty($metadata) ? [] : ['metadata' => $metadata];
		return $this->Json ('PUT', "/tss/$tssId", $body);
	}

	/**
	 * @param string $tssId
	 *
	 * @return array
	 * @throws RandomException	 */
	public function RetrieveTss (string $tssId): array {
		return $this->Json ('GET', "/tss/$tssId");
	}

	/**
	 * @param string      $tssId
	 * @param string      $state
	 * @param array       $metadata
	 * @param string|null $description
	 *
	 * @return array
	 * @throws RandomException	 */
	public function UpdateTss (string $tssId, string $state, array $metadata = [], ?string $description = null): array {
		$body = ['state' => $state];
		if ($description !== null) {
			$body['description'] = $description;
		}
		if (!empty($metadata)) {
			$body['metadata'] = $metadata;
		}
		return $this->Json ('PATCH', "/tss/$tssId", $body);
	}

	/**
	 * @param string $tssId
	 *
	 * @return array
	 * @throws RandomException
	 */
	public function SetUninitialized (string $tssId): array {
		return $this->UpdateTss ($tssId, 'UNINITIALIZED');
	}

	/**
	 * @param string $tssId
	 *
	 * @return array
	 * @throws RandomException
	 */
	public function SetInitialized (string $tssId): array {
		return $this->UpdateTss ($tssId, 'INITIALIZED');
	}

	/**
	 * @param string $tssId
	 *
	 * @return array
	 * @throws RandomException
	 */
	public function DisableTss (string $tssId): array {
		return $this->UpdateTss ($tssId, 'DISABLED');
	}

	/**
	 * @param array $query
	 *
	 * @return array
	 * @throws RandomException	 */
	public function ListTss (array $query = []): array {
		return $this->Json ('GET', '/tss', null, $query);
	}

	/**
	 * @param string $tssId
	 *
	 * @return array
	 * @throws RandomException	 */
	public function RetrieveMetadata (string $tssId): array {
		return $this->Json ('GET', "/tss/$tssId/metadata");
	}

	/**
	 * @param string $tssId
	 * @param array  $metadata
	 *
	 * @return array
	 * @throws RandomException	 */
	public function UpdateMetadata (string $tssId, array $metadata): array {
		return $this->Json ('PATCH', "/tss/$tssId/metadata", ['metadata' => $metadata]);
	}

}
