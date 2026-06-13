<?php

namespace DD\Fiskaly;

use DD\Fiskaly\Http\Response;

abstract class FiskalyBase {

	public bool $debug = false;

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

	#region Helper methods

	/**
	 * Print a title as an HTML heading for better readability of the output.
	 *
	 * @param $title
	 *
	 * @return void
	 */
	public function Print ($title): void {
		echo $this->debug ? '<h3>' . $title . '</h3>' : '';
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

		if ($this->debug) {

			echo '<h2>' . $title . '</h2>';
			echo '<pre>';
			print_r ($response instanceof Response ? $response->GetBody () : $response);
			echo '</pre>';
		}
	}

	#endregion

}
