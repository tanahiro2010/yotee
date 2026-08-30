<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration
{
    public function change(): void
    {
        // CHAR(36) UUIDs (PRD §40) generated as UUIDv7, which are time-ordered
        // — that keeps InnoDB's clustered-index insert pattern sequential
        // (same locality a BINARY(16) + auto-increment scheme would need
        // extra code to get) without paying the price of a fully random key.
        $users = $this->table('users', ['id' => false, 'primary_key' => 'id']);
        $users
            ->addColumn('id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('display_name', 'string', ['limit' => 100])
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('created_at', 'datetime', ['precision' => 6])
            ->addColumn('updated_at', 'datetime', ['precision' => 6])
            ->addIndex(['email'], ['unique' => true])
            ->create();

        // Maps a social login (Google/Apple) subject id to a Yotee user,
        // kept separate from `users` so the minimal PRD §42 user schema
        // doesn't grow provider-specific columns (§53 Social Login only).
        $identities = $this->table('user_identities', ['id' => false, 'primary_key' => 'id']);
        $identities
            ->addColumn('id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('user_id', 'char', ['limit' => 36])
            ->addColumn('provider', 'string', ['limit' => 20])
            ->addColumn('provider_user_id', 'string', ['limit' => 255])
            ->addColumn('created_at', 'datetime', ['precision' => 6])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addIndex(['provider', 'provider_user_id'], ['unique' => true])
            ->create();

        // Refresh tokens are opaque random values; only their SHA-256 hash is
        // ever stored, so a DB leak alone can't be replayed as a live session.
        $refreshTokens = $this->table('refresh_tokens', ['id' => false, 'primary_key' => 'id']);
        $refreshTokens
            ->addColumn('id', 'char', ['limit' => 36, 'null' => false])
            ->addColumn('user_id', 'char', ['limit' => 36])
            ->addColumn('token_hash', 'char', ['limit' => 64])
            ->addColumn('expires_at', 'datetime', ['precision' => 6])
            ->addColumn('revoked_at', 'datetime', ['precision' => 6, 'null' => true])
            ->addColumn('created_at', 'datetime', ['precision' => 6])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addIndex(['token_hash'], ['unique' => true])
            ->addIndex(['user_id'])
            ->create();
    }
}
