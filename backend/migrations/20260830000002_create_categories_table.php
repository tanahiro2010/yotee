<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCategoriesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('categories', ['id' => false, 'primary_key' => 'id']);
        $table
            ->addColumn('id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('owner_id', 'char', ['limit' => 36])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('visibility', 'enum', ['values' => ['private', 'unlisted', 'public']])
            ->addColumn('timezone', 'string', ['limit' => 64])
            // Bumped once per Item mutation under this Category (PRD §20,
            // §37) — the single field client delta-sync hinges on.
            ->addColumn('version', 'integer', ['signed' => false, 'default' => 1])
            ->addColumn('recommended_reminder', 'json', ['null' => true])
            ->addColumn('created_at', 'datetime', ['precision' => 6])
            ->addColumn('updated_at', 'datetime', ['precision' => 6])
            ->addColumn('deleted_at', 'datetime', ['precision' => 6, 'null' => true])
            ->addForeignKey('owner_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addIndex(['owner_id'])
            ->addIndex(['updated_at'])
            // Scopes /categories/search to public, non-deleted rows fast.
            ->addIndex(['visibility', 'deleted_at'])
            ->create();

        // Japanese content (ゴミの日, WORLD TOUR, ...) doesn't tokenize on
        // whitespace, so MySQL's default FULLTEXT parser (built for
        // space-delimited languages) would barely match anything useful.
        // The bundled `ngram` parser indexes fixed-length character n-grams
        // instead, which is the standard way to get real FULLTEXT
        // performance on CJK text in MySQL 8 (PRD §57 Search, §65 Performance).
        $this->execute(
            'ALTER TABLE categories ADD FULLTEXT INDEX ft_categories_search (name, description) WITH PARSER ngram'
        );
    }
}
