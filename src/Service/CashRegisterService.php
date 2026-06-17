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
     * @param array $body
     * @return array
     * @throws RandomException
     */
    public function CreateCashRegister (array $body): array {
        return $this->Json ('POST', '/cash_registers', $body);
    }

    /**
     * @param string $cashRegisterId
     * @return array
     * @throws RandomException
     */
    public function RetrieveCashRegister (string $cashRegisterId): array {
        return $this->Json ('GET', $this->Path('/cash_registers/{id}', ['id' => $cashRegisterId]));
    }

    /**
     * @param string $cashRegisterId
     * @param array $body
     * @return array
     * @throws RandomException
     */
    public function UpdateCashRegister (string $cashRegisterId, array $body): array {
        return $this->Json ('PATCH', $this->Path('/cash_registers/{id}', ['id' => $cashRegisterId]), $body);
    }

    /**
     * @param string $cashRegisterId
     * @return array
     * @throws RandomException
     */
    public function DeleteCashRegister (string $cashRegisterId): array {
        return $this->Json ('DELETE', $this->Path('/cash_registers/{id}', ['id' => $cashRegisterId]));
    }

}
