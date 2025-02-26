<?php

namespace CryptoExchange\Whitebit\Exceptions;

class WhitebitException extends \RuntimeException
{
    public function __construct(string $message, int $code = 0)
    {
        parent::__construct("Whitebit API Error: $message", $code);
    }
}
