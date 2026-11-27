<script setup lang="ts">
import type { SolutionSummary } from '~/types'

const api = useApi()
const { localeCode, routeLocale } = useLocale()
const copy = useCopy()

const copyText = computed(() => ({
  en: {
    eyebrow: 'Solutions',
    title: 'Engineering capability, organised around outcomes.',
    body: 'Six connected practices take an operation from constraint to a system that can be run, measured, and improved.',
    meta: ['AUTOMATION', 'DATA', 'INFRASTRUCTURE', 'INTEGRATION'],
    processEyebrow: 'How we work',
    processTitle: 'No technology without a decision behind it.',
    process: [
      ['01', 'Diagnose', 'We establish the operating constraint, the evidence, and the decision that has to change.'],
      ['02', 'Design', 'Architecture, responsibilities, interfaces, and acceptance criteria are written before build.'],
      ['03', 'Deliver', 'We commission in stages around live operations and test both the standard path and the exception paths.'],
      ['04', 'Transfer', 'Documentation, training, and measurable capability transfer are part of the scope.'],
    ],
    listEyebrow: 'Capabilities',
    listTitle: 'Choose the entry point. Keep the system view.',
    ctaEyebrow: 'Start a conversation',
    ctaHeading: 'Bring us the constraint that has to move.',
    ctaBody: 'A short conversation is usually enough to determine whether there is a sensible first step.',
  },
  th: {
    eyebrow: 'โซลูชัน',
    title: 'ขีดความสามารถทางวิศวกรรมที่จัดเรียงตามผลลัพธ์',
    body: 'หกแนวปฏิบัติที่เชื่อมกัน พาการดำเนินงานจากข้อจำกัดไปสู่ระบบที่เดินได้ วัดได้ และพัฒนาได้',
    meta: ['ระบบอัตโนมัติ', 'ข้อมูล', 'โครงสร้างพื้นฐาน', 'การบูรณาการ'],
    processEyebrow: 'วิธีการทำงาน',
    processTitle: 'ไม่มีเทคโนโลยีใดที่ไม่มีการตัดสินใจรองรับ',
    process: [
      ['01', 'วินิจฉัย', 'เราระบุข้อจำกัดการดำเนินงาน หลักฐาน และการตัดสินใจที่ต้องเปลี่ยน'],
      ['02', 'ออกแบบ', 'สถาปัตยกรรม ความรับผิดชอบ อินเทอร์เฟซ และเกณฑ์ยอมรับถูกเขียนก่อนลงมือ'],
      ['03', 'ส่งมอบ', 'เราทดสอบเป็นระยะรอบการดำเนินงานจริง ทั้งเส้นทางปกติและเส้นทางข้อยกเว้น'],
      ['04', 'ถ่ายทอด', 'เอกสาร การฝึกอบรม และการถ่ายทอดศักยภาพที่วัดได้เป็นส่วนหนึ่งของขอบเขตงาน'],
    ],
    listEyebrow: 'ขีดความสามารถ',
    listTitle: 'เลือกจุดเริ่มต้น โดยมองทั้งระบบ',
    ctaEyebrow: 'เริ่มต้นบทสนทนา',
    ctaHeading: 'นำข้อจำกัดที่ต้องขยับมาให้เรา',
    ctaBody: 'บทสนทนาสั้นๆ มักเพียงพอที่จะพิจารณาว่ามีก้าวแรกที่สมเหตุสมผลหรือไม่',
  },
  'zh-CN': {
    eyebrow: '解决方案',
    title: '围绕成果组织的工程能力。',
    body: '六项互联实践，把运营从约束带到可运行、可衡量、可改进的系统。',
    meta: ['自动化', '数据', '基础设施', '集成'],
    processEyebrow: '我们的方法',
    processTitle: '没有决策支撑的技术没有意义。',
    process: [
      ['01', '诊断', '确认运营约束、证据以及必须改变的决策。'],
      ['02', '设计', '在构建前写清架构、责任、接口与验收标准。'],
      ['03', '交付', '围绕在线运营分阶段调试，既测正常路径，也测异常路径。'],
      ['04', '转移', '文档、培训与可衡量的能力转移属于项目范围。'],
    ],
    listEyebrow: '能力',
    listTitle: '选择切入点，保留系统视角。',
    ctaEyebrow: '开始对话',
    ctaHeading: '把必须改变的约束交给我们。',
    ctaBody: '一次简短沟通通常足以判断是否存在合理的第一步。',
  },
})[localeCode.value]!)

const { data, error } = await useAsyncData(
  `solutions-${routeLocale.value}`,
  () => api.get<{ data: SolutionSummary[] }>('/public/solutions', { query: { locale: localeCode.value } }),
  { watch: [routeLocale] },
)

if (error.value) throw createError({ statusCode: 503, statusMessage: 'Solutions are unavailable.' })

const items = computed(() => data.value?.data ?? [])

useSimpleSeo({
  title: localeCode.value === 'th'
    ? 'โซลูชัน — เมอริเดียน อินดัสเทรียล กรุ๊ป'
    : localeCode.value === 'zh-CN'
      ? '解决方案 — Meridian 工业集团'
      : 'Solutions — Meridian Industrial Group',
  description: copyText.value.body,
  path: `/${routeLocale.value}/solutions`,
})
</script>

<template>
  <div>
    <PageHeader
      :eyebrow="copyText.eyebrow"
      :title="copyText.title"
      :body="copyText.body"
      :meta="copyText.meta"
    />

    <section class="section solution-list">
      <div class="container">
        <div class="solution-list__head">
          <div data-reveal>
            <p class="eyebrow">{{ copyText.listEyebrow }}</p>
            <h2 class="h2">{{ copyText.listTitle }}</h2>
          </div>
        </div>

        <div class="solution-list__rows">
          <NuxtLink
            v-for="(solution, index) in items"
            :key="solution.key"
            :to="solution.url"
            class="solution-block"
            :class="{ 'solution-block--reverse': index % 2 === 1 }"
            data-reveal
          >
            <div class="solution-block__media">
              <AppImage v-if="solution.image" :image="solution.image" ratio="4:3" sizes="(max-width: 900px) 100vw, 44vw" />
              <span class="solution-block__number mono">{{ String(index + 1).padStart(2, '0') }}</span>
            </div>
            <div class="solution-block__body">
              <p class="mono muted">{{ solution.icon }}</p>
              <h3 class="h2 solution-block__title">{{ solution.title }}</h3>
              <p class="solution-block__tagline">{{ solution.tagline }}</p>
              <p v-if="solution.overview" class="solution-block__overview">{{ solution.overview }}</p>
              <span class="text-link">
                {{ copy.explore }}
                <AppIcon name="arrow-up-right" :size="13" />
              </span>
            </div>
          </NuxtLink>
        </div>
      </div>
    </section>

    <section class="section process">
      <div class="container">
        <div class="process__head">
          <div data-reveal>
            <p class="eyebrow">{{ copyText.processEyebrow }}</p>
            <h2 class="h2">{{ copyText.processTitle }}</h2>
          </div>
        </div>
        <div class="process__grid">
          <div v-for="step in copyText.process" :key="step[0]" class="process-step" data-reveal>
            <span class="process-step__number mono">{{ step[0] }}</span>
            <h3 class="process-step__title">{{ step[1] }}</h3>
            <p class="process-step__body">{{ step[2] }}</p>
          </div>
        </div>
      </div>
    </section>

    <SiteCta
      :eyebrow="copyText.ctaEyebrow"
      :heading="copyText.ctaHeading"
      :body="copyText.ctaBody"
      :primary-label="copy.discuss"
      :primary-to="`/${routeLocale}/contact`"
      :secondary-label="copy.allCaseStudies"
      :secondary-to="`/${routeLocale}/case-studies`"
    />
  </div>
</template>

<style scoped>
.solution-list {
  background: var(--ivory);
}

.solution-list__head {
  margin-bottom: clamp(2.5rem, 5vw, 4rem);
}

.solution-list__head h2 {
  margin-top: 1.4rem;
  max-width: 18ch;
}

.solution-list__rows {
  border-top: 1px solid var(--line);
}

.solution-block {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: clamp(2rem, 5vw, 6rem);
  padding-block: clamp(2.5rem, 5vw, 4.5rem);
  border-bottom: 1px solid var(--line);
  align-items: center;
}

.solution-block--reverse .solution-block__media {
  order: 2;
}

.solution-block__media {
  position: relative;
}

.solution-block__number {
  position: absolute;
  top: 0.8rem;
  left: 0.8rem;
  background: var(--ivory);
  color: var(--ink);
  padding: 0.2rem 0.45rem;
  font-size: 0.75rem;
}

.solution-block__body {
  max-width: 54ch;
}

.solution-block__title {
  margin-top: 0.6rem;
  font-size: clamp(2rem, 3.2vw, 3.4rem);
  max-width: 14ch;
}

.solution-block__tagline {
  margin-top: 1rem;
  font-size: 1rem;
  line-height: 1.55;
  color: var(--ink-2);
}

.solution-block__overview {
  margin-top: 1rem;
  font-size: 0.9rem;
  line-height: 1.7;
  color: var(--steel-dark);
}

.solution-block__body .text-link {
  margin-top: 1.5rem;
}

.solution-block:hover .solution-block__title {
  color: var(--accent);
  transition: color 180ms ease;
}

.process {
  background: #e7e1d5;
}

.process__head {
  margin-bottom: clamp(2rem, 4vw, 3rem);
}

.process__head h2 {
  margin-top: 1.4rem;
  max-width: 17ch;
}

.process__grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: clamp(0.75rem, 1.5vw, 1.1rem);
}

.process-step {
  padding: clamp(1.5rem, 2.2vw, 1.9rem);
  border: 1px solid var(--glass-border);
  border-radius: var(--radius-md);
  background: rgba(255, 255, 255, 0.55);
  box-shadow: var(--shadow-sm);
  min-height: 260px;
  display: flex;
  flex-direction: column;
  transition: background 220ms ease, box-shadow 240ms var(--ease);
}

.process-step:hover {
  background: rgba(255, 255, 255, 0.84);
  box-shadow: var(--shadow-md);
}

.process-step__number {
  color: var(--accent);
  font-size: 0.75rem;
}

.process-step__title {
  margin-top: auto;
  font-size: clamp(1.3rem, 1.7vw, 1.6rem);
  font-weight: 560;
  letter-spacing: -0.025em;
}

.process-step__body {
  margin-top: 0.7rem;
  font-size: 0.9rem;
  line-height: 1.6;
  color: var(--steel-dark);
}

@media (max-width: 980px) {
  .solution-block {
    grid-template-columns: 1fr;
  }

  .solution-block--reverse .solution-block__media {
    order: 0;
  }

  .process__grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 560px) {
  .process__grid {
    grid-template-columns: 1fr;
  }
}
</style>
