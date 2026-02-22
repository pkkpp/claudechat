<template>
  <div class="claudechat-admin-settings">
    <h2>Claude AI Chat – Settings</h2>

    <div v-if="saved" class="success-banner">✅ Settings saved successfully!</div>
    <div v-if="error" class="error-banner">⚠️ {{ error }}</div>

    <div class="settings-form">
      <div class="setting-row">
        <label>Anthropic API Key</label>
        <input
          v-model="settings.api_key"
          type="password"
          placeholder="sk-ant-..."
          class="setting-input"
        />
        <p class="hint">
          Get your API key at
          <a href="https://console.anthropic.com/" target="_blank" rel="noopener">console.anthropic.com</a>.
          The key is stored securely and never shown in full again.
        </p>
      </div>

      <div class="setting-row">
        <label>Model</label>
        <select v-model="settings.model" class="setting-input">
          <option v-for="m in settings.models" :key="m" :value="m">{{ m }}</option>
        </select>
        <p class="hint">
          <strong>claude-sonnet-4-6</strong> is recommended for everyday use.
          <strong>claude-opus-4-6</strong> is more powerful but costs more.
          <strong>claude-haiku</strong> is fast and cheap for simple tasks.
        </p>
      </div>

      <div class="setting-row">
        <label>Max output tokens</label>
        <input
          v-model.number="settings.max_tokens"
          type="number"
          min="256"
          max="8192"
          class="setting-input"
        />
      </div>

      <div class="setting-row">
        <label>System prompt</label>
        <textarea
          v-model="settings.system_prompt"
          class="setting-input"
          rows="4"
          placeholder="You are a helpful AI assistant…"
        ></textarea>
        <p class="hint">This prompt sets Claude's behavior for all users.</p>
      </div>

      <button class="btn-primary" @click="saveSettings" :disabled="saving">
        {{ saving ? 'Saving…' : 'Save settings' }}
      </button>
    </div>

    <div class="status-box" :class="settings.configured ? 'ok' : 'warn'">
      <strong>Status:</strong>
      {{ settings.configured ? '✅ API key is configured – Claude is ready.' : '⚠️ API key is not set. Please enter your key above.' }}
    </div>
  </div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'

export default {
  name: 'ClaudeChatAdmin',
  data() {
    return {
      settings: {
        api_key: '',
        model: 'claude-sonnet-4-6',
        max_tokens: 4096,
        system_prompt: '',
        models: [],
        configured: false,
      },
      saving: false,
      saved: false,
      error: null,
    }
  },
  mounted() {
    this.loadSettings()
  },
  methods: {
    async loadSettings() {
      try {
        const res = await fetch(generateUrl('/apps/claudechat/admin/settings'))
        this.settings = await res.json()
      } catch (e) {
        this.error = 'Failed to load settings.'
      }
    },
    async saveSettings() {
      this.saving = true
      this.saved = false
      this.error = null
      try {
        await fetch(generateUrl('/apps/claudechat/admin/settings'), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'requesttoken': OC.requestToken,
          },
          body: JSON.stringify(this.settings),
        })
        this.saved = true
        await this.loadSettings()
        setTimeout(() => { this.saved = false }, 3000)
      } catch (e) {
        this.error = 'Failed to save settings.'
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
