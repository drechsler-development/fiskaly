<?php

declare(strict_types=1);

namespace DD\Fiskaly;

use DD\Fiskaly\Auth\IStorageInterface;
use DD\Fiskaly\Auth\SessionStorage;
use DD\Fiskaly\Configuration\Configuration;
use DD\Fiskaly\Http\AuthenticatedHttpClient;
use DD\Fiskaly\Http\HttpClient;
use DD\Fiskaly\Service\AuthenticationService;
use DD\Fiskaly\Service\CashPointClosingService;
use DD\Fiskaly\Service\CashRegisterService;
use DD\Fiskaly\Service\ExportServiceDsfinv;
use Exception;
use Random\RandomException;

class FiskalyDSFinV extends FiskalyBase {

    private HttpClient              $httpClient;
    private AuthenticatedHttpClient $authenticatedHttpClient;

    private AuthenticationService    $authenticationService;
    private CashRegisterService      $cashRegisterService;
    private CashPointClosingService  $cashPointClosingService;
    private ExportServiceDsfinv      $exportService;

    private ?string $apiKey;
    private ?string $apiSecret;

    /**
     * @param string            $apiKey
     * @param string            $apiSecret
     * @param IStorageInterface $tokenStorage
     * @throws RandomException
     */
    public function __construct (string $apiKey, string $apiSecret, IStorageInterface $tokenStorage = new SessionStorage()) {

        $configuration                 = new Configuration(Configuration::DEFAULT_BASE_URL_DSFINVK);
        $this->httpClient              = new HttpClient($configuration);
        $this->authenticatedHttpClient = new AuthenticatedHttpClient($configuration, $tokenStorage);
        $this->authenticationService   = new AuthenticationService($this->httpClient, $tokenStorage);

        $this->cashRegisterService     = new CashRegisterService($this->authenticatedHttpClient);
        $this->cashPointClosingService = new CashPointClosingService($this->authenticatedHttpClient);
        $this->exportService           = new ExportServiceDsfinv($this->authenticatedHttpClient);

        $this->apiKey    = $apiKey;
        $this->apiSecret = $apiSecret;

        $this->authenticationService->AuthenticateWithApiKey ($this->apiKey, $this->apiSecret);

    }

    #region GETTER

    public function AuthenticationService (): AuthenticationService {
        return $this->authenticationService;
    }

    public function CashRegisterService (): CashRegisterService {
        return $this->cashRegisterService;
    }

    public function CashPointClosingService (): CashPointClosingService {
        return $this->cashPointClosingService;
    }

    public function ExportService (): ExportServiceDsfinv {
        return $this->exportService;
    }

    #endregion

    /**
     * Authenticate with the Fiskaly DSFinV API using the provided API key and secret.
     * The authentication token will be stored in the token storage for subsequent requests.
     *
     * @return void
     * @throws Exception if authentication fails
     */
    public function Authenticate (): void {
        $response = $this->AuthenticationService ()->AuthenticateWithApiKey ($this->apiKey, $this->apiSecret);
        if ($this->debug) {
            $this->PrintResult ('AuthenticateDSFinV Info', $response);
        }
    }

    # Cash register methods

    public function ListCashRegisters (array $query = []): array {
        $response = $this->CashRegisterService ()->ListCashRegisters ($query);
        if ($this->debug) {
            $this->PrintResult ('List Cash Registers', $response);
        }
        return $response;
    }

    public function CreateCashRegister (string $clientId, array $data): array {
        $response = $this->CashRegisterService ()->CreateCashRegister ($clientId, $data);
        if ($this->debug) {
            $this->PrintResult ('Create Cash Register', $response);
        }
        return $response;
    }

    public function RetrieveCashRegister (string $clientId): array {
        $response = $this->CashRegisterService ()->RetrieveCashRegister ($clientId);
        if ($this->debug) {
            $this->PrintResult ('Retrieve Cash Register', $response);
        }
        return $response;
    }

    public function UpdateCashRegister (string $clientId, array $data): array {
        $response = $this->CashRegisterService ()->UpdateCashRegister ($clientId, $data);
        if ($this->debug) {
            $this->PrintResult ('Update Cash Register', $response);
        }
        return $response;
    }

    public function DeleteCashRegister (string $clientId): array {
        $response = $this->CashRegisterService ()->DeleteCashRegister ($clientId);
        if ($this->debug) {
            $this->PrintResult ('Delete Cash Register', $response);
        }
        return $response;
    }

    # Cash point closing methods

    public function ListCashPointClosings (array $query = []): array {
        $response = $this->CashPointClosingService ()->ListCashPointClosings ($query);
        if ($this->debug) {
            $this->PrintResult ('List Cash Point Closings', $response);
        }
        return $response;
    }

    public function CreateCashPointClosing (string $closingId, array $body = []): array {
        $response = $this->CashPointClosingService ()->CreateCashPointClosing ($closingId, $body);
        if ($this->debug) {
            $this->PrintResult ('Create Cash Point Closing', $response);
        }
        return $response;
    }

    public function RetrieveCashPointClosing (string $closingId): array {
        $response = $this->CashPointClosingService ()->RetrieveCashPointClosing ($closingId);
        if ($this->debug) {
            $this->PrintResult ('Retrieve Cash Point Closing', $response);
        }
        return $response;
    }

    public function UpdateCashPointClosing (string $closingId, array $body): array {
        $response = $this->CashPointClosingService ()->UpdateCashPointClosing ($closingId, $body);
        if ($this->debug) {
            $this->PrintResult ('Update Cash Point Closing', $response);
        }
        return $response;
    }

    public function DeleteCashPointClosing (string $closingId): array {
        $response = $this->CashPointClosingService ()->DeleteCashPointClosing ($closingId);
        if ($this->debug) {
            $this->PrintResult ('Delete Cash Point Closing', $response);
        }
        return $response;
    }

    # Export methods

    public function TriggerExport (string $cashRegisterId, string $exportId, array $query = []): array {
        $response = $this->ExportService ()->TriggerExport ($cashRegisterId, $exportId, $query);
        if ($this->debug) {
            $this->PrintResult ('Trigger Export DSFinV', $response);
        }
        return $response;
    }

    public function RetrieveExport (string $cashRegisterId, string $exportId): array {
        $response = $this->ExportService ()->RetrieveExport ($cashRegisterId, $exportId);
        if ($this->debug) {
            $this->PrintResult ('Retrieve Export DSFinV', $response);
        }
        return $response;
    }

    public function CancelExport (string $cashRegisterId, string $exportId): array {
        $response = $this->ExportService ()->CancelExport ($cashRegisterId, $exportId);
        if ($this->debug) {
            $this->PrintResult ('Cancel Export DSFinV', $response);
        }
        return $response;
    }

    public function RetrieveExportMetadata (string $cashRegisterId, string $exportId): array {
        $response = $this->ExportService ()->RetrieveMetadata ($cashRegisterId, $exportId);
        if ($this->debug) {
            $this->PrintResult ('Retrieve Export Metadata DSFinV', $response);
        }
        return $response;
    }

    public function UpdateExportMetadata (string $cashRegisterId, string $exportId, array $metadata): array {
        $response = $this->ExportService ()->UpdateMetadata ($cashRegisterId, $exportId, $metadata);
        if ($this->debug) {
            $this->PrintResult ('Update Export Metadata DSFinV', $response);
        }
        return $response;
    }

    public function ListExports (string $cashRegisterId, array $query = []): array {
        $response = $this->ExportService ()->ListExports ($cashRegisterId, $query);
        if ($this->debug) {
            $this->PrintResult ('List Exports DSFinV', $response);
        }
        return $response;
    }

    public function SaveExportFile (string $cashRegisterId, string $exportId, string $targetFile): string {
        $response = $this->ExportService ()->SaveExportFile ($cashRegisterId, $exportId, $targetFile);
        if ($this->debug) {
            $this->PrintResult ('Save Export File DSFinV', ['path' => $response]);
        }
        return $response;
    }

}
