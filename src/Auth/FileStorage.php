<?php

declare(strict_types=1);

namespace DD\Fiskaly\Auth;


use DD\Fiskaly\Exception\FileNotFoundException;
use DD\Fiskaly\Exception\FiskalyException;
use Exception;

final class FileStorage implements IStorageInterface {

	private string $filePath;

	/**
	 * @param string $filePath
	 */
	public function __construct (string $filePath) {

		if (!is_file ($filePath)) {
			throw new FileNotFoundException('Token-Datei existiert nicht: ' . $filePath);
		}

		$this->filePath = $filePath;

	}

	/**
	 * @return array|null
	 */
	public function GetTokenData (): ?array {

		try {

			$content = file_get_contents ($this->filePath);
			if ($content === false || trim ($content) === '') {
				return null;
			}

			$data = json_decode ($content, true);
			if (!is_array ($data)) {
				return null;
			}

		} catch (Exception $e) {
			throw new FiskalyException('Fehler beim Lesen der Token-Datei: ' . $e->getMessage(), 0, $e);
		}

		return $data;

	}

	/**
	 * @param array $tokenData
	 *
	 * @return void
	 */
	public function SetTokenData (array $tokenData): void {

		$directory = dirname ($this->filePath);

		if (!is_dir ($directory) && !mkdir ($directory, 0775, true) && !is_dir ($directory)) {
			throw new FiskalyException('Token-Verzeichnis konnte nicht erstellt werden: ' . $directory);
		}

		$json = json_encode ($tokenData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		if ($json === false || file_put_contents ($this->filePath, $json, LOCK_EX) === false) {
			throw new FiskalyException('Token-Datei konnte nicht geschrieben werden: ' . $this->filePath);
		}

	}

	/**
	 * @return void
	 */
	public function Clear (): void {

		if (is_file ($this->filePath)) {
			unlink ($this->filePath);
		}

	}
}
