<?php

declare(strict_types=1);

namespace DD\Fiskaly\Configuration;

use InvalidArgumentException;

final class Configuration {

	public const string DEFAULT_BASE_URL_SIGN_DE    = 'https://kassensichv-middleware.fiskaly.com/api/v2';
	public const string DEFAULT_BASE_URL_MANAGEMENT = 'https://dashboard.fiskaly.com/api/v0';
	// Base URL for the DSFinV-K API (used by FiskalyDSFinV)
	public const string DEFAULT_BASE_URL_DSFINVK    = 'https://kassensichv-middleware.fiskaly.com/api/dsfinvk/v1';

	public string $baseUrl;

	private int     $timeoutSeconds;
	private bool    $verifySsl;

	public function __construct (string $baseUrl, int $timeoutSeconds = 30, bool $verifySsl = true) {

		if ($baseUrl !== self::DEFAULT_BASE_URL_SIGN_DE && $baseUrl !== self::DEFAULT_BASE_URL_MANAGEMENT && $baseUrl !== self::DEFAULT_BASE_URL_DSFINVK) {
			throw new InvalidArgumentException('Ungültige Base URL. Erlaubt sind: ' . self::DEFAULT_BASE_URL_SIGN_DE . ' , ' . self::DEFAULT_BASE_URL_MANAGEMENT . ' und ' . self::DEFAULT_BASE_URL_DSFINVK);
		}

		$this->baseUrl        = rtrim ($baseUrl, '/');
		$this->timeoutSeconds = $timeoutSeconds;
		$this->verifySsl      = $verifySsl;
	}

	public function SetTimeout (int $timeout): void {
		$this->timeoutSeconds = $timeout;
	}

	public function GetTimeout (): int {
		return $this->timeoutSeconds;
	}

	public function SetBaseUrl (string $baseUrl): void {
		$this->baseUrl = $baseUrl;
	}

	public function GetBaseUrl (): string {
		return $this->baseUrl;
	}

	public function GetTimeoutSeconds (): int {
		return $this->timeoutSeconds;
	}

	public function ShouldVerifySsl (): bool {
		return $this->verifySsl;
	}

	public function SetVerifySsl (bool $verifySsl): void {
		$this->verifySsl = $verifySsl;
	}

	public function GetVerifySsl (): bool {
		return $this->verifySsl;
	}
}
