<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Http\AuthenticatedHttpClient;
use Random\RandomException;

final class ClientService extends AbstractService {

	/**
	 * @param AuthenticatedHttpClient $client
	 */
	public function __construct (AuthenticatedHttpClient $client) {

		parent::__construct ($client);

	}

	/**
	 * @param string $tssId
	 * @param string $clientId
	 * @param string $serialNumber
	 * @param array  $metadata
	 *
	 * @return array
	 * @throws RandomException	 */
	public function CreateClient (string $tssId, string $clientId, string $serialNumber, array $metadata = []): array {
		$body = ['serial_number' => $serialNumber];
		if (!empty($metadata)) {
			$body['metadata'] = $metadata;
		}
		return $this->Json ('PUT', "/api/v2/tss/$tssId/client/$clientId", $body);
	}

	/**
	 * @param string $tssId
	 * @param string $clientId
	 *
	 * @return array
	 * @throws RandomException	 */
	public function RetrieveClient (string $tssId, string $clientId): array {
		return $this->Json ('GET', "/api/v2/tss/$tssId/client/$clientId");
	}

	/**
	 * @param string $tssId
	 * @param string $clientId
	 * @param string $state
	 * @param array  $metadata
	 *
	 * @return array
	 * @throws RandomException	 */
	public function UpdateClient (string $tssId, string $clientId, string $state, array $metadata = []): array {
		$body = ['state' => $state];
		if (!empty($metadata)) {
			$body['metadata'] = $metadata;
		}
		return $this->Json ('PATCH', "/api/v2/tss/$tssId/client/$clientId", $body);
	}

	/**
	 * @param string $tssId
	 * @param string $clientId
	 *
	 * @return array
	 * @throws RandomException
	 */
	public function RegisterClient (string $tssId, string $clientId): array {
		return $this->UpdateClient ($tssId, $clientId, 'REGISTERED');
	}

	/**
	 * @param string $tssId
	 * @param string $clientId
	 *
	 * @return array
	 * @throws RandomException
	 */
	public function DeregisterClient (string $tssId, string $clientId): array {
		return $this->UpdateClient ($tssId, $clientId, 'DEREGISTERED');
	}

	/**
	 * @param string $tssId
	 * @param array  $query
	 *
	 * @return array
	 * @throws RandomException	 */
	public function ListClients (string $tssId, array $query = []): array {
		return $this->Json ('GET', "/api/v2/tss/$tssId/client", null, $query);
	}

	/**
	 * @param array $query
	 *
	 * @return array
	 * @throws RandomException	 */
	public function ListAllClients (array $query = []): array {
		return $this->Json ('GET', '/api/v2/client', null, $query);
	}

	/**
	 * @param string $tssId
	 * @param string $clientId
	 *
	 * @return array
	 * @throws RandomException	 */
	public function RetrieveMetadata (string $tssId, string $clientId): array {
		return $this->Json ('GET', "/api/v2/tss/$tssId/client/$clientId/metadata");
	}

	/**
	 * @param string $tssId
	 * @param string $clientId
	 * @param array  $metadata
	 *
	 * @return array
	 * @throws RandomException	 */
	public function UpdateMetadata (string $tssId, string $clientId, array $metadata): array {
		return $this->Json ('PATCH', "/api/v2/tss/$tssId/client/$clientId/metadata", ['metadata' => $metadata]);
	}

}
