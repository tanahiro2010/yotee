<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDevicesTable extends AbstractMigration
{
    public function change(): void
    {
        // Push tokens only ever trigger a `category.updated` re-sync signal
        // (CLAUDE.md core principle) — never a reminder payload.
        $table = $this->table('devices', ['id' => false, 'primary_key' => 'id']);
        $table
            ->addColumn('id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('user_id', 'char', ['limit' => 36])
            ->addColumn('platform', 'enum', ['values' => ['ios', 'android']])
            ->addColumn('push_token', 'string', ['limit' => 512])
            ->addColumn('last_seen_at', 'datetime', ['precision' => 6, 'null' => true])
            ->addColumn('created_at', 'datetime', ['precision' => 6])
            ->addColumn('updated_at', 'datetime', ['precision' => 6])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            // Re-registering the same token (e.g. after a token refresh from
            // the OS) upserts instead of accumulating duplicate rows.
            ->addIndex(['user_id', 'push_token'], ['unique' => true])
            ->create();
    }
}
