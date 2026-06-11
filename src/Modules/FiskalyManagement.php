<?php

declare(strict_types=1);

namespace DD\Fiskaly\Modules;

use DD\Fiskaly\Auth\IStorageInterface;
use DD\Fiskaly\Configuration\Configuration;
use DD\Fiskaly\Http\AuthenticatedHttpClient;
use DD\Fiskaly\Http\HttpClient;
use DD\Fiskaly\Service\ApiKeyService;
use DD\Fiskaly\Service\AuthenticationService;
use DD\Fiskaly\Service\BillingAddressService;
use DD\Fiskaly\Service\OrganizationService;
use DD\Fiskaly\Service\UserService;

final class FiskalyManagement {

	private HttpClient              $httpClient;
	private AuthenticatedHttpClient $authenticatedHttpClient;

	private AuthenticationService $authenticationService;
	private OrganizationService   $organizationService;
	private ApiKeyService         $apiKeyService;
	private UserService           $userService;
	private BillingAddressService $billingAddressService;

	public function __construct (Configuration $configuration, IStorageInterface $tokenStorage) {

		$this->httpClient              = new HttpClient($configuration);
		$this->authenticatedHttpClient = new AuthenticatedHttpClient($configuration, $tokenStorage);
		$this->authenticationService   = new AuthenticationService($this->httpClient, $tokenStorage, 'management');
		$this->organizationService     = new OrganizationService($this->authenticatedHttpClient);
		$this->apiKeyService           = new ApiKeyService($this->authenticatedHttpClient);
		$this->userService             = new UserService($this->authenticatedHttpClient);
		$this->billingAddressService   = new BillingAddressService($this->authenticatedHttpClient);
	}

	public function AuthenticationService (): AuthenticationService {
		return $this->authenticationService;
	}

	public function OrganizationService (): OrganizationService {
		return $this->organizationService;
	}

	public function ApiKeyService (): ApiKeyService {
		return $this->apiKeyService;
	}

	public function UserService (): UserService {
		return $this->userService;
	}

	public function BillingAddressService (): BillingAddressService {
		return $this->billingAddressService;
	}

}
