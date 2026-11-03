<?php

namespace App\Services;

use RuntimeException;

/**
 * Thrown by VestideAccountService::create() for an email that already has a
 * Keycloak account.
 */
class AccountAlreadyExists extends RuntimeException
{
    public function __construct(public readonly string $email)
    {
        parent::__construct("A Keycloak account already exists for {$email}.");
    }
}
