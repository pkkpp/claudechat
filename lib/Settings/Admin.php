<?php

declare(strict_types=1);

namespace OCA\ClaudeChat\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class Admin implements ISettings {

    public function getForm(): TemplateResponse {
        return new TemplateResponse('claudechat', 'admin');
    }

    public function getSection(): string {
        return 'claudechat';
    }

    public function getPriority(): int {
        return 50;
    }
}
