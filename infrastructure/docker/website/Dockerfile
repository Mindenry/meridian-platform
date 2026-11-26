# syntax=docker/dockerfile:1

FROM node:22-alpine AS build

RUN corepack enable

WORKDIR /repo

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
COPY apps/website/package.json apps/website/
COPY apps/studio/package.json apps/studio/
COPY apps/e2e/package.json apps/e2e/

RUN pnpm install --frozen-lockfile --filter @meridian/website...

COPY apps/website ./apps/website

ARG NUXT_PUBLIC_API_BASE=http://localhost:8000/api/v1
ARG NUXT_PUBLIC_SITE=meridian
ARG NUXT_PUBLIC_SITE_URL=http://localhost:3002
ARG NUXT_PUBLIC_STUDIO_URL=http://localhost:3003

ENV NUXT_PUBLIC_API_BASE=$NUXT_PUBLIC_API_BASE \
    NUXT_PUBLIC_SITE=$NUXT_PUBLIC_SITE \
    NUXT_PUBLIC_SITE_URL=$NUXT_PUBLIC_SITE_URL \
    NUXT_PUBLIC_STUDIO_URL=$NUXT_PUBLIC_STUDIO_URL \
    NUXT_PUBLIC_DEMO_MODE=true

RUN pnpm --filter @meridian/website build

FROM node:22-alpine AS runtime

WORKDIR /app

ENV NODE_ENV=production \
    HOST=0.0.0.0 \
    PORT=3002

COPY --from=build /repo/apps/website/.output ./.output

EXPOSE 3002

CMD ["node", ".output/server/index.mjs"]
