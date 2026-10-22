FROM mcr.microsoft.com/devcontainers/base:bookworm

RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y --no-install-recommends make bash jq \
    && rm -rf /var/lib/apt/lists/*
