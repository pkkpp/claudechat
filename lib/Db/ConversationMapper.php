<?php

declare(strict_types=1);

namespace OCA\ClaudeChat\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class ConversationMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'claudechat_conversations', Conversation::class);
    }

    public function findAllForUser(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
           ->orderBy('updated_at', 'DESC');
        return $this->findEntities($qb);
    }

    public function findForUser(int $id, string $userId): Conversation {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName())
           ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
           ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
        return $this->findEntity($qb);
    }
}
