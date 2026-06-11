# DD\Fiskaly

Kleine PHP-Klassenbibliothek für die fiskaly SIGN DE API v2 und die fiskaly Management API.  
Schlank gehalten, benötigt nur PHP, cURL und JSON. Alle öffentlichen Methoden sind in PascalCase benannt.

## Installation

```bash
composer require drechsler-development/fiskaly
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

// Option A: FULL usage (Management + Sign DE) — provide your MANAGED organization credentials
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

### Verwendung von Fiskaly Sign DE (TSS, Clients, Transaktionen, Exporte) ohne Management-Credentials

Wenn Sie nur die SIGN DE API (TSS, Clients, Transaktionen, Exporte) verwenden möchten und keine Management-Credentials (managed API-Key / Secret) haben, können Sie die Bibliothek ebenfalls verwenden. Geben Sie beim Konstruktor keine Management-Credentials an und setzen Sie stattdessen die Sign-DE API-Credentials:

```php
<?php

declare(strict_types=1);

use DD\Fiskaly\FiskalySignDe;

require __DIR__ . '/../vendor/autoload.php';

// Option A: FULL usage (Management + Sign DE) — provide your MANAGED organization credentials
$apiKey         = 'YOUR_TEST_API_KEY';
$apiSecret      = 'YOUR_TEST_API_SECRET';

try {
	$FiskalySignDe = new FiskalySignDe($apiKey, $apiSecret);
	$FiskalySignDe->SetDebugOutput(true);

	// Nun können Sie sich an der Sign DE API authentifizieren und Sign-DE-Methoden verwenden
	$FiskalySignDe->Authenticate();
	// z.B. $Fiskaly->CreateTss('tss-id');

} catch (Exception $e) {
	echo 'Fehler: ' . $e->getMessage();
}
```

### Direkte Nutzung der Modulklassen (Composer-User)

Wenn Sie die Module direkt verwenden und sich die Klassenbibliothek per Composer einbinden, können Sie `FiskalyManagement` und `FiskalySignDe` auch eigenständig instanziieren. Beide Klassen akzeptieren optional API-Credentials im Konstruktor — so können Sie die Authentifizierung sofort beim Erstellen durchführen.

Beispiele:

```php
use DD\Fiskaly\Auth\SessionStorage;use DD\Fiskaly\Configuration\Configuration;use DD\Fiskaly\FiskalyManagement;use DD\Fiskaly\FiskalySignDe;

$configSign = new Configuration(Configuration::DEFAULT_BASE_URL_SIGN_DE);
$configMgmt = new Configuration(Configuration::DEFAULT_BASE_URL_MANAGEMENT);
$storage = new SessionStorage();

// Sign DE direkt mit Credentials
$signDe = new FiskalySignDe($configSign, $storage, 'YOUR_SIGN_DE_API_KEY', 'YOUR_SIGN_DE_API_SECRET');
// nun direkt nutzbar: $signDe->TssService()->CreateTss('tss-id');

// Management direkt mit Credentials
$management = new FiskalyManagement($configMgmt, $storage, 'YOUR_MANAGED_API_KEY', 'YOUR_MANAGED_API_SECRET', 'YOUR_MANAGED_ORGANIZATION_ID');
// nun direkt nutzbar: $management->OrganizationService()->ListOrganizations([...]);
```

## Hinweise

- UUIDv4 für neue Ressourcen: `DD\Fiskaly\Util\Uuid::V4()`
- Admin-Operationen erfordern zuerst API-Authentifizierung, danach TSS-Level-Admin-Auth.
- Fehler werden als `ApiException` geworfen (Statuscode, fiskaly Error-Code, Response, Request-ID).

## Beispiele & Tests

Praktische Beispiele für alle Services befinden sich in [`examples/index.php`](examples/index.php).  
Die dort verwendete `TestClass` (`examples/TestClass.php`) deckt bereits ein paar Methoden zum Erstellen/Löschen einer Organisation, Erstellen einer TSE, eines Clients und Hinzufügen einer Transaktion ab und kann direkt als Ausgangspunkt genutzt werden. 
Rufe dabei die ->Run() Methode auf, um diese Beispiele auszuführen. Eine simple json ausgabe zeigt die Ergebnisse an.
