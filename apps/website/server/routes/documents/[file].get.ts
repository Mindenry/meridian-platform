export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const file = String(getRouterParam(event, 'file') || '')

  const response = await $fetch<{ data: Array<{ file_url?: string | null; title?: string }> }>(
    `${internalApiBase()}/public/documents`,
    { headers: { Accept: 'application/json', 'X-Site': config.public.site } },
  )

  const document = response.data.find((item) => item.file_url?.endsWith(`/${file}`))

  if (!document?.file_url) {
    throw createError({ statusCode: 404, statusMessage: 'Document not found' })
  }

  return sendRedirect(event, document.file_url, 302)
})
