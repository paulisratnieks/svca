FROM node:lts-alpine as build-stage
WORKDIR /app
COPY /web/package*.json ./
RUN npm install
COPY /web .
RUN npm run build

FROM nginx:1.27.2 as production-stage
COPY --from=build-stage /app/dist /usr/share/nginx/html
COPY /docker/web/default.conf /etc/nginx/conf.d/default.conf
CMD ["nginx", "-g", "daemon off;"]
