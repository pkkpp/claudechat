<template>
  <div class="claudechat-app">
    <!-- Sidebar -->
    <aside class="claudechat-sidebar">
      <div class="sidebar-header">
        <span class="sidebar-logo">🤖</span>
        <span class="sidebar-title">Claude AI</span>
        <button class="new-chat-btn" @click="createConversation" title="New conversation">
          <span>＋</span>
        </button>
      </div>

      <div class="conversations-list">
        <div
          v-for="conv in conversations"
          :key="conv.id"
          class="conversation-item"
          :class="{ active: activeConversationId === conv.id }"
          @click="selectConversation(conv.id)"
        >
          <span class="conv-title">{{ conv.title }}</span>
          <button
            class="conv-delete"
            @click.stop="deleteConversation(conv.id)"
            title="Delete conversation"
          >✕</button>
        </div>
        <div v-if="conversations.length === 0" class="no-conversations">
          No conversations yet.<br>Click ＋ to start.
        </div>
      </div>
    </aside>

    <!-- Main chat area -->
    <main class="claudechat-main">
      <div v-if="!activeConversationId" class="welcome-screen">
        <div class="welcome-content">
          <div class="welcome-icon">🤖</div>
          <h2>Welcome to Claude AI</h2>
          <p>Start a new conversation or select one from the sidebar.</p>
          <button class="btn-primary" @click="createConversation">Start new conversation</button>
        </div>
      </div>

      <template v-else>
        <!-- Messages -->
        <div class="messages-container" ref="messagesContainer">
          <div
            v-for="msg in messages"
            :key="msg.id"
            class="message"
            :class="msg.role"
          >
            <div class="message-avatar">
              {{ msg.role === 'user' ? '👤' : '🤖' }}
            </div>
            <div class="message-bubble">
              <div class="message-content" v-html="renderMarkdown(msg.content)"></div>
              <div class="message-time">{{ formatTime(msg.created_at) }}</div>
            </div>
          </div>

          <div v-if="isLoading" class="message assistant">
            <div class="message-avatar">🤖</div>
            <div class="message-bubble">
              <div class="typing-indicator">
                <span></span><span></span><span></span>
              </div>
            </div>
          </div>

          <div v-if="messages.length === 0 && !isLoading" class="empty-conv">
            Send a message or analyze a file to start.
          </div>
        </div>

        <!-- Error banner -->
        <div v-if="error" class="error-banner">
          ⚠️ {{ error }}
          <button @click="error = null">✕</button>
        </div>

        <!-- Input area -->
        <div class="input-area">
          <!-- File analyze bar -->
          <div v-if="selectedFile" class="file-bar">
            <span>📎 {{ selectedFile }}</span>
            <button @click="selectedFile = null">✕</button>
          </div>

          <div class="input-row">
            <button
              class="attach-btn"
              @click="showFilePicker = !showFilePicker"
              title="Analyze a file from your Nextcloud"
            >📎</button>

            <textarea
              ref="inputRef"
              v-model="userInput"
              class="message-input"
              placeholder="Message Claude… (Shift+Enter for newline)"
              @keydown.enter.exact.prevent="sendMessage"
              @input="autoResize"
              rows="1"
            ></textarea>

            <button
              class="send-btn"
              :disabled="isLoading || (!userInput.trim() && !selectedFile)"
              @click="selectedFile ? analyzeFile() : sendMessage()"
            >➤</button>
          </div>

          <!-- File picker dropdown -->
          <div v-if="showFilePicker" class="file-picker">
            <div class="file-picker-header">
              <span>📁 Select a file to analyze</span>
              <button @click="showFilePicker = false">✕</button>
            </div>
            <div class="file-picker-path">
              <button @click="navigateUp" :disabled="currentPath === '/'">⬆ Up</button>
              <span>{{ currentPath }}</span>
            </div>
            <div class="file-picker-list">
              <div
                v-for="item in fileList"
                :key="item.path"
                class="file-item"
                :class="{ folder: item.type === 'dir' }"
                @click="item.type === 'dir' ? navigateInto(item.path) : pickFile(item.path)"
              >
                <span>{{ item.type === 'dir' ? '📁' : '📄' }}</span>
                <span>{{ item.name }}</span>
              </div>
              <div v-if="fileList.length === 0" class="file-item">No files found.</div>
            </div>
            <div v-if="selectedFile" class="file-question">
              <input
                v-model="fileQuestion"
                type="text"
                placeholder="What should Claude do with this file?"
              />
              <button @click="analyzeFile" class="btn-primary">Analyze</button>
            </div>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'

export default {
  name: 'ClaudeChatApp',

  data() {
    return {
      conversations: [],
      activeConversationId: null,
      messages: [],
      userInput: '',
      isLoading: false,
      error: null,

      // File picker
      showFilePicker: false,
      selectedFile: null,
      fileQuestion: 'Please summarize and analyze this file.',
      currentPath: '/',
      fileList: [],
    }
  },

  mounted() {
    this.loadConversations()
  },

  methods: {
    // -----------------------------------------------------------------------
    // Conversations
    // -----------------------------------------------------------------------
    async loadConversations() {
      try {
        const res = await fetch(generateUrl('/apps/claudechat/api/conversations'))
        this.conversations = await res.json()
      } catch (e) {
        this.error = 'Failed to load conversations.'
      }
    },

    async selectConversation(id) {
      this.activeConversationId = id
      this.messages = []
      this.error = null
      this.showFilePicker = false
      this.selectedFile = null
      try {
        const res = await fetch(generateUrl(`/apps/claudechat/api/conversations/${id}`))
        this.messages = await res.json()
        this.$nextTick(() => this.scrollToBottom())
      } catch (e) {
        this.error = 'Failed to load messages.'
      }
    },

    async createConversation() {
      try {
        const res = await fetch(generateUrl('/apps/claudechat/api/conversations'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'requesttoken': OC.requestToken },
          body: new URLSearchParams({}),
        })
        const conv = await res.json()
        this.conversations.unshift(conv)
        await this.selectConversation(conv.id)
      } catch (e) {
        this.error = 'Failed to create conversation.'
      }
    },

    async deleteConversation(id) {
      if (!confirm('Delete this conversation?')) return
      try {
        await fetch(generateUrl(`/apps/claudechat/api/conversations/${id}`), {
          method: 'DELETE',
          headers: { 'requesttoken': OC.requestToken },
        })
        this.conversations = this.conversations.filter(c => c.id !== id)
        if (this.activeConversationId === id) {
          this.activeConversationId = null
          this.messages = []
        }
      } catch (e) {
        this.error = 'Failed to delete conversation.'
      }
    },

    // -----------------------------------------------------------------------
    // Messaging
    // -----------------------------------------------------------------------
    async sendMessage() {
      const text = this.userInput.trim()
      if (!text || this.isLoading) return

      this.isLoading = true
      this.error = null
      this.userInput = ''
      this.$nextTick(() => this.autoResize())

      try {
        const res = await fetch(generateUrl('/apps/claudechat/api/message'), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'requesttoken': document.getElementsByTagName('head')[0].getAttribute('data-requesttoken'),
          },
          body: new URLSearchParams({
            conversation_id: this.activeConversationId,
            message: text,
          }),
        })
        const data = await res.json()
        if (data.error) {
          this.error = data.error
        } else {
          this.messages.push(data.user_message)
          this.messages.push(data.assistant_message)
          // Update conversation title in sidebar
          const idx = this.conversations.findIndex(c => c.id === this.activeConversationId)
          if (idx !== -1) this.conversations[idx] = data.conversation
          this.$nextTick(() => this.scrollToBottom())
        }
      } catch (e) {
        this.error = 'Network error. Please try again.'
      } finally {
        this.isLoading = false
      }
    },

    // -----------------------------------------------------------------------
    // File analysis
    // -----------------------------------------------------------------------
    async loadFiles(path = '/') {
      try {
        const res = await fetch(generateUrl(`/apps/claudechat/api/files?path=${encodeURIComponent(path)}`))
        const data = await res.json()
        this.fileList = data.files || []
        this.currentPath = path
      } catch (e) {
        // Fallback: use WebDAV
        this.fileList = []
      }
    },

    navigateInto(path) {
	this.loadFilesViaWebdav(path)
    },

    navigateUp() {
      const parts = this.currentPath.split('/').filter(Boolean)
      parts.pop()
      this.loadFilesViaWebdav('/' + (parts.join('/') || ''))
    },

    pickFile(path) {
      this.selectedFile = path
    },

    async analyzeFile() {
      if (!this.selectedFile) return
      this.showFilePicker = false
      this.isLoading = true
      this.error = null
      try {

	const res = await fetch(generateUrl('/apps/claudechat/api/analyze'), {
	  method: 'POST',
	  headers: {
	    'Content-Type': 'application/x-www-form-urlencoded',
	    'requesttoken': document.getElementsByTagName('head')[0].getAttribute('data-requesttoken'),
	  },
	  body: new URLSearchParams({
	    conversation_id: this.activeConversationId,
	    file_path: this.selectedFile,
	    question: this.fileQuestion || 'Please summarize and analyze this file.',
	  }),
	})

        const data = await res.json()
        if (data.error) {
          this.error = data.error
        } else {
          this.messages.push(data.user_message)
          this.messages.push(data.assistant_message)
          this.selectedFile = null
          this.$nextTick(() => this.scrollToBottom())
        }
      } catch (e) {
        this.error = 'Failed to analyze file.'
      } finally {
        this.isLoading = false
      }
    },

    // -----------------------------------------------------------------------
    // File picker - WebDAV based
    // -----------------------------------------------------------------------
    async showFilePickerAndLoad() {
      this.showFilePicker = true
      await this.loadFilesViaWebdav(this.currentPath)
    },

async loadFilesViaWebdav(path) {
  const userId = OC.currentUser
  // Normalize path
  const cleanPath = path === '/' ? '' : path
  const davUrl = `/remote.php/dav/files/${userId}${cleanPath}/`
  try {
    const res = await fetch(davUrl, {
      method: 'PROPFIND',
      headers: {
        Depth: '1',
        'Content-Type': 'application/xml',
	'requesttoken': document.getElementsByTagName('head')[0].getAttribute('data-requesttoken'),
      },
      body: `<?xml version="1.0"?><d:propfind xmlns:d="DAV:"><d:prop><d:displayname/><d:resourcetype/></d:prop></d:propfind>`,
    })
    const text = await res.text()
    const parser = new DOMParser()
    const xml = parser.parseFromString(text, 'application/xml')
    const responses = Array.from(xml.querySelectorAll('response'))
    const items = []
    for (const r of responses) {
      const href = r.querySelector('href')?.textContent || ''
      const displayName = r.querySelector('displayname')?.textContent || ''
      const isCollection = r.querySelector('collection') !== null
      const hrefDecoded = decodeURIComponent(href)
      const basePath = `/remote.php/dav/files/${userId}${cleanPath}/`
      if (hrefDecoded === basePath || hrefDecoded === `/remote.php/dav/files/${userId}/`) continue
      const filePath = decodeURIComponent(href).replace(`/remote.php/dav/files/${userId}`, '')
      items.push({
        name: displayName || filePath.split('/').filter(Boolean).pop(),
        path: filePath.replace(/\/$/, ''),
        type: isCollection ? 'dir' : 'file'
      })
    }
    this.fileList = items
    this.currentPath = path
  } catch (e) {
    this.error = 'Dateien konnten nicht geladen werden: ' + e.message
  }
},
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------
    scrollToBottom() {
      const el = this.$refs.messagesContainer
      if (el) el.scrollTop = el.scrollHeight
    },

    autoResize() {
      const el = this.$refs.inputRef
      if (el) {
        el.style.height = 'auto'
        el.style.height = Math.min(el.scrollHeight, 180) + 'px'
      }
    },

    formatTime(ts) {
      return new Date(ts * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    },

    // Basic markdown rendering (bold, italic, code, newlines)
    renderMarkdown(text) {
      if (!text) return ''
      return text
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>')
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/\n/g, '<br>')
    },
  },

  watch: {
    showFilePicker(val) {
      if (val) this.loadFilesViaWebdav(this.currentPath)
    },
  },
}
</script>
