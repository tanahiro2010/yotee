<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSubscriptionsTable extends AbstractMigration
{
    public function change(): void
    {
        // No `deleted_at` — unsubscribing hard-deletes the row (PRD §46).
        // Per-user notification timing is deliberately not modeled here; it
        // lives only in the device's local SQLite.
        $table = $this->table('subscriptions', ['id' => false, 'primary_key' => 'id']);
        $table
            ->addColumn('id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('user_id', 'char', ['limit' => 36])
            ->addColumn('category_id', 'char', ['limit' => 36])
            ->addColumn('created_at', 'datetime', ['precision' => 6])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('category_id', 'categories', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addIndex(['user_id', 'category_id'], ['unique' => true])
            ->addIndex(['user_id', 'created_at'])
            ->create();
    }
}
