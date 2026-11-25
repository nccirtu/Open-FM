<?php

namespace App\Support;

final class ApiContext
{
    /** @var array<int, string> */
    private static array $organizationIds = [];

    /**
     * @param  array<int, string>  $organizationIds
     */
    public static function set(array $organizationIds): void
    {
        self::$organizationIds = array_values($organizationIds);
    }

    /**
     * @return array<int, string>
     */
    public static function organizationIds(): array
    {
        return self::$organizationIds;
    }

    public static function organizationId(): ?string
    {
        return self::$organizationIds[0] ?? null;
    }

    public static function clear(): void
    {
        self::$organizationIds = [];
    }
}
