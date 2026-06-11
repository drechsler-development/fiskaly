<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Auth\IStorageInterface;
use DD\Fiskaly\Http\HttpClient;
use Random\RandomException;

final class AuthenticationService
{
	public string $path;

	private HttpClient        $httpClient;
    private IStorageInterface $tokenStorage;

	/**
	 * @param HttpClient        $httpClient
	 * @param IStorageInterface $tokenStorage
	 * @param string            $apiName
	 */
	public function __construct(HttpClient $httpClient, IStorageInterface $tokenStorage, string $apiName) {

		$this->httpClient = $httpClient;
		$this->tokenStorage = $tokenStorage;

		$this->path = $apiName === 'management' ? '/auth' : '/api/v2/auth';

	}

	/**
	 * @param string $apiKey
	 * @param string $apiSecret
	 *
	 * @return array
	 * @throws RandomException
	 */
    public function AuthenticateWithApiKey(string $apiKey, string $apiSecret): array
    {
        $response = $this->httpClient->Request('POST', $this->path, [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
        ]);

        $body = $response->GetBody();
        $this->tokenStorage->SetTokenData(is_array($body) ? $body : []);
        return is_array($body) ? $body : [];
    }

	/**
	 * @param string $refreshToken
	 *
	 * @return array
	 * @throws RandomException
	 */
    public function AuthenticateWithRefreshToken(string $refreshToken): array
    {
        $response = $this->httpClient->Request('POST', $this->path, [
            'refresh_token' => $refreshToken,
        ]);

        $body = $response->GetBody();
        $this->tokenStorage->SetTokenData(is_array($body) ? $body : []);
        return is_array($body) ? $body : [];
    }

	/**
	 * @return array
	 * @throws RandomException
	 */
    public function RefreshFromStorage(): array
    {
        $tokenData = $this->tokenStorage->GetTokenData();
        $refreshToken = $tokenData['refresh_token'] ?? null;

        if (!is_string($refreshToken) || $refreshToken === '') {
            return [];
        }

        return $this->AuthenticateWithRefreshToken($refreshToken);
    }

	/**
	 * @return void
	 */
    public function ClearToken(): void
    {
        $this->tokenStorage->Clear();
    }
}
