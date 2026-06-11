<?php


namespace DD\Fiskaly\Transaction;

use InvalidArgumentException;

final class Money {

	/**
	 * @param string|int|float $amount
	 *
	 * @return string
	 */
	public static function Format (string|int|float $amount): string {

		if (is_float ($amount)) {
			$amount = number_format ($amount, 5, '.', '');
		}

		$amount = str_replace (',', '.', (string)$amount);

		if (!preg_match ('/^-?\d+(\.\d+)?$/', $amount)) {
			throw new InvalidArgumentException('Invalid amount: ' . $amount);
		}

		return number_format ((float)$amount, 2, '.', '');

	}
}
