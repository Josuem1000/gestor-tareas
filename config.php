<?php
/**
 * ==========================================================
 *  CONFIGURACIÓN DE CONEXIÓN A LA BASE DE DATOS
 * ==========================================================
 *  En cPanel, la base de datos y el usuario llevan como
 *  prefijo tu usuario de cPanel. Ejemplo:
 *      usuario cPanel : miusuario
 *      base de datos  : miusuario_tareas
 *      usuario BD     : miusuario_admin
 *
 *  Cambia SOLO los valores de abajo con los datos que
 *  creaste en "Bases de datos MySQL".
 * ==========================================================
 */

define('DB_HOST', 'localhost');           // En cPanel casi siempre es localhost
define('DB_NAME', 'miusuario_tareas');    // Nombre completo de la BD (con prefijo)
define('DB_USER', 'miusuario_admin');     // Usuario de la BD (con prefijo)
define('DB_PASS', 'CambiaEstaClave123!'); // Contraseña del usuario de la BD
define('DB_CHARSET', 'utf8mb4');

// Nombre visible de la aplicación
define('APP_NAME', 'Gestor de Tareas');
define('APP_AUTOR', 'Alex Elvis Mamani Bultron');

// Muestra errores detallados (ponlo en false cuando todo funcione)
define('APP_DEBUG', true);

date_default_timezone_set('America/La_Paz');
