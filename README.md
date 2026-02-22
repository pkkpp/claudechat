# Claude AI Chat – Nextcloud App

A Nextcloud app that integrates Anthropic's Claude AI with:
- Personal chat interface per user
- Persistent conversation history (stored in Nextcloud's database)
- File analysis (text files from your Nextcloud)
- Admin settings page (API key, model, system prompt)

---

## Requirements

- Nextcloud 25 – 29
- PHP 8.1+
- An [Anthropic API key](https://console.anthropic.com/)
- Node.js 18+ and npm (for building the frontend)

---

## Installation

### 1. Copy the app to your Nextcloud

```bash
cp -r claudechat /var/www/nextcloud/apps/
```
(Adjust the path to your Nextcloud installation.)

### 2. Build the JavaScript frontend

```bash
cd /var/www/nextcloud/apps/claudechat
npm install
npm run build
```

This produces `js/claudechat-main.js` and `js/claudechat-admin.js`.

### 3. Enable the app

```bash
cd /var/www/nextcloud
sudo -u www-data php occ app:enable claudechat
```

Or enable it via **Nextcloud Admin → Apps → Your Apps → Claude AI Chat → Enable**.

### 4. Run the database migration

```bash
sudo -u www-data php occ migrations:execute claudechat 1000Date20240101000000
```

(Or restart Nextcloud – it runs migrations automatically on the next request.)

### 5. Configure the API key

Go to **Nextcloud Admin → Settings → Claude AI Chat** and enter:
- Your **Anthropic API key** (`sk-ant-...`)
- Choose a **model** (claude-sonnet-4-6 is recommended)
- Optionally customize the **system prompt**

### 6. Use the app

Every user sees **Claude AI** in the left navigation bar. Each user has their own separate conversation history.

---

## File Analysis

In a conversation, click the **📎** button to browse your Nextcloud files. Select a text file and optionally type a question. Claude will read the file content and answer.

> **Note:** Only text-based files are supported (txt, md, html, csv, json, xml, log, php, py, js, etc.). Binary files (images, PDFs, docx) cannot be read as raw text.

---

## Troubleshooting

**"API key is not configured"** – Set the API key in Admin → Settings → Claude AI Chat.

**App not visible** – Make sure you ran `npm run build` before enabling the app.

**Database errors** – Run `php occ migrations:execute claudechat 1000Date20240101000000` manually.

**File picker is empty** – WebDAV might be blocked. Check your Nextcloud's WebDAV settings.

---

## Update

```bash
cd /var/www/nextcloud/apps/claudechat
git pull  # or replace files manually
npm install && npm run build
sudo -u www-data php occ upgrade
```

---

## File structure

```
claudechat/
├── appinfo/
│   ├── info.xml          – App metadata
│   └── routes.php        – URL routes
├── lib/
│   ├── Controller/
│   │   ├── PageController.php   – Serves the main page
│   │   ├── ChatController.php   – Chat & file analysis API
│   │   └── AdminController.php  – Admin settings API
│   ├── Db/
│   │   ├── Conversation.php / ConversationMapper.php
│   │   └── Message.php / MessageMapper.php
│   ├── Migration/
│   │   └── Version1000Date20240101000000.php  – DB schema
│   ├── Service/
│   │   └── ClaudeService.php    – Anthropic API client
│   └── Settings/
│       ├── Admin.php            – Admin settings form
│       └── AdminSection.php     – Sidebar section
├── templates/
│   ├── index.php         – Main app template
│   └── admin.php         – Admin settings template
├── js/src/
│   ├── main.js           – Vue app entry
│   ├── admin.js          – Admin Vue entry
│   ├── App.vue           – Main chat UI
│   └── Admin.vue         – Admin settings UI
├── css/
│   └── claudechat.css    – Styles
├── img/
│   └── app.svg           – App icon
├── package.json
└── webpack.config.js
```
