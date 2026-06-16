<?php

declare(strict_types=1);

namespace DD\Fiskaly\Examples;

use DD\Fiskaly\FiskalyManagement;
use DD\Fiskaly\FiskalySignDe;
use DD\Fiskaly\Transaction\PaymentAmount;
use DD\Fiskaly\Transaction\Receipt;
use DD\Fiskaly\Util\Uuid;
use Exception;
use Random\RandomException;

/**
 * This class demonstrates how to use the Fiskaly Management API and Sign DE API to manage organizations, API credentials, and TSEs.
 * It includes methods for authenticating, creating organizations, managing TSEs, and handling transactions.
 * The example data is stored in a local SQLite database for demonstration purposes.
 * Please ensure to replace the placeholder API credentials with your actual test credentials before running the example.
 * Note: This example is for demonstration purposes only and may not cover all edge cases or error handling scenarios. It is recommended to implement additional error handling and validation as needed for production use.
 */
class TestClass {

	public string $tssId                 = '';
	public string $clientSerialNumber    = '';
	public string $clientId;
	public string $txId                  = '';
	public array  $deleteOrganizationIds = [];
	public string $organizationId        = '';

	public string $managedApiKey;
	public string $managedApiSecret;
	public string $apiKey;
	public string $apiSecret;
	public bool   $debug;

	/**
	 * @param string $managedApiKey
	 * @param string $managedApiSecret
	 * @param array  $deleteOrganizationIds
	 */
	public function __construct (string $managedApiKey, string $managedApiSecret, array $deleteOrganizationIds = []) {

		$this->managedApiKey         = $managedApiKey;
		$this->managedApiSecret      = $managedApiSecret;
		$this->deleteOrganizationIds = $deleteOrganizationIds;

	}

	/**
	 * runs a simple example of using the Fiskaly Management API and Sign DE API to manage organizations, API credentials, TSEs, and transactions.
	 *
	 * @param array $newOrganization
	 * @param array $apiCredentials
	 * @param bool  $debug
	 *
	 * @return void
	 * @throws RandomException
	 */
	public function Run (array $newOrganization, array $apiCredentials, bool $debug = false): void {

		$this->clientSerialNumber =  'KASSE-' . rand (1000, 9999);
		$this->clientId = Uuid::V4 ();
		$this->debug = $debug;

		##################
		### MANAGEMENT ###
		##################

		echo "<h1>Fiskaly Management Example</h1>";

		$FiskalyManagement = new FiskalyManagement($this->managedApiKey, $this->managedApiSecret);
		$FiskalyManagement->SetDebug ($this->debug);
		$FiskalyManagement->Authenticate ();

		if (!empty($this->deleteOrganizationIds)) {

			foreach ($this->deleteOrganizationIds as $organizationId) {
				$FiskalyManagement->Print ('Delete Organization');
				$FiskalyManagement->DeleteOrganization ($organizationId);
			}

			$this->deleteOrganizationIds = [];
			$this->apiKey                = '';
			$this->apiSecret             = '';

		}


		$this->PrintTitle ('Create New Organization');
		$response = $FiskalyManagement->CreateOrganization ($newOrganization);

		$newOrganizationId    = $response->GetBody ()['_id'] ?? '';
		$this->organizationId = $newOrganizationId;

		$this->PrintTitle ('Create API Credentials for New Organization');
		$response = $FiskalyManagement->CreateApiCredentials ($this->organizationId, $apiCredentials);

		$apiKey          = $response->GetBody ()['key'] ?? '';
		$apiSecret       = $response->GetBody ()['secret'] ?? '';
		$this->apiKey    = $apiKey;
		$this->apiSecret = $apiSecret;

		$FiskalyManagement->ListApiCredentials ($this->organizationId);


		$this->PrintTitle ('List Organizations');
		$FiskalyManagement->ListOrganizations ();

		/*$response = $this->OrganizationService->RetrieveOrganization ($this->organizationId);
		$this->PrintResult ('Retrieve Organization', $response);*/

		###############
		### SIGN DE ###
		###############

		echo "<h1>Fiskaly Sign DE Example</h1>";

		$FiskalySignDe = new FiskalySignDe($this->apiKey, $this->apiSecret);
		$FiskalySignDe->SetDebug ($this->debug);

		$this->PrintTitle ('Authenticate Sign DE');
		$FiskalySignDe->Authenticate ();

		if (!empty($this->adminPuk) && !empty($this->tssId)) {
			$this->PrintTitle ('Change Admin Pin');
			$FiskalySignDe->ChangeAdminPin ($this->tssId,'12345678');
		}

		if (empty($this->tssId) && empty($this->adminPuk)) {

			$this->tssId              = Uuid::V4 ();
			$this->clientId           = Uuid::V4 ();
			$this->txId               = Uuid::V4 ();

			$this->PrintTitle ('Create TSE');
			$response = $FiskalySignDe->CreateTss ($this->tssId);

			// Admin PUK will be created only in CreateTss. Please note to safe it, because it is needed to change the default admin pin and to authenticate as admin. It will not be shown again after CreateTss.
			$FiskalySignDe->adminPuk = $response['admin_puk'] ?? null;
			if (empty($FiskalySignDe->adminPuk)) {
				throw new Exception('Admin PUK not found in CreateTss response. Please check the response and ensure that the TSS was created successfully.');
			}

			//Just to be sure, that we have the tssId in our class property, because we need it for the following steps. It should be there, because we just created the TSE with this ID, but we check it anyway.
			$this->tssId = $response['_id'] ?? null;

			$state = $response['state'] ?? null;
			if ($state === 'CREATED') {

				$this->PrintTitle ('Set TSE to Uninitialized');
				$response = $FiskalySignDe->SetUninitialized ($this->tssId);
				$state    = $response['state'] ?? null;

				if ($state === 'UNINITIALIZED') {

					//Only if the state is uninitialized, we can continue with the ChangeAdminPin and SetInitialized steps, because these steps are only allowed in the uninitialized state. If the state is not uninitialized, it means that something went wrong in the previous step, and we cannot continue with the example, because we need a TSE in uninitialized state to continue.
					//As forced and described in the documentation, the first thing to do after creating a TSE is to change the default admin pin, because it is not allowed to use the default admin pin for security reasons. You need the admin puk for this step, which you get in the response of CreateTss and which you should have saved, because it will not be shown again.
					$this->PrintTitle ('Change Admin Pin');
					$FiskalySignDe->ChangeAdminPin ($this->tssId, '12345678');

					$this->PrintTitle ('Set TSE to Initialized');
					$FiskalySignDe->SetInitialized ($this->tssId);

					$this->PrintTitle ('Create Client for TSE');
					$FiskalySignDe->CreateClient ($this->tssId, $this->clientId, $this->clientSerialNumber);

					$this->PrintTitle ('Logout Admin');
					$FiskalySignDe->LogoutAdmin ($this->tssId);

				} else {
					throw new Exception('Failed to set TSE to uninitialized state. Current state: ' . $state);
				}
			} else if ($state === 'INITIALIZED') {
				throw new Exception('TSE is already in ' . $state);
			} else if ($state === 'UNINITIALIZED') {
				throw new Exception('TSE is already in ' . $state);
			} else {
				throw new Exception('Unexpected TSE state: ' . $state);
			}

		}

		$this->PrintTitle ('Start Transaction');

		$receipt = new Receipt();
		$receipt->SetReceiptType (Receipt::TYPE_RECEIPT);
		$receipt->SetCurrencyCode ('EUR');
		$receipt->AddVatAmount ('NORMAL', '119.00');
		$receipt->AddPaymentAmount (PaymentAmount::CASH, '119.00');

		$metadata = [
			'sales_order_document_id'   => 'SO-' . rand (1000, 9999),
			'sales_invoice_document_id' => 'SI-' . rand (1000, 9999),
		];

		$started = $FiskalySignDe->StartTransaction ($this->tssId, $this->txId, $this->clientId, $receipt->ToSchemaArray (), $metadata);

		$this->PrintTitle ('Finish Transaction');
		$revision = $started['latest_revision'] ?? null;
		$revision = (int)$revision + 1;
		$FiskalySignDe->FinishTransaction ($this->tssId, $this->txId, $this->clientId, $receipt->ToSchemaArray (), $metadata, $revision);

		if (!empty($this->organizationId)) {
			$this->PrintTitle ('Delete New Organization Created in Example');
			$FiskalyManagement->DeleteOrganization ($this->organizationId);
		}

	}

	/**
	 * @return void
	 * @throws RandomException
	 */
	function DeleteOrganizations () : void {

		$FiskalyManagement = new FiskalyManagement($this->managedApiKey, $this->managedApiSecret);
		$FiskalyManagement->SetDebug (true);
		$FiskalyManagement->Authenticate ();

		if (!empty($this->deleteOrganizationIds)) {

			foreach ($this->deleteOrganizationIds as $organizationId) {
				$FiskalyManagement->Print ('Delete Organization');
				$FiskalyManagement->DeleteOrganization ($organizationId);
			}

			$this->deleteOrganizationIds = [];
			$this->apiKey                = '';
			$this->apiSecret             = '';

		}

	}

	#endregion

	#region Helper methods

	/**
	 * @param $title
	 *
	 * @return void
	 */
	private function PrintTitle ($title): void {
		echo '<h3>' . $title . '</h3>';
	}


	#endregion

}
