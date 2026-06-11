<?php

declare(strict_types=1);

namespace DD\Fiskaly\Exception;

class ApiException extends FiskalyException
{
    public function __construct(
        string $message,
        private int $statusCode = 0,
        private ?string $errorCode = null,
        private ?array $responseData = null,
        private ?string $requestId = null
    ) {
        parent::__construct($message, $statusCode);
    }

    public function GetStatusCode(): int
    {
        return $this->statusCode;
    }

    public function GetErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function GetResponseData(): ?array
    {
        return $this->responseData;
    }

    public function GetRequestId(): ?string
    {
        return $this->requestId;
    }
}
