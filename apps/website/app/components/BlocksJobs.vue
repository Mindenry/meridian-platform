<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const { localeCode } = useLocale()
const p = computed(() => props.block.props)
const items = computed(() => props.block.resolved?.items ?? [])

const labels = computed(() => ({
  open: localeCode.value === 'th' ? 'เปิดรับสมัคร' : localeCode.value === 'zh-CN' ? '招聘中' : 'Open role',
  view: localeCode.value === 'th' ? 'ดูรายละเอียด' : localeCode.value === 'zh-CN' ? '查看详情' : 'View role',
  empty: localeCode.value === 'th' ? 'ยังไม่มีตำแหน่งที่เปิดอยู่' : localeCode.value === 'zh-CN' ? '暂无开放职位' : 'No open roles right now.',
}))

const typeLabel = (type: string) => {
  const map: Record<string, Record<string, string>> = {
    full_time: { en: 'Full time', th: '全职', 'zh-CN': '全职' },
    part_time: { en: 'Part time', th: 'พาร์ทไทม์', 'zh-CN': '兼职' },
    contract: { en: 'Contract', th: 'สัญญาจ้าง', 'zh-CN': '合同' },
    internship: { en: 'Internship', th: 'ฝึกงาน', 'zh-CN': '实习' },
  }
  return map[type]?.[localeCode.value] || map[type]?.en || type
}
</script>

<template>
  <section id="open-roles" class="section jobs" :class="`jobs--${p.theme || 'light'}`">
    <div class="container">
      <div class="jobs__head">
        <div data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="h2 jobs__heading">{{ p.heading }}</h2>
        </div>
      </div>

      <div v-if="items.length" class="jobs__list">
        <NuxtLink v-for="job in items" :key="job.id" :to="job.url" class="job-row" data-reveal>
          <span class="job-row__dept mono">{{ job.department }}</span>
          <span class="job-row__title">{{ job.title }}</span>
          <span class="job-row__meta">{{ job.location }} · {{ typeLabel(job.employment_type) }}</span>
          <span class="job-row__status mono">{{ labels.open }}</span>
          <span class="job-row__arrow"><AppIcon name="arrow-up-right" :size="18" /></span>
        </NuxtLink>
      </div>
      <p v-else class="jobs__empty">{{ labels.empty }}</p>
    </div>
  </section>
</template>

<style scoped>
.jobs {
  background: var(--ivory);
}

.jobs--muted { background: #e7e1d5; }
.jobs--dark { background: var(--ink); color: var(--ivory); }

.jobs__head {
  margin-bottom: clamp(2rem, 4vw, 3rem);
}

.jobs__heading {
  margin-top: 1.4rem;
  max-width: 16ch;
}

.jobs__list {
  border-top: 1px solid var(--line);
}

.jobs--dark .jobs__list { border-color: var(--line-light); }

.job-row {
  display: grid;
  grid-template-columns: 12rem 1.6fr 1fr auto 2rem;
  gap: clamp(1rem, 2vw, 2rem);
  align-items: center;
  padding-block: 1.35rem;
  border-bottom: 1px solid var(--line);
  transition: padding-left 240ms var(--ease), color 180ms ease;
}

.jobs--dark .job-row { border-color: var(--line-light); }

.job-row:hover {
  padding-left: 0.5rem;
  color: var(--accent);
}

.job-row__dept {
  color: var(--steel);
  font-size: 0.75rem;
}

.job-row__title {
  font-size: clamp(1.05rem, 1.4vw, 1.3rem);
  font-weight: 560;
  letter-spacing: -0.025em;
}

.job-row__meta {
  font-size: 0.8rem;
  color: var(--steel-dark);
}

.job-row__status {
  color: var(--accent);
  font-size: 0.75rem;
}

.job-row__arrow {
  justify-self: end;
  color: var(--steel);
}

.jobs__empty {
  color: var(--steel);
  font-size: 0.9rem;
}

@media (max-width: 900px) {
  .job-row {
    grid-template-columns: 1fr auto;
    gap: 0.4rem 1rem;
  }

  .job-row__dept {
    grid-column: 1;
  }

  .job-row__title {
    grid-column: 1 / -1;
  }

  .job-row__meta {
    grid-column: 1;
  }

  .job-row__status {
    display: none;
  }

  .job-row__arrow {
    grid-column: 2;
    grid-row: 1 / span 3;
    align-self: center;
  }
}
</style>
