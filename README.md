# DD\Fiskaly

Kleine PHP-Klassenbibliothek für die fiskaly SIGN DE API v2 und die fiskaly Management API.  
Schlank gehalten, benötigt nur PHP, cURL und JSON. Alle öffentlichen Methoden sind in PascalCase benannt.

## Installation

```bash
composer require dd\fiskaly
```

## Aufbau

| Klasse | Beschreibung |
|---|---|
| `FiskalyManagement` | Management API (Organisationen, API-Keys, Benutzer, Rechnungsadressen) |
| `FiskalySignDe` | SIGN DE API v2 (TSS, Clients, Transaktionen, Exporte) |

## Token-Speicher

Der Token-Speicher wird beim Erstellen einer Instanz übergeben und speichert Access- und Refresh-Token.

| Klasse | Beschreibung |
|---|---|
| `Auth\FileTokenStorage` | Speichert Token in einer JSON-Datei |
| `Auth\SessionTokenStorage` | Speichert Token in der PHP-Session |

## Quickstart

Die folgende Beispielanwendung zeigt die grundlegende Nutzung der Fiskaly API.

```php
<?php

declare(strict_types=1);

use DD\Fiskaly\Fiskaly;

require __DIR__ . '/../vendor/autoload.php';

//MAIN Organization credentials
$managedApiKey         = 'YOUR_TEST_API_KEY';
$managedApiSecret      = 'YOUR_TEST_API_SECRET';
$managedOrganizationId = 'YOUR_TEST_ORGANIZATION_ID';

try {

	$Fiskaly = new Fiskaly($managedApiKey, $managedApiSecret, $managedOrganizationId);
	$Fiskaly->debugOutput = true;
	$response = $Fiskaly->ListOrganizations ();
	
	print_r($response);
	
	//...and more methods from the Fiskaly class to manage organizations, API credentials, TSEs, and transactions.

} catch (Exception $e) {
	echo 'Fehler: ' . $e->getMessage ();
}
```

## Hinweise

- UUIDv4 für neue Ressourcen: `DD\Fiskaly\Util\Uuid::V4()`
- Admin-Operationen erfordern zuerst API-Authentifizierung, danach TSS-Level-Admin-Auth.
- Fehler werden als `ApiException` geworfen (Statuscode, fiskaly Error-Code, Response, Request-ID).

## Beispiele & Tests

Praktische Beispiele für alle Services befinden sich in [`examples/index.php`](examples/index.php).  
Die dort verwendete `TestClass` (`examples/TestClass.php`) deckt bereits ein paar Methoden zum Erstellen/Löschen einer Organisation, Erstellen einer TSE, eines Clients und Hinzufügen einer Transaktion ab und kann direkt als Ausgangspunkt genutzt werden. 
Rufe dabei die ->Run() Methode auf, um diese Beispiele auszuführen. Eine simple json ausgabe zeigt die Ergebnisse an.
