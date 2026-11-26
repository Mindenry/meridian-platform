export interface LocaleOption {
  code: string
  route: string
  label: string
  short: string
  html: string
  is_default: boolean
}

export interface NavItem {
  label: string
  url: string
  key?: string | null
  target: string
  children: NavItem[]
}

export interface MediaImage {
  id?: number
  url: string
  alt: string
  caption?: string | null
  credit?: string | null
  width?: number | null
  height?: number | null
  focal_x?: number
  focal_y?: number
  variants?: Array<{ name: string; url: string; width: number | null; height: number | null }> | string
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

export interface SeoSchema {
  title: string
  description: string
  canonical: string
  robots: string
  focus_keyword?: string | null
  og: {
    title: string
    description: string
    image?: string | null
    type: string
  }
  schema: Record<string, any>[]
  breadcrumbs?: Breadcrumb[]
}

export interface Breadcrumb {
  label: string
  url?: string | null
}

export interface Alternate {
  locale: string
  hreflang: string
  url: string
}

export interface PageData {
  type: string
  id: number
  template: string
  status: string
  is_home: boolean
  locale: string
  content_locale: string
  is_fallback: boolean
  title: string
  slug: string
  excerpt?: string | null
  url: string
  published_at?: string | null
  updated_at?: string | null
  featured_image?: MediaImage | null
  blocks: Block[]
  seo: SeoSchema
  breadcrumbs: Breadcrumb[]
  alternates: Alternate[]
  preview: boolean
}

export interface SiteBootstrap {
  site: {
    name: string
    legal_name: string
    slug: string
    default_locale: string
    brand: {
      logo_text?: string
      descriptor?: string
      tagline?: string
      accent?: string
      ink?: string
      paper?: string
      steel?: string
    }
    theme?: Record<string, any>
    demo_mode: boolean
  }
  locales: LocaleOption[]
  current_locale: string
  navigation: Record<string, NavItem[]>
  settings: Record<string, any>
  organization: Record<string, any>
}

export interface ArticleSummary {
  id: number
  title: string
  slug: string
  excerpt: string
  type: string
  reading_time: number
  published_at: string
  is_featured: boolean
  category?: { slug: string; name: string } | null
  author?: { name: string; job_title?: string | null } | null
  image?: MediaImage | null
  url: string
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from?: number | null
  to?: number | null
}

export interface ArticleDetail {
  type: 'article'
  id: number
  title: string
  slug: string
  locale: string
  content_locale: string
  is_fallback: boolean
  excerpt: string
  body: string
  toc: Array<{ level: number; text: string; id: string }>
  article_type: string
  reading_time: number
  published_at: string
  updated_at: string
  author?: { name: string; job_title?: string | null; initials: string } | null
  category?: { slug: string; name: string } | null
  tags: Array<{ slug: string; name: string }>
  image?: MediaImage | null
  seo: SeoSchema
  alternates: Alternate[]
  related: ArticleSummary[]
}

export interface CaseStudySummary {
  id: number
  title: string
  slug: string
  summary: string
  year: number
  client_label?: string | null
  industry?: { title: string; slug: string; key: string } | null
  image?: MediaImage | null
  results: Array<{ value: string; label: string; note?: string }>
  url: string
}

export interface CaseStudyDetail {
  type: 'case_study'
  id: number
  title: string
  slug: string
  locale: string
  content_locale: string
  is_fallback: boolean
  summary: string
  challenge: string
  strategy: string
  implementation: string
  results: Array<{ value: string; label: string; note?: string }>
  gallery: Array<{ url: string; alt: string; caption?: string | null }>
  year: number
  client_label?: string | null
  illustrative_note?: string | null
  industry?: { title: string; slug: string; key: string; url: string } | null
  solutions: Array<{ title: string; slug: string; url: string }>
  image?: MediaImage | null
  seo: SeoSchema
  alternates: Alternate[]
  related: CaseStudySummary[]
}

export interface SolutionSummary {
  id: number
  key: string
  title: string
  slug: string
  tagline?: string | null
  overview?: string | null
  icon: string
  image?: MediaImage | null
  url: string
}

export interface SolutionDetail {
  type: 'solution'
  id: number
  key: string
  title: string
  slug: string
  locale: string
  content_locale: string
  is_fallback: boolean
  tagline?: string | null
  overview: string
  problems: Array<{ title: string; description: string }>
  approach: Array<{ title: string; description: string }>
  capabilities: Array<{ title: string; description: string }>
  benefits: Array<{ title: string; description: string }>
  icon: string
  image?: MediaImage | null
  industries: Array<{ title: string; slug: string; url: string; key: string }>
  related_projects: CaseStudySummary[]
  seo: SeoSchema
  breadcrumbs: Breadcrumb[]
  alternates: Alternate[]
}

export interface IndustrySummary {
  id: number
  key: string
  title: string
  slug: string
  tagline?: string | null
  icon: string
  image?: MediaImage | null
  url: string
}

export interface IndustryDetail extends Omit<SolutionDetail, 'type' | 'problems' | 'approach' | 'capabilities' | 'benefits' | 'overview'> {
  type: 'industry'
  overview: string
  challenges: Array<{ title: string; description: string }>
  capabilities: string[]
  stats: Array<{ value: string; label: string; note?: string }>
  solutions: SolutionSummary[]
  related_projects: CaseStudySummary[]
}

export interface JobSummary {
  id: number
  title: string
  slug: string
  summary: string
  department: string
  location: string
  employment_type: string
  posted_at?: string | null
  closes_at?: string | null
  url: string
}

export interface JobDetail {
  type: 'job'
  id: number
  title: string
  slug: string
  locale: string
  content_locale: string
  is_fallback: boolean
  summary: string
  description: string
  responsibilities: string[]
  requirements: string[]
  benefits: string[]
  department: string
  location: string
  employment_type: string
  posted_at?: string | null
  closes_at?: string | null
  seo: SeoSchema
  breadcrumbs: Breadcrumb[]
  alternates: Alternate[]
}

export interface ContactDetails {
  email: string
  careers_email?: string
  phone?: string
  hours?: Record<string, string>
  hq?: { city: string; address: string; label?: Record<string, string> }
  offices?: Array<{ city: string; label?: Record<string, string>; address: string; phone?: string; email?: string }>
  departments?: Array<{ name: Record<string, string>; email: string; description?: Record<string, string> }>
  legal_note?: string
}

export interface FormField {
  name: string
  type: string
  label: Record<string, string> | string
  placeholder?: Record<string, string> | string
  required: boolean
  options?: Array<{ value: string; label: Record<string, string> | string }>
  width?: string
}

export interface FormDefinition {
  key: string
  name: string
  description?: string | null
  fields: FormField[]
  settings?: Record<string, any>
}

export interface DocumentSummary {
  id: number
  category: string
  year: number
  title: string
  description?: string | null
  file_url?: string | null
  file_size?: number | null
  file_type?: string | null
  is_illustrative: boolean
}

export interface SearchResult {
  type: string
  title: string
  excerpt: string
  url: string
  meta?: Record<string, any>
}

export interface SearchGroup {
  type: string
  label: string
  items: SearchResult[]
}
