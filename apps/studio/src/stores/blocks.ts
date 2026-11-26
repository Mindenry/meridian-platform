import { ref } from 'vue'
import { api } from '@/api/client'
import type { BlockDefinition } from '@/types'

const definitions = ref<BlockDefinition[]>([])
const loading = ref(false)
const loaded = ref(false)

async function load() {
  if (loaded.value || loading.value) return
  loading.value = true
  try {
    const { data } = await api.get('/block-types')
    definitions.value = data.data
    loaded.value = true
  } finally {
    loading.value = false
  }
}

function definition(type: string): BlockDefinition | undefined {
  return definitions.value.find((item) => item.type === type)
}

export const blocks = { definitions, loading, loaded, load, definition }
