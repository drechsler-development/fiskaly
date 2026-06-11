<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Http\AuthenticatedHttpClient;
use DD\Fiskaly\Http\Response;
use Random\RandomException;

abstract class AbstractService {
	protected AuthenticatedHttpClient $httpClient;

	public function __construct (AuthenticatedHttpClient $client) {
		$this->httpClient = $client;
	}

	/**
	 * @param string $path
	 * @param array  $replacements
	 *
	 * @return string
	 */
	protected function Path (string $path, array $replacements = []): string {

		foreach ($replacements as $key => $value) {
			$path = str_replace ('{' . $key . '}', rawurlencode ($value), $path);
		}

		return $path;

	}

	/**
	 * @param string     $method
	 * @param string     $path
	 * @param array|null $body
	 * @param array      $query
	 *
	 * @return array
	 * @throws RandomException
	 */
	protected function Json (string $method, string $path, ?array $body = null, array $query = []): array {

		$response     = $this->SendHttpRequest ($method, $path, $body, $query);
		$responseBody = $response->GetBody ();

		return is_array ($responseBody) ? $responseBody : [];

	}

	/**
	 * @param string     $method
	 * @param string     $path
	 * @param array|null $body
	 * @param array      $query
	 *
	 * @return Response
	 * @throws RandomException
	 */
	protected function SendHttpRequest (string $method, string $path, ?array $body = null, array $query = []): Response {

		return $this->httpClient->Request ($method, $path, $body, $query);

	}

	/**
	 * @param array $array
	 *
	 * @return array
	 */
	protected function BuildQueryString (array $array): array {

		return array_filter ($array, static fn($value): bool => $value !== null && $value !== '');

	}
}
