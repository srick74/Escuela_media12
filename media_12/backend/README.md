# Panel de gestión

El panel requiere PHP 8 o superior con PDO MySQL y una base de datos MySQL/MariaDB. No funciona abriendo los archivos HTML directamente ni en un hosting exclusivamente estático.

## Publicación en un hosting PHP/MySQL

GitHub se usa para guardar el código fuente. La página pública y el panel deben servirse desde el mismo hosting PHP para que la sesión de administración funcione de forma confiable en distintos navegadores.

1. En el panel del hosting, creá una base MySQL/MariaDB y anotá el host, el nombre de la base, el usuario y la contraseña asignados.
2. Seleccioná esa base en phpMyAdmin e importá `backend/schema.sql`. El esquema solo agrega las tablas `usuarios` y `horarios_publicos`; no crea ni modifica las tablas existentes.
3. Subí el contenido del proyecto al directorio público del hosting, manteniendo las carpetas. **No subas `backend/config.local.php`**. El archivo `.gitignore` impide agregarlo por error a Git.
4. Creá `backend/config.local.php` en el servidor con las credenciales de la base:

```php
<?php
return [
    'host' => 'HOST_MYSQL_DEL_PROVEEDOR',
    'port' => '3306',
    'database' => 'NOMBRE_DE_LA_BASE',
    'username' => 'USUARIO_MYSQL',
    'password' => 'CONTRASEÑA_MYSQL',
];
```

5. Habilitá PHP 8 o superior, PDO MySQL y HTTPS. Conservá la protección de `backend/.htaccess`; si el servidor no usa Apache, configurá reglas equivalentes para proteger `config.php`, `config.local.php`, `http.php`, `create-user.php`, `schema.sql` y `README.md`.
6. Creá las cuentas autorizadas desde una terminal con PHP. En Windows, desde la raíz del proyecto:

```text
powershell -NoProfile -ExecutionPolicy Bypass -File .\backend\create-user.ps1 usuario "Nombre y apellido"
```

En Linux o macOS:

```text
php backend/create-user.php usuario "Nombre y apellido"
```

Las contraseñas deben tener al menos 12 caracteres y se guardan mediante `password_hash`. No hay registro público de cuentas.

No habilites GitHub Pages para este despliegue: el repositorio de GitHub es para el código fuente; la URL pública debe apuntar al hosting PHP.

No publiques credenciales ni uses la cuenta local de prueba `admin/admin`. Creá cuentas nuevas y contraseñas únicas para producción.

Los archivos `.frm` y `.ibd` de una carpeta de datos son internos de MariaDB: no se conectan directamente desde la página ni deben copiarse al hosting. Para migrar datos existentes se necesita una exportación SQL compatible.

## Desarrollo local

```text
CREATE DATABASE escuela_media12 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

En un MySQL local, creá la base una sola vez y luego importá el esquema indicando la base seleccionada:

```text
mysql -u root -p escuela_media12 < backend/schema.sql
```

Creá `backend/config.local.php` con tus credenciales locales y, desde la raíz del proyecto, iniciá el servidor de desarrollo:

```text
php -S localhost:8000
```

Luego abrí `http://localhost:8000/html/horarios.html` para consultar los horarios o `http://localhost:8000/html/editar-horarios.html` para modificarlos. El servidor integrado de PHP es solo para desarrollo, no para publicar el sitio.

## Uso del panel

Desde **Niveles Educativos y Horarios**, el enlace de edición lleva al panel `html/editar-horarios.html`. Las cuentas registradas en `usuarios` pueden editar los cursos y sus bloques; el servidor valida y guarda los cambios en `horarios_publicos`. La primera consulta copia los horarios actuales de `js/horarios-publicos.json` a la base, y a partir de entonces la página pública muestra la versión guardada en MySQL/MariaDB.

Si el API o la base no están disponibles, la página pública muestra el respaldo `js/horarios-publicos.json` y avisa que no está mostrando los cambios guardados en la base.