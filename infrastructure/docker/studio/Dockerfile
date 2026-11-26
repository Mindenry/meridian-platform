# syntax=docker/dockerfile:1

FROM node:22-alpine AS build

RUN corepack enable

WORKDIR /repo

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
COPY apps/website/package.json apps/website/
COPY apps/studio/package.json apps/studio/
COPY apps/e2e/package.json apps/e2e/

RUN pnpm install --frozen-lockfile --filter @meridian/studio...

COPY apps/studio ./apps/studio

ARG VITE_API_BASE=http://localhost:8000/api/v1
ENV VITE_API_BASE=$VITE_API_BASE

RUN pnpm --filter @meridian/studio build

FROM nginx:1.27-alpine

COPY infrastructure/docker/studio/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /repo/apps/studio/dist /usr/share/nginx/html

EXPOSE 80
