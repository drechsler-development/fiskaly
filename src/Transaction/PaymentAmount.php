<?php

namespace DD\Fiskaly\Transaction;

use InvalidArgumentException;

final class PaymentAmount {

	public const string CASH     = 'CASH';
	public const string NON_CASH = 'NON_CASH';

	private readonly string           $paymentType;
	private readonly string|int|float $amount;

	public function __construct (string $paymentType, string|int|float $amount) {

		$this->ValidatePaymentType ($paymentType);
		$this->paymentType = $paymentType;
		$this->amount      = $amount;
	}

	public function ToArray (): array {

		return [
			'payment_type' => $this->paymentType,
			'amount'       => Money::Format ($this->amount),
		];

	}

	private function ValidatePaymentType (string $paymentType): void {

		if (!in_array ($paymentType, [self::CASH, self::NON_CASH], true)) {
			throw new InvalidArgumentException('Invalid payment type: ' . $paymentType);
		}

	}
}
