<?php

declare(strict_types=1);

namespace DD\Fiskaly;

use DD\Fiskaly\Auth\IStorageInterface;
use DD\Fiskaly\Auth\SessionStorage;
use DD\Fiskaly\Configuration\Configuration;
use DD\Fiskaly\Http\AuthenticatedHttpClient;
use DD\Fiskaly\Http\HttpClient;
use DD\Fiskaly\Service\AdminService;
use DD\Fiskaly\Service\AuthenticationService;
use DD\Fiskaly\Service\ClientService;
use DD\Fiskaly\Service\ExportService;
use DD\Fiskaly\Service\TransactionService;
use DD\Fiskaly\Service\TssService;
use Exception;
use Random\RandomException;

class FiskalySignDe extends FiskalyBase {

	private HttpClient              $httpClient;
	private AuthenticatedHttpClient $authenticatedHttpClient;

	private AuthenticationService $authenticationService;
	private AdminService          $adminService;
	private TssService            $tssService;
	private ClientService         $clientService;
	private TransactionService    $transactionService;
	private ExportService         $exportService;

	private ?string $apiKey;
	private ?string $apiSecret;

	public string $adminPin = '';
	public string $adminPuk = '';

	/**
	 * @param string            $apiKey
	 * @param string            $apiSecret
	 * @param IStorageInterface $tokenStorage
	 *
	 * @throws RandomException
	 */
	public function __construct (string $apiKey, string $apiSecret, IStorageInterface $tokenStorage = new SessionStorage()) {

		$configuration                 = new Configuration(Configuration::DEFAULT_BASE_URL_SIGN_DE);
		$this->httpClient              = new HttpClient($configuration);
		$this->authenticatedHttpClient = new AuthenticatedHttpClient($configuration, $tokenStorage);
		$this->authenticationService   = new AuthenticationService($this->httpClient, $tokenStorage);

		$this->adminService       = new AdminService($this->authenticatedHttpClient);
		$this->tssService         = new TssService($this->authenticatedHttpClient);
		$this->clientService      = new ClientService($this->authenticatedHttpClient);
		$this->transactionService = new TransactionService($this->authenticatedHttpClient);
		$this->exportService      = new ExportService($this->authenticatedHttpClient);

		$this->apiKey    = $apiKey;
		$this->apiSecret = $apiSecret;

		$this->authenticationService->AuthenticateWithApiKey ($this->apiKey, $this->apiSecret);

	}

	#region GETTER

	public function AuthenticationService (): AuthenticationService {
		return $this->authenticationService;
	}

	public function AdminService (): AdminService {
		return $this->adminService;
	}

	public function TssService (): TssService {
		return $this->tssService;
	}

	public function ClientService (): ClientService {
		return $this->clientService;
	}

	public function TransactionService (): TransactionService {
		return $this->transactionService;
	}

	public function ExportService (): ExportService {
		return $this->exportService;
	}

	#endregion

	# Authentication
	/**
	 * Authenticate with the Fiskaly Sign DE API using the provided API key and secret.
	 * The authentication token will be stored in the token storage for subsequent requests.
	 *
	 * @return void
	 * @throws Exception if authentication fails
	 */
	public function Authenticate (): void {
		$response = $this->AuthenticationService ()->AuthenticateWithApiKey ($this->apiKey, $this->apiSecret);
		if ($this->debug) {
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

		$response = $this->AdminService ()->AuthenticateAdmin ($tssId, $this->adminPin);

		if ($this->debug) {
			$this->PrintResult ('Authenticate Admin', ['success' => empty($response)]);
		}
	}

	/**
	 * Change the admin PIN for the specified TSS ID using the stored admin PUK and new admin PIN.
	 *
	 * @param string $tssId The ID of the TSS for which to change the admin PIN.
	 * @param string $newAdminPin
	 *
	 * @return void
	 * @throws RandomException
	 */
	public function ChangeAdminPin (string $tssId, string $newAdminPin): void {

		//$this->AuthenticateAdmin ($tssId);

		$response = $this->AdminService ()->ChangeAdminPin ($tssId, $this->adminPuk, $newAdminPin);
		if ($this->debug) {
			$this->PrintResult ('Change Admin Pin', ['success' => empty($response)]);
		}

		$this->adminPin = $newAdminPin;

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

		$response = $this->AdminService ()->ChangeAdminPin ($tssId, $adminPuk, $newPin);
		if ($this->debug) {
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

		$response = $this->AdminService ()->LogoutAdmin ($tssId);

		if ($this->debug) {
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

		$response = $this->TssService ()->RetrieveTss ($tssId);
		if ($this->debug) {
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

		$response = $this->TssService ()->CreateTss ($tssId, [
			'internal_reference' => 'vabs-test-tss',
		]);

		if ($this->debug) {
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

		$response = $this->TssService ()->SetUninitialized ($tssId);
		if ($this->debug) {
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

		$response = $this->TssService ()->SetInitialized ($tssId);
		if ($this->debug) {
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
		$response = $this->TssService ()->UpdateTss ($tssId, $state, $metadata, $description);
		if ($this->debug) {
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

		$response = $this->TssService ()->DisableTss ($tssId);
		if ($this->debug) {
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
		$response = $this->TssService ()->ListTss ($query);
		if ($this->debug) {
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
		$response = $this->TssService ()->RetrieveMetadata ($tssId);
		if ($this->debug) {
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
		$response = $this->TssService ()->UpdateMetadata ($tssId, $metadata);
		if ($this->debug) {
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

		$response = $this->ClientService ()->ListAllClients ();
		if ($this->debug) {
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

		$response = $this->ClientService ()->ListClients ($tssId);
		if ($this->debug) {
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
	public function CreateClient (string $tssId, string $clientId, string $clientSerialNumber): array {

		$this->AuthenticateAdmin ($tssId);

		$response = $this->ClientService ()->CreateClient ($tssId, $clientId, $clientSerialNumber);
		if ($this->debug) {
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
		$response = $this->ClientService ()->RetrieveClient ($tssId, $clientId);
		if ($this->debug) {
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
		$response = $this->ClientService ()->RegisterClient ($tssId, $clientId);
		if ($this->debug) {
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
	 * @throws Exception
	 */
	public function DeregisterClient (string $tssId, string $clientId): array {

		$this->AuthenticateAdmin ($tssId);
		$response = $this->ClientService ()->DeregisterClient ($tssId, $clientId);
		if ($this->debug) {
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
		$response = $this->ClientService ()->RetrieveMetadata ($tssId, $clientId);
		if ($this->debug) {
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
		$response = $this->ClientService ()->UpdateMetadata ($tssId, $clientId, $metadata);
		if ($this->debug) {
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

		$response = $this->TransactionService ()->StartTransaction ($tssId, $txId, $clientId, $schema, $metadata, $txRevision);
		if ($this->debug) {
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

		$response = $this->TransactionService ()->FinishTransaction ($tssId, $txIdOrNumber, $clientId, $schema, $metadata, $txRevision);
		if ($this->debug) {
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

		$response = $this->TransactionService ()->CancelTransaction ($tssId, $txIdOrNumber, $clientId, $schema, $metadata, $txRevision);
		if ($this->debug) {
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

		$response = $this->TransactionService ()->RetrieveTransaction ($tssId, $txIdOrNumber, $query);
		if ($this->debug) {
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

		$response = $this->TransactionService ()->RetrieveTransactionLog ($tssId, $txIdOrNumber, $query);
		if ($this->debug) {
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

		$response = $this->TransactionService ()->ListTransactions ($tssId, $query);
		if ($this->debug) {
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

		$response = $this->TransactionService ()->ListAllTransactions ($query);
		if ($this->debug) {
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

		$response = $this->TransactionService ()->ListClientTransactions ($tssId, $clientId, $query);
		if ($this->debug) {
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

		$response = $this->TransactionService ()->RetrieveMetadata ($tssId, $txIdOrNumber);
		if ($this->debug) {
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

		$response = $this->TransactionService ()->UpdateMetadata ($tssId, $txIdOrNumber, $metadata);
		if ($this->debug) {
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
		$response = $this->ExportService ()->TriggerExport ($tssId, $exportId, $query);
		if ($this->debug) {
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
		$response = $this->ExportService ()->RetrieveExport ($tssId, $exportId);
		if ($this->debug) {
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
		$response = $this->ExportService ()->CancelExport ($tssId, $exportId);
		if ($this->debug) {
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
		$response = $this->ExportService ()->ListExports ($tssId, $query);
		if ($this->debug) {
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
		$response = $this->ExportService ()->ListAllExports ($query);
		if ($this->debug) {
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
		$response = $this->ExportService ()->RetrieveMetadata ($tssId, $exportId);
		if ($this->debug) {
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
		$response = $this->ExportService ()->UpdateMetadata ($tssId, $exportId, $metadata);
		if ($this->debug) {
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
		$response = $this->ExportService ()->SaveExportFile ($tssId, $exportId, $targetFile);
		if ($this->debug) {
			$this->PrintResult ('Save Export File', ['path' => $response]);
		}

		return $response;
	}
}
