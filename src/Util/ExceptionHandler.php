<?php

namespace DD\Fiskaly\Util;

use Exception;

class ExceptionHandler {

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

}
