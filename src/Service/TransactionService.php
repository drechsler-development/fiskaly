<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Http\AuthenticatedHttpClient;
use Random\RandomException;

final class TransactionService extends AbstractService {

	/**
	 * @param AuthenticatedHttpClient $client
	 */
	public function __construct (AuthenticatedHttpClient $client) {
		parent::__construct ($client);
	}

	/**
	 * @param string $tssId
	 * @param string $txId
	 * @param string $clientId
	 * @param array  $schema
	 * @param array  $metadata
	 * @param int    $txRevision
	 *
	 * @return array
	 * @throws RandomException
	 */
	public function StartTransaction (string $tssId, string $txId, string $clientId, array $schema = [], array $metadata = [], int $txRevision = 1): array {
		return $this->UpsertTransaction ($tssId, $txId, [
			'state'     => 'ACTIVE',
			'client_id' => $clientId,
			'schema'    => $schema,
			'metadata'  => $metadata,
		],                               $txRevision);
	}

	/**
	 * @param string   $tssId
	 * @param string   $txIdOrNumber
	 * @param string   $clientId
	 * @param array    $schema
	 * @param array    $metadata
	 * @param int|null $txRevision
	 *
	 * @return array
	 * @throws RandomException
	 */
	public function FinishTransaction (string $tssId, string $txIdOrNumber, string $clientId, array $schema, array $metadata = [], ?int $txRevision = null): array {
		return $this->UpsertTransaction ($tssId, $txIdOrNumber, [
			'state'     => 'FINISHED',
			'client_id' => $clientId,
			'schema'    => $schema,
			'metadata'  => $metadata,
		],                               $txRevision);
	}

	/**
	 * @param string   $tssId
	 * @param string   $txIdOrNumber
	 * @param string   $clientId
	 * @param array    $schema
	 * @param array    $metadata
	 * @param int|null $txRevision
	 *
	 * @return array
	 * @throws RandomException
	 */
	public function CancelTransaction (string $tssId, string $txIdOrNumber, string $clientId, array $schema = [], array $metadata = [], ?int $txRevision = null): array {
		return $this->UpsertTransaction ($tssId, $txIdOrNumber, [
			'state'     => 'CANCELLED',
			'client_id' => $clientId,
			'schema'    => $schema,
			'metadata'  => $metadata,
		],                               $txRevision);
	}

	/**
	 * @param string   $tssId
	 * @param string   $txIdOrNumber
	 * @param array    $body
	 * @param int|null $txRevision
	 *
	 * @return array
	 * @throws RandomException	 */
	public function UpsertTransaction (string $tssId, string $txIdOrNumber, array $body, ?int $txRevision = null): array {
		$body  = array_filter ($body, static fn($value): bool => $value !== null && $value !== []);
		$query = $txRevision !== null ? ['tx_revision' => $txRevision] : [];
		return $this->Json ('PUT', "/api/v2/tss/$tssId/tx/$txIdOrNumber", $body, $query);
	}

	/**
	 * @param string $tssId
	 * @param string $txIdOrNumber
	 * @param array  $query
	 *
	 * @return array
	 * @throws RandomException	 */
	public function RetrieveTransaction (string $tssId, string $txIdOrNumber, array $query = []): array {
		return $this->Json ('GET', "/api/v2/tss/$tssId/tx/$txIdOrNumber", null, $query);
	}

	/**
	 * @param string $tssId
	 * @param string $txIdOrNumber
	 * @param array  $query
	 *
	 * @return array
	 * @throws RandomException	 */
	public function RetrieveTransactionLog (string $tssId, string $txIdOrNumber, array $query = []): array {
		return $this->Json ('GET', "/api/v2/tss/$tssId/tx/$txIdOrNumber/log", null, $query);
	}

	/**
	 * @param string $tssId
	 * @param array  $query
	 *
	 * @return array
	 * @throws RandomException	 */
	public function ListTransactions (string $tssId, array $query = []): array {
		return $this->Json ('GET', "/api/v2/tss/$tssId/tx", null, $query);
	}

	/**
	 * @param array $query
	 *
	 * @return array
	 * @throws RandomException	 */
	public function ListAllTransactions (array $query = []): array {
		return $this->Json ('GET', '/api/v2/tx', null, $query);
	}

	/**
	 * @param string $tssId
	 * @param string $clientId
	 * @param array  $query
	 *
	 * @return array
	 * @throws RandomException	 */
	public function ListClientTransactions (string $tssId, string $clientId, array $query = []): array {
		return $this->Json ('GET', "/api/v2/tss/$tssId/client/$clientId/tx", null, $query);
	}

	/**
	 * @param string $tssId
	 * @param string $txIdOrNumber
	 *
	 * @return array
	 * @throws RandomException	 */
	public function RetrieveMetadata (string $tssId, string $txIdOrNumber): array {
		return $this->Json ('GET', "/api/v2/tss/$tssId/tx/$txIdOrNumber/metadata");
	}

	/**
	 * @param string $tssId
	 * @param string $txIdOrNumber
	 * @param array  $metadata
	 *
	 * @return array
	 * @throws RandomException	 */
	public function UpdateMetadata (string $tssId, string $txIdOrNumber, array $metadata): array {
		return $this->Json ('PATCH', "/api/v2/tss/$tssId/tx/$txIdOrNumber/metadata", ['metadata' => $metadata]);
	}

}
