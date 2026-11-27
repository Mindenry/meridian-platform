<script setup lang="ts">
import type { ContactDetails, NavItem, SiteBootstrap } from '~/types'

const props = defineProps<{ bootstrap: SiteBootstrap | null }>()
const { localeCode, localePath } = useLocale()
const { openPreferences } = useConsent()
const config = useRuntimeConfig()
const route = useRoute()

const contact = computed<ContactDetails | null>(() => props.bootstrap?.settings?.['contact.details'] ?? null)
const footerStatement = computed(() => {
  const statement = props.bootstrap?.settings?.['footer.statement']
  if (typeof statement === 'string') return statement
  return statement?.[localeCode.value] ?? statement?.en ?? ''
})

const menu = (key: string): NavItem[] => props.bootstrap?.navigation?.[key] ?? []

const labels = computed(() => ({
  contact: localeCode.value === 'th' ? 'ติดต่อ' : localeCode.value === 'zh-CN' ? '联系' : 'Contact',
  company: localeCode.value === 'th' ? 'บริษัท' : localeCode.value === 'zh-CN' ? '公司' : 'Company',
  capabilities: localeCode.value === 'th' ? 'ขีดความสามารถ' : localeCode.value === 'zh-CN' ? '能力' : 'Capabilities',
  legal: localeCode.value === 'th' ? 'กฎหมาย' : localeCode.value === 'zh-CN' ? '法律' : 'Legal',
  offices: localeCode.value === 'th' ? 'สำนักงาน' : localeCode.value === 'zh-CN' ? '办公室' : 'Offices',
  profile: localeCode.value === 'th' ? 'โปรไฟล์บริษัท' : localeCode.value === 'zh-CN' ? '公司简介' : 'Company profile',
  credits: localeCode.value === 'th' ? 'เครดิตรูปภาพ' : localeCode.value === 'zh-CN' ? '图片来源' : 'Image credits',
  accessibility: localeCode.value === 'th' ? 'การเข้าถึง' : localeCode.value === 'zh-CN' ? '无障碍声明' : 'Accessibility',
  cookies: localeCode.value === 'th' ? 'การตั้งค่าคุกกี้' : localeCode.value === 'zh-CN' ? 'Cookie 设置' : 'Cookie settings',
  demoNote: localeCode.value === 'th'
    ? 'เดโมแบบ static: เนื้อหาเป็นชุดข้อมูลตัวอย่าง และการส่งฟอร์มจะไม่ถูกส่งจริง'
    : localeCode.value === 'zh-CN'
      ? '静态演示：内容为快照，表单提交不会发送真实数据。'
      : 'Static demo: content is a snapshot and form submissions are simulated.',
  studioTour: localeCode.value === 'th' ? 'ทัวร์ Studio' : localeCode.value === 'zh-CN' ? 'Studio 导览' : 'Studio tour',
  backToTop: localeCode.value === 'th' ? 'กลับด้านบน' : localeCode.value === 'zh-CN' ? '返回顶部' : 'Back to top',
  startProject: localeCode.value === 'th' ? 'เริ่มโครงการ' : localeCode.value === 'zh-CN' ? '开始项目' : 'Start a project',
  notice: localeCode.value === 'th'
    ? 'เมอริเดียน อินดัสเทรียล กรุ๊ป เป็นแบรนด์สมมติสำหรับการสาธิตพอร์ตโฟลิโอ ตัวเลข ผลลัพธ์ และข้อมูลองค์กรทั้งหมดเป็นข้อมูลประกอบการสาธิต'
    : localeCode.value === 'zh-CN'
      ? 'Meridian 工业集团是为作品集演示创建的虚构品牌。所有数字、成果与企业信息均为示例内容。'
      : 'Meridian Industrial Group is a fictional brand created for portfolio demonstration. All figures, outcomes, and corporate information are illustrative.',
}))

const creditPath = computed(() => `/${route.params.locale || 'th'}/credits`)

function scrollTop() {
  if (!import.meta.client) return
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<template>
  <footer class="site-footer">
    <div class="footer-aura" aria-hidden="true" />

    <div class="container footer-inner">
      <div class="footer-main">
        <div class="footer-contact">
          <p class="eyebrow footer-label">{{ labels.contact }}</p>
          <a :href="`mailto:${contact?.email || 'hello@meridian.example'}`" class="footer-email">
            <span>{{ contact?.email || 'hello@meridian.example' }}</span>
            <span class="footer-email__icon">
              <AppIcon name="arrow-up-right" :size="20" />
            </span>
          </a>
          <p class="footer-statement">{{ footerStatement }}</p>
          <NuxtLink :to="localePath('contact')" class="footer-cta">
            <span>{{ labels.startProject }}</span>
            <AppIcon name="arrow-up-right" :size="14" />
          </NuxtLink>
        </div>

        <nav class="footer-nav" aria-label="Footer">
          <div class="footer-col">
            <p class="footer-heading">{{ labels.company }}</p>
            <NuxtLink v-for="item in menu('footer-company')" :key="item.url" :to="item.url" class="footer-link">{{ item.label }}</NuxtLink>
            <NuxtLink :to="localePath('studio')" class="footer-link">{{ labels.studioTour }}</NuxtLink>
          </div>
          <div class="footer-col">
            <p class="footer-heading">{{ labels.capabilities }}</p>
            <NuxtLink v-for="item in menu('footer-capabilities')" :key="item.url" :to="item.url" class="footer-link">{{ item.label }}</NuxtLink>
            <NuxtLink to="/documents/meridian-company-profile.pdf" class="footer-link" target="_blank">{{ labels.profile }}</NuxtLink>
          </div>
          <div class="footer-col">
            <p class="footer-heading">{{ labels.legal }}</p>
            <NuxtLink v-for="item in menu('footer-legal')" :key="item.url" :to="item.url" class="footer-link">{{ item.label }}</NuxtLink>
            <NuxtLink :to="localePath('accessibility')" class="footer-link">{{ labels.accessibility }}</NuxtLink>
            <button type="button" class="footer-link footer-link--button" @click="openPreferences">{{ labels.cookies }}</button>
            <NuxtLink :to="creditPath" class="footer-link">{{ labels.credits }}</NuxtLink>
          </div>
        </nav>
      </div>

      <div class="footer-offices">
        <p class="footer-heading footer-offices__label">{{ labels.offices }}</p>
        <div class="footer-offices__grid">
          <div v-for="office in contact?.offices" :key="office.city" class="footer-office">
            <span class="footer-office__city">{{ office.city }}</span>
            <span class="footer-office__address">{{ office.address }}</span>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <div class="footer-fineprint">
          <p class="footer-notice">{{ labels.notice }}</p>
          <p v-if="config.public.demoStatic" class="footer-demo">{{ labels.demoNote }}</p>
        </div>
        <div class="footer-meta">
          <LocaleSwitcher />
          <span class="footer-copy mono">© {{ new Date().getFullYear() }} MERIDIAN</span>
          <button type="button" class="footer-top-link" :aria-label="labels.backToTop" @click="scrollTop">
            <span class="footer-top-link__label">{{ labels.backToTop }}</span>
            <span class="footer-top-link__icon">
              <AppIcon name="arrow-down" :size="14" />
            </span>
          </button>
        </div>
      </div>
    </div>

    <div class="footer-wordmark" aria-hidden="true">
      <span>MERIDIAN</span>
    </div>
  </footer>
</template>

<style scoped>
.site-footer {
  position: relative;
  overflow: hidden;
  background:
    radial-gradient(900px 420px at 88% 0%, rgba(240, 82, 31, 0.09), transparent 60%),
    linear-gradient(180deg, #0e1411 0%, #0a0e0c 62%, #080b09 100%);
  color: var(--ivory);
  border-top: 1px solid rgba(241, 237, 228, 0.14);
  padding-top: clamp(4rem, 7vw, 6.5rem);
}

.site-footer::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: min(440px, 46%);
  height: 1px;
  background: linear-gradient(90deg, var(--accent), transparent);
  pointer-events: none;
}

.footer-aura {
  position: absolute;
  top: -20%;
  right: -10%;
  width: 52vw;
  height: 52vw;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(240, 82, 31, 0.1), transparent 62%);
  pointer-events: none;
}

.footer-inner {
  position: relative;
  z-index: 1;
}

.footer-main {
  display: grid;
  grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr);
  gap: clamp(2.5rem, 6vw, 7rem);
  align-items: start;
}

.footer-label {
  color: rgba(241, 237, 228, 0.62);
}

.footer-email {
  margin-top: 1.3rem;
  display: inline-flex;
  align-items: center;
  gap: 1rem;
  max-width: 100%;
  font-family: var(--font-display);
  font-optical-sizing: auto;
  font-size: clamp(1.6rem, 3vw, 2.9rem);
  line-height: 1.05;
  letter-spacing: -0.03em;
  font-weight: 500;
  border-bottom: 1px solid rgba(241, 237, 228, 0.3);
  padding-bottom: 0.45rem;
  transition: color 220ms ease, border-color 220ms ease;
}

.footer-email__icon {
  flex: 0 0 auto;
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border: 1px solid rgba(241, 237, 228, 0.3);
  border-radius: 50%;
  transition: background 220ms ease, border-color 220ms ease, color 220ms ease, transform 320ms var(--ease);
}

.footer-email:hover {
  color: var(--accent);
  border-color: var(--accent);
}

.footer-email:hover .footer-email__icon {
  background: var(--accent);
  border-color: var(--accent);
  color: var(--ink);
  transform: rotate(45deg);
}

.footer-statement {
  margin-top: 1.6rem;
  max-width: 36ch;
  font-size: 0.95rem;
  line-height: 1.65;
  color: rgba(241, 237, 228, 0.6);
}

.footer-cta {
  margin-top: 2rem;
  display: inline-flex;
  align-items: center;
  gap: 0.7rem;
  min-height: 46px;
  padding: 0.7rem 1.1rem;
  border: 1px solid rgba(241, 237, 228, 0.3);
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--ivory);
  background: rgba(241, 237, 228, 0.04);
  transition: background 200ms ease, border-color 200ms ease, color 200ms ease, transform 160ms var(--ease);
}

.footer-cta svg {
  transition: transform 220ms var(--ease);
}

.footer-cta:hover {
  background: var(--ivory);
  border-color: var(--ivory);
  color: var(--ink);
}

.footer-cta:hover svg {
  transform: translate(2px, -2px);
}

.footer-cta:active {
  transform: scale(0.985);
}

.footer-nav {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(1.5rem, 3vw, 3rem);
  padding-top: 0.35rem;
}

.footer-heading {
  font-family: var(--font-mono);
  font-size: 0.7rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: rgba(241, 237, 228, 0.45);
  margin-bottom: 0.85rem;
}

.footer-link {
  display: flex;
  align-items: center;
  min-height: 44px;
  padding-block: 0.15rem;
  color: rgba(241, 237, 228, 0.72);
  font-size: 0.88rem;
  text-align: left;
  transition: color 160ms ease, transform 200ms var(--ease);
}

.footer-link--button {
  border: 0;
  background: transparent;
  width: 100%;
  padding-inline: 0;
}

.footer-link:hover {
  color: var(--ivory);
  transform: translateX(3px);
}

.footer-offices {
  margin-top: clamp(2.5rem, 5vw, 4rem);
  padding-top: 1.4rem;
  border-top: 1px solid rgba(241, 237, 228, 0.12);
  display: grid;
  grid-template-columns: minmax(0, 0.55fr) minmax(0, 3fr);
  gap: clamp(1.5rem, 3vw, 3rem);
}

.footer-offices__label {
  margin-bottom: 0;
}

.footer-offices__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(1.25rem, 3vw, 3rem);
}

.footer-office {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.88rem;
  line-height: 1.5;
}

.footer-office__city {
  color: var(--ivory);
}

.footer-office__address {
  max-width: 26ch;
  color: rgba(241, 237, 228, 0.5);
  font-size: 0.8rem;
}

.footer-bottom {
  margin-top: clamp(2.5rem, 5vw, 4rem);
  padding-block: 1.6rem 1.8rem;
  border-top: 1px solid rgba(241, 237, 228, 0.12);
  display: flex;
  justify-content: space-between;
  gap: 2rem;
  align-items: flex-start;
}

.footer-fineprint {
  max-width: 66ch;
}

.footer-notice {
  font-size: 0.72rem;
  line-height: 1.6;
  color: rgba(241, 237, 228, 0.42);
}

.footer-demo {
  margin-top: 0.35rem;
  font-size: 0.72rem;
  line-height: 1.6;
  color: rgba(241, 237, 228, 0.42);
}

.footer-meta {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 1.25rem;
  color: rgba(241, 237, 228, 0.6);
}

.footer-meta :deep(.locale-switcher__item) {
  color: rgba(241, 237, 228, 0.55);
}

.footer-meta :deep(.locale-switcher__item.is-active),
.footer-meta :deep(.locale-switcher__item:hover) {
  color: var(--ivory);
}

.footer-copy {
  font-size: 0.68rem;
  letter-spacing: 0.08em;
  color: rgba(241, 237, 228, 0.45);
  white-space: nowrap;
}

.footer-top-link {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  border: 0;
  background: transparent;
  color: rgba(241, 237, 228, 0.62);
  font-family: var(--font-mono);
  font-size: 0.68rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 0;
}

.footer-top-link__icon {
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  border: 1px solid rgba(241, 237, 228, 0.22);
  border-radius: 50%;
  transition: background 180ms ease, border-color 180ms ease, color 180ms ease, transform 220ms var(--ease);
}

.footer-top-link__icon :deep(svg) {
  transform: rotate(180deg);
}

.footer-top-link:hover {
  color: var(--ivory);
}

.footer-top-link:hover .footer-top-link__icon {
  background: var(--ivory);
  border-color: var(--ivory);
  color: var(--ink);
  transform: translateY(-2px);
}

.footer-wordmark {
  position: relative;
  z-index: 1;
  margin-top: 0.75rem;
  border-top: 1px solid rgba(241, 237, 228, 0.08);
  overflow: hidden;
  line-height: 0;
  padding-top: clamp(1rem, 2vw, 2rem);
}

.footer-wordmark span {
  display: block;
  width: 100%;
  font-family: var(--font-display);
  font-optical-sizing: auto;
  font-size: clamp(4.2rem, 16.6vw, 18rem);
  line-height: 0.72;
  letter-spacing: -0.05em;
  font-weight: 500;
  white-space: nowrap;
  text-align: center;
  transform: translateY(14%);
  background: linear-gradient(180deg, rgba(241, 237, 228, 0.95) 0%, rgba(241, 237, 228, 0.42) 100%);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

@media (forced-colors: active) {
  .footer-wordmark span {
    background: none;
    color: CanvasText;
  }
}

@media (max-width: 980px) {
  .footer-main {
    grid-template-columns: 1fr;
  }

  .footer-nav {
    gap: 1.5rem;
  }

  .footer-bottom {
    flex-direction: column;
    align-items: flex-start;
  }

  .footer-meta {
    justify-content: flex-start;
  }
}

@media (max-width: 760px) {
  .footer-nav {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.25rem 2rem;
  }

  .footer-nav .footer-col:last-child {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0 2rem;
  }

  .footer-nav .footer-col:last-child .footer-heading {
    grid-column: 1 / -1;
  }

  .footer-offices {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .footer-offices__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.5rem;
  }

  .footer-wordmark span {
    font-size: clamp(3.6rem, 20vw, 7rem);
  }
}

@media (max-width: 560px) {
  .footer-email {
    font-size: clamp(1.35rem, 7vw, 1.9rem);
    gap: 0.7rem;
  }

  .footer-email__icon {
    width: 38px;
    height: 38px;
  }

  .footer-offices__grid {
    grid-template-columns: 1fr;
  }

  .footer-meta {
    gap: 0.9rem;
  }

  .footer-top-link__label {
    display: none;
  }
}
</style>
