<script setup lang="ts">
const { localeCode, routeLocale } = useLocale()
const copy = useCopy()

const content = computed(() => ({
  th: {
    eyebrow: 'การเข้าถึง',
    title: 'ความมุ่งมั่นด้านการเข้าถึง',
    intro: 'เว็บไซต์นี้ออกแบบให้ผู้ใช้ทุกคนเข้าถึงเนื้อหาและบริการได้ รวมถึงผู้ใช้โปรแกรมอ่านหน้าจอ ผู้ใช้แป้นพิมพ์ และผู้ใช้ที่ต้องการตัวอักษรขนาดใหญ่',
    statusTitle: 'สถานะความสอดคล้อง',
    status: 'เว็บไซต์นี้ตั้งเป้าที่ระดับ WCAG 2.2 AA และได้รับการตรวจด้วยเครื่องมืออัตโนมัติ (axe) และการทดสอบด้วยแป้นพิมพ์ในทุกการอัปเดตหลัก เรายังไม่ได้รับการรับรองอย่างเป็นทางการจากหน่วยงานภายนอก',
    measuresTitle: 'สิ่งที่เราทำ',
    measures: [
      'ใช้โครงสร้างหัวข้อ ภูมิภาค และลำดับการโฟกัสที่ถูกต้อง',
      'รองรับการนำทางด้วยแป้นพิมพ์ รวมถึงการข้ามไปยังเนื้อหาหลัก',
      'ตรวจคอนทราสต์ของข้อความและขนาดเป้าหมายการสัมผัส',
      'ทดสอบภาษาไทย จีน และอังกฤษด้วยแบบอักษรที่เหมาะสมกับแต่ละภาษา',
      'เคารพการตั้งค่าลดการเคลื่อนไหวของผู้ใช้',
    ],
    limitsTitle: 'ข้อจำกัดที่ทราบ',
    limits: [
      'เอกสาร PDF บางไฟล์อาจไม่ได้ติดแท็กสำหรับโปรแกรมอ่านหน้าจออย่างสมบูรณ์',
      'ลิงก์ภายนอกและเนื้อหาที่ฝังจากบุคคลที่สามอยู่นอกการควบคุมของเรา',
      'เนื้อหาบางส่วนที่เผยแพร่ก่อนการปรับปรุงอาจยังไม่ผ่านการตรวจทั้งหมด',
    ],
    feedbackTitle: 'แจ้งปัญหา',
    feedback: 'หากพบอุปสรรคในการใช้งาน โปรดติดต่อเราเพื่อให้เราช่วยเหลือและแก้ไข',
    contact: 'ติดต่อทีมงาน',
    updated: 'ปรับปรุงล่าสุด',
    date: '27 พฤศจิกายน 2026',
  },
  en: {
    eyebrow: 'Accessibility',
    title: 'Our accessibility commitment',
    intro: 'This website is designed so that everyone can reach its content and services, including screen reader users, keyboard users, and people who need larger text.',
    statusTitle: 'Conformance status',
    status: 'This site targets WCAG 2.2 AA and is checked with automated tooling (axe) plus keyboard testing on every major update. It has not been formally certified by an external auditor.',
    measuresTitle: 'What we do',
    measures: [
      'Correct heading, landmark, and focus order.',
      'Keyboard navigation, including a skip link to the main content.',
      'Text contrast and touch target checks.',
      'Thai, Chinese, and English tested with script-appropriate typefaces.',
      'Respect for the operating system reduced-motion preference.',
    ],
    limitsTitle: 'Known limitations',
    limits: [
      'Some PDF documents may not be fully tagged for screen readers.',
      'External links and third-party embedded content are outside our control.',
      'Older published content may not yet have passed every check.',
    ],
    feedbackTitle: 'Feedback',
    feedback: 'If you meet a barrier, please contact us so we can help and fix it.',
    contact: 'Talk to the team',
    updated: 'Last updated',
    date: '27 November 2026',
  },
  'zh-CN': {
    eyebrow: '无障碍',
    title: '我们的无障碍承诺',
    intro: '本网站旨在让所有人都能获取内容与服务，包括屏幕阅读器用户、键盘用户以及需要更大字号的用户。',
    statusTitle: '符合性状态',
    status: '本网站以 WCAG 2.2 AA 为目标，并在每次重要更新时使用自动化工具（axe）和键盘测试进行检查。目前尚未获得外部机构的正式认证。',
    measuresTitle: '我们采取的措施',
    measures: [
      '正确的标题、地标与焦点顺序。',
      '支持键盘导航，包括跳到主要内容的链接。',
      '检查文字对比度与触摸目标尺寸。',
      '泰语、中文和英语均使用适合各自文字的字体进行测试。',
      '尊重操作系统的减少动态效果设置。',
    ],
    limitsTitle: '已知限制',
    limits: [
      '部分 PDF 文件可能未完整标记，屏幕阅读器体验有限。',
      '外部链接和第三方嵌入内容不在我们的控制范围内。',
      '较早发布的内容可能尚未通过全部检查。',
    ],
    feedbackTitle: '反馈',
    feedback: '如果您遇到使用障碍，请联系我们，以便我们提供帮助并修复问题。',
    contact: '联系团队',
    updated: '最后更新',
    date: '2026 年 11 月 27 日',
  },
})[localeCode.value]!)

const breadcrumbs = computed(() => [
  { label: copy.value.home, url: `/${routeLocale.value}` },
  { label: content.value.eyebrow, url: `/${routeLocale.value}/accessibility` },
])

useSimpleSeo({
  title: `${content.value.title} — Meridian Industrial Group`,
  description: content.value.intro,
  path: `/${routeLocale.value}/accessibility`,
})

useSchema({
  '@context': 'https://schema.org',
  '@type': 'BreadcrumbList',
  itemListElement: breadcrumbs.value.map((item, index) => ({
    '@type': 'ListItem',
    position: index + 1,
    name: item.label,
    item: item.url,
  })),
})
</script>

<template>
  <div>
    <Breadcrumbs :items="breadcrumbs" />

    <header class="access-hero">
      <div class="container access-hero__inner">
        <p class="eyebrow">{{ content.eyebrow }}</p>
        <h1 class="access-hero__title">{{ content.title }}</h1>
        <p class="access-hero__intro">{{ content.intro }}</p>
        <p class="mono access-hero__date">{{ content.updated }} · {{ content.date }}</p>
      </div>
    </header>

    <section class="section access-body">
      <div class="container access-grid">
        <article class="access-block">
          <h2 class="h3">{{ content.statusTitle }}</h2>
          <p>{{ content.status }}</p>
        </article>

        <article class="access-block">
          <h2 class="h3">{{ content.measuresTitle }}</h2>
          <ul>
            <li v-for="item in content.measures" :key="item">{{ item }}</li>
          </ul>
        </article>

        <article class="access-block">
          <h2 class="h3">{{ content.limitsTitle }}</h2>
          <ul>
            <li v-for="item in content.limits" :key="item">{{ item }}</li>
          </ul>
        </article>

        <article class="access-block">
          <h2 class="h3">{{ content.feedbackTitle }}</h2>
          <p>{{ content.feedback }}</p>
          <NuxtLink :to="`/${routeLocale}/contact`" class="text-link">{{ content.contact }}</NuxtLink>
        </article>
      </div>
    </section>
  </div>
</template>

<style scoped>
.access-hero {
  background: var(--ivory);
  padding-block: clamp(3rem, 6vw, 5rem) clamp(2rem, 4vw, 3rem);
}

.access-hero__inner {
  display: grid;
  gap: 1rem;
  max-width: 74ch;
}

.access-hero__title {
  font-size: clamp(2.4rem, 5vw, 4.6rem);
  line-height: 1.04;
  letter-spacing: -0.02em;
  font-weight: 500;
}

.access-hero__intro {
  color: var(--steel-dark);
  font-size: clamp(1rem, 1.2vw, 1.15rem);
  line-height: 1.65;
  max-width: 62ch;
}

.access-hero__date {
  color: var(--steel-dark);
}

.access-body {
  background: var(--ivory);
}

.access-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: clamp(2rem, 4vw, 3.5rem);
}

.access-block {
  border-top: 1px solid var(--line);
  padding-top: 1.5rem;
}

.access-block p,
.access-block li {
  margin-top: 0.85rem;
  color: var(--steel-dark);
  line-height: 1.7;
}

.access-block ul {
  padding-left: 1.15rem;
}

.access-block li {
  margin-top: 0.5rem;
}

.access-block .text-link {
  margin-top: 1.25rem;
}

@media (max-width: 760px) {
  .access-grid {
    grid-template-columns: 1fr;
  }
}
</style>
