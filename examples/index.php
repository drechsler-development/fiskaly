<?php

declare(strict_types=1);

use DD\Fiskaly\Examples\TestClass;

require __DIR__ . '/../vendor/autoload.php';

//MAIN Organization credentials
$managedApiKey         = 'YOUR_TEST_API_KEY';
$managedApiSecret      = 'YOUR_TEST_API_SECRET';
$managedOrganizationId = 'YOUR_TEST_ORGANIZATION_ID';

$randomNumber       = rand (1000, 9999);
$apiCredentials     = [
	'name'   => 'test-api-key-kunde-' . $randomNumber,
	'status' => 'enabled',
];
$adminPin           = '12345678';
$organizationFilter = [
	'limit'    => 100,
	'order_by' => 'name',
	'order'    => 'asc',
];
$newOrganization    = [
	'name'                       => 'Kunde 1',
	'country_code'               => 'DEU',
	'address_line1'              => 'Musterstraße 1',
	'zip'                        => '12345',
	'town'                       => 'Musterstadt',
	'managed_by_organization_id' => $managedOrganizationId,
];

try {

	$TestClass              = new TestClass($managedApiKey, $managedApiSecret, $managedOrganizationId);
	$TestClass->debugOutput = true;

	$TestClass->newApiCredentials      = $apiCredentials;
	$TestClass->listOrganizationFilter = $organizationFilter;
	$TestClass->newOrganization        = $newOrganization;
	$TestClass->adminPin               = $adminPin;
	$TestClass->clientSerialNumber     = 'KASSE-' . $randomNumber;

	$TestClass->Run (false, true, true, false);

} catch (Exception $e) {
	echo 'Fehler: ' . $e->getMessage ();
}
