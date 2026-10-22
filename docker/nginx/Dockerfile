# --- ETAPA 1: Compilar el Frontend con Node ---
FROM node:lts-alpine AS build-stage
WORKDIR /app
COPY frontend/package*.json ./
RUN npm install
COPY frontend/ ./
ARG VITE_BUILD_SHA=__BUILD_SHA__
ARG VITE_BUILD_DATE=__BUILD_DATE__
ENV VITE_API_BASE=/api
ENV VITE_BUILD_SHA=${VITE_BUILD_SHA}
ENV VITE_BUILD_DATE=${VITE_BUILD_DATE}
RUN npm run build

# --- ETAPA 2: Servidor Nginx de Produccion ---
FROM nginx:alpine AS production-stage
COPY docker/nginx/default.prod.conf /etc/nginx/conf.d/default.conf
COPY --from=build-stage /app/dist /var/www/frontend
COPY backend/public /var/www/backend/public

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
