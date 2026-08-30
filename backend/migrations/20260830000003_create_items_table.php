<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateItemsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('items', ['id' => false, 'primary_key' => 'id']);
        $table
            ->addColumn('id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('category_id', 'char', ['limit' => 36])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('schedule_type', 'enum', [
                'values' => ['once', 'weekly', 'monthly_day', 'monthly_nth_weekday', 'yearly'],
            ])
            // Shape varies per schedule_type (PRD §45) — validated server-side
            // by Validation\ScheduleRuleValidation before it ever reaches here.
            ->addColumn('schedule_rule', 'json')
            ->addColumn('location', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('url', 'string', ['limit' => 2048, 'null' => true])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('created_at', 'datetime', ['precision' => 6])
            ->addColumn('updated_at', 'datetime', ['precision' => 6])
            ->addColumn('deleted_at', 'datetime', ['precision' => 6, 'null' => true])
            ->addForeignKey('category_id', 'categories', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            // Serves both "list Items for a Category" (owner/public detail)
            // and the sync delta query (Items across owned+subscribed ids).
            ->addIndex(['category_id', 'deleted_at'])
            ->addIndex(['category_id', 'updated_at'])
            ->create();
    }
}
