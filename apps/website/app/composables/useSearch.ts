import type { SearchGroup } from '~/types'

export function useSearch() {
  const isOpen = useState('search-open', () => false)
  const query = useState('search-query', () => '')
  const groups = useState<SearchGroup[]>('search-groups', () => [])
  const loading = useState('search-loading', () => false)
  const error = useState<string | null>('search-error', () => null)

  const open = () => {
    isOpen.value = true
  }

  const close = () => {
    isOpen.value = false
    query.value = ''
    groups.value = []
    error.value = null
  }

  const toggle = () => {
    isOpen.value = !isOpen.value
  }

  return { isOpen, query, groups, loading, error, open, close, toggle }
}
