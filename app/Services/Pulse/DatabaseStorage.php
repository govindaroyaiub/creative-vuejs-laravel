<?php

namespace App\Services\Pulse;

use Laravel\Pulse\Storage\DatabaseStorage as PulseDatabaseStorage;

/**
 * Pulse storage that works on MySQL 26.x.
 *
 * Pulse's stock MySQL migration makes `key_hash` a generated column
 * (`unhex(md5(key))`). MySQL 26 rejects md5() in generated columns, so our
 * migration creates a plain column instead and the hash is computed in PHP —
 * the same path Pulse already uses for SQLite.
 *
 * Databases created by the old migration still have the generated column, and
 * MySQL errors if you write to one, so we only hash in PHP when the column is
 * a plain one.
 */
class DatabaseStorage extends PulseDatabaseStorage
{
    private ?bool $manualKeyHash = null;

    protected function requiresManualKeyHash(): bool
    {
        if ($this->manualKeyHash !== null) return $this->manualKeyHash;

        $connection = $this->connection();
        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return $this->manualKeyHash = parent::requiresManualKeyHash();
        }

        $extra = $connection->selectOne(
            'SELECT EXTRA AS extra FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$connection->getTablePrefix() . 'pulse_entries', 'key_hash'],
        )?->extra ?? '';

        return $this->manualKeyHash = ! str_contains(strtoupper($extra), 'GENERATED');
    }
}
