<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Expresiones SQL de agrupación temporal compatibles con PostgreSQL, MySQL/MariaDB y SQLite.
 */
final class DbDateAgg
{
    public static function driver(): string
    {
        return Schema::getConnection()->getDriverName();
    }

    /** Clave día calendario tipo YYYY-MM-DD (texto), alias típico `k`. */
    public static function exprDay(string $column = 'fecha'): string
    {
        return match (self::driver()) {
            'pgsql' => "CAST({$column} AS DATE)::text",
            'sqlite' => "date({$column})",
            default => "DATE({$column})",
        };
    }

    /** Clave mes YYYY-MM, alias típico `k`. */
    public static function exprMonth(string $column = 'fecha'): string
    {
        return match (self::driver()) {
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    /** Clave semana ISO tipo 2026-W05, alias típico `k`. */
    public static function exprIsoWeek(string $column = 'fecha'): string
    {
        return match (self::driver()) {
            'pgsql' => "to_char({$column}, 'IYYY') || '-W' || to_char({$column}, 'IW')",
            'sqlite' => "(strftime('%G', {$column}) || '-W' || strftime('%V', {$column}))",
            default => "DATE_FORMAT({$column}, '%x-W%v')",
        };
    }
}
