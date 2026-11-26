<script setup lang="ts">
import type { ContactDetails, NavItem, SiteBootstrap } from '~/types'

const props = defineProps<{ bootstrap: SiteBootstrap | null }>()
const { localeCode, localePath } = useLocale()
const { openPreferences } = useConsent()
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
  backToTop: localeCode.value === 'th' ? 'กลับด้านบน' : localeCode.value === 'zh-CN' ? '返回顶部' : 'Back to top',
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
    <div class="container">
      <div class="footer-top" data-reveal>
        <div>
          <p class="eyebrow footer-eyebrow">{{ labels.contact }}</p>
          <a :href="`mailto:${contact?.email || 'hello@meridian.example'}`" class="footer-email">
            {{ contact?.email || 'hello@meridian.example' }}
            <AppIcon name="arrow-up-right" :size="20" />
          </a>
        </div>
        <div class="footer-contact">
          <p class="footer-contact__label">{{ labels.offices }}</p>
          <p v-for="office in contact?.offices" :key="office.city" class="footer-office">
            <span>{{ office.city }}</span>
            <span class="footer-office__address">{{ office.address }}</span>
          </p>
        </div>
      </div>

      <div class="footer-wordmark" aria-hidden="true">MERIDIAN</div>

      <div class="footer-grid">
        <div class="footer-brand">
          <SiteLogo variant="light" />
          <p class="footer-statement">{{ footerStatement }}</p>
          <div class="footer-badges">
            <span class="mono">EST. 2009</span>
            <span class="mono">BANGKOK · RAYONG · SINGAPORE</span>
          </div>
        </div>

        <div>
          <p class="footer-heading">{{ labels.company }}</p>
          <NuxtLink v-for="item in menu('footer-company')" :key="item.url" :to="item.url" class="footer-link">{{ item.label }}</NuxtLink>
        </div>
        <div>
          <p class="footer-heading">{{ labels.capabilities }}</p>
          <NuxtLink v-for="item in menu('footer-capabilities')" :key="item.url" :to="item.url" class="footer-link">{{ item.label }}</NuxtLink>
          <NuxtLink to="/documents/meridian-company-profile.pdf" class="footer-link" target="_blank">{{ labels.profile }}</NuxtLink>
        </div>
        <div>
          <p class="footer-heading">{{ labels.legal }}</p>
          <NuxtLink v-for="item in menu('footer-legal')" :key="item.url" :to="item.url" class="footer-link">{{ item.label }}</NuxtLink>
          <NuxtLink :to="localePath('accessibility')" class="footer-link">{{ labels.accessibility }}</NuxtLink>
          <button type="button" class="footer-link footer-link--button" @click="openPreferences">{{ labels.cookies }}</button>
          <NuxtLink :to="creditPath" class="footer-link">{{ labels.credits }}</NuxtLink>
        </div>
      </div>

      <div class="footer-bottom">
        <p class="footer-notice">{{ labels.notice }}</p>
        <div class="footer-bottom__actions">
          <LocaleSwitcher />
          <span class="mono">© {{ new Date().getFullYear() }} MERIDIAN</span>
          <button type="button" class="footer-top-link" @click="scrollTop">
            {{ labels.backToTop }}
            <AppIcon name="arrow-down" :size="13" style="transform: rotate(180deg)" />
          </button>
        </div>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.footer-top {
  display: flex;
  justify-content: space-between;
  gap: 3rem;
  padding-bottom: clamp(3rem, 6vw, 5rem);
  border-bottom: 1px solid var(--line-light);
}

.footer-eyebrow {
  color: rgba(241, 237, 228, 0.58);
}

.footer-email {
  margin-top: 1.25rem;
  display: inline-flex;
  align-items: center;
  gap: 0.8rem;
  font-size: clamp(1.5rem, 3.4vw, 3rem);
  letter-spacing: -0.035em;
  border-bottom: 1px solid rgba(241, 237, 228, 0.35);
  padding-bottom: 0.35rem;
  transition: color 200ms ease, border-color 200ms ease;
}

.footer-email:hover {
  color: var(--accent);
  border-color: var(--accent);
}

.footer-contact {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 2rem;
  max-width: 720px;
}

.footer-contact__label {
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(241, 237, 228, 0.76);
  grid-column: 1 / -1;
}

.footer-office {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  font-size: 0.85rem;
  line-height: 1.45;
}

.footer-office__address {
  color: rgba(241, 237, 228, 0.76);
  font-size: 0.78rem;
}

.footer-wordmark {
  margin-top: clamp(3rem, 6vw, 5rem);
  border-bottom: 1px solid var(--line-light);
  padding-bottom: 1.5rem;
}

.footer-grid {
  padding-block: clamp(3rem, 5vw, 4.5rem);
  border-bottom: 1px solid var(--line-light);
}

.footer-statement {
  margin-top: 1.5rem;
  max-width: 30ch;
  font-size: 1rem;
  line-height: 1.55;
  color: rgba(241, 237, 228, 0.78);
}

.footer-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 2rem;
  color: rgba(241, 237, 228, 0.72);
  font-size: 0.75rem;
}

.footer-heading {
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(241, 237, 228, 0.76);
  margin-bottom: 1.2rem;
}

.footer-bottom {
  padding-block: 1.75rem 2.25rem;
  display: flex;
  justify-content: space-between;
  gap: 2rem;
  align-items: flex-end;
}

.footer-notice {
  max-width: 68ch;
  font-size: 0.7rem;
  line-height: 1.55;
  color: rgba(241, 237, 228, 0.7);
}

.footer-bottom__actions {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
  justify-content: flex-end;
  color: rgba(241, 237, 228, 0.76);
}

.footer-bottom__actions :deep(.locale-switcher__item) {
  color: rgba(241, 237, 228, 0.55);
}

.footer-bottom__actions :deep(.locale-switcher__item.is-active),
.footer-bottom__actions :deep(.locale-switcher__item:hover) {
  color: var(--ivory);
}

.footer-top-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border: 0;
  background: transparent;
  color: rgba(241, 237, 228, 0.6);
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.footer-top-link:hover {
  color: var(--ivory);
}

@media (max-width: 980px) {
  .footer-top {
    flex-direction: column;
  }

  .footer-contact {
    max-width: 100%;
  }

  .footer-bottom {
    flex-direction: column;
    align-items: flex-start;
  }

  .footer-bottom__actions {
    justify-content: flex-start;
  }
}

@media (max-width: 560px) {
  .footer-contact {
    grid-template-columns: 1fr;
  }
}
</style>
