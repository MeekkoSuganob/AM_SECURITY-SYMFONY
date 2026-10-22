<?php

namespace App\Service;

final class LicenseKeyGenerator
{
    /**
     * Makes a key like AMSEC-4F2A-9C1D-7B03-E86A.
     * It is built from random bytes (not a counter), so keys can't be guessed by counting up.
     */
    public function generate(): string
    {
        $groups = str_split(strtoupper(bin2hex(random_bytes(8))), 4);

        return 'AMSEC-'.implode('-', $groups);
    }
}
