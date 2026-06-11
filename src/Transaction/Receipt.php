<?php

namespace DD\Fiskaly\Transaction;

use InvalidArgumentException;
use RuntimeException;

final class Receipt {

	public const string TYPE_RECEIPT         = 'RECEIPT';
	public const string TYPE_TRAINING        = 'TRAINING';
	public const string TYPE_TRANSFER        = 'TRANSFER';
	public const string TYPE_ORDER           = 'ORDER';
	public const string TYPE_CANCELLATION    = 'CANCELLATION';
	public const string TYPE_ABORT           = 'ABORT';
	public const string TYPE_BENEFIT_IN_KIND = 'BENEFIT_IN_KIND';
	public const string TYPE_INVOICE         = 'INVOICE';
	public const string TYPE_OTHER           = 'OTHER';
	public const string TYPE_ANNULATION      = 'ANNULATION';

	/**
	 * @var VatAmount[]
	 */
	private array $amountsPerVatRate = [];

	/**
	 * @var PaymentAmount[]
	 */
	private array $amountsPerPaymentType = [];

	private string $receiptType;
	private string $currencyCode;

	public function __construct (string $receiptType = self::TYPE_RECEIPT,string $currencyCode = 'EUR') {

		$this->SetReceiptType ($receiptType);
		$this->SetCurrencyCode ($currencyCode);

	}

	public function SetReceiptType (string $receiptType): self {

		$allowedTypes = [
			self::TYPE_RECEIPT,
			self::TYPE_TRAINING,
			self::TYPE_TRANSFER,
			self::TYPE_ORDER,
			self::TYPE_CANCELLATION,
			self::TYPE_ABORT,
			self::TYPE_BENEFIT_IN_KIND,
			self::TYPE_INVOICE,
			self::TYPE_OTHER,
			self::TYPE_ANNULATION,
		];

		if (!in_array ($receiptType, $allowedTypes, true)) {
			throw new InvalidArgumentException('Invalid receipt type: ' . $receiptType);
		}

		$this->receiptType = $receiptType;

		return $this;
	}

	public function SetCurrencyCode (string $currencyCode): self {
		if (!preg_match ('/^[a-zA-Z]{3}$/', $currencyCode)) {
			throw new InvalidArgumentException('Invalid currency code: ' . $currencyCode);
		}

		$this->currencyCode = strtoupper ($currencyCode);

		return $this;
	}

	public function AddVatAmount (string $vatRate, string|int|float $amount): self {
		$this->amountsPerVatRate[] = new VatAmount($vatRate, $amount);

		return $this;
	}

	public function AddPaymentAmount (string $paymentType, string|int|float $amount): self {
		$this->amountsPerPaymentType[] = new PaymentAmount($paymentType, $amount);

		return $this;
	}

	public function ToArray (): array {
		if (empty($this->amountsPerVatRate)) {
			throw new RuntimeException('Receipt needs at least one VAT amount.');
		}

		return [
			'receipt_type'             => $this->receiptType,
			'amounts_per_vat_rate'     => array_map (
				static fn(VatAmount $amount): array => $amount->ToArray (),
				$this->amountsPerVatRate
			),
			'amounts_per_payment_type' => array_map (
				static fn(PaymentAmount $amount): array => $amount->ToArray (),
				$this->amountsPerPaymentType
			),
			'currency_code'            => $this->currencyCode,
		];
	}

	public function ToSchemaArray (): array {
		return [
			'standard_v1' => [
				'receipt' => $this->ToArray (),
			],
		];
	}
}
