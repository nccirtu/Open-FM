<?php

namespace App\Support;

final class OpenFm
{
    public const NAME = 'Open-FM';

    public const VERSION = '1.0.0-beta.1';

    public const AUTHOR = 'Gladys Slawina und Nicolae Cristian Cirtu';

    public const LICENSE = 'AGPL-3.0';

    public const LICENSE_URL = 'https://www.gnu.org/licenses/agpl-3.0.html';

    public const REPOSITORY_URL = 'https://github.com/nccirtu/Open-FM';

    public static function attribution(): string
    {
        return self::NAME.' by '.self::AUTHOR;
    }
}
