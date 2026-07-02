# =============================================================================
# TaskSync Pro — Frontend Dockerfile (multi-stage)
# Stage 1: Node dependencies + Vite build
# Stage 2: Nginx (alpine) serving built assets
# =============================================================================

# --- Stage 1: Build ---
FROM node:20-alpine AS builder

WORKDIR /build

COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund

COPY . .
RUN npm run build

# --- Stage 2: Serve ---
FROM nginx:1.27-alpine AS runtime

LABEL org.opencontainers.image.title="TaskSync Pro Frontend"
LABEL org.opencontainers.image.description="Vue 3 SPA served by Nginx"
LABEL org.opencontainers.image.version="1.0.0"

# Remove default nginx config
RUN rm -rf /etc/nginx/conf.d/default.conf

# Copy custom nginx config
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Copy built assets from builder
COPY --from=builder /build/dist /var/www/html

# Brotli precompressed assets support
RUN apk add --no-cache brotli && \
    find /var/www/html -type f \( -name '*.js' -o -name '*.css' -o -name '*.html' \) \
    -exec sh -c 'brotli -f -q 11 "$1" -o "$1.br"' _ {} \;

HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
    CMD wget -qO- http://localhost:80/nginx-health || exit 1

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
