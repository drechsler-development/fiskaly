<?php

declare(strict_types=1);

namespace DD\Fiskaly;

use DD\Fiskaly\Auth\IStorageInterface;
use DD\Fiskaly\Auth\SessionStorage;
use DD\Fiskaly\Configuration\Configuration;
use DD\Fiskaly\Exception\FiskalyException;
use DD\Fiskaly\Http\AuthenticatedHttpClient;
use DD\Fiskaly\Http\HttpClient;
use DD\Fiskaly\Http\Response;
use DD\Fiskaly\Service\ApiKeyService;
use DD\Fiskaly\Service\AuthenticationService;
use DD\Fiskaly\Service\BillingAddressService;
use DD\Fiskaly\Service\OrganizationService;
use DD\Fiskaly\Service\UserService;
use Exception;
use Random\RandomException;

class FiskalyManagement extends FiskalyBase {

	private HttpClient              $HttpClient;
	private AuthenticatedHttpClient $AuthenticatedHttpClient;

	private AuthenticationService $AuthenticationService;
	private OrganizationService   $OrganizationService;
	private ApiKeyService         $ApiKeyService;
	private UserService           $UserService;
	private BillingAddressService $BillingAddressService;

	private string $apiKey;
	private string $apiSecret;

	private string  $managedApiKey;
	private string  $managedApiSecret;
	private ?string $organizationId;

	public array $listOrganizationFilter = [
		'limit'    => 100,
		'order_by' => 'name',
		'order'    => 'asc',
	];

	public array $newOrganization;
	public array $newApiCredentials;

	/**
	 * @param string            $managedApiKey
	 * @param string            $managedApiSecret
	 * @param IStorageInterface $tokenStorage
	 * @param string|null       $organizationId Optional default organization id
	 *
	 * @throws RandomException
	 */
	public function __construct (string $managedApiKey, string $managedApiSecret, IStorageInterface $tokenStorage = new SessionStorage(), ?string $organizationId = null) {

		$configuration                 = new Configuration(Configuration::DEFAULT_BASE_URL_MANAGEMENT);
		$this->HttpClient              = new HttpClient($configuration);
		$this->AuthenticatedHttpClient = new AuthenticatedHttpClient($configuration, $tokenStorage);
		$this->AuthenticationService   = new AuthenticationService($this->HttpClient, $tokenStorage, 'management');
		$this->OrganizationService     = new OrganizationService($this->AuthenticatedHttpClient);
		$this->ApiKeyService           = new ApiKeyService($this->AuthenticatedHttpClient);
		$this->UserService             = new UserService($this->AuthenticatedHttpClient);
		$this->BillingAddressService   = new BillingAddressService($this->AuthenticatedHttpClient);

		$this->managedApiKey         = $managedApiKey;
		$this->managedApiSecret      = $managedApiSecret;
		$this->organizationId = $organizationId;

		// Falls API-Credentials beim Konstruktor angegeben wurden, führe sofort Authentifizierung durch
		$this->AuthenticationService->AuthenticateWithApiKey ($this->managedApiKey, $this->managedApiSecret);

	}

	#region GETTER

	public function AuthenticationService (): AuthenticationService {
		return $this->AuthenticationService;
	}

	public function OrganizationService (): OrganizationService {
		return $this->OrganizationService;
	}

	public function ApiKeyService (): ApiKeyService {
		return $this->ApiKeyService;
	}

	public function UserService (): UserService {
		return $this->UserService;
	}

	public function BillingAddressService (): BillingAddressService {
		return $this->BillingAddressService;
	}

	#endregion

	# Authentication

	/**
	 * Authenticate with the Fiskaly Management API using the provided API key and secret.
	 * The authentication token will be stored in the token storage for subsequent requests.
	 *
	 * @return void
	 * @throws Exception if authentication fails
	 */
	public function Authenticate (): void {
		if (empty($this->managedApiKey) || empty($this->managedApiSecret)) {
			throw new FiskalyException('Management API credentials not configured. Provide managedApiKey and managedApiSecret to use the Management API.');
		}

		$response = $this->AuthenticationService ()->AuthenticateWithApiKey ($this->managedApiKey, $this->managedApiSecret);
		if ($this->debugOutput) {
			$this->PrintResult ('AuthenticateManagement Info', $response);
		}
	}

	# Organization methods

	/**
	 * List all organizations associated with the authenticated API key, with optional filtering and pagination.
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function ListOrganizations (): Response {

		$response = $this->OrganizationService->ListOrganizations ($this->listOrganizationFilter);

		if ($this->debugOutput) {
			$this->PrintResult ('List Organizations Response', $response);
		}

		return $response;
	}

	/**
	 * Retrieve the details of a specific organization by its ID.
	 *
	 * @param $organizationId
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function ListApiCredentials ($organizationId): Response {

		$response = $this->ApiKeyService ()->ListApiKeys ($organizationId);

		if ($this->debugOutput) {
			$this->PrintResult ('List API Credentials', $response);
		}

		return $response;
	}

	/**
	 * Retrieve the details of a specific organization by its ID.
	 *
	 * @param $organizationId
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function RetrieveOrganization ($organizationId): Response {

		$response = $this->OrganizationService->RetrieveOrganization ($organizationId);
		if ($this->debugOutput) {
			$this->PrintResult ('Retrieve Organization', $response);
		}

		return $response;
	}

	/**
	 * Update the details of a specific organization by its ID.
	 *
	 * @param       $organizationId
	 * @param array $data
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function UpdateOrganization ($organizationId, array $data): Response {

		$response = $this->OrganizationService->UpdateOrganization ($organizationId, $data);
		if ($this->debugOutput) {
			$this->PrintResult ('Update Organization', $response);
		}

		return $response;
	}

	/**
	 * Disable a specific environment for an organization by its ID.
	 *
	 * @param        $organizationId
	 * @param string $env
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function EnableEnvironment ($organizationId, string $env = 'TEST'): Response {

		$response = $this->OrganizationService->EnableEnvironment ($organizationId, $env);
		if ($this->debugOutput) {
			$this->PrintResult ('Enable Environment', $response);
		}

		return $response;
	}

	/**
	 * Disable a specific environment for an organization by its ID.
	 *
	 * @param $organizationId
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function DeleteOrganization ($organizationId): Response {

		$response = $this->OrganizationService->DeleteOrganization ($organizationId);

		if ($this->debugOutput) {
			$this->PrintResult ('Delete Organization Response', $response);
		}

		return $response;
	}

	/**
	 * Create a new organization with the provided details.
	 *
	 * @param array $organisation
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function CreateOrganization (array $organisation = []): Response {

		$response = $this->OrganizationService->CreateOrganization ($organisation ?? $this->newOrganization);

		if ($this->debugOutput) {
			$this->PrintResult ('Create New Organization Response', $response);
		}

		return $response;
	}

	# API Credential methods

	/**
	 * Create new API credentials for a specific organization by its ID.
	 *
	 * @param $organizationId
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function CreateApiCredentials (string $organizationId, array $apiCredentials = []): Response {

		if(!empty($apiCredentials)) {
			$this->newApiCredentials = $apiCredentials;
		}

		$response = $this->ApiKeyService ()->CreateApiKey ($organizationId, $this->newApiCredentials);

		if ($this->debugOutput) {
			$this->PrintResult ('Create API Credentials Response', $response);
		}

		return $response;
	}

	/**
	 * Retrieve the details of specific API credentials by their ID for a given organization.
	 *
	 * @param $organizationId
	 * @param $keyId
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function RetrieveApiCredentials ($organizationId, $keyId): Response {

		$response = $this->ApiKeyService ()->RetrieveApiKey ($organizationId, $keyId);
		if ($this->debugOutput) {
			$this->PrintResult ('Retrieve API Credentials', $response);
		}

		return $response;
	}

	/**
	 * Update the details of specific API credentials by their ID for a given organization.
	 *
	 * @param       $organizationId
	 * @param       $keyId
	 * @param array $data
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function UpdateApiCredentials ($organizationId, $keyId, array $data): Response {

		$response = $this->ApiKeyService ()->UpdateApiKey ($organizationId, $keyId, $data);
		if ($this->debugOutput) {
			$this->PrintResult ('Update API Credentials', $response);
		}

		return $response;
	}

	/**
	 * Disable specific API credentials by their ID for a given organization.
	 *
	 * @param $organizationId
	 * @param $keyId
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function DeleteApiCredentials ($organizationId, $keyId): Response {

		$response = $this->ApiKeyService ()->DeleteApiKey ($organizationId, $keyId);
		if ($this->debugOutput) {
			$this->PrintResult ('Delete API Credentials', $response);
		}

		return $response;
	}

	# User methods

	/**
	 * List all users associated with a specific organization by its ID, with optional filtering and pagination.
	 *
	 * @param       $organizationId
	 * @param array $filter
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function ListUsers ($organizationId, array $filter = []): Response {

		$response = $this->UserService ()->ListUsers ($organizationId, $filter);
		if ($this->debugOutput) {
			$this->PrintResult ('List Users', $response);
		}

		return $response;
	}

	/**
	 * Retrieve the details of a specific user by their ID for a given organization.
	 *
	 * @param             $organizationId
	 * @param string      $email
	 * @param string|null $firstName
	 * @param string|null $lastName
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function InviteUser ($organizationId, string $email, ?string $firstName = null, ?string $lastName = null): Response {

		$response = $this->UserService ()->InviteUser ($organizationId, $email, $firstName, $lastName);
		if ($this->debugOutput) {
			$this->PrintResult ('Invite User', $response);
		}

		return $response;
	}

	/**
	 * Update the details of a specific user by their ID for a given organization.
	 *
	 * @param $organizationId
	 * @param $userId
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function DeleteUser ($organizationId, $userId): Response {

		$response = $this->UserService ()->DeleteUser ($organizationId, $userId);
		if ($this->debugOutput) {
			$this->PrintResult ('Delete User', $response);
		}

		return $response;
	}

	# Billing address methods

	/**
	 * List all billing addresses associated with the authenticated organization, with optional filtering and pagination.
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function ListBillingAddresses (): Response {

		$response = $this->BillingAddressService ()->ListBillingAddresses ();
		if ($this->debugOutput) {
			$this->PrintResult ('List Billing Addresses', $response);
		}

		return $response;
	}

	/**
	 * Create a new billing address with the provided details for the authenticated organization.
	 *
	 * @param array $data
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function CreateBillingAddress (array $data): Response {

		$response = $this->BillingAddressService ()->CreateBillingAddress ($data);
		if ($this->debugOutput) {
			$this->PrintResult ('Create Billing Address', $response);
		}

		return $response;
	}

	/**
	 * Retrieve the details of a specific billing address by its ID for the authenticated organization.
	 *
	 * @param string $addressId
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function RetrieveBillingAddress (string $addressId): Response {

		$response = $this->BillingAddressService ()->RetrieveBillingAddress ($addressId);
		if ($this->debugOutput) {
			$this->PrintResult ('Retrieve Billing Address', $response);
		}

		return $response;
	}

	/**
	 * Update the details of a specific billing address by its ID for the authenticated organization.
	 *
	 * @param string $addressId
	 * @param array  $data
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function UpdateBillingAddress (string $addressId, array $data): Response {

		$response = $this->BillingAddressService ()->UpdateBillingAddress ($addressId, $data);
		if ($this->debugOutput) {
			$this->PrintResult ('Update Billing Address', $response);
		}

		return $response;
	}

	/**
	 * Delete a specific billing address by its ID for the authenticated organization.
	 *
	 * @param string $addressId
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function DeleteBillingAddress (string $addressId): Response {

		$response = $this->BillingAddressService ()->DeleteBillingAddress ($addressId);
		if ($this->debugOutput) {
			$this->PrintResult ('Delete Billing Address', $response);
		}

		return $response;
	}

}
