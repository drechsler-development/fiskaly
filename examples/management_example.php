<?php

declare(strict_types=1);

use DD\Fiskaly\Examples\TestClass;
use DD\Fiskaly\Util\ExceptionHandler;

require __DIR__ . '/../vendor/autoload.php';

//MAIN Organization credentials
$managedApiKey         = 'YOUR_TEST_API_KEY';
$managedApiSecret      = 'YOUR_TEST_API_SECRET';
$managedOrganizationId = 'YOUR_TEST_ORGANIZATION_ID';

$randomNumber        = rand (1000, 9999);
$apiCredentials      = [
	'name'   => 'test-api-key-kunde-' . $randomNumber,
	'status' => 'enabled',
];

$exampleOrganization = [
	'name'                       => 'Kunde 1',
	'country_code'               => 'DEU',
	'address_line1'              => 'Musterstraße 1',
	'zip'                        => '12345',
	'town'                       => 'Musterstadt',
	'managed_by_organization_id' => $managedOrganizationId,
];

try {

	//If you want to delete a previous created organizations that are not deleted yet in FOR TESTING PURPOSES, add the organization ID here, otherwise leave the array empty
	$orgIdsToBeDeleted = [
		//'xxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'
	];

	$TestClass = new TestClass($managedApiKey, $managedApiSecret, $orgIdsToBeDeleted);

	//Only if you want to delete a previous organization, otherwise comment out the following line or set the organization ID to an empty string
	//$TestClass->organizationId = 'XXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
	$TestClass->Run ($exampleOrganization, $apiCredentials, true);

	session_destroy ();

} catch (Exception $e) {

	ExceptionHandler::HandleError ($e);
}
