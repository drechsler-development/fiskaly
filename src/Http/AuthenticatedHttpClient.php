<?php

declare(strict_types=1);

namespace DD\Fiskaly\Http;

use DD\Fiskaly\Auth\IStorageInterface;
use DD\Fiskaly\Configuration\Configuration;
use DD\Fiskaly\Exception\AuthenticationException;
use Random\RandomException;

final class AuthenticatedHttpClient {

	private HttpClient        $httpClient;
	private IStorageInterface $tokenStorage;

	/**
	 * @param Configuration     $configuration
	 * @param IStorageInterface $tokenStorage
	 */
	public function __construct (Configuration $configuration, IStorageInterface $tokenStorage) {

		$this->httpClient   = new HttpClient($configuration);
		$this->tokenStorage = $tokenStorage;

	}

	/**
	 * @param string     $method
	 * @param string     $path
	 * @param array|null $body
	 * @param array      $query
	 * @param array      $headers
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function Request (string $method, string $path, ?array $body = null, array $query = [], array $headers = []): Response {

		return $this->httpClient->Request ($method, $path, $body, $query, $this->BuildHeaders($headers));

	}

	/**
	 * @param string $method
	 * @param string $path
	 * @param array  $query
	 * @param array  $headers
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function RequestRaw (string $method, string $path, array $query = [], array $headers = []): Response {

		return $this->httpClient->RequestRaw ($method, $path, $query, $this->BuildHeaders ($headers));

	}

	/**
	 * @return string
	 */
	private function GetAccessToken (): string {
		$tokenData   = $this->tokenStorage->GetTokenData ();
		$accessToken = $tokenData['access_token'] ?? null;

		if (!is_string ($accessToken) || $accessToken === '') {
			throw new AuthenticationException('Es ist kein Access Token vorhanden. Bitte zuerst Auth()->AuthenticateWithApiKey(...) ausführen.', 401);
		}

		return $accessToken;
	}

	/**
	 * @param array $headers
	 *
	 * @return array
	 */
	private function BuildHeaders (array $headers): array {

		$token = $this->GetAccessToken ();
		$authHeader = ['Authorization' => 'Bearer ' . $token];

		return array_merge ($authHeader, $headers);


	}
}
