<?php

declare(strict_types=1);

namespace DD\Fiskaly\Http;

use DD\Fiskaly\Configuration\Configuration;
use DD\Fiskaly\Exception\ApiException;
use DD\Fiskaly\Util\Uuid;
use Random\RandomException;

readonly class HttpClient {

	private Configuration $configuration;

	#region PUBLIC METHODS

	/**
	 * @param Configuration $configuration
	 */
	public function __construct (Configuration $configuration) {

		$this->configuration = $configuration;

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

		$url            = $this->BuildUrl ($path, $query);
		$requestHeaders = array_merge ([
			                               'Accept: application/json',
			                               'Content-Type: application/json',
			                               'request-id: ' . Uuid::V4 (),
		                               ], $this->NormalizeHeaders ($headers));

		$curl = curl_init ($url);
		if ($curl === false) {
			throw new ApiException('cURL konnte nicht initialisiert werden.');
		}

		curl_setopt_array ($curl, [
			CURLOPT_CUSTOMREQUEST  => strtoupper ($method),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,
			CURLOPT_HTTPHEADER     => $requestHeaders,
			CURLOPT_TIMEOUT        => $this->configuration->GetTimeoutSeconds (),
			CURLOPT_SSL_VERIFYPEER => $this->configuration->ShouldVerifySsl (),
			CURLOPT_SSL_VERIFYHOST => $this->configuration->ShouldVerifySsl () ? 2 : 0,
		]);

		if (str_contains ($path, 'logout')) {
			curl_setopt ($curl, CURLOPT_POSTFIELDS, '{}');
		} else if ($body !== null) {
			curl_setopt ($curl, CURLOPT_POSTFIELDS, json_encode ($body, JSON_UNESCAPED_SLASHES));
		}

		$rawResponse = curl_exec ($curl);
		if ($rawResponse === false) {
			$error = curl_error ($curl);
			curl_close ($curl);
			throw new ApiException('HTTP-Request fehlgeschlagen: ' . $error);
		}

		$statusCode = (int)curl_getinfo ($curl, CURLINFO_RESPONSE_CODE);
		$headerSize = (int)curl_getinfo ($curl, CURLINFO_HEADER_SIZE);
		curl_close ($curl);

		$rawHeaders    = substr ($rawResponse, 0, $headerSize);
		$rawBody       = substr ($rawResponse, $headerSize);
		$parsedHeaders = $this->ParseHeaders ($rawHeaders);
		//$contentType   = $parsedHeaders['content-type'] ?? '';
		$decodedBody   = null;

		if ($rawBody !== '') {
			$json        = json_decode ($rawBody, true);
			$decodedBody = json_last_error () === JSON_ERROR_NONE ? $json : $rawBody;
		}

		if ($statusCode < 200 || $statusCode >= 300) {
			$message   = 'API-Request fehlgeschlagen.';
			$errorCode = null;

			if (is_array ($decodedBody)) {
				$message   = (string)($decodedBody['message'] ?? $decodedBody['error'] ?? $message);
				$errorCode = isset($decodedBody['code']) ? (string)$decodedBody['code'] : null;
			}

			throw new ApiException($message, $statusCode, $errorCode, is_array ($decodedBody) ? $decodedBody : null, $parsedHeaders['request-id'] ?? null);
		}

		return new Response($statusCode, $decodedBody, $parsedHeaders, $rawBody);
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
		return $this->Request ($method, $path, null, $query, array_merge (['Accept' => 'application/octet-stream'], $headers));
	}

	#endregion

	#region PRIVATE METHODS

	/**
	 * @param string $path
	 * @param array  $query
	 *
	 * @return string
	 */
	private function BuildUrl (string $path, array $query = []): string {

		$url   = $this->configuration->GetBaseUrl () . '/' . ltrim ($path, '/');
		$query = array_filter ($query, static fn($value): bool => $value !== null && $value !== '');
		if (!empty($query)) {
			$url .= '?' . http_build_query ($query);
		}
		return $url;
	}

	/**
	 * @param array $headers
	 *
	 * @return array
	 */
	private function NormalizeHeaders (array $headers): array {

		$normalized = [];
		foreach ($headers as $name => $value) {
			if (is_int ($name)) {
				$normalized[] = $value;
				continue;
			}
			$normalized[] = $name . ': ' . $value;
		}
		return $normalized;
	}

	/**
	 * @param string $rawHeaders
	 *
	 * @return array
	 */
	private function ParseHeaders (string $rawHeaders): array {

		$headers = [];
		foreach (explode ("\r\n", trim ($rawHeaders)) as $line) {
			if (!str_contains ($line, ':')) {
				continue;
			}
			[$name, $value] = explode (':', $line, 2);
			$headers[strtolower (trim ($name))] = trim ($value);
		}
		return $headers;
	}

	#endregion
}
