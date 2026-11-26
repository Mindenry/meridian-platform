<script setup lang="ts">
import { computed } from 'vue'
import type { Block } from '~/types'
import BlocksHero from '~/components/BlocksHero.vue'
import BlocksSplitHero from '~/components/BlocksSplitHero.vue'
import BlocksStatement from '~/components/BlocksStatement.vue'
import BlocksCapabilities from '~/components/BlocksCapabilities.vue'
import BlocksSolutionsGrid from '~/components/BlocksSolutionsGrid.vue'
import BlocksFeaturedProject from '~/components/BlocksFeaturedProject.vue'
import BlocksProjectGrid from '~/components/BlocksProjectGrid.vue'
import BlocksIndustryGrid from '~/components/BlocksIndustryGrid.vue'
import BlocksStatistics from '~/components/BlocksStatistics.vue'
import BlocksTechnology from '~/components/BlocksTechnology.vue'
import BlocksInsights from '~/components/BlocksInsights.vue'
import BlocksCta from '~/components/BlocksCta.vue'
import BlocksRichText from '~/components/BlocksRichText.vue'
import BlocksImage from '~/components/BlocksImage.vue'
import BlocksImageGallery from '~/components/BlocksImageGallery.vue'
import BlocksVideo from '~/components/BlocksVideo.vue'
import BlocksTimeline from '~/components/BlocksTimeline.vue'
import BlocksTeamGrid from '~/components/BlocksTeamGrid.vue'
import BlocksQuote from '~/components/BlocksQuote.vue'
import BlocksMissionValues from '~/components/BlocksMissionValues.vue'
import BlocksContactForm from '~/components/BlocksContactForm.vue'
import BlocksContactCards from '~/components/BlocksContactCards.vue'
import BlocksJobs from '~/components/BlocksJobs.vue'
import BlocksDocumentList from '~/components/BlocksDocumentList.vue'
import BlocksInvestorHighlights from '~/components/BlocksInvestorHighlights.vue'
import BlocksHtml from '~/components/BlocksHtml.vue'

const props = defineProps<{ block: Block }>()

const componentMap: Record<string, any> = {
  hero: BlocksHero,
  split_hero: BlocksSplitHero,
  statement: BlocksStatement,
  capabilities: BlocksCapabilities,
  services_grid: BlocksSolutionsGrid,
  featured_project: BlocksFeaturedProject,
  project_grid: BlocksProjectGrid,
  industry_grid: BlocksIndustryGrid,
  statistics: BlocksStatistics,
  technology_ecosystem: BlocksTechnology,
  latest_insights: BlocksInsights,
  article_list: BlocksInsights,
  cta: BlocksCta,
  rich_text: BlocksRichText,
  image: BlocksImage,
  image_gallery: BlocksImageGallery,
  video: BlocksVideo,
  timeline: BlocksTimeline,
  team_grid: BlocksTeamGrid,
  quote: BlocksQuote,
  mission_values: BlocksMissionValues,
  contact_form: BlocksContactForm,
  contact_cards: BlocksContactCards,
  job_list: BlocksJobs,
  document_list: BlocksDocumentList,
  investor_highlights: BlocksInvestorHighlights,
  html_embed: BlocksHtml,
}

const component = computed(() => componentMap[props.block.type] || BlocksRichText)

const visibilityClass = computed(() => ({
  'block-hide-desktop': props.block.responsive?.desktop === false,
  'block-hide-tablet': props.block.responsive?.tablet === false,
  'block-hide-mobile': props.block.responsive?.mobile === false,
}))
</script>

<template>
  <div :id="`block-${block.uid}`" class="block" :class="visibilityClass" :data-block-type="block.type">
    <component :is="component" :block="block" />
  </div>
</template>

<style scoped>
.block {
  position: relative;
}

@media (min-width: 1024px) {
  .block-hide-desktop { display: none; }
}

@media (min-width: 768px) and (max-width: 1023px) {
  .block-hide-tablet { display: none; }
}

@media (max-width: 767px) {
  .block-hide-mobile { display: none; }
}
</style>
