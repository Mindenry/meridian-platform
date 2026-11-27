<script setup lang="ts">
const { localeCode, routeLocale } = useLocale()
const copy = useCopy()

const content = computed(() => ({
  en: {
    eyebrow: 'Meridian Studio',
    title: 'The CMS behind the website.',
    intro: 'Meridian Studio is the editorial application for the site: pages, a visual block builder, review and scheduling, media, articles, case studies, careers, leads, SEO, analytics, and site-scoped access control.',
    demoTitle: 'How this demo works',
    demoBody: 'This public site is a static snapshot of the Laravel API. Search and language switching work, while form submissions are simulated. The full CMS and API are in the repository; run them with Docker to edit content end to end.',
    repo: 'View the repository',
    features: [
      {
        title: 'Pages, review, and scheduling',
        body: 'Every page moves through draft, review, approval, and publish or schedule. Editors cannot publish without the permission, and the scheduler publishes on time without manual work.',
        image: '/studio/studio-pages.png',
        alt: 'Meridian Studio page list with status, locales, author, and updated date',
      },
      {
        title: 'Pages composed from typed blocks',
        body: 'Pages are built from typed blocks with a schema the server validates. The builder supports undo and redo, autosave, locale tabs, and a preview of the real website.',
        image: '/studio/studio-builder.png',
        alt: 'Meridian Studio visual page builder with block library and canvas',
      },
      {
        title: 'Content operations in one place',
        body: 'Articles, case studies, solutions, industries, jobs, documents, navigation, and media all live in the same CMS. Media uploads generate responsive WebP variants.',
        image: '/studio/studio-page-editor.png',
        alt: 'Meridian Studio page editor with SEO and content tabs',
      },
      {
        title: 'Leads with context',
        body: 'Public enquiries and job applications become leads with source, consent, and a reference. The team can review, update, export, and follow up without leaving the CMS.',
        image: '/studio/studio-leads.png',
        alt: 'Meridian Studio lead inbox with status filters and lead detail',
      },
    ],
    archTitle: 'What runs where',
    arch: [
      { label: 'Laravel API', body: 'Content model, workflow, validation, media, search indexing, forms, analytics, and site-scoped permissions.' },
      { label: 'Vue Studio', body: 'The editorial interface: builder, editors, inbox, SEO health, and team settings.' },
      { label: 'Nuxt website', body: 'The public site in Thai, English, and Chinese, with SSR, sitemaps, RSS, and structured data.' },
    ],
    stackTitle: 'Data and services',
    stack: 'PostgreSQL, Redis, Meilisearch, and S3-compatible object storage. Meilisearch falls back to database search when it is unavailable; media falls back to local disk.',
    ctaTitle: 'Want the full stack?',
    ctaBody: 'The repository includes Docker Compose, demo data, tests, and deployment guides for the API and the Netlify front ends.',
  },
  th: {
    eyebrow: 'Meridian Studio',
    title: 'CMS ที่อยู่เบื้องหลังเว็บไซต์',
    intro: 'Meridian Studio คือแอปพลิเคชันสำหรับทีมเนื้อหา: หน้าเว็บ, visual block builder, ขั้นตอนตรวจและตั้งเวลาเผยแพร่, สื่อ, บทความ, ผลงาน, ตำแหน่งงาน, ลีด, SEO, analytics และการควบคุมสิทธิ์แยกตามเว็บไซต์',
    demoTitle: 'เดโมนี้ทำงานอย่างไร',
    demoBody: 'เว็บสาธารณะนี้เป็น snapshot แบบ static จาก Laravel API การค้นหาและการเปลี่ยนภาษาทำงานจริง ส่วนการส่งฟอร์มเป็นเพียงการจำลอง CMS และ API ฉบับเต็มอยู่ใน repository สามารถรันด้วย Docker เพื่อแก้เนื้อหาได้ครบวงจร',
    repo: 'ดู repository',
    features: [
      {
        title: 'หน้าเว็บ การตรวจ และการตั้งเวลา',
        body: 'ทุกหน้าผ่านสถานะ draft, review, approve และ publish หรือ schedule บรรณาธิการไม่สามารถเผยแพร่ได้หากไม่มีสิทธิ์ และ scheduler เผยแพร่ตามเวลาโดยไม่ต้องทำงาน manual',
        image: '/studio/studio-pages.png',
        alt: 'รายการหน้าเว็บใน Meridian Studio พร้อมสถานะ ภาษา ผู้เขียน และวันที่อัปเดต',
      },
      {
        title: 'หน้าเว็บประกอบจาก block ที่ตรวจสอบได้',
        body: 'หน้าเว็บประกอบจาก block ที่มี schema ตรวจฝั่งเซิร์ฟเวอร์ รองรับ undo/redo, autosave, แท็บภาษา และ preview หน้าเว็บจริง',
        image: '/studio/studio-builder.png',
        alt: 'หน้า visual builder ของ Meridian Studio พร้อมคลัง block และ canvas',
      },
      {
        title: 'งานเนื้อหาอยู่ในที่เดียว',
        body: 'บทความ ผลงาน โซลูชัน อุตสาหกรรม ตำแหน่งงาน เอกสาร navigation และสื่อ อยู่ใน CMS เดียวกัน การอัปโหลดสื่อสร้าง WebP หลายขนาดโดยอัตโนมัติ',
        image: '/studio/studio-page-editor.png',
        alt: 'หน้าแก้ไขเนื้อหาของ Meridian Studio พร้อมแท็บ SEO',
      },
      {
        title: 'ลีดพร้อมบริบท',
        body: 'แบบฟอร์มและใบสมัครงานกลายเป็นลีดที่มีแหล่งที่มา consent และ reference ทีมงานตรวจสอบ อัปเดต ส่งออก และติดตามได้ใน CMS',
        image: '/studio/studio-leads.png',
        alt: 'กล่องลีดของ Meridian Studio พร้อมตัวกรองสถานะ',
      },
    ],
    archTitle: 'อะไรทำงานที่ไหน',
    arch: [
      { label: 'Laravel API', body: 'โมเดลเนื้อหา เวิร์กโฟลว์ validation สื่อ การทำดัชนีค้นหา ฟอร์ม analytics และสิทธิ์แยกตามไซต์' },
      { label: 'Vue Studio', body: 'อินเทอร์เฟซสำหรับทีมเนื้อหา: builder, editors, inbox, SEO health และการตั้งค่าทีม' },
      { label: 'Nuxt website', body: 'เว็บสาธารณะ 3 ภาษา พร้อม SSR, sitemap, RSS และ structured data' },
    ],
    stackTitle: 'ข้อมูลและบริการ',
    stack: 'PostgreSQL, Redis, Meilisearch และ object storage ที่รองรับ S3 หาก Meilisearch ใช้งานไม่ได้จะ fallback ไปค้นจากฐานข้อมูล และสื่อ fallback ไปยังดิสก์ local',
    ctaTitle: 'อยากเห็นทั้งระบบ?',
    ctaBody: 'repository มี Docker Compose, ข้อมูลตัวอย่าง, tests และคู่มือ deploy สำหรับ API และ front end บน Netlify',
  },
  'zh-CN': {
    eyebrow: 'Meridian Studio',
    title: '网站背后的 CMS。',
    intro: 'Meridian Studio 是内容团队的编辑应用：页面、可视化区块构建器、审核与定时发布、媒体、文章、案例、招聘、线索、SEO、分析，以及按站点隔离的权限控制。',
    demoTitle: '这个演示如何工作',
    demoBody: '这个公开网站是 Laravel API 的静态快照。搜索和语言切换可用，表单提交为模拟。完整 CMS 与 API 在代码仓库中，可用 Docker 启动并完整编辑内容。',
    repo: '查看代码仓库',
    features: [
      {
        title: '页面、审核与定时发布',
        body: '每个页面都经过草稿、审核、批准，然后发布或定时发布。没有权限的编辑无法发布，调度器会按时发布，无需人工操作。',
        image: '/studio/studio-pages.png',
        alt: 'Meridian Studio 页面列表，包含状态、语言、作者和更新时间',
      },
      {
        title: '由类型化区块组成的页面',
        body: '页面由带服务端校验的类型化区块组成。构建器支持撤销/重做、自动保存、语言标签和真实网站预览。',
        image: '/studio/studio-builder.png',
        alt: 'Meridian Studio 可视化页面构建器与区块库',
      },
      {
        title: '内容运营集中在一处',
        body: '文章、案例、解决方案、行业、职位、文档、导航和媒体都在同一个 CMS 中。上传媒体会自动生成多尺寸 WebP。',
        image: '/studio/studio-page-editor.png',
        alt: 'Meridian Studio 页面编辑器与 SEO 标签',
      },
      {
        title: '带上下文的线索',
        body: '公开咨询和职位申请会变成带来源、同意状态和参考编号的线索。团队可在 CMS 中查看、更新、导出和跟进。',
        image: '/studio/studio-leads.png',
        alt: 'Meridian Studio 线索收件箱与状态筛选',
      },
    ],
    archTitle: '各部分运行在哪里',
    arch: [
      { label: 'Laravel API', body: '内容模型、工作流、校验、媒体、搜索索引、表单、分析和按站点隔离的权限。' },
      { label: 'Vue Studio', body: '内容团队的编辑界面：构建器、编辑器、收件箱、SEO 健康检查和团队设置。' },
      { label: 'Nuxt website', body: '三语公开网站，包含 SSR、站点地图、RSS 和结构化数据。' },
    ],
    stackTitle: '数据与服务',
    stack: 'PostgreSQL、Redis、Meilisearch 和支持 S3 的对象存储。Meilisearch 不可用时回退到数据库搜索，媒体回退到本地磁盘。',
    ctaTitle: '想运行完整系统？',
    ctaBody: '代码仓库包含 Docker Compose、示例数据、测试，以及 API 与 Netlify 前端的部署指南。',
  },
})[localeCode.value]!)

const breadcrumbs = computed(() => [
  { label: copy.value.home, url: `/${routeLocale.value}` },
  { label: content.value.eyebrow, url: `/${routeLocale.value}/studio` },
])

useSimpleSeo({
  title: `${content.value.eyebrow} — Meridian Industrial Group`,
  description: content.value.intro,
  path: `/${routeLocale.value}/studio`,
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

    <header class="studio-hero">
      <div class="container studio-hero__inner">
        <p class="eyebrow">{{ content.eyebrow }}</p>
        <h1 class="studio-hero__title">{{ content.title }}</h1>
        <p class="studio-hero__intro">{{ content.intro }}</p>
      </div>
    </header>

    <section class="section studio-features">
      <div class="container">
        <article v-for="(feature, index) in content.features" :key="feature.title" class="studio-feature" :class="{ 'studio-feature--reverse': index % 2 === 1 }">
          <div class="studio-feature__copy">
            <p class="mono studio-feature__index">{{ String(index + 1).padStart(2, '0') }}</p>
            <h2 class="h3">{{ feature.title }}</h2>
            <p>{{ feature.body }}</p>
          </div>
          <figure class="studio-feature__media">
            <img :src="feature.image" :alt="feature.alt" width="1440" height="900" loading="lazy" decoding="async">
          </figure>
        </article>
      </div>
    </section>

    <section class="section section-muted studio-arch">
      <div class="container">
        <h2 class="h3">{{ content.archTitle }}</h2>
        <div class="studio-arch__grid">
          <div v-for="item in content.arch" :key="item.label" class="studio-arch__item">
            <p class="mono">{{ item.label }}</p>
            <p>{{ item.body }}</p>
          </div>
        </div>
        <div class="studio-stack">
          <p class="mono">{{ content.stackTitle }}</p>
          <p>{{ content.stack }}</p>
        </div>
      </div>
    </section>

    <section class="section studio-cta">
      <div class="container studio-cta__inner">
        <div>
          <h2 class="h3">{{ content.ctaTitle }}</h2>
          <p>{{ content.demoBody }}</p>
        </div>
        <div class="studio-cta__actions">
          <a href="https://github.com/Mindenry/meridian-platform" target="_blank" rel="noopener" class="btn">
            {{ content.repo }}
            <AppIcon name="external" :size="15" />
          </a>
          <NuxtLink :to="`/${routeLocale}/contact`" class="text-link">{{ copy.discuss }}</NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.studio-hero {
  background: var(--ivory);
  padding-block: clamp(3rem, 6vw, 5rem) clamp(2rem, 4vw, 3rem);
}

.studio-hero__inner {
  display: grid;
  gap: 1rem;
  max-width: 78ch;
}

.studio-hero__title {
  font-size: clamp(2.4rem, 5vw, 4.8rem);
  line-height: 1.04;
  letter-spacing: -0.02em;
  font-weight: 500;
}

.studio-hero__intro {
  color: var(--steel-dark);
  font-size: clamp(1rem, 1.2vw, 1.15rem);
  line-height: 1.7;
  max-width: 68ch;
}

.studio-features {
  background: var(--ivory);
}

.studio-feature {
  display: grid;
  grid-template-columns: 0.8fr 1.2fr;
  gap: clamp(2rem, 4vw, 4rem);
  align-items: center;
  padding-block: clamp(2rem, 4vw, 3.5rem);
  border-top: 1px solid var(--line);
}

.studio-feature:first-child {
  border-top: 0;
}

.studio-feature--reverse .studio-feature__copy {
  order: 2;
}

.studio-feature__index {
  color: var(--accent-text);
  margin-bottom: 0.75rem;
}

.studio-feature__copy h2 {
  margin-bottom: 0.85rem;
}

.studio-feature__copy p:not(.mono) {
  color: var(--steel-dark);
  line-height: 1.7;
  max-width: 48ch;
}

.studio-feature__media {
  margin: 0;
  border: 1px solid var(--line);
  background: var(--paper);
  overflow: hidden;
}

.studio-feature__media img {
  display: block;
  width: 100%;
  height: auto;
}

.studio-arch__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 2rem;
  margin-top: 2rem;
}

.studio-arch__item {
  border-top: 1px solid var(--line);
  padding-top: 1rem;
}

.studio-arch__item p:last-child,
.studio-stack p:last-child {
  margin-top: 0.6rem;
  color: var(--steel-dark);
  line-height: 1.7;
}

.studio-stack {
  margin-top: 2.5rem;
  border-top: 1px solid var(--line);
  padding-top: 1rem;
  max-width: 78ch;
}

.studio-cta {
  background: var(--ivory);
}

.studio-cta__inner {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.studio-cta__inner p {
  margin-top: 0.75rem;
  color: var(--steel-dark);
  line-height: 1.7;
  max-width: 62ch;
}

.studio-cta__actions {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 1rem;
  flex: 0 0 auto;
}

@media (max-width: 900px) {
  .studio-feature {
    grid-template-columns: 1fr;
  }

  .studio-feature--reverse .studio-feature__copy {
    order: 0;
  }

  .studio-arch__grid {
    grid-template-columns: 1fr;
  }

  .studio-cta__inner {
    flex-direction: column;
  }
}
</style>
