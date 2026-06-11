<?php

declare(strict_types=1);

namespace DD\Fiskaly\Examples;

use DD\Fiskaly\Fiskaly;
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
class TestClass extends Fiskaly {

	public string $tssId          = '';
	public string $clientId       = '';
	public string $txId           = '';
	public string $organizationId = '';

	/**
	 * @param string $managedApiKey
	 * @param string $managedApiSecret
	 * @param string $managedOrganizationId
	 */
	public function __construct (string $managedApiKey, string $managedApiSecret, string $managedOrganizationId) {

		parent::__construct ($managedApiKey, $managedApiSecret, $managedOrganizationId);

	}

	/**
	 * runs a simple example of using the Fiskaly Management API and Sign DE API to manage organizations, API credentials, TSEs, and transactions.
	 *
	 * @param bool $deleteOrganization
	 * @param bool $createOrganization
	 * @param bool $createTSE
	 * @param bool $deleteNewOrganisationAfterwards
	 *
	 * @return void
	 * @throws RandomException
	 * @throws Exception
	 */
	public function Run (bool $deleteOrganization = false, bool $createOrganization = false, bool $createTSE = false, bool $deleteNewOrganisationAfterwards = false): void {

		##################
		### MANAGEMENT ###
		##################

		$this->AuthenticateManagement ();

		if ($deleteOrganization && empty($this->organizationId)) {
			throw new Exception('Delete organization flag is set to true, but no organization ID is available to delete.');
		}

		if ($deleteOrganization) {

			$this->Print ('Delete Organization');
			$this->DeleteOrganization ($this->organizationId);

			$this->organizationId = '';
			$this->apiKey         = '';
			$this->apiSecret      = '';

		}

		if ($createOrganization) {

			$this->Print ('Create New Organization');
			$response = $this->CreateOrganization ($this->newOrganization);

			$newOrganizationId    = $response->GetBody ()['_id'] ?? '';
			$this->organizationId = $newOrganizationId;

			$this->Print ('Create API Credentials for New Organization');
			$response = $this->CreateApiCredentials ($this->organizationId);

			$apiKey          = $response->GetBody ()['key'] ?? '';
			$apiSecret       = $response->GetBody ()['secret'] ?? '';
			$this->apiKey    = $apiKey;
			$this->apiSecret = $apiSecret;

			$this->ListApiCredentials ($this->organizationId);

		}

		$this->Print ('List Organizations');
		$this->ListOrganizations ();

		/*$response = $this->OrganizationService->RetrieveOrganization ($organizationId);
		$this->PrintResult ('Retrieve Organization', $response);*/

		###############
		### SIGN DE ###
		###############

		echo "<h1>Fiskaly Sign DE Example</h1>";

		$this->Print ('Authenticate Sign DE');
		$this->AuthenticateSignDe ();

		if (!empty($this->adminPuk) && !empty($this->tssId)) {
			$this->Print ('Change Admin Pin');
			$this->ChangeAdminPin ($this->tssId);
		}

		if ($createTSE && empty($this->tssId) && empty($this->adminPuk)) {

			$this->tssId    = Uuid::V4 ();
			$this->clientId = Uuid::V4 ();
			$this->txId     = Uuid::V4 ();

			$this->Print ('Create TSE');
			$response = $this->CreateTss ($this->tssId);

			// Admin PUK will be created only in CreateTss. Please note to safe it, because it is needed to change the default admin pin and to authenticate as admin. It will not be shown again after CreateTss.
			$this->adminPuk = $response['admin_puk'] ?? null;
			if (empty($this->adminPuk)) {
				throw new Exception('Admin PUK not found in CreateTss response. Please check the response and ensure that the TSS was created successfully.');
			}

			//Just to be sure, that we have the tssId in our class property, because we need it for the following steps. It should be there, because we just created the TSE with this ID, but we check it anyway.
			$this->tssId = $response['_id'] ?? null;

			$state = $response['state'] ?? null;
			if ($state === 'CREATED') {

				$this->Print ('Set TSE to Uninitialized');
				$response = $this->SetUninitialized ($this->tssId);
				$state    = $response['state'] ?? null;

				$this->Print ('Change Admin Pin');
				$this->ChangeAdminPin ($this->tssId);

				if ($state === 'UNINITIALIZED') {

					$this->Print ('Set TSE to Initialized');
					$this->SetInitialized ($this->tssId);

					$this->Print ('Create Client for TSE');
					$this->CreateClient ($this->tssId, $this->clientId);

					$this->Print ('Logout Admin');
					$this->LogoutAdmin ($this->tssId);

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

		$this->Print ('Start Transaction');

		$receipt = new Receipt();
		$receipt->SetReceiptType (Receipt::TYPE_RECEIPT);
		$receipt->SetCurrencyCode ('EUR');
		$receipt->AddVatAmount ('NORMAL', '119.00');
		$receipt->AddPaymentAmount (PaymentAmount::CASH, '119.00');

		$metadata = [
			'sales_order_document_id'   => 'SO-' . rand (1000, 9999),
			'sales_invoice_document_id' => 'SI-' . rand (1000, 9999),
		];

		$started = $this->StartTransaction ($this->tssId, $this->txId, $this->clientId, $receipt->ToSchemaArray (), $metadata, 1);

		$this->Print ('Finish Transaction');
		$revision = $started['latest_revision'] ?? null;
		$revision = (int)$revision + 1;
		$this->FinishTransaction ($this->tssId, $this->txId, $this->clientId, $receipt->ToSchemaArray (), $metadata, $revision);

		if ($deleteNewOrganisationAfterwards && !empty($this->organizationId)) {
			$this->Print ('Delete New Organization Created in Example');
			$this->DeleteOrganization ($this->organizationId);
		}

	}

	#endregion

	#region Helper methods

	/**
	 * @param $title
	 *
	 * @return void
	 */
	private function Print ($title): void {
		echo '<h3>' . $title . '</h3>';
	}

	/**
	 * @param Exception $e
	 *
	 * @return void
	 */
	public static function HandleError (Exception $e): void {

		echo '<div style="color: red;">';
		echo 'Error: ' . $e->getMessage ();
		echo '</div>';
		echo '<div style="color: orangered;">';
		echo 'In file: ' . $e->getFile () . ' line: ' . $e->getLine ();
		echo '</div>';
		echo 'Stack trace: <pre>' . $e->getTraceAsString () . '</pre>';

		/*echo "<pre>";
		print_r ($e);
		echo "</pre>";*/
	}

	#endregion

}
