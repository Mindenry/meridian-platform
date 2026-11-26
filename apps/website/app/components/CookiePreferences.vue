<script setup lang="ts">
const {
  preferences,
  preferencesOpen,
  savePreferences,
  closePreferences,
} = useConsent()
const { localeCode, routeLocale } = useLocale()

const draft = ref({ analytics: false })
const panel = ref<HTMLElement | null>(null)
const closeButton = ref<HTMLButtonElement | null>(null)
let previousFocus: HTMLElement | null = null

const labels = computed(() => ({
  title: localeCode.value === 'th' ? 'การตั้งค่าคุกกี้' : localeCode.value === 'zh-CN' ? 'Cookie 设置' : 'Cookie settings',
  body: localeCode.value === 'th'
    ? 'เลือกประเภทคุกกี้ที่คุณยอมให้ใช้ คุกกี้ที่จำเป็นต้องใช้เพื่อให้เว็บทำงานและเปิดใช้เสมอ'
    : localeCode.value === 'zh-CN'
      ? '选择允许使用的 Cookie 类别。网站运行所必需的 Cookie 始终启用。'
      : 'Choose which cookie categories you allow. Cookies required for the site to function are always on.',
  necessary: localeCode.value === 'th' ? 'จำเป็น' : localeCode.value === 'zh-CN' ? '必要' : 'Necessary',
  necessaryNote: localeCode.value === 'th'
    ? 'ความปลอดภัย การเข้าสู่ระบบ และการจดจำภาษาที่เลือก'
    : localeCode.value === 'zh-CN'
      ? '安全性、登录状态以及语言偏好。'
      : 'Security, sign-in state, and language preference.',
  analytics: localeCode.value === 'th' ? 'การวัดผล' : localeCode.value === 'zh-CN' ? '分析' : 'Analytics',
  analyticsNote: localeCode.value === 'th'
    ? 'สถิติการเข้าชมแบบไม่ระบุตัวตน เพื่อปรับปรุงเนื้อหา'
    : localeCode.value === 'zh-CN'
      ? '匿名访问统计，用于改进内容。'
      : 'Anonymous traffic statistics used to improve content.',
  noMarketing: localeCode.value === 'th'
    ? 'เว็บนี้ไม่ใช้คุกกี้การตลาดหรือคุกกี้บุคคลที่สาม'
    : localeCode.value === 'zh-CN'
      ? '本网站不使用营销或第三方 Cookie。'
      : 'This site does not use marketing or third-party cookies.',
  save: localeCode.value === 'th' ? 'บันทึกการตั้งค่า' : localeCode.value === 'zh-CN' ? '保存设置' : 'Save preferences',
  cancel: localeCode.value === 'th' ? 'ยกเลิก' : localeCode.value === 'zh-CN' ? '取消' : 'Cancel',
  policy: localeCode.value === 'th' ? 'อ่านนโยบายความเป็นส่วนตัว' : localeCode.value === 'zh-CN' ? '阅读隐私政策' : 'Read the privacy policy',
}))

watch(preferencesOpen, async (open) => {
  if (!import.meta.client) return

  document.documentElement.style.overflow = open ? 'hidden' : ''

  if (open) {
    draft.value = { analytics: preferences.value.analytics }
    previousFocus = document.activeElement as HTMLElement | null
    await nextTick()
    closeButton.value?.focus()
  } else {
    previousFocus?.focus?.()
    previousFocus = null
  }
})

function save() {
  savePreferences({ analytics: draft.value.analytics })
  closePreferences()
}

function onKeydown(event: KeyboardEvent) {
  if (!preferencesOpen.value) return

  if (event.key === 'Escape') {
    closePreferences()
    return
  }

  if (event.key !== 'Tab' || !panel.value) return

  const focusable = [...panel.value.querySelectorAll<HTMLElement>('button, a[href], input:not([disabled])')]
    .filter((element) => element.offsetParent !== null)

  if (!focusable.length) return

  const first = focusable[0]
  const last = focusable[focusable.length - 1]

  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  if (import.meta.client) document.documentElement.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="prefs">
      <div v-if="preferencesOpen" class="prefs-overlay" @keydown="onKeydown">
        <button type="button" class="prefs-overlay__backdrop" :aria-label="labels.cancel" tabindex="-1" @click="closePreferences" />
        <div ref="panel" class="prefs-panel" role="dialog" aria-modal="true" aria-labelledby="prefs-title">
          <div class="prefs-head">
            <h2 id="prefs-title" class="prefs-title">{{ labels.title }}</h2>
            <button ref="closeButton" type="button" class="prefs-close" :aria-label="labels.cancel" @click="closePreferences">
              <AppIcon name="close" :size="20" />
            </button>
          </div>

          <p class="prefs-body">{{ labels.body }}</p>

          <div class="prefs-group">
            <div class="prefs-row">
              <div>
                <p class="prefs-row__title">{{ labels.necessary }}</p>
                <p class="prefs-row__note">{{ labels.necessaryNote }}</p>
              </div>
              <span class="prefs-locked mono">{{ localeCode === 'th' ? 'เปิดใช้' : localeCode === 'zh-CN' ? '已启用' : 'Always on' }}</span>
            </div>

            <label class="prefs-row prefs-row--toggle">
              <span>
                <span class="prefs-row__title">{{ labels.analytics }}</span>
                <span class="prefs-row__note">{{ labels.analyticsNote }}</span>
              </span>
              <input v-model="draft.analytics" type="checkbox" class="prefs-switch" :aria-label="labels.analytics">
            </label>
          </div>

          <p class="prefs-note">{{ labels.noMarketing }}</p>

          <div class="prefs-actions">
            <button type="button" class="btn btn-sm" @click="save">{{ labels.save }}</button>
            <button type="button" class="prefs-cancel" @click="closePreferences">{{ labels.cancel }}</button>
            <NuxtLink :to="`/${routeLocale}/privacy-policy`" class="prefs-policy" @click="closePreferences">{{ labels.policy }}</NuxtLink>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.prefs-overlay {
  position: fixed;
  inset: 0;
  z-index: 140;
  display: grid;
  place-items: center;
  padding: 1rem;
}

.prefs-overlay__backdrop {
  position: absolute;
  inset: 0;
  border: 0;
  background: rgba(14, 20, 17, 0.72);
  backdrop-filter: blur(6px);
}

.prefs-panel {
  position: relative;
  width: min(560px, 100%);
  max-height: 90vh;
  overflow-y: auto;
  background: var(--ivory);
  border: 1px solid var(--line-strong);
  padding: clamp(1.25rem, 3vw, 2rem);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.prefs-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.prefs-title {
  font-family: var(--font-display);
  font-size: clamp(1.5rem, 3vw, 2rem);
  line-height: 1.15;
}

.prefs-close {
  border: 0;
  background: transparent;
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  color: var(--ink);
}

.prefs-close:hover {
  color: var(--accent-text);
}

.prefs-body,
.prefs-note {
  color: var(--steel-dark);
  font-size: 0.9rem;
  line-height: 1.6;
}

.prefs-group {
  border-top: 1px solid var(--line);
}

.prefs-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  padding-block: 1rem;
  border-bottom: 1px solid var(--line);
}

.prefs-row--toggle {
  cursor: pointer;
}

.prefs-row__title {
  display: block;
  font-weight: 650;
  font-size: 0.95rem;
}

.prefs-row__note {
  display: block;
  margin-top: 0.25rem;
  color: var(--steel-dark);
  font-size: 0.82rem;
  line-height: 1.5;
  max-width: 46ch;
}

.prefs-locked {
  color: var(--steel-dark);
  white-space: nowrap;
}

.prefs-switch {
  width: 44px;
  height: 26px;
  accent-color: var(--accent-text);
  flex: 0 0 auto;
}

.prefs-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
}

.prefs-cancel {
  border: 0;
  background: transparent;
  min-height: 44px;
  padding: 0 0.25rem;
  font-size: 0.85rem;
  color: var(--steel-dark);
  text-decoration: underline;
  text-underline-offset: 4px;
}

.prefs-policy {
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  font-size: 0.85rem;
  text-decoration: underline;
  text-underline-offset: 4px;
}

.prefs-enter-active,
.prefs-leave-active {
  transition: opacity 180ms ease;
}

.prefs-enter-from,
.prefs-leave-to {
  opacity: 0;
}
</style>
