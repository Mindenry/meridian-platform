<script setup lang="ts">
const { localeCode, routeLocale } = useLocale()

const pageCopy = computed(() => ({
  en: {
    eyebrow: 'Credits',
    title: 'Image and content credits.',
    body: 'Meridian is a fictional brand. Photography is sourced from Wikimedia Commons under the licences listed below. Interface design, copy, and code are original portfolio work.',
    note: 'Images are colour-graded in the browser. No image is presented as a real Meridian facility or client project.',
  },
  th: {
    eyebrow: 'เครดิต',
    title: 'เครดิตภาพและเนื้อหา',
    body: 'เมอริเดียนเป็นแบรนด์สมมติ ภาพถ่ายมาจาก Wikimedia Commons ภายใต้สัญญาอนุญาตที่ระบุด้านล่าง การออกแบบอินเทอร์เฟซ ข้อความ และโค้ดเป็นผลงานพอร์ตโฟลิโอต้นฉบับ',
    note: 'ภาพถูกปรับโทนในเบราว์เซอร์ ไม่มีภาพใดถูกนำเสนอเป็นโรงงานหรือโครงการลูกค้าจริงของเมอริเดียน',
  },
  'zh-CN': {
    eyebrow: '致谢',
    title: '图片与内容来源。',
    body: 'Meridian 是虚构品牌。摄影素材来自 Wikimedia Commons，许可协议见下方。界面设计、文案与代码为原创作品集内容。',
    note: '图片在浏览器中进行色彩处理，不表示 Meridian 的真实设施或客户项目。',
  },
})[localeCode.value]!)

const credits = [
  { image: 'hero-facility', title: 'Seaforth Container Terminal 2022', creator: 'Niels Johannes', license: 'CC BY-SA 4.0', url: 'https://commons.wikimedia.org/wiki/File:Seaforth_Container_Terminal_2022.jpg' },
  { image: 'manufacturing', title: 'Car factory assembly line, Opel Gliwice', creator: 'Marek Slusarczyk (Tupungato)', license: 'CC BY 3.0', url: 'https://commons.wikimedia.org/wiki/File:001_Car_factory_assembly_line_-_Opel_factory_in_Gliwice,_Poland.jpg' },
  { image: 'automation', title: 'UR16e robot arm', creator: 'Auledas', license: 'CC BY-SA 4.0', url: 'https://commons.wikimedia.org/wiki/File:UR16e_robot_arm.png' },
  { image: 'infrastructure', title: 'Container crane, Rotterdam', creator: 'Guilhem Vellut', license: 'CC BY 2.0', url: 'https://commons.wikimedia.org/wiki/File:Container_crane_@_Container_terminal_@_Harbour_Tour_@_Spido_@_Rotterdam_(30530491716).jpg' },
  { image: 'logistics', title: 'FEMA Caribbean distribution centre', creator: 'FEMA / DHS', license: 'Public domain', url: 'https://commons.wikimedia.org/wiki/File:Emergency_Planning_and_Security_%5E_Technological_-_Caguas,_Puerto_Rico,_May_29,_2012_--_FEMA%27s_Logistics_Management_Division_open_the_agency%27s_new,_strategically-located_Caribbean_Di_-_DPLA_-_2587281d6d4d9ff5388ca88f17688f95.jpg' },
  { image: 'energy', title: 'Electrical substation, Vizianagaram', creator: 'Saiphani02', license: 'CC BY 4.0', url: 'https://commons.wikimedia.org/wiki/File:Electrical_substation_of_railways_in_Vizianagaram.jpg' },
  { image: 'precision', title: 'Deckel Maho DMU 50 machining centre', creator: 'Wikimedia Commons contributor', license: 'CC BY-SA 2.0', url: 'https://commons.wikimedia.org/wiki/File:DeckelMaho-DMU50e-MachiningCenter.jpg' },
]

useSimpleSeo({
  title: localeCode.value === 'th' ? 'เครดิต — เมอริเดียน' : localeCode.value === 'zh-CN' ? '致谢 — Meridian' : 'Credits — Meridian Industrial Group',
  description: pageCopy.value.body,
  path: `/${routeLocale.value}/credits`,
})
</script>

<template>
  <div>
    <PageHeader :eyebrow="pageCopy.eyebrow" :title="pageCopy.title" :body="pageCopy.body" />

    <section class="section credits">
      <div class="container">
        <div class="credits__list">
          <a v-for="credit in credits" :key="credit.image" :href="credit.url" target="_blank" rel="noopener" class="credit-row">
            <span class="credit-row__name mono">{{ credit.image }}</span>
            <span class="credit-row__title">{{ credit.title }}</span>
            <span class="credit-row__creator">{{ credit.creator }}</span>
            <span class="credit-row__license mono">{{ credit.license }}</span>
            <AppIcon name="external" :size="15" />
          </a>
        </div>

        <div class="credits__note">
          <span class="mono">NOTE</span>
          <p>{{ pageCopy.note }}</p>
        </div>

        <div class="credits__legal">
          <p>{{ localeCode === 'th' ? 'สัญญาอนุญาต CC BY และ CC BY-SA กำหนดให้ระบุผู้สร้างและลิงก์สัญญาอนุญาตเมื่อนำภาพไปใช้ต่อ' : localeCode === 'zh-CN' ? 'CC BY 与 CC BY-SA 许可要求在使用图片时注明作者与许可链接。' : 'CC BY and CC BY-SA licences require attribution to the creator and a link to the licence when the image is reused.' }}</p>
          <NuxtLink :to="`/${routeLocale}/contact`" class="text-link">{{ localeCode === 'th' ? 'ติดต่อเรา' : localeCode === 'zh-CN' ? '联系我们' : 'Contact us' }}</NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.credits {
  background: var(--ivory);
}

.credits__list {
  border-top: 1px solid var(--line);
}

.credit-row {
  display: grid;
  grid-template-columns: 10rem 1.5fr 1fr 8rem 1.5rem;
  gap: 1.5rem;
  align-items: baseline;
  padding-block: 1rem;
  border-bottom: 1px solid var(--line);
  transition: padding-left 220ms var(--ease), color 160ms ease;
}

.credit-row:hover {
  padding-left: 0.5rem;
  color: var(--accent);
}

.credit-row__name {
  color: var(--steel);
  font-size: 0.75rem;
}

.credit-row__title {
  font-size: 0.92rem;
  font-weight: 550;
}

.credit-row__creator {
  font-size: 0.82rem;
  color: var(--steel-dark);
}

.credit-row__license {
  color: var(--steel);
  font-size: 0.75rem;
}

.credits__note {
  margin-top: 2rem;
  display: flex;
  gap: 1rem;
  border: 1px solid var(--line);
  padding: 1rem;
  align-items: flex-start;
}

.credits__note .mono {
  color: var(--accent-text);
  font-size: 0.75rem;
  flex: 0 0 auto;
}

.credits__note p {
  font-size: 0.8rem;
  line-height: 1.6;
  color: var(--steel-dark);
}

.credits__legal {
  margin-top: 3rem;
  display: flex;
  justify-content: space-between;
  gap: 2rem;
  align-items: flex-start;
  border-top: 1px solid var(--line);
  padding-top: 1.5rem;
}

.credits__legal p {
  max-width: 70ch;
  font-size: 0.8rem;
  line-height: 1.6;
  color: var(--steel-dark);
}

@media (max-width: 900px) {
  .credit-row {
    grid-template-columns: 1fr 1.5rem;
    gap: 0.35rem 1rem;
  }

  .credit-row__name {
    grid-column: 1 / -1;
  }

  .credit-row__title,
  .credit-row__creator,
  .credit-row__license {
    grid-column: 1;
    overflow-wrap: anywhere;
  }

  .credit-row > svg {
    grid-column: 2;
    grid-row: 1 / span 4;
    align-self: center;
  }

  .credits__legal {
    flex-direction: column;
  }
}
</style>
