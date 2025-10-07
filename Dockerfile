# Dockerfile COMPLETO - Tayrona Kiosco POS
# Replica EXACTAMENTE el entorno de Laragon: React + PHP + Apache

FROM php:8.1-apache

# Instalar extensiones PHP (igual que Laragon)
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libicu-dev \
    curl wget unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli zip intl

# Instalar Node.js 18 (para compilar React)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Habilitar módulos Apache necesarios
RUN a2enmod rewrite headers expires

# Configurar Apache EXACTAMENTE como Laragon
COPY <<EOF /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    
    # Configuración para React SPA
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        # React Router - manejar rutas SPA
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_URI} !^/api/
        RewriteRule . /index.html [L]
    </Directory>
    
    # Configuración específica para API PHP
    <Directory /var/www/html/api>
        Options -Indexes
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Copiar TODO el proyecto
COPY . /var/www/html/

# Compilar React frontend
WORKDIR /var/www/html
RUN npm install --production=false
RUN npm run build

# Mover archivos compilados de React al directorio web
RUN cp -r build/* /var/www/html/ \
    && rm -rf build src node_modules

# Configurar permisos (igual que Laragon)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/api/logs \
    && mkdir -p /var/www/html/uploads \
    && chmod -R 777 /var/www/html/api/logs \
    && chmod -R 777 /var/www/html/uploads

# Configurar PHP (igual que Laragon)
RUN echo 'memory_limit = 256M' >> /usr/local/etc/php/php.ini \
    && echo 'upload_max_filesize = 20M' >> /usr/local/etc/php/php.ini \
    && echo 'post_max_size = 50M' >> /usr/local/etc/php/php.ini \
    && echo 'max_execution_time = 300' >> /usr/local/etc/php/php.ini \
    && echo 'date.timezone = America/Argentina/Buenos_Aires' >> /usr/local/etc/php/php.ini

# Crear archivo de configuración de BD para Railway
RUN echo '<?php' > /var/www/html/api/bd_conexion.php \
    && echo 'class Conexion {' >> /var/www/html/api/bd_conexion.php \
    && echo '    private static $conexion = null;' >> /var/www/html/api/bd_conexion.php \
    && echo '    public static function obtenerConexion() {' >> /var/www/html/api/bd_conexion.php \
    && echo '        if (self::$conexion !== null) return self::$conexion;' >> /var/www/html/api/bd_conexion.php \
    && echo '        $host = $_ENV["MYSQL_HOST"] ?? "localhost";' >> /var/www/html/api/bd_conexion.php \
    && echo '        $db = $_ENV["MYSQL_DATABASE"] ?? "railway";' >> /var/www/html/api/bd_conexion.php \
    && echo '        $user = $_ENV["MYSQL_USER"] ?? "root";' >> /var/www/html/api/bd_conexion.php \
    && echo '        $pass = $_ENV["MYSQL_PASSWORD"] ?? "";' >> /var/www/html/api/bd_conexion.php \
    && echo '        $port = $_ENV["MYSQL_PORT"] ?? "3306";' >> /var/www/html/api/bd_conexion.php \
    && echo '        self::$conexion = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [' >> /var/www/html/api/bd_conexion.php \
    && echo '            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,' >> /var/www/html/api/bd_conexion.php \
    && echo '            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC' >> /var/www/html/api/bd_conexion.php \
    && echo '        ]);' >> /var/www/html/api/bd_conexion.php \
    && echo '        return self::$conexion;' >> /var/www/html/api/bd_conexion.php \
    && echo '    }' >> /var/www/html/api/bd_conexion.php \
    && echo '}' >> /var/www/html/api/bd_conexion.php \
    && echo '?>' >> /var/www/html/api/bd_conexion.php

# Configurar punto de entrada principal
COPY <<EOF /var/www/html/index.php
<?php
// Punto de entrada principal - maneja React SPA y APIs PHP
\$request_uri = \$_SERVER['REQUEST_URI'];

// Si es una API, incluir el archivo PHP correspondiente
if (strpos(\$request_uri, '/api/') === 0) {
    \$api_file = __DIR__ . \$request_uri;
    if (file_exists(\$api_file)) {
        include \$api_file;
        exit;
    }
}

// Para todo lo demás, servir index.html de React
if (file_exists(__DIR__ . '/index.html')) {
    readfile(__DIR__ . '/index.html');
} else {
    echo 'Sistema iniciando...';
}
?>
EOF

EXPOSE 80
CMD ["apache2-foreground"]
