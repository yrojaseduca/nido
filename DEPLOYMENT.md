# Despliegue de Nido

## 0. Preparación inicial

### 0.1. Crear el subdominio en DonDominio

En el panel DNS de `listocasa.es`, crea:

```text
Tipo:    A
Nombre:  nido
Valor:   209.97.179.224
TTL:     automático o 3600
```

El resultado debe ser `nido.listocasa.es A 209.97.179.224`. Comprueba la propagación con:

```bash
dig +short nido.listocasa.es
```

El DNS solo apunta el dominio; la web responderá cuando Nginx esté levantado.

### 0.2. Crear el Droplet en DigitalOcean

En `Create → Droplets` selecciona Ubuntu LTS 64-bit, una región cercana, una clave SSH y, preferiblemente, backups automáticos. Usa al menos 2 GB de RAM para construir Docker. El Droplet actual de 512 MB necesita swap.

Permite en el firewall:

```text
22 SSH
80 HTTP
443 HTTPS
```

No abras `3306`; MariaDB debe quedar dentro de Docker.

### 0.3. Entrar en el Droplet

```bash
ssh root@209.97.179.224
```

La clave seleccionada en DigitalOcean sirve para entrar al servidor. Es distinta de la clave que el Droplet usará para leer GitHub.

### 0.4. Instalar Docker

Dentro del Droplet, como `root`:

```bash
apt update
apt install -y docker.io docker-compose-v2
systemctl enable --now docker
docker --version
docker compose version
```

### 0.5. Dar acceso del Droplet a GitHub

```bash
mkdir -p /root/.ssh
chmod 700 /root/.ssh
ssh-keygen -t ed25519 -C "nido-digitalocean" -f /root/.ssh/nido_github
cat /root/.ssh/nido_github.pub
```

Añade la salida en GitHub: `Settings → Deploy keys → Add deploy key`, dentro de `yrojaseduca/nido`, con solo lectura.

Crea `/root/.ssh/config`:

```ssh
Host github-nido
    HostName github.com
    User git
    IdentityFile /root/.ssh/nido_github
    IdentitiesOnly yes
```

```bash
chmod 600 /root/.ssh/config /root/.ssh/nido_github
ssh -T git@github-nido
```

### 0.6. Clonar Nido

```bash
mkdir -p /opt
cd /opt
git clone git@github-nido:yrojaseduca/nido.git nido
cd /opt/nido
```

Para una copia existente:

```bash
cd /opt/nido
git pull --ff-only origin main
```

## 1. Subir el código a GitHub

La clave SSH personal está configurada con el alias `github-personal`. El remoto debe usar ese alias:

```bash
git remote set-url origin git@github-personal:yrojaseduca/nido.git
ssh -T git@github-personal
git add .
git commit -m "Describe el cambio"
git push -u origin main
```

Comprueba que no se incluyen secretos:

```bash
git status --short
git ls-files .env frontend/.env
```

Los archivos `.env`, `vendor`, `node_modules` y `dist` están excluidos por `.gitignore`.

## 1.1. Diferencia entre las claves SSH

La clave SSH seleccionada al crear el Droplet en DigitalOcean sirve para entrar al servidor. No concede acceso a GitHub.

Para que el Droplet pueda clonar este repositorio, genera una Deploy Key dentro del propio Droplet:

```bash
mkdir -p /root/.ssh
chmod 700 /root/.ssh
ssh-keygen -t ed25519 -C "nido-digitalocean" -f /root/.ssh/nido_github
cat /root/.ssh/nido_github.pub
```

Añade el resultado en GitHub: `Settings → Deploy keys → Add deploy key`. Usa solo lectura.

Configura el alias en `/root/.ssh/config`:

```ssh
Host github-nido
    HostName github.com
    User git
    IdentityFile /root/.ssh/nido_github
    IdentitiesOnly yes
```

Protege los archivos y prueba la conexión:

```bash
chmod 600 /root/.ssh/config /root/.ssh/nido_github
ssh -T git@github-nido
```

Clona usando el alias, no `git@github.com` directamente:

```bash
cd /opt
git clone git@github-nido:yrojaseduca/nido.git nido
```

## 2. Preparar el Droplet de DigitalOcean

El servidor de producción de Nido se ejecuta en un Droplet de DigitalOcean. Crea un Droplet con Ubuntu LTS y selecciona una clave SSH durante la creación. Esa clave permite acceder al servidor; es independiente de la clave Deploy Key que el servidor usará para leer el repositorio de GitHub.

Configuración inicial recomendada:

- Ubuntu LTS 64-bit.
- 2 GB de RAM y 1 vCPU como punto de partida.
- Backups automáticos activados para producción.
- Firewall con `22`, `80` y `443`.
- MariaDB sin puertos públicos.

Conéctate usando la IP pública del Droplet:

```bash
ssh root@IP_DEL_DROPLET
```

En esta instalación el Droplet tiene la IP pública `209.97.179.224`:

```bash
ssh root@209.97.179.224
```

Instala Docker Engine y Docker Compose Plugin siguiendo la documentación oficial de Ubuntu/Docker. No guardes claves privadas ni contraseñas dentro del repositorio.

Instala el código en una ruta concreta, por ejemplo `/opt/nido`:

```bash
sudo mkdir -p /opt/nido
sudo chown "$USER":"$USER" /opt/nido
git clone git@github.com:yrojaseduca/nido.git /opt/nido
cd /opt/nido
```

Si el Droplet no tiene configurado acceso SSH a GitHub, añade una clave de despliegue al repositorio o usa un token de GitHub mediante HTTPS. La clave debe tener solo el acceso necesario.

## 3. Configurar variables de producción

Copia la plantilla y edítala en el servidor:

```bash
cp .env.example .env
chmod 600 .env
nano .env
```

Valores mínimos:

```env
PROJECT_NAME=nido
HOST_PORT=80
APP_ENV=production
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=nido
DB_USERNAME=nido
DB_PASSWORD=una-clave-larga-y-aleatoria
DB_ROOT_PASSWORD=otra-clave-larga-y-aleatoria
```

Si se activa Pusher, añade las credenciales privadas solo al `.env` del servidor. La clave pública de frontend puede configurarse mediante las variables `VITE_*` antes de construir la imagen.

## 4. Arrancar Nido

Desde `/opt/nido`:

En un Droplet de 512 MB, añade swap antes de construir las imágenes:

```bash
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
```

```bash
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml up -d --build
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml ps
```

El override de producción usa PHP 8.5, elimina los volúmenes de desarrollo de PHP y mantiene únicamente la configuración de Nginx. El frontend se compila dentro de la imagen de Nginx.

Las migraciones se ejecutan automáticamente al iniciar PHP. Para ejecutarlas manualmente:

```bash
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml exec php php bin/migrate.php
```

## 5. Subdominio en DonDominio y HTTPS

El dominio se gestiona en DonDominio y Nido se publica en:

```text
nido.listocasa.es
```

En el panel DNS de DonDominio crea un registro:

```text
Tipo:  A
Nombre: nido
Valor:  209.97.179.224
TTL:    automático o 3600
```

No uses un registro `CNAME` apuntando a una IP. Comprueba la propagación desde tu ordenador:

```bash
dig +short nido.listocasa.es
```

El resultado debe ser:

```text
209.97.179.224
```

Abre los puertos `80` y `443` en el firewall de DigitalOcean.

La configuración actual sirve HTTP. Para HTTPS hay que añadir un proxy TLS, como Caddy o Certbot con Nginx, y configurar `server_name` con el subdominio real. El certificado debe cubrir exactamente ese subdominio. No publiques MariaDB: su puerto interno no debe exponerse a Internet.

## 6. Actualizar una versión

Después de subir cambios a GitHub:

```bash
cd /opt/nido
git pull --ff-only origin main
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml up -d --build
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml ps
```

Comprueba salud y logs:

```bash
curl -f http://127.0.0.1/api/health
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml logs --tail=100 php nginx
```

## 7. Copias de seguridad de MariaDB

Antes de migraciones importantes, exporta la base de datos:

```bash
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml exec -T mariadb \
    mariadb-dump -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > backup-$(date +%Y%m%d-%H%M%S).sql
```

Guarda las copias fuera del Droplet y prueba periódicamente su restauración.

## 8. Comprobaciones antes de publicar

En el repositorio:

```bash
git diff --check
make test-unit
make frontend-build
```

En el servidor:

```bash
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml config
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml ps
curl -f http://127.0.0.1/api/health
```
