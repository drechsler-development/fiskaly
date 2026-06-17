<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Http\AuthenticatedHttpClient;
use Random\RandomException;

final class CashRegisterService extends AbstractService {

    public function __construct (AuthenticatedHttpClient $client) {
        parent::__construct ($client);
    }

    /**
     * @param array $query
     * @return array
     * @throws RandomException
     */
    public function ListCashRegisters (array $query = []): array {
        return $this->Json ('GET', '/cash_registers', null, $this->BuildQueryString($query));
    }

	/**
	 * @param string $clientId
	 * @param array  $body
	 *
	 * @return array
	 * @throws RandomException
	 */
    public function CreateCashRegister (string $clientId, array $body): array {
        return $this->Json ('PUT', $this->Path ('/cash_registers/{clientId}', ['clientId' => $clientId]), $body);
    }

    /**
     * @param string $clientId
     *
     * @return array
     * @throws RandomException
     */
    public function RetrieveCashRegister (string $clientId): array {
        return $this->Json ('GET', $this->Path('/cash_registers/{clientId}', ['clientId' => $clientId]));
    }

    /**
     * @param string $clientId
     * @param array $body
     *
     * @return array
     * @throws RandomException
     */
    public function UpdateCashRegister (string $clientId, array $body): array {
        return $this->Json ('PUT', $this->Path('/cash_registers/{clientId}', ['clientId' => $clientId]), $body);
    }

    /**
     * @param string $clientId
     *
     * @return array
     * @throws RandomException
     */
    public function DeleteCashRegister (string $clientId): array {
        return $this->Json ('DELETE', $this->Path('/cash_registers/{clientId}', ['clientId' => $clientId]));
    }

}
