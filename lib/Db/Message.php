<?php

declare(strict_types=1);

namespace OCA\ClaudeChat\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getId()
 * @method int getConversationId()
 * @method void setConversationId(int $id)
 * @method string getRole()
 * @method void setRole(string $role)
 * @method string getContent()
 * @method void setContent(string $content)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 */
class Message extends Entity {
    protected $conversationId;
    protected $role;
    protected $content;
    protected $createdAt;

    public function jsonSerialize(): array {
        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversationId,
            'role'            => $this->role,
            'content'         => $this->content,
            'created_at'      => $this->createdAt,
        ];
    }
}
