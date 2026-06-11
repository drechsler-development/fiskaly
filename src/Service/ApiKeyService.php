<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Http\AuthenticatedHttpClient;
use DD\Fiskaly\Http\Response;
use Random\RandomException;

final class ApiKeyService extends AbstractService {

	/**
	 * @param AuthenticatedHttpClient $client
	 */
	public function __construct (AuthenticatedHttpClient $client) {

		parent::__construct ($client);

	}

	/**
	 * @param string              $organizationId
	 * @param array<string,mixed> $filter
	 *
	 * @return Response

	 * @throws RandomException
	 */
	public function ListApiKeys (string $organizationId, array $filter = []): Response {

		$replacements = [
			'organization_id' => $organizationId,
		];

		return $this->SendHttpRequest ('GET', $this->Path ('/organizations/{organization_id}/api-keys', $replacements), $filter);

	}

	/**
	 * @param string $organizationId
	 * @param array  $body
	 *
	 * @return Response

	 * @throws RandomException
	 */
	public function CreateApiKey (string $organizationId, array $body): Response {

		$replacements = [
			'organization_id' => $organizationId,
		];

		return $this->SendHttpRequest ('POST', $this->Path ('/organizations/{organization_id}/api-keys', $replacements),$body);

	}

	/**
	 * @param string $organizationId
	 * @param string $keyId
	 *
	 * @return Response

	 * @throws RandomException
	 */
	public function RetrieveApiKey (string $organizationId, string $keyId): Response {

		$replacements = [
			'organization_id' => $organizationId,
			'key_id'          => $keyId,
		];

		return $this->SendHttpRequest ('GET', $this->Path ('/organizations/{organization_id}/api-keys/{key_id}', $replacements));

	}

	/**
	 * @param string              $organizationId
	 * @param string              $keyId
	 * @param array<string,mixed> $data
	 *
	 * @return Response

	 * @throws RandomException
	 */
	public function UpdateApiKey (string $organizationId, string $keyId, array $data): Response {

		$replacements = [
			'organization_id' => $organizationId,
			'key_id'          => $keyId,
		];

		return $this->SendHttpRequest ('PATCH', $this->Path ('/organizations/{organization_id}/api-keys/{key_id}', $replacements),[], $data);

	}

	/**
	 * @param string $organizationId
	 * @param string $keyId
	 *
	 * @return Response

	 * @throws RandomException
	 */
	public function DeleteApiKey (string $organizationId, string $keyId): Response {

		$replacements = [
			'organization_id' => $organizationId,
			'key_id'          => $keyId,
		];

		return $this->SendHttpRequest ('DELETE', $this->Path ('/organizations/{organization_id}/api-keys/{key_id}', $replacements));

	}
}
