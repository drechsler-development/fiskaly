<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Http\AuthenticatedHttpClient;
use Random\RandomException;

final class AdminService extends AbstractService {

	/**
	 * @param AuthenticatedHttpClient $client
	 */
	public function __construct (AuthenticatedHttpClient $client) {

		parent::__construct ($client);

	}

	/**
	 * @param string $tssId
	 * @param string $adminPin
	 *
	 * @return array
	 * @throws RandomException
	 */
	public function AuthenticateAdmin (string $tssId, string $adminPin): array {

		$body = ['admin_pin' => $adminPin];
		return $this->Json ('POST', "/tss/$tssId/admin/auth", $body);

	}

	/**
	 * @param string $tssId
	 *
	 * @return array
	 * @throws RandomException
	 */
	public function LogoutAdmin (string $tssId): array {

		return $this->Json ('POST', "/tss/$tssId/admin/logout", []);

	}

	/**
	 * @param string $tssId
	 * @param string $adminPuk
	 * @param string $newAdminPin
	 *
	 * @return array
	 * @throws RandomException
	 */
	public function ChangeAdminPin (string $tssId, string $adminPuk, string $newAdminPin): array {

		$body = [
			'admin_puk'     => $adminPuk,
			'new_admin_pin' => $newAdminPin,
		];

		return $this->Json ('PATCH', "/tss/$tssId/admin", $body);

	}
}
