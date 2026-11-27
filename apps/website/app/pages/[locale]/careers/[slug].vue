<script setup lang="ts">
import type { JobDetail } from '~/types'

const route = useRoute()
const api = useApi()
const { localeCode, routeLocale } = useLocale()
const copy = useCopy()

const slug = computed(() => {
  const param = route.params.slug
  return Array.isArray(param) ? String(param[0] || '') : String(param || '')
})

const { data, error } = await useAsyncData(
  `job-${routeLocale.value}-${slug.value}`,
  () => api.get<{ data: JobDetail }>(`/public/careers/${encodeURIComponent(slug.value)}`, {
    query: { locale: localeCode.value },
  }),
  { watch: [slug, routeLocale] },
)

if (error.value) throw createError({ statusCode: 404, statusMessage: 'Role not found' })

const job = computed<JobDetail | null>(() => data.value?.data ?? null)
usePageSeo(job)

const form = reactive({
  name: '',
  email: '',
  phone: '',
  cover_letter: '',
  portfolio_url: '',
  consent: false,
  website: '',
})
const resume = ref<File | null>(null)
const errors = reactive<Record<string, string>>({})
const sending = ref(false)
const success = ref<string | null>(null)
const reference = ref<string | null>(null)
const startedAt = ref(Date.now())

const { departmentLabel, locationLabel, employmentLabel } = useJobLabels()

const formatDate = (iso?: string | null) => {
  if (!iso) return ''
  return new Intl.DateTimeFormat(
    localeCode.value === 'th' ? 'th-TH' : localeCode.value === 'zh-CN' ? 'zh-CN' : 'en-GB',
    { day: 'numeric', month: 'short', year: 'numeric' },
  ).format(new Date(iso))
}

function onFile(event: Event) {
  const target = event.target as HTMLInputElement
  resume.value = target.files?.[0] ?? null
}

async function submit() {
  Object.keys(errors).forEach((key) => delete errors[key])

  if (!form.consent) {
    errors.consent = localeCode.value === 'th' ? 'กรุณายินยอมก่อนส่งใบสมัคร' : localeCode.value === 'zh-CN' ? '提交前请同意。' : 'Please provide consent before submitting.'
    return
  }

  if (!resume.value) {
    errors.resume = localeCode.value === 'th' ? 'กรุณาแนบเรซูเม่ PDF' : localeCode.value === 'zh-CN' ? '请附上 PDF 简历。' : 'Please attach a PDF résumé.'
    return
  }

  sending.value = true
  const body = new FormData()
  body.append('name', form.name)
  body.append('email', form.email)
  body.append('phone', form.phone)
  body.append('cover_letter', form.cover_letter)
  body.append('portfolio_url', form.portfolio_url)
  body.append('consent', form.consent ? '1' : '0')
  body.append('website', form.website)
  body.append('resume', resume.value)

  try {
    const response = await api.post<{ message: string; reference: string }>(
      `/public/jobs/${encodeURIComponent(slug.value)}/applications`,
      body,
    )
    success.value = response.message
    reference.value = response.reference
  } catch (error: any) {
    const serverErrors = error?.data?.errors
    if (serverErrors) {
      for (const [key, messages] of Object.entries(serverErrors)) {
        errors[key] = Array.isArray(messages) ? String(messages[0]) : String(messages)
      }
    }
    errors._form = error?.data?.message || copy.value.somethingWrong
  } finally {
    sending.value = false
  }
}

const sectionLabels = computed(() => ({
  description: localeCode.value === 'th' ? 'เกี่ยวกับตำแหน่ง' : localeCode.value === 'zh-CN' ? '关于职位' : 'About the role',
  responsibilities: localeCode.value === 'th' ? 'ความรับผิดชอบ' : localeCode.value === 'zh-CN' ? '职责' : 'Responsibilities',
  requirements: localeCode.value === 'th' ? 'คุณสมบัติ' : localeCode.value === 'zh-CN' ? '要求' : 'Requirements',
  benefits: localeCode.value === 'th' ? 'สิ่งที่เราเสนอ' : localeCode.value === 'zh-CN' ? '我们提供' : 'What we offer',
  apply: localeCode.value === 'th' ? 'สมัครตำแหน่งนี้' : localeCode.value === 'zh-CN' ? '申请此职位' : 'Apply for this role',
}))
</script>

<template>
  <div v-if="job">
    <Breadcrumbs :items="job.breadcrumbs" />
    <FallbackNotice v-if="job.is_fallback" :locale="job.content_locale || 'en'" />

    <header class="job-hero">
      <div class="job-hero__grid" aria-hidden="true" />
      <div class="container job-hero__inner">
        <div>
          <p class="eyebrow">{{ copy.careers }} / {{ departmentLabel(job.department) }}</p>
          <h1 class="job-hero__title">{{ job.title }}</h1>
          <p class="job-hero__summary">{{ job.summary }}</p>
          <div class="job-hero__meta mono">
            <span>{{ locationLabel(job.location) }}</span>
            <span>{{ employmentLabel(job.employment_type) }}</span>
            <span>{{ copy.posted }} {{ formatDate(job.posted_at) }}</span>
          </div>
        </div>
        <a href="#apply" class="btn btn-light job-hero__apply">{{ copy.applyNow }} <AppIcon name="arrow-down" :size="14" /></a>
      </div>
    </header>

    <section class="job-body">
      <div class="container job-body__grid">
        <div class="job-content">
          <section class="job-section">
            <p class="eyebrow">{{ sectionLabels.description }}</p>
            <div class="prose job-description" v-html="job.description" />
          </section>

          <section v-if="job.responsibilities?.length" class="job-section">
            <p class="eyebrow">{{ sectionLabels.responsibilities }}</p>
            <ul class="job-list">
              <li v-for="item in job.responsibilities" :key="item">{{ item }}</li>
            </ul>
          </section>

          <section v-if="job.requirements?.length" class="job-section">
            <p class="eyebrow">{{ sectionLabels.requirements }}</p>
            <ul class="job-list">
              <li v-for="item in job.requirements" :key="item">{{ item }}</li>
            </ul>
          </section>

          <section v-if="job.benefits?.length" class="job-section">
            <p class="eyebrow">{{ sectionLabels.benefits }}</p>
            <ul class="job-list job-list--columns">
              <li v-for="item in job.benefits" :key="item">{{ item }}</li>
            </ul>
          </section>
        </div>

        <aside class="job-side">
          <div class="job-side__block">
            <p class="mono">{{ copy.jobDetails }}</p>
            <div class="job-side__row"><span>{{ copy.department }}</span><span>{{ departmentLabel(job.department) }}</span></div>
            <div class="job-side__row"><span>{{ copy.location }}</span><span>{{ locationLabel(job.location) }}</span></div>
            <div class="job-side__row"><span>{{ copy.employment }}</span><span>{{ employmentLabel(job.employment_type) }}</span></div>
            <div v-if="job.closes_at" class="job-side__row"><span>{{ copy.closing }}</span><span>{{ formatDate(job.closes_at) }}</span></div>
          </div>
          <a href="#apply" class="btn">{{ copy.apply }} <AppIcon name="arrow-down" :size="14" /></a>
        </aside>
      </div>
    </section>

    <section id="apply" class="section job-apply">
      <div class="container job-apply__grid">
        <div data-reveal>
          <p class="eyebrow">{{ copy.apply }}</p>
          <h2 class="h2 job-apply__heading">{{ sectionLabels.apply }}</h2>
          <p class="job-apply__body">
            {{ localeCode === 'th' ? 'กรอกข้อมูลด้านล่างและแนบเรซูเม่ PDF ทีมสรรหาจะติดต่อกลับภายในสองสัปดาห์' : localeCode === 'zh-CN' ? '填写下方信息并附上 PDF 简历。招聘团队将在大约两周内联系您。' : 'Complete the form and attach a PDF résumé. Our team will respond within about two weeks.' }}
          </p>
        </div>

        <div class="job-apply__panel" data-reveal>
          <div v-if="success" class="form-success" role="status">
            <span class="form-success__icon"><AppIcon name="check" :size="22" /></span>
            <h3 class="h3">{{ copy.applicationSuccess }}</h3>
            <p>{{ success }}</p>
            <p v-if="reference" class="mono">REFERENCE {{ reference }}</p>
          </div>

          <form v-else class="form" novalidate @submit.prevent="submit">
            <div class="form-grid">
              <div class="field">
                <label for="job-name">{{ copy.formName }} <span class="req">*</span></label>
                <input id="job-name" v-model="form.name" type="text" class="input" required autocomplete="name" :aria-invalid="!!errors.name">
                <p v-if="errors.name" class="form-error">{{ errors.name }}</p>
              </div>
              <div class="field">
                <label for="job-email">{{ copy.formEmail }} <span class="req">*</span></label>
                <input id="job-email" v-model="form.email" type="email" class="input" required autocomplete="email" :aria-invalid="!!errors.email">
                <p v-if="errors.email" class="form-error">{{ errors.email }}</p>
              </div>
              <div class="field">
                <label for="job-phone">{{ copy.formPhone }}</label>
                <input id="job-phone" v-model="form.phone" type="tel" class="input" autocomplete="tel">
                <p v-if="errors.phone" class="form-error">{{ errors.phone }}</p>
              </div>
              <div class="field">
                <label for="job-portfolio">{{ copy.formPortfolio }}</label>
                <input id="job-portfolio" v-model="form.portfolio_url" type="url" class="input" autocomplete="url">
                <p v-if="errors.portfolio_url" class="form-error">{{ errors.portfolio_url }}</p>
              </div>
              <div class="field field--full">
                <label for="job-cover">{{ copy.formCover }}</label>
                <textarea id="job-cover" v-model="form.cover_letter" class="textarea" rows="5" />
                <p v-if="errors.cover_letter" class="form-error">{{ errors.cover_letter }}</p>
              </div>
              <div class="field field--full">
                <label for="job-resume">{{ copy.formResume }} <span class="req">*</span></label>
                <input id="job-resume" type="file" accept="application/pdf" class="file-input" required @change="onFile">
                <p v-if="errors.resume" class="form-error">{{ errors.resume }}</p>
              </div>
              <label class="checkbox-row field--full">
                <input v-model="form.consent" type="checkbox" required>
                <span>{{ copy.formConsent }}</span>
              </label>
              <p v-if="errors.consent" class="form-error">{{ errors.consent }}</p>
            </div>

            <div class="honeypot" aria-hidden="true">
              <label for="job-website">Website</label>
              <input id="job-website" v-model="form.website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <button type="submit" class="btn" :disabled="sending">
              <span>{{ sending ? copy.submitting : copy.submitApplication }}</span>
              <AppIcon name="arrow-up-right" :size="14" class="btn-icon" />
            </button>
            <p v-if="errors._form" class="form-error form-error--global" role="alert">{{ errors._form }}</p>
          </form>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.job-hero {
  background: var(--ink);
  color: var(--ivory);
  position: relative;
  overflow: hidden;
  padding-block: clamp(3.5rem, 8vw, 7rem);
}

.job-hero__grid {
  position: absolute;
  inset: 0;
  background-image: linear-gradient(to right, rgba(241, 237, 228, 0.055) 1px, transparent 1px);
  background-size: calc(100% / 12) 100%;
}

.job-hero__inner {
  position: relative;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 3rem;
}

.job-hero__title {
  margin-top: 1.25rem;
  font-size: clamp(2.8rem, 6vw, 6rem);
  line-height: 0.94;
  letter-spacing: -0.055em;
  font-weight: 500;
  max-width: 14ch;
}

.job-hero__summary {
  margin-top: 1.5rem;
  max-width: 58ch;
  font-size: clamp(1rem, 1.25vw, 1.15rem);
  line-height: 1.6;
  color: rgba(241, 237, 228, 0.65);
}

.job-hero__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  margin-top: 1.75rem;
  color: rgba(241, 237, 228, 0.45);
  font-size: 0.75rem;
}

.job-hero__apply {
  flex: 0 0 auto;
}

.job-body {
  background: var(--ivory);
  padding-block: clamp(3.5rem, 7vw, 6rem);
}

.job-body__grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 20rem;
  gap: clamp(2rem, 6vw, 6rem);
  align-items: start;
}

.job-content {
  display: flex;
  flex-direction: column;
  gap: clamp(2.5rem, 5vw, 4rem);
}

.job-section > .eyebrow {
  margin-bottom: 1rem;
}

.job-description {
  max-width: 72ch;
}

.job-list {
  list-style: none;
  border-top: 1px solid var(--line);
}

.job-list li {
  position: relative;
  padding: 0.8rem 0 0.8rem 1.5rem;
  border-bottom: 1px solid var(--line);
  font-size: 0.95rem;
  line-height: 1.55;
}

.job-list li::before {
  content: "";
  position: absolute;
  left: 0;
  top: 1.3rem;
  width: 6px;
  height: 1px;
  background: var(--accent);
}

.job-list--columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0 2rem;
}

.job-side {
  position: sticky;
  top: calc(var(--header-h) + 2rem);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.job-side__block {
  border-top: 1px solid var(--line);
  padding-top: 1rem;
}

.job-side__block > .mono {
  color: var(--steel);
  font-size: 0.75rem;
  margin-bottom: 0.75rem;
}

.job-side__row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding-block: 0.55rem;
  border-bottom: 1px solid var(--line);
  font-size: 0.8rem;
  color: var(--steel-dark);
}

.job-side__row span:last-child {
  color: var(--ink);
  text-align: right;
}

.job-apply {
  background: #e7e1d5;
}

.job-apply__grid {
  display: grid;
  grid-template-columns: 1fr 1.1fr;
  gap: clamp(2rem, 6vw, 6rem);
  align-items: start;
}

.job-apply__heading {
  margin-top: 1.4rem;
  max-width: 14ch;
}

.job-apply__body {
  margin-top: 1.5rem;
  max-width: 44ch;
  font-size: 0.95rem;
  line-height: 1.65;
  color: var(--steel-dark);
}

.job-apply__panel {
  background: var(--paper);
  border: 1px solid var(--line);
  padding: clamp(1.5rem, 3vw, 2.5rem);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}

.field--full {
  grid-column: 1 / -1;
}

.req {
  color: var(--accent);
}

.file-input {
  border: 1px dashed var(--line-strong);
  padding: 0.8rem;
  background: var(--ivory);
  font-size: 0.82rem;
}

.honeypot {
  position: absolute;
  left: -10000px;
  width: 1px;
  height: 1px;
  overflow: hidden;
}

.form-success {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: flex-start;
}

.form-success__icon {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border: 1px solid var(--accent);
  color: var(--accent);
}

.form-error--global {
  margin-top: 1rem;
}

@media (max-width: 900px) {
  .job-hero__inner {
    flex-direction: column;
    align-items: flex-start;
  }

  .job-body__grid,
  .job-apply__grid {
    grid-template-columns: 1fr;
  }

  .job-side {
    position: static;
  }

  .job-list--columns {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 560px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
