<?php

namespace DD\Fiskaly\Transaction;

use InvalidArgumentException;

final readonly class VatAmount {

	const string VAT_RATE_NORMAL  = 'NORMAL';
	const string VAT_RATE_REDUCED = 'REDUCED_1';
	const string VAT_RATE_SPECIAL = 'SPECIAL_RATE_1';
	const string VAT_RATE_NULL    = 'NULL';


	private string           $vatRate;
	private string|int|float $amount;

	/**
	 * @param string           $vatRate
	 * @param string|int|float $amount
	 */
	public function __construct (string $vatRate, string|int|float $amount) {

		if(!in_array ($vatRate, [self::VAT_RATE_NORMAL, self::VAT_RATE_REDUCED, self::VAT_RATE_SPECIAL, self::VAT_RATE_NULL], true)) {
			throw new InvalidArgumentException('Invalid VAT rate: ' . $vatRate . '. Allowed values are: ' . implode(', ', [self::VAT_RATE_NORMAL, self::VAT_RATE_REDUCED, self::VAT_RATE_SPECIAL, self::VAT_RATE_NULL]));
		}

		$this->vatRate = $vatRate;
		$this->amount  = $amount;

	}

	/**
	 * @return array
	 */
	public function ToArray (): array {
		return [
			'vat_rate' => $this->vatRate,
			'amount'   => Money::Format ($this->amount),
		];
	}
}
