export function useReveal() {
  const route = useRoute()
  const reduced = usePreferredReducedMotion()

  const run = async () => {
    if (!import.meta.client || reduced.value === 'reduce') return

    const { gsap } = await import('gsap')
    const { ScrollTrigger } = await import('gsap/ScrollTrigger')
    gsap.registerPlugin(ScrollTrigger)

    ScrollTrigger.getAll().forEach((trigger) => trigger.kill())

    await nextTick()

    const elements = gsap.utils.toArray<HTMLElement>('[data-reveal]')
    elements.forEach((element) => {
      if (element.closest('[data-hero]')) return

      gsap.fromTo(
        element,
        { autoAlpha: 0, y: 26 },
        {
          autoAlpha: 1,
          y: 0,
          duration: 0.95,
          ease: 'power3.out',
          scrollTrigger: {
            trigger: element,
            start: 'top 88%',
            once: true,
          },
          onComplete: () => {
            element.removeAttribute('data-reveal-hidden')
          },
        },
      )
    })
  }

  onMounted(() => {
    run()
  })

  watch(
    () => route.fullPath,
    () => {
      nextTick(() => {
        setTimeout(run, 80)
      })
    },
  )

  onBeforeUnmount(() => {
    if (!import.meta.client) return
    import('gsap/ScrollTrigger').then(({ ScrollTrigger }) => {
      ScrollTrigger.getAll().forEach((trigger) => trigger.kill())
    })
  })
}
