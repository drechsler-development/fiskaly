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
     * @param array $query
     * @return array
     * @throws RandomException
     */
    public function ListCashPointClosings (array $query = []): array {
        return $this->Json ('GET', $this->Path('/cash_point_closings'), null, $this->BuildQueryString($query));
    }

    /**
     * @param string $closingId
     * @param array $body
     * @return array
     * @throws RandomException
     */
    public function CreateCashPointClosing (string $closingId, array $body = []): array {
        return $this->Json ('PUT', $this->Path('/cash_point_closings/{closingId}', ['closingId' => $closingId]), $body);
    }

    /**
     * @param string $closingId
     * @return array
     * @throws RandomException
     */
    public function RetrieveCashPointClosing (string $closingId): array {
        return $this->Json ('GET', $this->Path('/cash_point_closings/{closingId}', ['closingId' => $closingId]));
    }

    /**
     * Update a cash point closing
     *
     * @param string $closingId
     * @param array $body
     * @return array
     * @throws RandomException
     */
    public function UpdateCashPointClosing (string $closingId, array $body): array {
        return $this->Json('PATCH', $this->Path('/cash_point_closings/{closingId}', ['closingId' => $closingId]), $body);
    }

    /**
     * Delete a cash point closing
     *
     * @param string $closingId
     * @return array
     * @throws RandomException
     */
    public function DeleteCashPointClosing (string $closingId): array {
        return $this->Json('DELETE', $this->Path('/cash_point_closings/{closingId}', ['closingId' => $closingId]));
    }

}
