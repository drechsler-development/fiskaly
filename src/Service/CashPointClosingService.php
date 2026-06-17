<?php

declare(strict_types=1);

namespace DD\Fiskaly\Service;

use DD\Fiskaly\Http\AuthenticatedHttpClient;
use Random\RandomException;

final class CashPointClosingService extends AbstractService {

    public function __construct (AuthenticatedHttpClient $client) {
        parent::__construct ($client);
    }

    /**
     * @param string $cashRegisterId
     * @param array $query
     * @return array
     * @throws RandomException
     */
    public function ListCashPointClosings (string $cashRegisterId, array $query = []): array {
        return $this->Json ('GET', $this->Path('/cash_registers/{id}/cash_point_closings', ['id' => $cashRegisterId]), null, $this->BuildQueryString($query));
    }

    /**
     * @param string $cashRegisterId
     * @param string $closingId
     * @param array $body
     * @return array
     * @throws RandomException
     */
    public function CreateCashPointClosing (string $cashRegisterId, string $closingId, array $body = []): array {
        return $this->Json ('PUT', $this->Path('/cash_registers/{id}/cash_point_closings/{closingId}', ['id' => $cashRegisterId, 'closingId' => $closingId]), $body);
    }

    /**
     * @param string $cashRegisterId
     * @param string $closingId
     * @return array
     * @throws RandomException
     */
    public function RetrieveCashPointClosing (string $cashRegisterId, string $closingId): array {
        return $this->Json ('GET', $this->Path('/cash_registers/{id}/cash_point_closings/{closingId}', ['id' => $cashRegisterId, 'closingId' => $closingId]));
    }

    /**
     * Update a cash point closing
     *
     * @param string $cashRegisterId
     * @param string $closingId
     * @param array $body
     * @return array
     * @throws RandomException
     */
    public function UpdateCashPointClosing (string $cashRegisterId, string $closingId, array $body): array {
        return $this->Json('PATCH', $this->Path('/cash_registers/{id}/cash_point_closings/{closingId}', ['id' => $cashRegisterId, 'closingId' => $closingId]), $body);
    }

    /**
     * Delete a cash point closing
     *
     * @param string $cashRegisterId
     * @param string $closingId
     * @return array
     * @throws RandomException
     */
    public function DeleteCashPointClosing (string $cashRegisterId, string $closingId): array {
        return $this->Json('DELETE', $this->Path('/cash_registers/{id}/cash_point_closings/{closingId}', ['id' => $cashRegisterId, 'closingId' => $closingId]));
    }

}
