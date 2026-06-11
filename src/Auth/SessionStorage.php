<?php

declare(strict_types=1);

namespace DD\Fiskaly\Auth;

use DD\Fiskaly\Exception\FiskalyException;

final class SessionStorage implements IStorageInterface {

	private string $sessionKey;

	/**
	 * @param string $sessionKey
	 */
	public function __construct (string $sessionKey = 'fiskaly_token_data') {

		$this->sessionKey = $sessionKey;
		$this->EnsureSessionStarted ();

	}

	/**
	 * @return array|null
	 */
	public function GetTokenData (): ?array {

		$this->EnsureSessionStarted ();
		$tokenData = $_SESSION[$this->sessionKey] ?? null;

		return is_array ($tokenData) ? $tokenData : null;

	}

	/**
	 * @param array $tokenData
	 *
	 * @return void
	 */
	public function SetTokenData (array $tokenData): void {

		$this->EnsureSessionStarted ();
		$_SESSION[$this->sessionKey] = $tokenData;

	}

	/**
	 * @return void
	 */
	public function Clear (): void {

		$this->EnsureSessionStarted ();
		unset($_SESSION[$this->sessionKey]);

	}

	/**
	 * @return void
	 */
	private function EnsureSessionStarted (): void {

		if (session_status () === PHP_SESSION_ACTIVE) {
			return;
		}

		if (session_status () === PHP_SESSION_NONE) {
			@session_start ();
		}

		if (session_status () !== PHP_SESSION_ACTIVE) {
			throw new FiskalyException('PHP-Session could not be started. Please ensure that session_start() is called before using SessionTokenStorage.');
		}

	}
}
