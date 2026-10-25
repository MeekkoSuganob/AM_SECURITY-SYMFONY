<?php

namespace App\Service;

final class LicenseKeyGenerator
{
    
    public function generate(): string
    {
        $groups = str_split(strtoupper(bin2hex(random_bytes(8))), 4);

        return 'AMSEC-'.implode('-', $groups). '-OnSkibidi';
    }
}
