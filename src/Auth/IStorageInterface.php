<?php

declare(strict_types=1);

namespace DD\Fiskaly\Auth;

interface IStorageInterface
{
    public function GetTokenData(): ?array;

    public function SetTokenData(array $tokenData): void;

    public function Clear(): void;
}
