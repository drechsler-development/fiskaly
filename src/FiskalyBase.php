<?php

namespace DD\Fiskaly;

use DD\Fiskaly\Http\Response;
use Exception;

abstract class FiskalyBase {

	public bool $debug = false;
	public bool $debugOutput = false;

	/**
	 * Enable or disable debug mode for the Fiskaly class. When enabled, additional debug information will be printed to the console for API calls and responses.
	 *
	 * @param bool $debug Set to true to enable debug mode, or false to disable it.
	 *
	 * @return void
	 */
	public function SetDebug (bool $debug): void {
		$this->debug = $debug;
	}

	/**
	 * Enable or disable debug output for API responses. When enabled, the results of API calls will be printed to the console for debugging purposes.
	 *
	 * @param bool $debugOutput Set to true to enable debug output, or false to disable it.
	 *
	 * @return void
	 */
	public function SetDebugOutput (bool $debugOutput): void {
		$this->debugOutput = $debugOutput;
	}

	#region Helper methods

	/**
	 * Print a title as an HTML heading for better readability of the output.
	 *
	 * @param $title
	 *
	 * @return void
	 */
	public function Print ($title): void {
		echo '<h3>' . $title . '</h3>';
	}

	/**
	 * Print the response from the API in a readable format, including the title for context.
	 *
	 * @param                $title
	 * @param Response|array $response
	 *
	 * @return void
	 */
	protected function PrintResult ($title, Response|array $response): void {
		echo '<h2>' . $title . '</h2>';
		echo '<pre>';
		print_r ($response instanceof Response ? $response->GetBody () : $response);
		echo '</pre>';
	}

	/**
	 * Handle exceptions by printing the error message, file, line number, and stack trace for debugging purposes.
	 *
	 * @param Exception $e
	 *
	 * @return void
	 */
	protected function HandleError (Exception $e): void {

		if ($this->debugOutput) {

			echo 'Error: ' . $e->getMessage () . '<br>';
			echo 'In file: ' . $e->getFile () . ' at line ' . $e->getLine () . '<br>';
			echo 'Stack trace: <pre>' . $e->getTraceAsString () . '</pre>';
		} else if ($this->debug) {
			echo 'An error occurred' . $e->getMessage () . '<br>';
		} else {
			echo 'An error occurred. Please check the logs for more details.';
		}
	}

	#endregion

}
