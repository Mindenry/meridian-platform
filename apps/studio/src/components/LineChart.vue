<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  series: Array<{ date: string; views: number; visitors?: number }>
  height?: number
  accent?: string
}>(), { height: 220, accent: '#e36d3f' })

const width = 820
const pad = { top: 12, right: 8, bottom: 24, left: 32 }

const max = computed(() => Math.max(1, ...props.series.map((point) => point.views)))
const points = computed(() => {
  const innerW = width - pad.left - pad.right
  const innerH = props.height - pad.top - pad.bottom
  const step = props.series.length > 1 ? innerW / (props.series.length - 1) : innerW
  return props.series.map((point, index) => ({
    ...point,
    x: pad.left + index * step,
    y: pad.top + innerH - (point.views / max.value) * innerH,
  }))
})

const linePath = computed(() => points.value.map((point, index) => `${index === 0 ? 'M' : 'L'}${point.x.toFixed(1)},${point.y.toFixed(1)}`).join(' '))
const areaPath = computed(() => {
  if (!points.value.length) return ''
  const first = points.value[0]
  const last = points.value[points.value.length - 1]
  return `${linePath.value} L${last.x.toFixed(1)},${props.height - pad.bottom} L${first.x.toFixed(1)},${props.height - pad.bottom} Z`
})

const yTicks = computed(() => [0, 0.25, 0.5, 0.75, 1].map((fraction) => ({
  value: Math.round(max.value * fraction),
  y: pad.top + (props.height - pad.top - pad.bottom) * (1 - fraction),
})))

const xLabels = computed(() => {
  if (!points.value.length) return []
  const interval = Math.max(1, Math.ceil(points.value.length / 6))
  return points.value.filter((_, index) => index % interval === 0 || index === points.value.length - 1)
})

const formatDate = (date: string) => {
  const parsed = new Date(date)
  return new Intl.DateTimeFormat('en', { day: 'numeric', month: 'short' }).format(parsed)
}
</script>

<template>
  <div>
    <svg v-if="series.length" class="chart-svg" :viewBox="`0 0 ${width} ${height}`" role="img" aria-label="Traffic over time">
      <g class="chart-grid">
        <line v-for="tick in yTicks" :key="tick.y" :x1="pad.left" :x2="width - pad.right" :y1="tick.y" :y2="tick.y" />
      </g>
      <text v-for="tick in yTicks" :key="`label-${tick.y}`" class="chart-axis" :x="pad.left - 6" :y="tick.y + 3" text-anchor="end">{{ tick.value }}</text>
      <path :d="areaPath" class="chart-area" />
      <path :d="linePath" class="chart-line" :style="{ stroke: accent }" />
      <circle v-for="point in points" :key="point.date" :cx="point.x" :cy="point.y" r="2" fill="#fbfaf7" :stroke="accent" stroke-width="1.4">
        <title>{{ formatDate(point.date) }} — {{ point.views }} views</title>
      </circle>
      <text v-for="point in xLabels" :key="`x-${point.date}`" class="chart-axis" :x="point.x" :y="height - 6" text-anchor="middle">{{ formatDate(point.date) }}</text>
    </svg>
    <p v-else class="muted" style="font-size: 12px;padding:20px 0;text-align:center">No traffic recorded for this period.</p>
  </div>
</template>
