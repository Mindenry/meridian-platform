<script setup lang="ts">
import { computed } from 'vue'
import type { Block } from '@/types'

const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props || {})
const items = computed<any[]>(() => Array.isArray(p.value.items) ? p.value.items : [])
const image = computed<any>(() => p.value.image || null)
const linkLabel = (link: any) => link?.label || 'Call to action'

function text(value: any): string {
  if (!value || typeof value !== 'string') return ''
  return value.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
}
</script>

<template>
  <div class="bc" :class="`bc--${block.type}`">
    <template v-if="block.type === 'hero'">
      <div class="bc-hero" :class="`bc-hero--${p.theme || 'dark'}`">
        <div class="bc-hero__text">
          <span v-if="p.eyebrow" class="bc-eyebrow">{{ p.eyebrow }}</span>
          <h1>{{ p.headline || 'Hero headline' }}</h1>
          <p v-if="p.accent_line" class="bc-accent">{{ p.accent_line }}</p>
          <p v-if="p.body" class="bc-body">{{ p.body }}</p>
          <div class="bc-actions">
            <span v-if="p.primary_cta" class="bc-btn">{{ linkLabel(p.primary_cta) }}</span>
            <span v-if="p.secondary_cta" class="bc-btn bc-btn--outline">{{ linkLabel(p.secondary_cta) }}</span>
          </div>
        </div>
        <div v-if="image?.url" class="bc-hero__media"><img :src="image.url" :alt="image.alt || ''"></div>
      </div>
    </template>

    <template v-else-if="block.type === 'split_hero'">
      <div class="bc-split" :class="{ 'bc-split--reverse': p.reverse }">
        <div>
          <span v-if="p.eyebrow" class="bc-eyebrow">{{ p.eyebrow }}</span>
          <h1 class="bc-h1">{{ p.headline || 'Page headline' }}</h1>
          <p v-if="p.body" class="bc-body">{{ p.body }}</p>
          <span v-if="p.primary_cta" class="bc-btn">{{ linkLabel(p.primary_cta) }}</span>
        </div>
        <div v-if="image?.url" class="bc-media"><img :src="image.url" :alt="image.alt || ''"></div>
      </div>
    </template>

    <template v-else-if="block.type === 'statement'">
      <div class="bc-statement">
        <span v-if="p.eyebrow" class="bc-eyebrow">{{ p.eyebrow }}</span>
        <h2>{{ p.heading || 'Positioning statement' }}</h2>
        <div class="bc-two">
          <p class="bc-body">{{ p.body }}</p>
          <p class="bc-aside">{{ p.aside }}</p>
        </div>
      </div>
    </template>

    <template v-else-if="block.type === 'capabilities' || block.type === 'services_grid'">
      <div class="bc-section">
        <span v-if="p.eyebrow" class="bc-eyebrow">{{ p.eyebrow }}</span>
        <h2>{{ p.heading }}</h2>
        <div class="bc-rows">
          <div v-for="(item, index) in items" :key="index" class="bc-row">
            <span class="bc-index">{{ item.number || String(index + 1).padStart(2, '0') }}</span>
            <strong>{{ item.title }}</strong>
            <span class="bc-muted">{{ item.description || item.tagline }}</span>
          </div>
          <div v-if="!items.length && block.type === 'services_grid'" class="bc-resolved-note">
            Solution records are resolved from the CMS when the page is rendered publicly.
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="block.type === 'featured_project'">
      <div class="bc-project-hero">
        <div>
          <span class="bc-eyebrow">{{ p.eyebrow || 'Selected work' }}</span>
          <h2>{{ p.heading }}</h2>
          <p class="bc-muted">Project “{{ p.project_slug || '—' }}” and its verified results are resolved from the case study collection.</p>
        </div>
        <div class="bc-project-hero__media"><span>PROJECT IMAGE</span></div>
      </div>
    </template>

    <template v-else-if="['project_grid','industry_grid','latest_insights','article_list','team_grid','job_list','document_list'].includes(block.type)">
      <div class="bc-section">
        <span v-if="p.eyebrow" class="bc-eyebrow">{{ p.eyebrow }}</span>
        <h2>{{ p.heading || block.type.replace(/_/g, ' ') }}</h2>
        <p v-if="p.intro" class="bc-body">{{ p.intro }}</p>
        <div class="bc-placeholder-grid">
          <div v-for="i in 3" :key="i" class="bc-placeholder-card">
            <div class="bc-placeholder-card__media" />
            <span class="bc-placeholder-card__line" />
            <span class="bc-placeholder-card__line bc-placeholder-card__line--short" />
          </div>
        </div>
        <p class="bc-resolved">Resolved from the CMS at render time.</p>
      </div>
    </template>

    <template v-else-if="block.type === 'statistics'">
      <div class="bc-section">
        <span class="bc-eyebrow">{{ p.eyebrow }}</span>
        <h2>{{ p.heading }}</h2>
        <div class="bc-stats">
          <div v-for="(item, index) in items" :key="index" class="bc-stat">
            <strong>{{ item.value }}</strong>
            <span>{{ item.label }}</span>
          </div>
        </div>
        <p v-if="p.disclaimer" class="bc-disclaimer">{{ p.disclaimer }}</p>
      </div>
    </template>

    <template v-else-if="block.type === 'technology_ecosystem'">
      <div class="bc-section">
        <span class="bc-eyebrow">{{ p.eyebrow }}</span>
        <h2>{{ p.heading }}</h2>
        <div class="bc-tech-grid">
          <div v-for="(item, index) in items" :key="index" class="bc-tech">
            <span>{{ item.name }}</span><span class="bc-mono">{{ item.category }}</span>
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="block.type === 'cta'">
      <div class="bc-cta">
        <span class="bc-eyebrow">{{ p.eyebrow }}</span>
        <h2>{{ p.heading }}</h2>
        <p class="bc-body">{{ p.body }}</p>
        <div class="bc-actions">
          <span v-if="p.primary_cta" class="bc-btn bc-btn--light">{{ linkLabel(p.primary_cta) }}</span>
          <span v-if="p.secondary_cta" class="bc-btn bc-btn--outline">{{ linkLabel(p.secondary_cta) }}</span>
        </div>
      </div>
    </template>

    <template v-else-if="block.type === 'rich_text'">
      <div class="bc-section bc-rich">
        <span v-if="p.eyebrow" class="bc-eyebrow">{{ p.eyebrow }}</span>
        <h2 v-if="p.heading">{{ p.heading }}</h2>
        <p class="bc-body bc-rich__body">{{ text(p.body) || 'Rich text content will appear here.' }}</p>
      </div>
    </template>

    <template v-else-if="block.type === 'image'">
      <figure class="bc-image">
        <img v-if="image?.url" :src="image.url" :alt="image.alt || ''">
        <div v-else class="bc-image__empty">Image block — choose media in the inspector</div>
        <figcaption v-if="p.caption">{{ p.caption }}</figcaption>
      </figure>
    </template>

    <template v-else-if="block.type === 'image_gallery'">
      <div class="bc-gallery">
        <div v-for="(item, index) in items" :key="index" class="bc-gallery__item">
          <img v-if="item.image?.url" :src="item.image.url" :alt="item.image.alt || ''">
          <div v-else class="bc-image__empty" />
          <span v-if="item.caption" class="bc-caption">{{ item.caption }}</span>
        </div>
      </div>
    </template>

    <template v-else-if="block.type === 'video'">
      <div class="bc-video">
        <div class="bc-video__player"><span>VIDEO / {{ p.url ? p.url.slice(0, 50) : 'No URL set' }}</span></div>
        <p v-if="p.caption" class="bc-caption">{{ p.caption }}</p>
      </div>
    </template>

    <template v-else-if="block.type === 'timeline'">
      <div class="bc-section">
        <span class="bc-eyebrow">{{ p.eyebrow }}</span>
        <h2>{{ p.heading }}</h2>
        <div class="bc-timeline">
          <div v-for="(item, index) in items" :key="index" class="bc-timeline__row">
            <span class="bc-mono">{{ item.year }}</span>
            <div><strong>{{ item.title }}</strong><p class="bc-muted">{{ item.description }}</p></div>
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="block.type === 'quote'">
      <div class="bc-quote">
        <p>“{{ p.quote }}”</p>
        <span v-if="p.attribution" class="bc-mon">{{ p.attribution }}</span>
      </div>
    </template>

    <template v-else-if="block.type === 'mission_values'">
      <div class="bc-section">
        <span class="bc-eyebrow">{{ p.eyebrow }}</span>
        <h2>{{ p.heading }}</h2>
        <p class="bc-body">{{ p.body }}</p>
        <div class="bc-values">
          <div v-for="(item, index) in items" :key="index" class="bc-value">
            <span class="bc-index">{{ String(index + 1).padStart(2, '0') }}</span>
            <div><strong>{{ item.title }}</strong><p class="bc-muted">{{ item.description }}</p></div>
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="block.type === 'contact_form'">
      <div class="bc-form">
        <div>
          <span class="bc-eyebrow">{{ p.eyebrow }}</span>
          <h2>{{ p.heading }}</h2>
          <p class="bc-body">{{ p.body }}</p>
        </div>
        <div class="bc-form__fields">
          <span v-for="i in 5" :key="i" class="bc-form__field" />
          <span class="bc-btn">Submit enquiry</span>
        </div>
      </div>
    </template>

    <template v-else-if="block.type === 'contact_cards'">
      <div class="bc-section">
        <span class="bc-eyebrow">{{ p.eyebrow }}</span>
        <h2>{{ p.heading }}</h2>
        <div class="bc-offices">
          <div v-for="(item, index) in items" :key="index" class="bc-office">
            <strong>{{ item.city }}</strong>
            <span class="bc-muted">{{ item.address }}</span>
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="block.type === 'investor_highlights'">
      <div class="bc-section">
        <span class="bc-eyebrow">{{ p.eyebrow }}</span>
        <h2>{{ p.heading }}</h2>
        <p class="bc-disclaimer">{{ p.note }}</p>
        <div class="bc-placeholder-grid">
          <div v-for="i in 3" :key="i" class="bc-placeholder-card bc-placeholder-card--short">
            <span class="bc-placeholder-card__line" />
            <span class="bc-placeholder-card__line bc-placeholder-card__line--short" />
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="bc-section">
        <span class="bc-eyebrow">{{ block.type.replace(/_/g, ' ') }}</span>
        <h2>{{ p.heading || p.title || 'Block content' }}</h2>
        <p v-if="p.html" class="bc-body">{{ text(p.html) }}</p>
        <pre v-else class="bc-json">{{ JSON.stringify(p, null, 2).slice(0, 340) }}</pre>
      </div>
    </template>
  </div>
</template>

<style scoped>
.bc { font-family: var(--font-sans); }
.bc-eyebrow { font-family: var(--font-mono); font-size: 8px; letter-spacing: .1em; text-transform: uppercase; color: #89939d; display: block; margin-bottom: 8px; }
.bc h1 { font-size: 34px; line-height: .96; letter-spacing: -.045em; font-weight: 500; margin: 0; max-width: 15ch; }
.bc h2 { font-size: 26px; line-height: 1.02; letter-spacing: -.035em; font-weight: 500; margin: 0 0 12px; max-width: 18ch; }
.bc-body { font-size: 12px; line-height: 1.6; color: #4c5a66; margin: 8px 0 0; max-width: 60ch; }
.bc-muted { font-size: 12px; color: #74808a; line-height: 1.45; }
.bc-mono { font-family: var(--font-mono); font-size: 8px; color: #89939d; text-transform: uppercase; }
.bc-index { font-family: var(--font-mono); font-size: 8px; color: #89939d; }
.bc-accent { font-family: var(--font-mono); font-size: 8px; text-transform: uppercase; color: #e36d3f; margin-top: 10px; }
.bc-actions { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 14px; }
.bc-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 12px; background: #101d2b; color: #f5f3ee; font-family: var(--font-mono); font-size: 8px; text-transform: uppercase; border: 1px solid #101d2b; }
.bc-btn--outline { background: transparent; color: #101d2b; }
.bc-btn--light { background: #f5f3ee; color: #101d2b; border-color: #f5f3ee; }
.bc-hero { display: grid; grid-template-columns: 1.15fr 1fr; background: #101d2b; color: #f5f3ee; min-height: 340px; }
.bc-hero__text { padding: 38px; align-self: center; }
.bc-hero .bc-eyebrow { color: rgba(245,243,238,.45); }
.bc-hero .bc-body { color: rgba(245,243,238,.6); }
.bc-hero .bc-btn { background: #f5f3ee; color: #101d2b; border-color: #f5f3ee; }
.bc-hero .bc-btn--outline { background: transparent; color: #f5f3ee; border-color: rgba(245,243,238,.35); }
.bc-hero__media img { width: 100%; height: 100%; object-fit: cover; filter: grayscale(1); min-height: 340px; }
.bc-split { display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; padding: 38px; align-items: center; background: #101d2b; color: #f5f3ee; }
.bc-split .bc-body { color: rgba(245,243,238,.6); }
.bc-split .bc-btn { background: #f5f3ee; color: #101d2b; border-color: #f5f3ee; }
.bc-split--reverse > :first-child { order: 2; }
.bc-media img { width: 100%; aspect-ratio: 4/3; object-fit: cover; filter: grayscale(1); }
.bc-statement { padding: 38px; display: grid; gap: 18px; background: #fbfaf7; }
.bc-statement h2 { font-size: 31px; }
.bc-two { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; }
.bc-aside { font-size: 12px; color: #74808a; border-top: 1px solid #e0dcd4; padding-top: 10px; margin: 0; }
.bc-section { padding: 32px 38px; background: #fbfaf7; }
.bc-rows { border-top: 1px solid #e0dcd4; margin-top: 14px; }
.bc-row { display: grid; grid-template-columns: 28px 1fr 1.4fr; gap: 12px; padding: 10px 0; border-bottom: 1px solid #e0dcd4; align-items: baseline; }
.bc-row strong { font-size: 12px; font-weight: 600; }
.bc-project-hero { display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; padding: 38px; background: #101d2b; color: #f5f3ee; align-items: center; }
.bc-project-hero .bc-muted { color: rgba(245,243,238,.55); }
.bc-project-hero__media { aspect-ratio: 4/3; background: #17283a; display: grid; place-items: center; color: rgba(245,243,238,.35); font-family: var(--font-mono); font-size: 8px; }
.bc-placeholder-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 14px; }
.bc-placeholder-card { border: 1px solid #e0dcd4; padding: 8px; background: #fff; }
.bc-placeholder-card__media { aspect-ratio: 3/2; background: #e8e4dc; margin-bottom: 7px; }
.bc-placeholder-card__line { display: block; height: 5px; background: #e0dcd4; margin-top: 5px; }
.bc-placeholder-card__line--short { width: 60%; }
.bc-placeholder-card--short { min-height: 80px; }
.bc-resolved, .bc-resolved-note { margin-top: 10px; font-family: var(--font-mono); font-size: 7px; text-transform: uppercase; color: #98a1a9; }
.bc-stats { display: grid; grid-template-columns: repeat(4, 1fr); border-top: 1px solid #e0dcd4; margin-top: 12px; }
.bc-stat { padding: 12px 10px 12px 0; border-right: 1px solid #e0dcd4; }
.bc-stat strong { display: block; font-size: 26px; letter-spacing: -.04em; font-weight: 500; }
.bc-stat span { font-size: 12px; color: #74808a; }
.bc-disclaimer { margin-top: 10px; font-size: 8px; color: #98a1a9; line-height: 1.5; }
.bc-tech-grid { display: grid; grid-template-columns: repeat(3, 1fr); border-top: 1px solid #e0dcd4; border-left: 1px solid #e0dcd4; margin-top: 12px; }
.bc-tech { display: flex; justify-content: space-between; gap: 8px; align-items: baseline; padding: 10px; border-right: 1px solid #e0dcd4; border-bottom: 1px solid #e0dcd4; font-size: 12px; }
.bc-cta { background: #101d2b; color: #f5f3ee; padding: 44px 38px; }
.bc-cta .bc-body { color: rgba(245,243,238,.6); }
.bc-rich__body { white-space: pre-line; }
.bc-image { margin: 0; padding: 30px 38px; background: #fbfaf7; }
.bc-image img { width: 100%; aspect-ratio: 16/9; object-fit: cover; display: block; }
.bc-image__empty { aspect-ratio: 16/9; background: #e8e4dc; display: grid; place-items: center; font-size: 12px; color: #89939d; }
.bc-image figcaption, .bc-caption { margin-top: 6px; font-size: 12px; color: #74808a; }
.bc-gallery { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding: 30px 38px; background: #fbfaf7; }
.bc-gallery__item img, .bc-gallery__item .bc-image__empty { aspect-ratio: 4/3; object-fit: cover; width: 100%; }
.bc-video { padding: 30px 38px; background: #fbfaf7; }
.bc-video__player { aspect-ratio: 16/9; background: #101d2b; display: grid; place-items: center; color: rgba(245,243,238,.4); font-family: var(--font-mono); font-size: 8px; }
.bc-timeline { border-top: 1px solid #e0dcd4; margin-top: 12px; }
.bc-timeline__row { display: grid; grid-template-columns: 45px 1fr; gap: 12px; padding: 9px 0; border-bottom: 1px solid #e0dcd4; }
.bc-timeline__row strong { font-size: 12px; }
.bc-quote { background: #101d2b; color: #f5f3ee; padding: 44px 38px; text-align: center; }
.bc-quote p { font-size: 25px; line-height: 1.15; letter-spacing: -.03em; margin: 0 auto; max-width: 26ch; }
.bc-quote .bc-mon { display: block; margin-top: 14px; font-size: 12px; color: rgba(245,243,238,.5); }
.bc-values { margin-top: 14px; border-top: 1px solid #e0dcd4; }
.bc-value { display: grid; grid-template-columns: 28px 1fr; gap: 10px; padding: 9px 0; border-bottom: 1px solid #e0dcd4; }
.bc-value strong { font-size: 12px; }
.bc-form { display: grid; grid-template-columns: .9fr 1fr; gap: 30px; padding: 38px; background: #fbfaf7; }
.bc-form__fields { display: flex; flex-direction: column; gap: 8px; }
.bc-form__field { height: 26px; border: 1px solid #e0dcd4; background: #fff; }
.bc-form__fields .bc-btn { align-self: flex-start; margin-top: 5px; }
.bc-offices { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 12px; }
.bc-office { border-top: 1px solid #e0dcd4; padding-top: 8px; }
.bc-office strong { display: block; font-size: 14px; margin-bottom: 3px; }
.bc-json { background: #f2f0eb; padding: 10px; font-size: 8px; overflow: hidden; color: #74808a; max-height: 120px; }
@media (max-width: 800px) {
  .bc-hero, .bc-split, .bc-project-hero, .bc-form { grid-template-columns: 1fr; }
  .bc-split--reverse > :first-child { order: 0; }
  .bc-placeholder-grid, .bc-tech-grid, .bc-offices { grid-template-columns: 1fr 1fr; }
  .bc-stats { grid-template-columns: 1fr 1fr; }
}
</style>
