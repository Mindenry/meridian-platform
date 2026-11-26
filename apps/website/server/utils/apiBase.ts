/**
 * SSR and Nitro routes run inside the Compose network, so they cannot use the
 * browser-facing `localhost` API URL. Prefer the explicit internal override and
 * fall back to the private runtime config.
 */
export function internalApiBase(): string {
  return process.env.NUXT_API_INTERNAL_BASE || String(useRuntimeConfig().apiInternal)
}
