FROM node:20-bookworm-slim

WORKDIR /app

COPY frontend/package*.json ./

RUN npm install

COPY frontend/ .

COPY docker/node/dev-entrypoint.sh /usr/local/bin/vue-dev-entrypoint.sh
RUN chmod +x /usr/local/bin/vue-dev-entrypoint.sh

EXPOSE 5173

CMD ["/usr/local/bin/vue-dev-entrypoint.sh"]