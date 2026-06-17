<?php
/**** TEST FiskalyDSFin API ****/

declare(strict_types=1);

use DD\Fiskaly\FiskalyDSFinV;
use DD\Fiskaly\Util\ExceptionHandler;

require __DIR__ . '/../vendor/autoload.php';

$apiKey    = 'YOUR_API_KEY';
$apiSecret = 'YOUR_API_SECRET';

try {

	$client = new FiskalyDSFinV($apiKey, $apiSecret);
	// Optional: Authentifizierung (again)
	$client->Authenticate ();

	// Liste Kassen
	$cashRegisters = $client->ListCashRegisters (['limit' => 50]);
	print_r ($cashRegisters);

	if (!empty($cashRegisters)) {
		$first = $cashRegisters[0] ?? null;
		$id    = $first['id'] ?? null;
		if ($id) {
			// Trigger Export (Example: export id 'dsfinvk')
			$exportId = 'dsfinvk';
			$trigger  = $client->TriggerExport ($id, $exportId);
			print_r ($trigger);

			// Warten / Polling in real world: hier direkt abrufen
			$export = $client->RetrieveExport ($id, $exportId);
			print_r ($export);

			// Save file if available
			$target = __DIR__ . '/export_' . $id . '_' . $exportId . '.zip';
			$path   = $client->SaveExportFile ($id, $exportId, $target);
			echo "Saved export to: $path\n";
		}
	}

} catch (Exception $e) {
	ExceptionHandler::HandleError ($e);
}
