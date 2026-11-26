<script setup lang="ts">
const { consent, hydrated, accept, deny, openPreferences } = useConsent()
const { localeCode, routeLocale } = useLocale()

const visible = computed(() => hydrated.value && consent.value === null)

const labels = computed(() => ({
  title: localeCode.value === 'th' ? 'การวัดผลบนเว็บไซต์' : localeCode.value === 'zh-CN' ? '网站分析' : 'Website analytics',
  body: localeCode.value === 'th'
    ? 'เราเก็บสถิติการเข้าชมแบบไม่ระบุตัวตน เพื่อปรับปรุงเนื้อหาและประสบการณ์ของคุณ คุณเลือกได้ว่าจะอนุญาตหรือไม่'
    : localeCode.value === 'zh-CN'
      ? '我们收集匿名的第一方访问统计，以改进内容与体验。您可以决定是否允许。'
      : 'We collect first-party, privacy-conscious analytics to improve our content and your experience. You choose whether to allow it.',
  accept: localeCode.value === 'th' ? 'อนุญาตการวัดผล' : localeCode.value === 'zh-CN' ? '允许分析' : 'Allow analytics',
  deny: localeCode.value === 'th' ? 'ไม่ตอนนี้' : localeCode.value === 'zh-CN' ? '暂不' : 'Not now',
  manage: localeCode.value === 'th' ? 'จัดการการตั้งค่า' : localeCode.value === 'zh-CN' ? '管理设置' : 'Manage preferences',
  policy: localeCode.value === 'th' ? 'นโยบายความเป็นส่วนตัว' : localeCode.value === 'zh-CN' ? '隐私政策' : 'Privacy policy',
}))
</script>

<template>
  <Transition name="cookie">
    <aside v-if="visible" class="cookie-banner" aria-label="Analytics consent">
      <div class="cookie-banner__body">
        <p class="mono cookie-banner__label">{{ labels.title }}</p>
        <p class="cookie-banner__text">{{ labels.body }}</p>
        <NuxtLink :to="`/${routeLocale}/privacy-policy`" class="cookie-banner__link">{{ labels.policy }}</NuxtLink>
      </div>
      <div class="cookie-banner__actions">
        <button type="button" class="btn btn-sm" @click="accept">{{ labels.accept }}</button>
        <button type="button" class="cookie-banner__deny" @click="deny">{{ labels.deny }}</button>
      </div>
      <button type="button" class="cookie-banner__manage" @click="openPreferences">{{ labels.manage }}</button>
    </aside>
  </Transition>
</template>

<style scoped>
.cookie-banner {
  position: fixed;
  right: max(1rem, env(safe-area-inset-right));
  bottom: max(1rem, env(safe-area-inset-bottom));
  z-index: 90;
  width: min(380px, calc(100vw - 2rem));
  background: var(--paper);
  border: 1px solid var(--line-strong);
  box-shadow: 0 16px 40px rgba(14, 20, 17, 0.14);
  padding: 1rem 1.05rem;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}

.cookie-banner__label {
  color: var(--steel-dark);
  margin-bottom: 0.35rem;
}

.cookie-banner__text {
  font-size: 0.82rem;
  line-height: 1.55;
  color: var(--ink-2);
}

.cookie-banner__link {
  display: inline-block;
  margin-top: 0.35rem;
  min-height: 32px;
  font-size: 0.8rem;
  text-decoration: underline;
  text-underline-offset: 3px;
}

.cookie-banner__actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.cookie-banner__deny,
.cookie-banner__manage {
  border: 0;
  background: transparent;
  min-height: 44px;
  padding: 0 0.15rem;
  font-size: 0.8rem;
  color: var(--steel-dark);
  text-decoration: underline;
  text-underline-offset: 4px;
}

.cookie-banner__manage {
  align-self: flex-start;
  margin-top: -0.4rem;
}

.cookie-banner__deny:hover,
.cookie-banner__manage:hover {
  color: var(--ink);
}

.cookie-enter-active,
.cookie-leave-active {
  transition: opacity 200ms ease, transform 260ms var(--ease);
}

.cookie-enter-from,
.cookie-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
