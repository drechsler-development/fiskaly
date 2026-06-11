<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Http\Response;
use Random\RandomException;

final class OrganizationService extends AbstractService {

	/**
	 * @param array $filter
	 *
	 * @return Response
	 * @throws RandomException	 */
	public function ListOrganizations (array $filter = []): Response {
		return $this->SendHttpRequest ('GET', '/organizations', $filter);
	}

	/**
	 * @param array $body
	 *
	 * @return Response
	 * @throws RandomException	 */
	public function CreateOrganization (array $body): Response {
		return $this->SendHttpRequest ('POST', '/organizations', $body);
	}

	/**
	 * @param string $organizationId
	 *
	 * @return Response
	 * @throws RandomException	 */
	public function RetrieveOrganization (string $organizationId): Response {
		return $this->SendHttpRequest ('GET', $this->Path ('/organizations/{organization_id}', [
			'organization_id' => $organizationId,
		]));
	}

	/**
	 * @param string $organizationId
	 * @param array  $data
	 *
	 * @return Response
	 * @throws RandomException	 */
	public function UpdateOrganization (string $organizationId, array $data): Response {
		return $this->SendHttpRequest ('PATCH', $this->Path ('/organizations/{organization_id}', [
			'organization_id' => $organizationId,
		]),                            [], $data);
	}

	/**
	 * @param string $organizationId
	 *
	 * @return Response
	 * @throws RandomException	 */
	public function DeleteOrganization (string $organizationId): Response {
		return $this->SendHttpRequest ('DELETE', $this->Path ('/organizations/{organization_id}', [
			'organization_id' => $organizationId,
		]));
	}

	/**
	 * @param string $organizationId
	 * @param string $env
	 *
	 * @return Response
	 * @throws RandomException	 */
	public function EnableEnvironment (string $organizationId, string $env = 'TEST'): Response {
		return $this->SendHttpRequest ('POST', $this->Path ('/organizations/{organization_id}/enable-env', [
			'organization_id' => $organizationId,
		]),                            [], [
			                               'env' => $env,
		                               ]);
	}
}
