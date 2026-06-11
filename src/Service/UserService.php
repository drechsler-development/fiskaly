<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Http\AuthenticatedHttpClient;
use DD\Fiskaly\Http\Response;
use Random\RandomException;

class UserService extends AbstractService {

	/**
	 * @param AuthenticatedHttpClient $client
	 */
	public function __construct (AuthenticatedHttpClient $client) {
		parent::__construct ($client);
	}

	/**
	 * @param string $organizationId
	 * @param array  $filter
	 *
	 * @return Response
	 * @throws RandomException	 */
	public function ListUsers (string $organizationId, array $filter = []): Response {
		return $this->SendHttpRequest ('GET', $this->Path ('/organizations/{organization_id}/users', [
			'organization_id' => $organizationId,
		]),                            $filter);
	}

	/**
	 * @param string      $organizationId
	 * @param string      $email
	 * @param string|null $firstName
	 * @param string|null $lastName
	 *
	 * @return Response
	 * @throws RandomException	 */
	public function InviteUser (string $organizationId, string $email, ?string $firstName = null, ?string $lastName = null): Response {

		$user = [
			'email'      => $email,
			'first_name' => $firstName,
			'last_name'  => $lastName,
		];

		return $this->SendHttpRequest ('POST', $this->Path ('/organizations/{organization_id}/users', [
			'organization_id' => $organizationId,
		]), $user, []);
	}

	/**
	 * @param string $organizationId
	 * @param string $userId
	 *
	 * @return Response
	 * @throws RandomException	 */
	public function DeleteUser (string $organizationId, string $userId): Response {
		return $this->SendHttpRequest ('DELETE', $this->Path ('/organizations/{organization_id}/users/{user_id}', [
			'organization_id' => $organizationId,
			'user_id'         => $userId,
		]));
	}
}
