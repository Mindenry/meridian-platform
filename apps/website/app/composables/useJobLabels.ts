const departments: Record<string, Record<string, string>> = {
  'Industrial automation': { th: 'ระบบอัตโนมัติอุตสาหกรรม', 'zh-CN': '工业自动化' },
  'Digital systems': { th: 'ระบบดิจิทัล', 'zh-CN': '数字系统' },
  'Infrastructure': { th: 'โครงสร้างพื้นฐาน', 'zh-CN': '基础设施' },
  'People & culture': { th: 'บุคลากรและวัฒนธรรม', 'zh-CN': '人才与文化' },
}

const locations: Record<string, Record<string, string>> = {
  Bangkok: { th: 'กรุงเทพฯ', 'zh-CN': '曼谷' },
  Rayong: { th: 'ระยอง', 'zh-CN': '罗勇' },
  Singapore: { th: 'สิงคโปร์', 'zh-CN': '新加坡' },
}

const employmentTypes: Record<string, Record<string, string>> = {
  full_time: { en: 'Full time', th: 'เต็มเวลา', 'zh-CN': '全职' },
  part_time: { en: 'Part time', th: 'พาร์ทไทม์', 'zh-CN': '兼职' },
  contract: { en: 'Contract', th: 'สัญญาจ้าง', 'zh-CN': '合同' },
  internship: { en: 'Internship', th: 'ฝึกงาน', 'zh-CN': '实习' },
}

/**
 * Job records keep department, location, and employment type as one stored
 * value shared by every locale. This maps the known values for display and
 * passes anything unknown through unchanged.
 */
export function useJobLabels() {
  const { localeCode } = useLocale()

  const code = computed(() => {
    if (localeCode.value === 'th') return 'th'
    if (localeCode.value === 'zh-CN') return 'zh-CN'
    return 'en'
  })

  const departmentLabel = (value?: string | null) =>
    value ? departments[value]?.[code.value] ?? value : ''

  const locationLabel = (value?: string | null) =>
    value ? locations[value]?.[code.value] ?? value : ''

  const employmentLabel = (value?: string | null) => {
    if (!value) return ''
    const labels = employmentTypes[value]
    return labels?.[code.value] ?? labels?.en ?? value
  }

  return { departmentLabel, locationLabel, employmentLabel }
}
