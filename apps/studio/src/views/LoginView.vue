<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Eye, EyeOff, ArrowRight, LockKeyhole, Layers, Workflow, SearchCheck, BarChart3 } from 'lucide-vue-next'
import { auth } from '@/stores/auth'
import { apiErrorMessage } from '@/api/client'

const router = useRouter()
const route = useRoute()
const email = ref('admin@meridian.example')
const password = ref('Meridian!2026')
const remember = ref(true)
const showPassword = ref(false)
const error = ref('')
const submitting = ref(false)

const demoAccounts = [
  { label: 'Super administrator', email: 'admin@meridian.example' },
  { label: 'Marketing manager', email: 'manager@meridian.example' },
  { label: 'Content editor', email: 'editor@meridian.example' },
  { label: 'SEO specialist', email: 'seo@meridian.example' },
]

async function submit() {
  error.value = ''
  submitting.value = true
  try {
    await auth.login(email.value, password.value, remember.value)
    const redirect = route.query.redirect ? String(route.query.redirect) : '/'
    await router.push(redirect)
  } catch (err) {
    error.value = apiErrorMessage(err)
  } finally {
    submitting.value = false
  }
}

function useAccount(accountEmail: string) {
  email.value = accountEmail
  password.value = 'Meridian!2026'
}

const features = [
  { icon: Layers, title: 'Content, one roof', text: 'Pages, collections, media, locales, and publishing in one system.' },
  { icon: Workflow, title: 'Workflow that holds', text: 'Review, approval, scheduling, and a complete audit trail.' },
  { icon: SearchCheck, title: 'SEO as engineering', text: 'Metadata, hreflang, sitemaps, redirects, and deterministic health checks.' },
  { icon: BarChart3, title: 'Evidence, not theatre', text: 'First-party analytics with honest disconnected states.' },
]
</script>

<template>
  <div class="login">
    <aside class="login__brand">
      <div class="login__grid" aria-hidden="true" />
      <div class="login__brand-inner">
        <div class="brand-lockup">
          <svg viewBox="0 0 32 32" class="brand-mark" aria-hidden="true">
            <path d="M16 2.5v27" stroke="#f5f3ee" stroke-width="1.1" />
            <path d="M4.5 16h23" stroke="#f5f3ee" stroke-width="1.1" opacity="0.4" />
            <path d="M8 24V8l8 10 8-10v16" fill="none" stroke="#f5f3ee" stroke-width="1.6" />
            <circle cx="24.5" cy="7.5" r="2.4" fill="#e36d3f" />
          </svg>
          <div>
            <p class="brand-word">MERIDIAN</p>
            <p class="brand-studio">STUDIO</p>
          </div>
        </div>

        <div class="login__statement">
          <p class="mono login__kicker">MERIDIAN STUDIO / ENTERPRISE CMS</p>
          <h1>Your entire digital presence,<br><span>under one roof.</span></h1>
          <p class="login__lede">A content platform for a multi-site, multi-language industrial group — built to be operated, not merely demonstrated.</p>
        </div>

        <div class="login__features">
          <div v-for="feature in features" :key="feature.title" class="login-feature">
            <component :is="feature.icon" :size="15" />
            <div>
              <p class="login-feature__title">{{ feature.title }}</p>
              <p class="login-feature__text">{{ feature.text }}</p>
            </div>
          </div>
        </div>

        <div class="login__foot">
          <span class="mono">FICTIONAL PORTFOLIO BRAND</span>
          <span class="mono">v1.0 / 2026</span>
        </div>
      </div>
    </aside>

    <main class="login__main">
      <div class="login__card">
        <div class="login__card-head">
          <p class="mono muted">SECURE ACCESS</p>
          <h2>Sign in to Studio</h2>
          <p>Use your Meridian team account. Access is scoped by website and role.</p>
        </div>

        <form class="login__form" novalidate @submit.prevent="submit">
          <div class="field-s">
            <label class="field-s__label" for="login-email">Email address</label>
            <input id="login-email" v-model="email" type="email" class="input-s" autocomplete="username" required>
          </div>

          <div class="field-s">
            <label class="field-s__label" for="login-password">Password</label>
            <div class="password-wrap">
              <input id="login-password" v-model="password" :type="showPassword ? 'text' : 'password'" class="input-s" autocomplete="current-password" required>
              <button type="button" class="password-toggle" :aria-label="showPassword ? 'Hide password' : 'Show password'" @click="showPassword = !showPassword">
                <EyeOff v-if="showPassword" :size="15" />
                <Eye v-else :size="15" />
              </button>
            </div>
          </div>

          <div class="login__row">
            <label class="checkbox-s">
              <input v-model="remember" type="checkbox">
              <span>Keep me signed in</span>
            </label>
            <span class="mono faint">SESSION / SECURE</span>
          </div>

          <p v-if="error" class="error-s" role="alert">{{ error }}</p>

          <button type="submit" class="login__submit" :disabled="submitting">
            <span>{{ submitting ? 'Signing in…' : 'Sign in' }}</span>
            <ArrowRight :size="16" />
          </button>
        </form>

        <div class="login__demo">
          <div class="login__demo-head">
            <p class="mono">DEMONSTRATION ACCOUNTS</p>
            <span class="mono faint">PASSWORD: MERIDIAN!2026</span>
          </div>
          <div class="login__demo-grid">
            <button v-for="account in demoAccounts" :key="account.email" type="button" class="demo-account" @click="useAccount(account.email)">
              <span>{{ account.label }}</span>
              <span class="mono">{{ account.email }}</span>
            </button>
          </div>
          <p class="login__demo-note">
            These accounts and the content behind them are illustrative. Do not reuse this password in a real deployment.
          </p>
        </div>

        <div class="login__security">
          <LockKeyhole :size="13" />
          <span>Cookie-based session, CSRF protection, rate-limited sign-in, and site-scoped authorization.</span>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.login {
  min-height: 100vh;
  display: grid;
  grid-template-columns: minmax(0, 1.05fr) minmax(480px, 0.95fr);
  background: var(--s-bg);
}

.login__brand {
  background: var(--s-navy);
  color: #f5f3ee;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.login__grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(to right, rgba(245, 243, 238, 0.055) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(245, 243, 238, 0.035) 1px, transparent 1px);
  background-size: calc(100% / 8) 100%, 100% 80px;
}

.login__brand-inner {
  position: relative;
  z-index: 1;
  padding: clamp(28px, 4vw, 52px);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.brand-lockup {
  display: flex;
  align-items: center;
  gap: 11px;
}

.brand-mark {
  width: 34px;
  height: 34px;
}

.brand-word {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.2em;
}

.brand-studio {
  font-family: var(--font-mono);
  font-size: 8px;
  letter-spacing: 0.2em;
  color: rgba(245, 243, 238, 0.42);
}

.login__statement {
  margin-top: auto;
  padding-block: 7vh;
}

.login__kicker {
  color: var(--s-accent);
  font-size: 12px;
}

.login__statement h1 {
  margin-top: 18px;
  font-size: clamp(2.3rem, 4.2vw, 4rem);
  line-height: 0.98;
  letter-spacing: -0.05em;
  font-weight: 500;
  max-width: 15ch;
}

.login__statement h1 span {
  color: rgba(245, 243, 238, 0.42);
}

.login__lede {
  margin-top: 20px;
  max-width: 48ch;
  font-size: 13px;
  line-height: 1.65;
  color: rgba(245, 243, 238, 0.55);
}

.login__features {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
  padding-top: 24px;
  border-top: 1px solid rgba(245, 243, 238, 0.12);
}

.login-feature {
  display: flex;
  gap: 10px;
  color: rgba(245, 243, 238, 0.55);
}

.login-feature__title {
  font-size: 12px;
  font-weight: 700;
  color: #f5f3ee;
}

.login-feature__text {
  margin-top: 3px;
  font-size: 12px;
  line-height: 1.45;
  max-width: 28ch;
}

.login__foot {
  margin-top: auto;
  padding-top: 26px;
  display: flex;
  justify-content: space-between;
  gap: 12px;
  color: rgba(245, 243, 238, 0.28);
  font-size: 8px;
}

.login__main {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 28px;
}

.login__card {
  width: min(460px, 100%);
}

.login__card-head .mono {
  font-size: 12px;
}

.login__card-head h2 {
  margin-top: 8px;
  font-size: 28px;
  line-height: 1.05;
  letter-spacing: -0.04em;
  font-weight: 600;
}

.login__card-head p:last-child {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.55;
  color: var(--s-muted);
}

.login__form {
  margin-top: 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.password-wrap {
  position: relative;
}

.password-wrap .input-s {
  padding-right: 38px;
}

.password-toggle {
  position: absolute;
  right: 4px;
  top: 50%;
  transform: translateY(-50%);
  border: 0;
  background: transparent;
  color: var(--s-faint);
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
}

.login__row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}

.login__submit {
  height: 44px;
  border: 1px solid var(--s-ink);
  background: var(--s-ink);
  color: #f5f3ee;
  border-radius: var(--s-radius);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 14px;
  font-size: 12px;
  font-weight: 650;
  transition: background 160ms ease;
}

.login__submit:hover:not(:disabled) {
  background: var(--s-navy-2);
}

.login__submit:disabled {
  opacity: 0.6;
}

.login__demo {
  margin-top: 26px;
  border-top: 1px solid var(--s-line);
  padding-top: 16px;
}

.login__demo-head {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: baseline;
  font-size: 8px;
}

.login__demo-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
  margin-top: 10px;
}

.demo-account {
  border: 1px solid var(--s-line);
  background: var(--s-surface);
  border-radius: var(--s-radius);
  padding: 8px 9px;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: 12px;
  transition: border-color 150ms ease, background 150ms ease;
}

.demo-account:hover {
  border-color: var(--s-ink);
  background: var(--s-white);
}

.demo-account .mono {
  font-size: 7.5px;
  color: var(--s-faint);
  text-transform: none;
  letter-spacing: 0;
}

.login__demo-note {
  margin-top: 9px;
  font-size: 9.5px;
  line-height: 1.5;
  color: var(--s-faint);
}

.login__security {
  margin-top: 24px;
  display: flex;
  gap: 8px;
  align-items: flex-start;
  color: var(--s-faint);
  font-size: 9.5px;
  line-height: 1.5;
}

@media (max-width: 980px) {
  .login {
    grid-template-columns: 1fr;
  }

  .login__brand {
    display: none;
  }

  .login__main {
    min-height: 100vh;
  }
}

@media (max-width: 480px) {
  .login__demo-grid {
    grid-template-columns: 1fr;
  }
}
</style>
