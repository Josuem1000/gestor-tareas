# Gestor de Tareas — Aplicación monolítica PHP + MySQL

Aplicación web para gestionar tareas (crear, editar, completar, eliminar, filtrar por estado/categoría y buscar).
Todo el sistema vive en **un solo directorio raíz**, sin frameworks ni dependencias externas.

**Autor:** Maykol Josue Espinoza Alvarez

## Tecnologías
- HTML5 + CSS3 (diseño responsive, modo oscuro automático)
- JavaScript (modales, edición, confirmaciones)
- PHP 8 (backend monolítico, PDO con consultas preparadas, token CSRF)
- MySQL / MariaDB (`dump.sql`)

## Estructura
```
gestor-tareas/
├── index.php      # Controlador + vista (toda la lógica)
├── db.php         # Conexión PDO
├── config.php     # Credenciales de la base de datos  <-- EDITAR
├── dump.sql       # Estructura + datos de ejemplo
├── .htaccess      # Bloquea acceso público a .sql/.md
└── assets/
    ├── style.css
    └── app.js
```

## Base de datos
- `categorias` (id, nombre, color)
- `tareas` (id, titulo, descripcion, prioridad, estado, fecha_limite, categoria_id → categorias.id, creado_en, actualizado_en)

## Despliegue rápido en cPanel
1. Comprimir el contenido de la carpeta en `gestor-tareas.zip`.
2. cPanel → Administrador de archivos → `public_html` → Cargar → Extraer.
3. cPanel → Bases de datos MySQL: crear BD, crear usuario, agregar usuario a la BD con **todos los privilegios**.
4. phpMyAdmin → seleccionar la BD → Importar → `dump.sql`.
5. Editar `config.php` con `DB_NAME`, `DB_USER`, `DB_PASS` (con el prefijo de cPanel).
6. Abrir `https://tudominio.com/`.

Ver la guía completa en `Guia_Despliegue_cPanel.pdf`.

## Ejecutar localmente (XAMPP/Laragon)
Copiar la carpeta a `htdocs`, importar `dump.sql` en phpMyAdmin y ajustar `config.php`
(`DB_USER=root`, `DB_PASS=''`, `DB_NAME` = nombre de la BD local).
