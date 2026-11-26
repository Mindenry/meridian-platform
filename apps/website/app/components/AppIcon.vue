<script setup lang="ts">
withDefaults(defineProps<{ name: string; size?: number }>(), { size: 18 })

const paths: Record<string, string> = {
  'arrow-up-right': 'M6 18 18 6M8 6h10v10',
  'arrow-right': 'M4 12h16m-6-6 6 6-6 6',
  'arrow-down': 'M12 4v16m-6-6 6 6 6-6',
  search: 'm20 20-4.5-4.5M18 11a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z',
  menu: 'M4 7h16M4 12h16M4 17h16',
  close: 'm6 6 12 12M18 6 6 18',
  plus: 'M12 5v14M5 12h14',
  minus: 'M5 12h14',
  'chevron-down': 'm6 9 6 6 6-6',
  'chevron-right': 'm9 6 6 6-6 6',
  check: 'm5 13 4 4L19 7',
  download: 'M12 3v12m-5-5 5 5 5-5M4 21h16',
  play: 'm8 5 11 7-11 7V5Z',
  quote: 'M9 7H5v6h4c0 3-1 5-4 6M19 7h-4v6h4c0 3-1 5-4 6',
  mail: 'M3 6h18v12H3V6Zm0 1 9 7 9-7',
  phone: 'M8 3H5a2 2 0 0 0-2 2c0 8.8 7.2 16 16 16a2 2 0 0 0 2-2v-3l-5-2-2 2a14 14 0 0 1-6-6l2-2-2-5Z',
  pin: 'M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Zm0-9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z',
  external: 'M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5',
  globe: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-9-9h18M12 3c2.5 2.4 4 5.6 4 9s-1.5 6.6-4 9c-2.5-2.4-4-5.6-4-9s1.5-6.6 4-9Z',
  filter: 'M4 5h16l-6 7v6l-4 2v-8L4 5Z',
  article: 'M5 3h11l3 3v15H5V3Zm11 0v4h4M8 11h8M8 15h8M8 7h4',
  circuit: 'M6 6h4v4H6V6Zm8 8h4v4h-4v-4Zm-4-8h4m-2 0v4m0 0v4m2 0h2m-8-2v2',
  factory: 'M3 21V9l5 3V9l5 3V5h8v16H3Zm4-4h2m4 0h2m4 0h2',
  nodes: 'M5 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm14 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM12 22a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM6.5 5.5l4.5 12m8-12-4.5 12M7 4h10',
  shield: 'M12 3 5 6v5c0 4.4 3 8.4 7 10 4-1.6 7-5.6 7-10V6l-7-3Z',
  layers: 'm12 3 9 5-9 5-9-5 9-5Zm-9 9 9 5 9-5M3 16l9 5 9-5',
}
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path :d="paths[name] || paths['arrow-up-right']" />
  </svg>
</template>
