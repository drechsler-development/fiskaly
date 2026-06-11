<?php

declare(strict_types=1);

namespace DD\Fiskaly;

use DD\Fiskaly\Auth\SessionStorage;
use DD\Fiskaly\Auth\IStorageInterface;
use DD\Fiskaly\Configuration\Configuration;
use DD\Fiskaly\Modules\FiskalyManagement;
use DD\Fiskaly\Modules\FiskalySignDe;
use DD\Fiskaly\Http\Response;
use DD\Fiskaly\Service\OrganizationService;
use Exception;
use Random\RandomException;

/**
 * This class demonstrates how to use the Fiskaly Management API and Sign DE API to manage organizations, API credentials, and TSEs.
 * It includes methods for authenticating, creating organizations, managing TSEs, and handling transactions.
 * The example data is stored in a local SQLite database for demonstration purposes.
 * Please ensure to replace the placeholder API credentials with your actual test credentials before running the example.
 * Note: This example is for demonstration purposes only and may not cover all edge cases or error handling scenarios. It is recommended to implement additional error handling and validation as needed for production use.
 */
class Fiskaly {

	public IStorageInterface   $TokenStorage;
	public FiskalyManagement   $FiskalyManagement;
	public FiskalySignDe       $FiskalySignDe;
	public OrganizationService $OrganizationService;

	private bool $debug       = false;
	public bool  $debugOutput = false;

	public string $apiKey;
	public string $apiSecret;

	public string $managedOrganizationId;
	public string $managedApiKey;
	public string $managedApiSecret;

	public array  $listOrganizationFilter = [
		'limit'    => 100,
		'order_by' => 'name',
		'order'    => 'asc',
	];
	public array  $newOrganization;
	public array  $newApiCredentials;
	public string $clientSerialNumber;
	public string $adminPin;
	public string $adminPuk;


	/**
	 * @param string $managedApiKey
	 * @param string $managedApiSecret
	 * @param string $managedOrganizationId
	 *
	 * @throws Exception
	 */
	public function __construct (string $managedApiKey, string $managedApiSecret, string $managedOrganizationId) {

		$this->managedApiKey         = $managedApiKey;
		$this->managedApiSecret      = $managedApiSecret;
		$this->managedOrganizationId = $managedOrganizationId;

		$this->TokenStorage = new SessionStorage();

		// Initiate the Fiskaly Management and Sign DE clients with the token storage and default configuration.
		$configuration           = new Configuration(Configuration::DEFAULT_BASE_URL_MANAGEMENT);
		$this->FiskalyManagement = new FiskalyManagement($configuration, $this->TokenStorage);

		// Initiate the Fiskaly Sign DE client with the token storage and default configuration.
		$configuration       = new Configuration(Configuration::DEFAULT_BASE_URL_SIGN_DE);
		$this->FiskalySignDe = new FiskalySignDe($configuration, $this->TokenStorage);


		$this->OrganizationService = $this->FiskalyManagement->OrganizationService ();
	}

	/**
	 * Enable or disable debug mode for the Fiskaly class. When enabled, additional debug information will be printed to the console for API calls and responses.
	 *
	 * @param bool $debug Set to true to enable debug mode, or false to disable it.
	 *
	 * @return void
	 */
	public function SetDebug (bool $debug): void {
		$this->debug = $debug;
	}

	/**
	 * Enable or disable debug output for API responses. When enabled, the results of API calls will be printed to the console for debugging purposes.
	 *
	 * @param bool $debugOutput Set to true to enable debug output, or false to disable it.
	 *
	 * @return void
	 */
	public function SetDebugOutput (bool $debugOutput): void {
		$this->debugOutput = $debugOutput;
	}

	#region SIGN DE methods

	# Authentication
	/**
	 * Authenticate with the Fiskaly Sign DE API using the provided API key and secret.
	 * The authentication token will be stored in the token storage for subsequent requests.
	 *
	 * @return void
	 * @throws Exception if authentication fails
	 */
	public function AuthenticateSignDe (): void {
		$response = $this->FiskalySignDe->AuthenticationService ()->AuthenticateWithApiKey ($this->apiKey, $this->apiSecret);
		if ($this->debugOutput) {
			$this->PrintResult ('AuthenticateSignDe Info', $response);
		}
	}

	# Admin methods

	/**
	 * Authenticate as admin for the specified TSS ID using the stored admin PIN.
	 *
	 * @param string $tssId The ID of the TSS for which to authenticate as admin.
	 *
	 * @return void
	 * @throws Exception if authentication fails
	 */
	public function AuthenticateAdmin (string $tssId): void {

		$response = $this->FiskalySignDe->AdminService ()->AuthenticateAdmin ($tssId, $this->adminPin);

		if ($this->debugOutput) {
			$this->PrintResult ('Authenticate Admin', ['success' => empty($response)]);
		}
	}

	/**
	 * Change the admin PIN for the specified TSS ID using the stored admin PUK and new admin PIN.
	 *
	 * @param string $tssId The ID of the TSS for which to change the admin PIN.
	 *
	 * @return void
	 * @throws Exception if changing the admin PIN fails
	 */
	public function ChangeAdminPin (string $tssId): void {

		//$this->AuthenticateAdmin ($tssId);

		$response = $this->FiskalySignDe->AdminService ()->ChangeAdminPin ($tssId, $this->adminPuk, $this->adminPin);
		if ($this->debugOutput) {
			$this->PrintResult ('Change Admin Pin', ['success' => empty($response)]);
		}

	}

	/**
	 * Change the admin PIN for the specified TSS ID using the provided admin PUK and new admin PIN.
	 *
	 * @param string $tssId    The ID of the TSS for which to change the admin PIN.
	 * @param string $adminPuk The admin PUK to be used for changing the admin PIN.
	 * @param string $newPin   The new admin PIN to be set for the specified TSS ID.
	 *
	 * @return void
	 * @throws Exception if changing the admin PIN fails
	 */
	public function ChangeAdminPinWithPuk (string $tssId, string $adminPuk, string $newPin): void {

		$response = $this->FiskalySignDe->AdminService ()->ChangeAdminPin ($tssId, $adminPuk, $newPin);
		if ($this->debugOutput) {
			$this->PrintResult ('Change Admin Pin', $response);
		}

	}

	/**
	 * Logout the admin session for the specified TSS ID.
	 *
	 * @param string $tssId The ID of the TSS for which to logout the admin session.
	 *
	 * @return void
	 * @throws Exception if logout fails
	 */
	public function LogoutAdmin (string $tssId): void {

		$response = $this->FiskalySignDe->AdminService ()->LogoutAdmin ($tssId);

		if ($this->debugOutput) {
			$this->PrintResult ('Logout Admin', $response);
		}
	}

	# TSS methods

	/**
	 * Retrieve the details of a specific TSS by its ID.
	 *
	 * @param string $tssId The ID of the TSS to be retrieved.
	 *
	 * @return array The details of the specified TSS.
	 * @throws Exception if retrieving the TSS fails
	 */
	public function RetrieveTss (string $tssId): array {

		$this->AuthenticateAdmin ($tssId);

		$response = $this->FiskalySignDe->TssService ()->RetrieveTss ($tssId);
		if ($this->debugOutput) {
			$this->PrintResult ('Retrieve TSE Info', $response);
		}

		return $response;
	}

	/**
	 * Create a new TSS with the specified ID and optional internal reference.
	 *
	 * @param string $tssId The ID of the TSS to be created.
	 *
	 * @return array The response from the API after creating the TSS, including details of the created TSS and admin PUK.
	 * @throws Exception if creating the TSS fails
	 */
	public function CreateTss (string $tssId): array {

		$response = $this->FiskalySignDe->TssService ()->CreateTss ($tssId, [
			'internal_reference' => 'vabs-test-tss',
		]);

		if ($this->debugOutput) {
			$this->PrintResult ('Create TSE Response', $response);
		}

		return $response;

	}

	/**
	 * Set the specified TSS to the "UNINITIALIZED" state.
	 *
	 * @param string $tssId The ID of the TSS to be set to "UNINITIALIZED".
	 *
	 * @return array The response from the API after setting the TSS to "UNINITIALIZED", including the updated TSS details.
	 * @throws Exception if setting the TSS to "UNINITIALIZED" fails
	 */
	public function SetUninitialized (string $tssId): array {

		$response = $this->FiskalySignDe->TssService ()->SetUninitialized ($tssId);
		if ($this->debugOutput) {
			$this->PrintResult ('Set TSE Uninitialized', $response);
		}

		return $response;
	}

	/**
	 * Set the specified TSS to the "INITIALIZED" state.
	 *
	 * @param string $tssId The ID of the TSS to be set to "INITIALIZED".
	 *
	 * @return void
	 * @throws Exception if setting the TSS to "INITIALIZED" fails
	 */
	public function SetInitialized (string $tssId): void {

		$this->AuthenticateAdmin ($tssId);

		$response = $this->FiskalySignDe->TssService ()->SetInitialized ($tssId);
		if ($this->debugOutput) {
			$this->PrintResult ('Set TSE Initialized', $response);
		}
	}

	/**
	 * Update the state, metadata, and description of a specific TSS.
	 *
	 * @param string      $tssId       The ID of the TSS to be updated.
	 * @param string      $state       The new state to be set for the TSS (e.g., "ACTIVE", "INACTIVE").
	 * @param array       $metadata    An associative array containing the metadata key-value pairs to be updated for the TSS.
	 * @param string|null $description An optional description to be set for the TSS.
	 *
	 * @return array The response from the API after updating the TSS, including the updated TSS details.
	 * @throws Exception if updating the TSS fails
	 */
	public function UpdateTss (string $tssId, string $state, array $metadata = [], ?string $description = null): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->TssService ()->UpdateTss ($tssId, $state, $metadata, $description);
		if ($this->debugOutput) {
			$this->PrintResult ('Update TSE', $response);
		}

		return $response;
	}

	/**
	 * Disable a specific TSS by its ID.
	 *
	 * @param string $tssId The ID of the TSS to be disabled.
	 *
	 * @return void
	 * @throws Exception if disabling the TSS fails
	 */
	public function DisableTss (string $tssId): void {

		$this->AuthenticateAdmin ($tssId);

		$response = $this->FiskalySignDe->TssService ()->DisableTss ($tssId);
		if ($this->debugOutput) {
			$this->PrintResult ('TSE DisableTss', $response);
		}
	}

	/**
	 * List all TSS associated with the authenticated admin, with optional filtering and pagination.
	 *
	 * @param string $tssId The ID of the TSS for which to list associated TSS.
	 * @param array  $query Optional query parameters for filtering and pagination (e.g., 'limit', 'offset', 'state').
	 *
	 * @return array An array of TSS matching the specified criteria.
	 * @throws Exception if listing the TSS fails
	 */
	public function ListTss (string $tssId, array $query = []): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->TssService ()->ListTss ($query);
		if ($this->debugOutput) {
			$this->PrintResult ('List TSE', $response);
		}

		return $response;
	}

	/**
	 * Retrieve the metadata of a specific TSS by its ID.
	 *
	 * @param string $tssId The ID of the TSS for which to retrieve metadata.
	 *
	 * @return array The metadata of the specified TSS.
	 * @throws Exception if retrieving the TSS metadata fails
	 */
	public function RetrieveTssMetadata (string $tssId): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->TssService ()->RetrieveMetadata ($tssId);
		if ($this->debugOutput) {
			$this->PrintResult ('Retrieve TSE Metadata', $response);
		}

		return $response;
	}

	/**
	 * Update the metadata of a specific TSS by its ID.
	 *
	 * @param string $tssId    The ID of the TSS for which to update metadata.
	 * @param array  $metadata An associative array containing the metadata key-value pairs to be updated for the TSS.
	 *
	 * @return array The response from the API after updating the TSS metadata, including the updated metadata details.
	 * @throws Exception if updating the TSS metadata fails
	 */
	public function UpdateTssMetadata (string $tssId, array $metadata): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->TssService ()->UpdateMetadata ($tssId, $metadata);
		if ($this->debugOutput) {
			$this->PrintResult ('Update TSE Metadata', $response);
		}

		return $response;
	}

	# Client methods

	/**
	 * List all clients associated with the authenticated admin, with optional filtering and pagination.
	 *
	 * @param string $tssId The ID of the TSS for which to list associated clients.
	 *
	 * @return array An array of clients matching the specified criteria.
	 * @throws Exception if listing the clients fails
	 */
	public function ListAllClients (string $tssId): array {

		$this->AuthenticateAdmin ($tssId);

		$response = $this->FiskalySignDe->ClientService ()->ListAllClients ();
		if ($this->debugOutput) {
			$this->PrintResult ('ListAllClients', $response);
		}

		return $response;
	}

	/**
	 * List all clients associated with the specified TSS ID, with optional filtering and pagination.
	 *
	 * @param string $tssId The ID of the TSS for which to list associated clients.
	 *
	 * @return array An array of clients matching the specified criteria.
	 * @throws Exception if listing the clients fails
	 */
	public function ListClients (string $tssId): array {

		$this->AuthenticateAdmin ($tssId);

		$response = $this->FiskalySignDe->ClientService ()->ListClients ($tssId);
		if ($this->debugOutput) {
			$this->PrintResult ('Create Client', $response);
		}

		return $response;
	}

	/**
	 * Create a new client with the specified ID for the given TSS ID.
	 *
	 * @param string $tssId    The ID of the TSS for which to create the client.
	 * @param string $clientId The ID of the client to be created.
	 *
	 * @return array The response from the API after creating the client, including details of the created client.
	 * @throws Exception if creating the client fails
	 */
	public function CreateClient (string $tssId, string $clientId): array {

		$this->AuthenticateAdmin ($tssId);

		$response = $this->FiskalySignDe->ClientService ()->CreateClient ($tssId, $clientId, $this->clientSerialNumber);
		if ($this->debugOutput) {
			$this->PrintResult ('Create Client', $response);
		}

		return $response;
	}

	/**
	 * Create a new client with the specified ID for the given TSS ID.
	 *
	 * @param string $tssId    The ID of the TSS for which to create the client.
	 * @param string $clientId The ID of the client to be created.
	 *
	 * @return array The response from the API after creating the client, including details of the created client.
	 * @throws Exception if creating the client fails
	 */
	public function RetrieveClient (string $tssId, string $clientId): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->ClientService ()->RetrieveClient ($tssId, $clientId);
		if ($this->debugOutput) {
			$this->PrintResult ('Retrieve Client', $response);
		}

		return $response;
	}

	/**
	 * Register a client with the specified ID for the given TSS ID.
	 *
	 * @param string $tssId    The ID of the TSS for which to register the client.
	 * @param string $clientId The ID of the client to be registered.
	 *
	 * @return array The response from the API after registering the client, including details of the registered client.
	 * @throws Exception if registering the client fails
	 */
	public function RegisterClient (string $tssId, string $clientId): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->ClientService ()->RegisterClient ($tssId, $clientId);
		if ($this->debugOutput) {
			$this->PrintResult ('Register Client', $response);
		}

		return $response;
	}

	/**
	 * Deregister a client with the specified ID for the given TSS ID.
	 *
	 * @param string $tssId The ID of the TSS for which to deregister the client.
	 * @param string $clientId
	 *
	 * @return array The response from the API after deregistering the client, including details of the deregistered client.
	 * @throws RandomException
	 */
	public function DeregisterClient (string $tssId, string $clientId): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->ClientService ()->DeregisterClient ($tssId, $clientId);
		if ($this->debugOutput) {
			$this->PrintResult ('Deregister Client', $response);
		}

		return $response;
	}

	/**
	 * Retrieve the metadata of a specific client by its ID for the given TSS ID.
	 *
	 * @param string $tssId    The ID of the TSS for which to retrieve the client metadata.
	 * @param string $clientId The ID of the client for which to retrieve metadata.
	 *
	 * @return array The metadata of the specified client.
	 * @throws Exception if retrieving the client metadata fails
	 */
	public function RetrieveClientMetadata (string $tssId, string $clientId): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->ClientService ()->RetrieveMetadata ($tssId, $clientId);
		if ($this->debugOutput) {
			$this->PrintResult ('Retrieve Client Metadata', $response);
		}

		return $response;
	}

	/**
	 * Update the metadata of a specific client by its ID for the given TSS ID.
	 *
	 * @param string $tssId    The ID of the TSS for which to update the client metadata.
	 * @param string $clientId The ID of the client for which to update metadata.
	 * @param array  $metadata An associative array containing the metadata key-value pairs to be updated for the client.
	 *
	 * @return array The response from the API after updating the client metadata, including the updated metadata details.
	 * @throws Exception if updating the client metadata fails
	 */
	public function UpdateClientMetadata (string $tssId, string $clientId, array $metadata): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->ClientService ()->UpdateMetadata ($tssId, $clientId, $metadata);
		if ($this->debugOutput) {
			$this->PrintResult ('Update Client Metadata', $response);
		}

		return $response;
	}

	# Transaction methods

	/**
	 * Start a new transaction with the specified details for the given TSS ID and client ID.
	 *
	 * @param string $tssId      The ID of the TSS for which to start the transaction.
	 * @param string $txId       The ID of the transaction to be started.
	 * @param string $clientId   The ID of the client for which to start the transaction.
	 * @param array  $schema     An associative array containing the schema key-value pairs for the transaction.
	 * @param array  $metadata   An associative array containing the metadata key-value pairs for the transaction.
	 * @param int    $txRevision The revision number of the transaction (default is 1).
	 *
	 * @return array The response from the API after starting the transaction, including details of the started transaction.
	 * @throws Exception if starting the transaction fails
	 */
	public function StartTransaction (string $tssId, string $txId, string $clientId, array $schema = [], array $metadata = [], int $txRevision = 1): array {

		$response = $this->FiskalySignDe->TransactionService ()->StartTransaction ($tssId, $txId, $clientId, $schema, $metadata, $txRevision);
		if ($this->debugOutput) {
			$this->PrintResult ('Start Transaction', $response);
		}

		return $response;
	}

	/**
	 * Finish an existing transaction with the specified details for the given TSS ID and client ID.
	 *
	 * @param string   $tssId        The ID of the TSS for which to finish the transaction.
	 * @param string   $txIdOrNumber The ID or number of the transaction to be finished.
	 * @param string   $clientId     The ID of the client for which to finish the transaction.
	 * @param array    $schema       An associative array containing the schema key-value pairs for the transaction.
	 * @param array    $metadata     An associative array containing the metadata key-value pairs for the transaction.
	 * @param int|null $txRevision   The revision number of the transaction (optional).
	 *
	 * @return array The response from the API after finishing the transaction, including details of the finished transaction.
	 * @throws Exception if finishing the transaction fails
	 */
	public function FinishTransaction (string $tssId, string $txIdOrNumber, string $clientId, array $schema, array $metadata = [], ?int $txRevision = null): array {

		$response = $this->FiskalySignDe->TransactionService ()->FinishTransaction ($tssId, $txIdOrNumber, $clientId, $schema, $metadata, $txRevision);
		if ($this->debugOutput) {
			$this->PrintResult ('Finish Transaction', $response);
		}

		return $response;
	}

	/**
	 * Cancel an existing transaction with the specified details for the given TSS ID and client ID.
	 *
	 * @param string   $tssId        The ID of the TSS for which to cancel the transaction.
	 * @param string   $txIdOrNumber The ID or number of the transaction to be canceled.
	 * @param string   $clientId     The ID of the client for which to cancel the transaction.
	 * @param array    $schema       An associative array containing the schema key-value pairs for the transaction.
	 * @param array    $metadata     An associative array containing the metadata key-value pairs for the transaction.
	 * @param int|null $txRevision   The revision number of the transaction (optional).
	 *
	 * @return array The response from the API after canceling the transaction, including details of the canceled transaction.
	 * @throws Exception if canceling the transaction fails
	 */
	public function CancelTransaction (string $tssId, string $txIdOrNumber, string $clientId, array $schema = [], array $metadata = [], ?int $txRevision = null): array {

		$response = $this->FiskalySignDe->TransactionService ()->CancelTransaction ($tssId, $txIdOrNumber, $clientId, $schema, $metadata, $txRevision);
		if ($this->debugOutput) {
			$this->PrintResult ('Cancel Transaction', $response);
		}

		return $response;
	}

	/**
	 * Retrieve the details of a specific transaction by its ID or number for the given TSS ID.
	 *
	 * @param string $tssId        The ID of the TSS for which to retrieve the transaction.
	 * @param string $txIdOrNumber The ID or number of the transaction to be retrieved.
	 * @param array  $query        Optional query parameters for retrieving the transaction (e.g., 'include_logs').
	 *
	 * @return array The details of the specified transaction, including its state, schema, metadata, and logs (if requested).
	 * @throws Exception if retrieving the transaction fails
	 */
	public function RetrieveTransaction (string $tssId, string $txIdOrNumber, array $query = []): array {

		$response = $this->FiskalySignDe->TransactionService ()->RetrieveTransaction ($tssId, $txIdOrNumber, $query);
		if ($this->debugOutput) {
			$this->PrintResult ('Retrieve Transaction', $response);
		}

		return $response;
	}

	/**
	 * Retrieve the logs of a specific transaction by its ID or number for the given TSS ID.
	 *
	 * @param string $tssId        The ID of the TSS for which to retrieve the transaction logs.
	 * @param string $txIdOrNumber The ID or number of the transaction for which to retrieve logs.
	 * @param array  $query        Optional query parameters for retrieving the transaction logs (e.g., 'limit', 'offset').
	 *
	 * @return array An array of logs associated with the specified transaction, including log details such as timestamp, message, and log level.
	 * @throws Exception if retrieving the transaction logs fails
	 */
	public function RetrieveTransactionLog (string $tssId, string $txIdOrNumber, array $query = []): array {

		$response = $this->FiskalySignDe->TransactionService ()->RetrieveTransactionLog ($tssId, $txIdOrNumber, $query);
		if ($this->debugOutput) {
			$this->PrintResult ('Retrieve Transaction Log', $response);
		}

		return $response;
	}

	/**
	 * List all transactions associated with the specified TSS ID, with optional filtering and pagination.
	 *
	 * @param string $tssId The ID of the TSS for which to list transactions.
	 * @param array  $query Optional query parameters for filtering and pagination (e.g., 'limit', 'offset', 'state').
	 *
	 * @return array An array of transactions matching the specified criteria, including transaction details such as state, schema, metadata, and timestamps.
	 * @throws Exception if listing the transactions fails
	 */
	public function ListTransactions (string $tssId, array $query = []): array {

		$response = $this->FiskalySignDe->TransactionService ()->ListTransactions ($tssId, $query);
		if ($this->debugOutput) {
			$this->PrintResult ('List Transactions', $response);
		}

		return $response;
	}

	/**
	 * List all transactions associated with the authenticated admin, with optional filtering and pagination.
	 *
	 * @param array $query Optional query parameters for filtering and pagination (e.g., 'limit', 'offset', 'state').
	 *
	 * @return array An array of transactions matching the specified criteria, including transaction details such as state, schema, metadata, and timestamps.
	 * @throws Exception if listing the transactions fails
	 */
	public function ListAllTransactions (array $query = []): array {

		$response = $this->FiskalySignDe->TransactionService ()->ListAllTransactions ($query);
		if ($this->debugOutput) {
			$this->PrintResult ('List All Transactions', $response);
		}

		return $response;
	}

	/**
	 * List all transactions associated with the specified TSS ID and client ID, with optional filtering and pagination.
	 *
	 * @param string $tssId    The ID of the TSS for which to list transactions.
	 * @param string $clientId The ID of the client for which to list transactions.
	 * @param array  $query    Optional query parameters for filtering and pagination (e.g., 'limit', 'offset', 'state').
	 *
	 * @return array An array of transactions matching the specified criteria, including transaction details such as state, schema, metadata, and timestamps.
	 * @throws Exception if listing the transactions fails
	 */
	public function ListClientTransactions (string $tssId, string $clientId, array $query = []): array {

		$response = $this->FiskalySignDe->TransactionService ()->ListClientTransactions ($tssId, $clientId, $query);
		if ($this->debugOutput) {
			$this->PrintResult ('List Client Transactions', $response);
		}

		return $response;
	}

	/**
	 * Retrieve the metadata of a specific transaction by its ID or number for the given TSS ID.
	 *
	 * @param string $tssId        The ID of the TSS for which to retrieve the transaction metadata.
	 * @param string $txIdOrNumber The ID or number of the transaction for which to retrieve metadata.
	 *
	 * @return array The metadata of the specified transaction.
	 * @throws Exception if retrieving the transaction metadata fails
	 */
	public function RetrieveTransactionMetadata (string $tssId, string $txIdOrNumber): array {

		$response = $this->FiskalySignDe->TransactionService ()->RetrieveMetadata ($tssId, $txIdOrNumber);
		if ($this->debugOutput) {
			$this->PrintResult ('Retrieve Transaction Metadata', $response);
		}

		return $response;
	}

	/**
	 * Update the metadata of a specific transaction by its ID or number for the given TSS ID.
	 *
	 * @param string $tssId        The ID of the TSS for which to update the transaction metadata.
	 * @param string $txIdOrNumber The ID or number of the transaction for which to update metadata.
	 * @param array  $metadata     An associative array containing the metadata key-value pairs to be updated for the transaction.
	 *
	 * @return array The response from the API after updating the transaction metadata, including the updated metadata details.
	 * @throws Exception if updating the transaction metadata fails
	 */
	public function UpdateTransactionMetadata (string $tssId, string $txIdOrNumber, array $metadata): array {

		$response = $this->FiskalySignDe->TransactionService ()->UpdateMetadata ($tssId, $txIdOrNumber, $metadata);
		if ($this->debugOutput) {
			$this->PrintResult ('Update Transaction Metadata', $response);
		}

		return $response;
	}

	# Export methods

	/**
	 * Trigger an export process for the specified TSS and export ID.
	 *
	 * @param string $tssId    The ID of the TSS for which to trigger the export.
	 * @param string $exportId The ID of the export to be triggered.
	 * @param array  $query    Optional query parameters for the export (e.g., filters, date range).
	 *
	 * @return array The response from the API after triggering the export, including export details and status.
	 * @throws Exception if triggering the export fails
	 */
	public function TriggerExport (string $tssId, string $exportId, array $query = []): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->ExportService ()->TriggerExport ($tssId, $exportId, $query);
		if ($this->debugOutput) {
			$this->PrintResult ('Trigger Export', $response);
		}

		return $response;
	}

	/**
	 * Retrieve the status and details of a specific export.
	 *
	 * @param string $tssId    The ID of the TSS for which the export was triggered.
	 * @param string $exportId The ID of the export to be retrieved.
	 *
	 * @return array The details of the specified export, including its status and metadata.
	 * @throws Exception if retrieving the export fails
	 */
	public function RetrieveExport (string $tssId, string $exportId): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->ExportService ()->RetrieveExport ($tssId, $exportId);
		if ($this->debugOutput) {
			$this->PrintResult ('Retrieve Export', $response);
		}

		return $response;
	}

	/**
	 * Cancel an ongoing export process for the specified TSS and export ID.
	 *
	 * @param string $tssId    The ID of the TSS for which the export was triggered.
	 * @param string $exportId The ID of the export to be canceled.
	 *
	 * @return array The response from the API after canceling the export.
	 * @throws Exception if canceling the export fails
	 */
	public function CancelExport (string $tssId, string $exportId): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->ExportService ()->CancelExport ($tssId, $exportId);
		if ($this->debugOutput) {
			$this->PrintResult ('Cancel Export', $response);
		}

		return $response;
	}

	/**
	 * List all exports triggered by the authenticated admin for the specified TSS.
	 *
	 * @param string $tssId The ID of the TSS for which to list exports.
	 * @param array  $query Optional query parameters for filtering and pagination (e.g., 'limit', 'offset', 'status').
	 *
	 * @return array An array of exports matching the specified criteria.
	 * @throws Exception if listing exports fails
	 */
	public function ListExports (string $tssId, array $query = []): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->ExportService ()->ListExports ($tssId, $query);
		if ($this->debugOutput) {
			$this->PrintResult ('List Exports', $response);
		}

		return $response;
	}

	/**
	 * List all exports for the authenticated TSS, including those triggered by other admins.
	 *
	 * @param string $tssId The ID of the TSS for which to list all exports.
	 * @param array  $query Optional query parameters for filtering and pagination (e.g., 'limit', 'offset', 'status').
	 *
	 * @return array An array of exports matching the specified criteria.
	 * @throws Exception if listing all exports fails
	 */
	public function ListAllExports (string $tssId, array $query = []): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->ExportService ()->ListAllExports ($query);
		if ($this->debugOutput) {
			$this->PrintResult ('List All Exports', $response);
		}

		return $response;
	}

	/**
	 * Retrieve the metadata of a specific export.
	 *
	 * @param string $tssId    The ID of the TSS for which the export was triggered.
	 * @param string $exportId The ID of the export for which to retrieve metadata.
	 *
	 * @return array The metadata of the specified export.
	 * @throws Exception if retrieving the export metadata fails
	 */
	public function RetrieveExportMetadata (string $tssId, string $exportId): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->ExportService ()->RetrieveMetadata ($tssId, $exportId);
		if ($this->debugOutput) {
			$this->PrintResult ('Retrieve Export Metadata', $response);
		}

		return $response;
	}

	/**
	 * Update the metadata of a specific export.
	 *
	 * @param string $tssId    The ID of the TSS for which the export was triggered.
	 * @param string $exportId The ID of the export to be updated.
	 * @param array  $metadata An associative array containing the metadata key-value pairs to be updated.
	 *
	 * @return array The response from the API after updating the export metadata.
	 * @throws Exception if updating the export metadata fails
	 */
	public function UpdateExportMetadata (string $tssId, string $exportId, array $metadata): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->ExportService ()->UpdateMetadata ($tssId, $exportId, $metadata);
		if ($this->debugOutput) {
			$this->PrintResult ('Update Export Metadata', $response);
		}

		return $response;
	}

	/**
	 * Save the export file to the specified target file path.
	 *
	 * @param string $tssId      The ID of the TSS for which the export was triggered.
	 * @param string $exportId   The ID of the export to be saved.
	 * @param string $targetFile The file path where the export file should be saved.
	 *
	 * @return string The path to the saved export file.
	 * @throws Exception if saving the export file fails
	 */
	public function SaveExportFile (string $tssId, string $exportId, string $targetFile): string {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->FiskalySignDe->ExportService ()->SaveExportFile ($tssId, $exportId, $targetFile);
		if ($this->debugOutput) {
			$this->PrintResult ('Save Export File', ['path' => $response]);
		}

		return $response;
	}

	#endregion

	#region Management API methods

	# Authentication
	/**
	 * Authenticate with the Fiskaly Management API using the provided API key and secret.
	 * The authentication token will be stored in the token storage for subsequent requests.
	 *
	 * @return void
	 * @throws Exception if authentication fails
	 */
	public function AuthenticateManagement (): void {
		$response = $this->FiskalyManagement->AuthenticationService ()->AuthenticateWithApiKey ($this->managedApiKey, $this->managedApiSecret);
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

		$response = $this->FiskalyManagement->ApiKeyService ()->ListApiKeys ($organizationId);

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
	public function CreateApiCredentials ($organizationId): Response {

		$response = $this->FiskalyManagement->ApiKeyService ()->CreateApiKey ($organizationId, $this->newApiCredentials);

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

		$response = $this->FiskalyManagement->ApiKeyService ()->RetrieveApiKey ($organizationId, $keyId);
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

		$response = $this->FiskalyManagement->ApiKeyService ()->UpdateApiKey ($organizationId, $keyId, $data);
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

		$response = $this->FiskalyManagement->ApiKeyService ()->DeleteApiKey ($organizationId, $keyId);
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

		$response = $this->FiskalyManagement->UserService ()->ListUsers ($organizationId, $filter);
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

		$response = $this->FiskalyManagement->UserService ()->InviteUser ($organizationId, $email, $firstName, $lastName);
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

		$response = $this->FiskalyManagement->UserService ()->DeleteUser ($organizationId, $userId);
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

		$response = $this->FiskalyManagement->BillingAddressService ()->ListBillingAddresses ();
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

		$response = $this->FiskalyManagement->BillingAddressService ()->CreateBillingAddress ($data);
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

		$response = $this->FiskalyManagement->BillingAddressService ()->RetrieveBillingAddress ($addressId);
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

		$response = $this->FiskalyManagement->BillingAddressService ()->UpdateBillingAddress ($addressId, $data);
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

		$response = $this->FiskalyManagement->BillingAddressService ()->DeleteBillingAddress ($addressId);
		if ($this->debugOutput) {
			$this->PrintResult ('Delete Billing Address', $response);
		}

		return $response;
	}

	#endregion

	#region Helper methods

	/**
	 * Print a title as an HTML heading for better readability of the output.
	 *
	 * @param $title
	 *
	 * @return void
	 */
	private function Print ($title): void {
		echo '<h3>' . $title . '</h3>';
	}

	/**
	 * Print the response from the API in a readable format, including the title for context.
	 *
	 * @param                $title
	 * @param Response|array $response
	 *
	 * @return void
	 */
	private function PrintResult ($title, Response|array $response): void {
		echo '<h2>' . $title . '</h2>';
		echo '<pre>';
		print_r ($response instanceof Response ? $response->GetBody () : $response);
		echo '</pre>';
	}

	/**
	 * Handle exceptions by printing the error message, file, line number, and stack trace for debugging purposes.
	 *
	 * @param Exception $e
	 *
	 * @return void
	 */
	private function HandleError (Exception $e): void {

		if ($this->debugOutput) {

			echo 'Error: ' . $e->getMessage () . '<br>';
			echo 'In file: ' . $e->getFile () . ' at line ' . $e->getLine () . '<br>';
			echo 'Stack trace: <pre>' . $e->getTraceAsString () . '</pre>';
		} else if ($this->debug) {
			echo 'An error occurred' . $e->getMessage () . '<br>';
		} else {
			echo 'An error occurred. Please check the logs for more details.';
		}
	}

	#endregion

}
