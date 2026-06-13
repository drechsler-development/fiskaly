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

		$errorCode = method_exists ($e, 'GetErrorCode') ? $e->GetErrorCode () : 'N/A';

		echo '<div style="color: red;">';
		echo 'ErrorMessage: ' . $e->getMessage ();
		echo '</div>';
		echo '<div style="color: red;">';
		echo 'Code: ' . $e->getCode ();
		echo '</div>';
		echo '<div style="color: red;">';
		echo 'ErrorCode: ' . $errorCode;
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
