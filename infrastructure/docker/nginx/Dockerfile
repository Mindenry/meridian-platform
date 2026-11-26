# syntax=docker/dockerfile:1

FROM nginx:1.27-alpine

COPY infrastructure/docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY apps/api/public /var/www/html/public

EXPOSE 80
