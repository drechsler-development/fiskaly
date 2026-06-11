<?php

declare(strict_types=1);

namespace DD\Fiskaly\Modules;

use DD\Fiskaly\Auth\IStorageInterface;
use DD\Fiskaly\Configuration\Configuration;
use DD\Fiskaly\Http\AuthenticatedHttpClient;
use DD\Fiskaly\Http\HttpClient;
use DD\Fiskaly\Service\AdminService;
use DD\Fiskaly\Service\AuthenticationService;
use DD\Fiskaly\Service\ClientService;
use DD\Fiskaly\Service\ExportService;
use DD\Fiskaly\Service\TransactionService;
use DD\Fiskaly\Service\TssService;

final class FiskalySignDe {

	private HttpClient              $httpClient;
	private AuthenticatedHttpClient $authenticatedHttpClient;

	private AuthenticationService $authenticationService;
	private AdminService          $adminService;
	private TssService            $tssService;
	private ClientService         $clientService;
	private TransactionService    $transactionService;
	private ExportService         $exportService;

	public function __construct (Configuration $configuration, IStorageInterface $tokenStorage) {

		$this->httpClient              = new HttpClient($configuration);
		$this->authenticatedHttpClient = new AuthenticatedHttpClient($configuration, $tokenStorage);
		$this->authenticationService   = new AuthenticationService($this->httpClient, $tokenStorage, 'sign_de');

		$this->adminService            = new AdminService($this->authenticatedHttpClient);
		$this->tssService              = new TssService($this->authenticatedHttpClient);
		$this->clientService           = new ClientService($this->authenticatedHttpClient);
		$this->transactionService      = new TransactionService($this->authenticatedHttpClient);
		$this->exportService           = new ExportService($this->authenticatedHttpClient);

	}

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
}
