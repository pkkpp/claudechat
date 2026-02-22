<?php

declare(strict_types=1);

namespace OCA\ClaudeChat\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {

    public function __construct(
        private IL10N $l,
        private IURLGenerator $urlGenerator,
    ) {}

    public function getID(): string {
        return 'claudechat';
    }

    public function getName(): string {
        return 'Claude AI Chat';
    }

    public function getPriority(): int {
        return 75;
    }

    public function getIcon(): string {
        return $this->urlGenerator->imagePath('claudechat', 'app.svg');
    }
}
