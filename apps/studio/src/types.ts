export interface StudioSite {
  id: number
  name: string
  slug: string
  domain?: string | null
  default_locale: string
  enabled_locales: string[]
}

export interface StudioUser {
  id: number
  name: string
  email: string
  job_title?: string | null
  phone?: string | null
  locale: string
  avatar_url?: string | null
  initials: string
  is_super_admin: boolean
  roles: Array<{ id: number; name: string; slug: string; level: number; site_id: number | null }>
  permissions: string[]
  sites: StudioSite[]
  current_site_id: number | null
  notifications_unread: number
}

export interface MediaImage {
  id?: number
  url: string
  alt?: string
  caption?: string | null
  width?: number | null
  height?: number | null
}

export interface Block {
  uid: string
  type: string
  props: Record<string, any>
  resolved?: Record<string, any>
  responsive?: {
    desktop: boolean
    tablet: boolean
    mobile: boolean
    overrides?: Record<string, any>
  }
}

export interface Seo {
  title: string | null
  description: string | null
  canonical: string | null
  focus_keyword: string | null
  og_title: string | null
  og_description: string | null
  og_image?: MediaImage | null
  robots: string
  schema_type?: string | null
}

export interface TranslationSummary {
  locale: string
  title: string
  slug: string
  excerpt?: string | null
  updated_at?: string | null
}

export interface PageRow {
  id: number
  title: string
  slug: string
  status: string
  is_home: boolean
  author?: string | null
  updated_at?: string | null
  published_at?: string | null
  scheduled_at?: string | null
  lock_version: number
  locales: Array<{ locale: string; title: string; slug: string }>
  missing_locales: string[]
}

export interface PageDetail {
  id: number
  title: string
  slug: string
  excerpt: string | null
  status: string
  template: string
  is_home: boolean
  parent_id: number | null
  author?: { id: number; name: string; email: string } | null
  featured_media?: MediaImage | null
  published_at?: string | null
  scheduled_at?: string | null
  updated_at?: string | null
  lock_version: number
  locale: string
  is_fallback: boolean
  translations: TranslationSummary[]
  missing_locales: string[]
  blocks: Block[]
  seo: Seo | null
  revisions: Array<{ id: number; version: number; message: string | null; locale: string; user?: string | null; created_at?: string | null }>
  workflow: null | {
    id: number
    status: string
    current_step: number
    steps: Array<{ name: string; permission?: string }>
    submitted_at?: string | null
    approvals: Array<{ step: number; step_name: string; decision: string; note: string | null; user: string | null; decided_at: string | null }>
  }
  capabilities: { edit: boolean; delete: boolean; publish: boolean; approve: boolean }
}

export interface BlockField {
  key: string
  type: string
  label: string
  default?: any
  maxLength?: number
  rows?: number
  min?: number
  max?: number
  maxItems?: number
  options?: Array<{ value: string; label: string }>
  fields?: BlockField[]
}

export interface BlockDefinition {
  type: string
  label: string
  category: string
  icon: string
  description: string
  defaults: Record<string, any>
  fields: BlockField[]
}

export interface MediaAsset {
  id: number
  url: string
  original_name: string
  filename: string
  mime_type: string
  extension: string
  size: number
  width?: number | null
  height?: number | null
  alt?: Record<string, string> | null
  caption?: Record<string, string> | null
  credit?: string | null
  license?: string | null
  source_url?: string | null
  focal_x: number
  focal_y: number
  variants: Array<{ name: string; url: string; width: number | null; height: number | null }>
  usage_count: number
  folder?: { id: number; name: string } | null
  uploaded_by?: string | null
  created_at?: string | null
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface ToastMessage {
  id: number
  title: string
  message?: string
  tone: 'success' | 'error' | 'info'
}
