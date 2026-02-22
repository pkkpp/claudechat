<?php

declare(strict_types=1);

namespace OCA\ClaudeChat\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class MessageMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'claudechat_messages', Message::class);
    }

    public function findByConversation(int $conversationId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('conversation_id', $qb->createNamedParameter($conversationId)))
           ->orderBy('created_at', 'ASC');
        return $this->findEntities($qb);
    }

    public function deleteByConversation(int $conversationId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
           ->where($qb->expr()->eq('conversation_id', $qb->createNamedParameter($conversationId)));
        $qb->executeStatement();
    }
}
