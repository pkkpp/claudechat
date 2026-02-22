<?php

declare(strict_types=1);

namespace OCA\ClaudeChat\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1000Date20240101000000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Conversations table
        if (!$schema->hasTable('claudechat_conversations')) {
            $table = $schema->createTable('claudechat_conversations');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('title', 'string', [
                'notnull' => true,
                'length' => 255,
                'default' => 'New conversation',
            ]);
            $table->addColumn('created_at', 'bigint', [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', 'bigint', [
                'notnull' => true,
            ]);
	    $table->setPrimaryKey(['id'], 'cc_conv_pk');
            $table->addIndex(['user_id'], 'claudechat_conv_user_idx');
        }

        // Messages table
        if (!$schema->hasTable('claudechat_messages')) {
            $table = $schema->createTable('claudechat_messages');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('conversation_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('role', 'string', [
                'notnull' => true,
                'length' => 16, // 'user' or 'assistant'
            ]);
            $table->addColumn('content', 'text', [
                'notnull' => true,
            ]);
            $table->addColumn('created_at', 'bigint', [
                'notnull' => true,
            ]);
	    $table->setPrimaryKey(['id'], 'cc_msg_pk');
            $table->addIndex(['conversation_id'], 'claudechat_msg_conv_idx');
        }

        return $schema;
    }
}
