<?php

declare(strict_types=1);

namespace OCA\ClaudeChat\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getId()
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getTitle()
 * @method void setTitle(string $title)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 */
class Conversation extends Entity {
    protected $userId;
    protected $title;
    protected $createdAt;
    protected $updatedAt;

    public function jsonSerialize(): array {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
