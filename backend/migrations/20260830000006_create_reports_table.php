<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateReportsTable extends AbstractMigration
{
    public function change(): void
    {
        // Minimal moderation intake for public Lists (PRD §58, §70 Safety) —
        // no review UI or workflow in MVP, just a durable record to act on.
        $table = $this->table('reports', ['id' => false, 'primary_key' => 'id']);
        $table
            ->addColumn('id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('category_id', 'char', ['limit' => 36])
            ->addColumn('reporter_user_id', 'char', ['limit' => 36])
            ->addColumn('reason', 'enum', [
                'values' => ['spam', 'misinformation', 'impersonation', 'inappropriate', 'other'],
            ])
            ->addColumn('detail', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime', ['precision' => 6])
            ->addForeignKey('category_id', 'categories', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('reporter_user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addIndex(['category_id'])
            ->create();
    }
}
