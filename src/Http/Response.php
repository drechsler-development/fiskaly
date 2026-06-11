<?php

declare(strict_types=1);

namespace DD\Fiskaly\Http;

final readonly class Response {

	private int $statusCode;
	private array|string|null $body;
	private array $headers;
	private ?string $rawBody;

	/**
	 * @param int               $statusCode
	 * @param array|string|null $body
	 * @param array             $headers
	 * @param string|null       $rawBody
	 */
	public function __construct (int $statusCode, array|string|null $body, array $headers = [], ?string $rawBody = null) {

		$this->statusCode = $statusCode;
		$this->body       = $body;
		$this->headers    = $headers;
		$this->rawBody    = $rawBody;

	}

	/**
	 * @return int
	 */
	public function GetStatusCode (): int {
		return $this->statusCode;
	}

	/**
	 * @return array|string|null
	 */
	public function GetBody (): array|string|null {
		return $this->body;
	}

	/**
	 * @return string|null
	 */
	public function GetRawBody (): ?string {
		return $this->rawBody;
	}

	/**
	 * @return array
	 */
	public function GetHeaders (): array {
		return $this->headers;
	}

	/**
	 * @param string $name
	 *
	 * @return string|null
	 */
	public function GetHeader (string $name): ?string {
		$key = strtolower ($name);
		return $this->headers[$key] ?? null;
	}

	/**
	 * @return string|null
	 */
	public function GetRequestId (): ?string {
		return $this->headers['x-request-id'] ?? null;
	}
}
