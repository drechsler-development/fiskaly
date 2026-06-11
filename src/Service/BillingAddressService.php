<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Http\AuthenticatedHttpClient;
use DD\Fiskaly\Http\Response;
use Random\RandomException;

final class BillingAddressService extends AbstractService {

	public function __construct (AuthenticatedHttpClient $client) {
		parent::__construct ($client);
	}

	/**
	 * @return Response

	 * @throws RandomException
	 */
	public function ListBillingAddresses (): Response {
		return $this->SendHttpRequest ('GET', '/billing-addresses');
	}

	/**
	 * @param array<string,mixed> $data
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function CreateBillingAddress (array $data): Response {
		return $this->SendHttpRequest ('POST', '/billing-addresses', [], $data);
	}

	/**
	 * @param string $addressId
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function RetrieveBillingAddress (string $addressId): Response {
		return $this->SendHttpRequest ('GET', $this->Path ('/billing-addresses/{address_id}', [
			'address_id' => $addressId,
		]));
	}

	/**
	 * @param string              $addressId
	 * @param array<string,mixed> $data
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function UpdateBillingAddress (string $addressId, array $data): Response {
		return $this->SendHttpRequest ('PATCH', $this->Path ('/billing-addresses/{address_id}', [
			'address_id' => $addressId,
		]),                            [], $data);
	}

	/**
	 * @param string $addressId
	 *
	 * @return Response
	 * @throws RandomException
	 */
	public function DeleteBillingAddress (string $addressId): Response {
		return $this->SendHttpRequest ('DELETE', $this->Path ('/billing-addresses/{address_id}', [
			'address_id' => $addressId,
		]));
	}
}
