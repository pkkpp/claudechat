<?php

return [
    'routes' => [
        // Main page
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET', 'requirements' => []],

        // Chat API
        ['name' => 'chat#sendMessage',      'url' => '/api/message',             'verb' => 'POST'],
        ['name' => 'chat#getConversations', 'url' => '/api/conversations',        'verb' => 'GET'],
        ['name' => 'chat#getMessages',      'url' => '/api/conversations/{id}',   'verb' => 'GET'],
        ['name' => 'chat#deleteConversation','url' => '/api/conversations/{id}',  'verb' => 'DELETE'],
        ['name' => 'chat#newConversation',  'url' => '/api/conversations',        'verb' => 'POST'],
        ['name' => 'chat#analyzeFile',      'url' => '/api/analyze',             'verb' => 'POST'],

        // Admin API
        ['name' => 'admin#saveSettings',    'url' => '/admin/settings',          'verb' => 'POST'],
        ['name' => 'admin#getSettings',     'url' => '/admin/settings',          'verb' => 'GET'],
    ],
];
