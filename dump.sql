-- =========================================================
--  Gestor de Tareas - Script de base de datos (MySQL/MariaDB)
--  Importar en phpMyAdmin dentro de la BD creada en cPanel.
--  NOTA: no incluye CREATE DATABASE porque en cPanel la BD
--  se crea desde el panel (nombre con prefijo: usuario_nombre).
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `tareas`;
DROP TABLE IF EXISTS `categorias`;

-- ---------------------------------------------------------
-- Tabla: categorias
-- ---------------------------------------------------------
CREATE TABLE `categorias` (
  `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(60)  NOT NULL,
  `color`  CHAR(7)      NOT NULL DEFAULT '#4f46e5',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categorias_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Tabla: tareas
-- ---------------------------------------------------------
CREATE TABLE `tareas` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo`         VARCHAR(150) NOT NULL,
  `descripcion`    TEXT NULL,
  `prioridad`      ENUM('baja','media','alta') NOT NULL DEFAULT 'media',
  `estado`         ENUM('pendiente','en_progreso','completada') NOT NULL DEFAULT 'pendiente',
  `fecha_limite`   DATE NULL,
  `categoria_id`   INT UNSIGNED NULL,
  `creado_en`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tareas_estado` (`estado`),
  KEY `idx_tareas_categoria` (`categoria_id`),
  CONSTRAINT `fk_tareas_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Datos de ejemplo
-- ---------------------------------------------------------
INSERT INTO `categorias` (`id`, `nombre`, `color`) VALUES
  (1, 'Universidad', '#4f46e5'),
  (2, 'Trabajo',     '#0891b2'),
  (3, 'Personal',    '#16a34a'),
  (4, 'Hogar',       '#ea580c');

INSERT INTO `tareas` (`titulo`, `descripcion`, `prioridad`, `estado`, `fecha_limite`, `categoria_id`) VALUES
  ('Desplegar app en cPanel', 'Subir el .zip a public_html, crear la BD e importar dump.sql.', 'alta', 'en_progreso', '2026-10-29', 1),
  ('Elaborar guía de despliegue', 'Documento PDF paso a paso con capturas.', 'alta', 'completada', '2026-10-27', 1),
  ('Estudiar para el parcial de Redes', 'Capítulos 3 al 5: TCP/IP y subnetting.', 'media', 'pendiente', '2026-11-05', 1),
  ('Enviar reporte semanal', 'Resumen de avances al supervisor.', 'media', 'pendiente', '2026-10-24', 2),
  ('Hacer ejercicio', '30 minutos de caminata.', 'baja', 'pendiente', NULL, 3),
  ('Pagar servicios', 'Luz y agua antes del fin de mes.', 'alta', 'pendiente', '2026-10-30', 4);

SET FOREIGN_KEY_CHECKS = 1;
