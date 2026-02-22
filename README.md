# Claude AI Chat – Nextcloud App

A Nextcloud app that integrates Anthropic's Claude AI directly into your Nextcloud instance.

**Features:**
- Personal chat interface per user with persistent conversation history
- File analysis – browse and analyze text files from your Nextcloud
- Admin settings page (API key, model selection, system prompt)
- Separate conversation history per Nextcloud user

---

## Requirements

- Nextcloud 28 or newer (tested on NC 32.0.6)
- PHP 8.1+
- Node.js 18+ and npm (for building the frontend)
- An [Anthropic API key](https://console.anthropic.com/)

---

## Installation

### 1. Copy the app to your Nextcloud

```bash
cp -r claudechat /var/www/nextcloud/apps/
```

### 2. Install Node.js (if not already installed)

```bash
apt install -y nodejs npm
```

### 3. Build the JavaScript frontend

```bash
cd /var/www/nextcloud/apps/claudechat
npm install
npm install --save-dev @nextcloud/browserslist-config
npm run build
```

### 4. Set correct permissions

```bash
chown -R www-data:www-data /var/www/nextcloud/apps/claudechat/
```

### 5. Enable the app

```bash
sudo -u www-data php /var/www/nextcloud/occ app:enable claudechat
```

Or via **Nextcloud Admin → Apps → Your Apps → Claude AI Chat → Enable**.

### 6. Run the database migration

```bash
sudo -u www-data php /var/www/nextcloud/occ migrations:execute claudechat 1000Date20240101000000
```

### 7. Configure the API key

Go to **Nextcloud Admin → Settings → Claude AI Chat** and enter:
- Your Anthropic API key (`sk-ant-...`)
- Choose a model (`claude-sonnet-4-6` is recommended)
- Optionally customize the system prompt

### 8. Use the app

Every user sees **Claude AI** in the left navigation bar. Each user has their own separate conversation history.

---

## File Analysis

Click the **📎** button in a conversation to browse your Nextcloud files. Select a text file, optionally type a question, and Claude will analyze it.

**Supported file types:** txt, md, html, csv, json, xml, log, php, py, js, ts, yaml, vcf, and any other text-based format.

**Not supported:** ODT, DOCX, PDF, images, audio, video and other binary formats.

---

## Troubleshooting

**"API key is not configured"**
Set the API key in Admin → Settings → Claude AI Chat.

**App not visible after enabling**
Make sure you ran `npm run build` and that the files `js/claudechat-main.js` and `js/claudechat-admin.js` exist.

**npm run build fails with `@nextcloud/browserslist-config` error**
Run `npm install --save-dev @nextcloud/browserslist-config` first.

**Database errors on enable**
Run the migration manually:
```bash
sudo -u www-data php /var/www/nextcloud/occ migrations:execute claudechat 1000Date20240101000000
```

**File picker is empty**
Make sure WebDAV is enabled in your Nextcloud. Check that `/remote.php/dav/` is accessible.

---

## Updating

```bash
cd /var/www/nextcloud/apps/claudechat
git pull
npm install
npm run build
chown -R www-data:www-data /var/www/nextcloud/apps/claudechat/
sudo -u www-data php /var/www/nextcloud/occ upgrade
```

---

## File structure

```
claudechat/
├── appinfo/
│   ├── info.xml              – App metadata
│   └── routes.php            – URL routes
├── lib/
│   ├── Controller/
│   │   ├── PageController.php    – Serves the main page
│   │   ├── ChatController.php    – Chat & file analysis API
│   │   └── AdminController.php   – Admin settings API
│   ├── Db/
│   │   ├── Conversation.php / ConversationMapper.php
│   │   └── Message.php / MessageMapper.php
│   ├── Migration/
│   │   └── Version1000Date20240101000000.php  – DB schema
│   ├── Service/
│   │   └── ClaudeService.php     – Anthropic API client
│   └── Settings/
│       ├── Admin.php             – Admin settings form
│       └── AdminSection.php      – Sidebar section
├── templates/
│   ├── index.php             – Main app template
│   └── admin.php             – Admin settings template
├── js/src/
│   ├── main.js               – Vue app entry
│   ├── admin.js              – Admin Vue entry
│   ├── App.vue               – Main chat UI
│   └── Admin.vue             – Admin settings UI
├── css/
│   └── claudechat.css        – Styles
├── img/
│   └── app.svg               – App icon
├── package.json
└── webpack.config.js
```

---

## License

AGPL-3.0 – see [LICENSE](LICENSE)
