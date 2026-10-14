-- =====================================================================================
-- SISTEMA DE GESTIÓN Y CONTROL DE TURNOS - HOSPITAL JAPONÉS
-- Base de Datos: control_turnos_hospital
-- Motor: MySQL 8.0+ / MariaDB (InnoDB)
-- Codificación: UTF-8 Unicode (utf8mb4_spanish_ci)
-- =====================================================================================
-- DESCRIPCIÓN DEL SISTEMA:
-- Este script define la estructura relacional completa para la planificación de roles
-- mensuales de turnos del personal médico, enfermería y áreas hospitalarias, control de
-- asignaciones diarias, cómputo de horas laborales, gestión de permutas/reemplazos,
-- licencias/vacaciones y calendario de feriados para guardias.
-- =====================================================================================

CREATE DATABASE IF NOT EXISTS `control_turnos_hospital` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_spanish_ci;

USE `control_turnos_hospital`;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================================
-- 1. TABLA: CARGOS
-- Clasificación de los puestos o roles institucionales dentro del hospital.
-- =====================================================================================
DROP TABLE IF EXISTS `cargos`;
CREATE TABLE `cargos` (
  `id_cargo` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del cargo',
  `nombre_cargo` varchar(150) NOT NULL COMMENT 'Nombre del cargo (ej. Jefe de Servicio, Operador, etc.)',
  PRIMARY KEY (`id_cargo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================================================
-- 2. TABLA: DEPARTAMENTOS_SERVICIOS (ÁREAS MÉDICAS Y DE APOYO)
-- Servicios clínicos y dependencias del hospital (Emergencias, Pediatría, Enfermería, etc.).
-- =====================================================================================
DROP TABLE IF EXISTS `departamentos_servicios`;
CREATE TABLE `departamentos_servicios` (
  `id_departamento` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del departamento o servicio',
  `nombre_departmento` varchar(150) NOT NULL COMMENT 'Nombre del servicio o unidad médica',
  PRIMARY KEY (`id_departamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================================================
-- 3. TABLA: FUENTES_FINANCIAMIENTO
-- Origen presupuestario de los ítems de salud en Bolivia (TGN, IDH, Recursos Propios, GOB, SUS).
-- =====================================================================================
DROP TABLE IF EXISTS `fuentes_financiamiento`;
CREATE TABLE `fuentes_financiamiento` (
  `id_fuente` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la fuente de financiamiento',
  `codigo_fuente` varchar(50) NOT NULL COMMENT 'Acrónimo o código (ej. TGN, IDH, RP, GOB, SUS)',
  `descripcion` varchar(255) DEFAULT NULL COMMENT 'Descripción detallada de la fuente presupuestaria',
  PRIMARY KEY (`id_fuente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================================================
-- 4. TABLA: TURNOS
-- Catálogo oficial de tipos de turnos, horarios y horas efectivas computables.
-- =====================================================================================
DROP TABLE IF EXISTS `turnos`;
CREATE TABLE `turnos` (
  `id_turno` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del turno',
  `codigo_turno` char(2) NOT NULL COMMENT 'Código corto mostrado en la planilla (M, M*, T, N, L)',
  `nombre_turno` varchar(100) NOT NULL COMMENT 'Nombre descriptivo (Mañana, Mañana extendido, Tarde, Noche, Libre)',
  `hora_entrada` time DEFAULT NULL COMMENT 'Hora de inicio programada del turno',
  `hora_salida` time DEFAULT NULL COMMENT 'Hora de conclusión programada del turno',
  `horas_computables` decimal(4,2) NOT NULL DEFAULT '6.00' COMMENT 'Horas efectivas para el cómputo de la carga mensual de trabajo',
  `color_hex` varchar(20) DEFAULT '#0284c7' COMMENT 'Color hexadecimal para la interfaz web y reportes',
  PRIMARY KEY (`id_turno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================================================
-- 5. TABLA: EMPLEADOS
-- Registro maestro del personal de salud (médicos, enfermeras, técnicos, residentes).
-- =====================================================================================
DROP TABLE IF EXISTS `empleados`;
CREATE TABLE `empleados` (
  `id_empleado` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del empleado',
  `item_id` varchar(50) DEFAULT NULL COMMENT 'Número de ítem presupuestario asignado',
  `codigo_biometrico` varchar(100) NOT NULL COMMENT 'Código único del reloj biométrico o tarjeta RFID',
  `ci` varchar(30) DEFAULT NULL COMMENT 'Cédula de identidad (CI) del funcionario',
  `telefono` varchar(50) DEFAULT NULL COMMENT 'Teléfono o celular de contacto',
  `nombre_completo` varchar(255) NOT NULL COMMENT 'Nombres y apellidos completos',
  `id_cargo` int DEFAULT NULL COMMENT 'Cargo laboral que desempeña',
  `id_departamento` int DEFAULT NULL COMMENT 'Departamento o servicio asignado (null = sin área/flotante)',
  `activo` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1: Funcionario activo; 0: De baja / inactivo',
  `profesion_especialidad` varchar(150) DEFAULT NULL COMMENT 'Especialidad médica, titulación o grado académico',
  PRIMARY KEY (`id_empleado`),
  UNIQUE KEY `codigo_biometrico` (`codigo_biometrico`),
  KEY `id_cargo` (`id_cargo`),
  KEY `id_departamento` (`id_departamento`),
  CONSTRAINT `empleados_ibfk_1` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id_cargo`) ON DELETE SET NULL,
  CONSTRAINT `empleados_ibfk_2` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos_servicios` (`id_departamento`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================================================
-- 6. TABLA: USUARIOS
-- Cuentas de acceso y autenticación al sistema web de turnos.
-- =====================================================================================
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del usuario',
  `nombre_usuario` varchar(100) NOT NULL COMMENT 'Nombre de usuario para iniciar sesión',
  `correo` varchar(150) NOT NULL COMMENT 'Correo electrónico institucional',
  `password_hash` varchar(255) NOT NULL COMMENT 'Contraseña cifrada (bcrypt)',
  `rol` varchar(50) NOT NULL DEFAULT 'Operador' COMMENT 'Nivel de acceso: Administrador, Jefe de Area, Jefe de Servicio, Operador',
  `id_empleado` int DEFAULT NULL COMMENT 'Enlace opcional al perfil del empleado',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================================================
-- 7. TABLA: ROLES_MENSUALES
-- Encabezado de la planilla mensual de turnos por cada departamento/servicio.
-- Controla el estado de aprobación por Recursos Humanos y Dirección.
-- =====================================================================================
DROP TABLE IF EXISTS `roles_mensuales`;
CREATE TABLE `roles_mensuales` (
  `id_rol_mensual` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del rol mensual',
  `mes` int NOT NULL COMMENT 'Mes del rol (1 = Enero ... 12 = Diciembre)',
  `anio` int NOT NULL COMMENT 'Año del rol (ej. 2026)',
  `id_departamento` int DEFAULT NULL COMMENT 'Departamento al que corresponde este rol (null = consolidado global)',
  `fecha_recepcion` date DEFAULT NULL COMMENT 'Fecha en que RRHH recibió la planilla',
  `estado` enum('Borrador','Enviado','Aprobado','Rechazado','Cerrado') NOT NULL DEFAULT 'Borrador' COMMENT 'Estado del flujo de aprobación institucional',
  `aprobado_por` int DEFAULT NULL COMMENT 'Usuario de RRHH o Jefatura que aprobó formalmente el rol',
  `fecha_aprobacion` datetime DEFAULT NULL COMMENT 'Fecha y hora exacta de la aprobación',
  `observaciones` text DEFAULT NULL COMMENT 'Observaciones o motivos de rechazo/corrección',
  PRIMARY KEY (`id_rol_mensual`),
  KEY `id_departamento` (`id_departamento`),
  KEY `fk_roles_aprobador` (`aprobado_por`),
  CONSTRAINT `fk_roles_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL,
  CONSTRAINT `roles_mensuales_ibfk_1` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos_servicios` (`id_departamento`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================================================
-- 8. TABLA: ASIGNACION_TURNOS_DIARIOS
-- Matriz de programación día a día: asigna el turno de cada empleado en cada fecha.
-- =====================================================================================
DROP TABLE IF EXISTS `asignacion_turnos_diarios`;
CREATE TABLE `asignacion_turnos_diarios` (
  `id_asignacion` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la asignación diaria',
  `id_rol_mensual` int NOT NULL COMMENT 'Rol mensual al que pertenece la asignación',
  `id_empleado` int NOT NULL COMMENT 'Empleado que tiene asignado este turno',
  `id_fuente` int DEFAULT NULL COMMENT 'Fuente de financiamiento del ítem en este período (opcional)',
  `fecha` date NOT NULL COMMENT 'Fecha calendario del turno (YYYY-MM-DD)',
  `id_turno` int NOT NULL COMMENT 'Turno asignado (M, M*, T, N, L)',
  PRIMARY KEY (`id_asignacion`),
  KEY `id_rol_mensual` (`id_rol_mensual`),
  KEY `id_empleado` (`id_empleado`),
  KEY `id_fuente` (`id_fuente`),
  KEY `id_turno` (`id_turno`),
  KEY `idx_empleado_fecha` (`id_empleado`, `fecha`),
  CONSTRAINT `asignacion_turnos_diarios_ibfk_1` FOREIGN KEY (`id_rol_mensual`) REFERENCES `roles_mensuales` (`id_rol_mensual`) ON DELETE CASCADE,
  CONSTRAINT `asignacion_turnos_diarios_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE CASCADE,
  CONSTRAINT `asignacion_turnos_diarios_ibfk_3` FOREIGN KEY (`id_fuente`) REFERENCES `fuentes_financiamiento` (`id_fuente`) ON DELETE SET NULL,
  CONSTRAINT `asignacion_turnos_diarios_ibfk_4` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id_turno`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================================================
-- 9. TABLA: CAMBIOS_TURNOS (PERMUTAS Y COBERTURAS ENTRE COLEGAS)
-- Permite registrar, auditar y aprobar solicitudes de cambio de guardia entre personal.
-- =====================================================================================
DROP TABLE IF EXISTS `cambios_turnos`;
CREATE TABLE `cambios_turnos` (
  `id_cambio` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del cambio de turno',
  `id_empleado_solicitante` int NOT NULL COMMENT 'Empleado titular programado originalmente',
  `id_empleado_reemplazo` int NOT NULL COMMENT 'Empleado colega que cubre el turno',
  `fecha_turno` date NOT NULL COMMENT 'Fecha del turno a permutar',
  `id_turno_original` int NOT NULL COMMENT 'Turno programado inicialmente',
  `id_turno_nuevo` int NOT NULL COMMENT 'Turno acordado para la cobertura',
  `motivo` text DEFAULT NULL COMMENT 'Motivo o justificación de la permuta',
  `estado` enum('Pendiente','Aprobado','Rechazado') NOT NULL DEFAULT 'Pendiente' COMMENT 'Estado de la autorización por Jefatura',
  `aprobado_por` int DEFAULT NULL COMMENT 'Usuario con rol de Jefe o Administrador que autorizó',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cambio`),
  KEY `id_empleado_solicitante` (`id_empleado_solicitante`),
  KEY `id_empleado_reemplazo` (`id_empleado_reemplazo`),
  KEY `id_turno_original` (`id_turno_original`),
  KEY `id_turno_nuevo` (`id_turno_nuevo`),
  KEY `aprobado_por` (`aprobado_por`),
  CONSTRAINT `fk_cambio_solicitante` FOREIGN KEY (`id_empleado_solicitante`) REFERENCES `empleados` (`id_empleado`) ON DELETE CASCADE,
  CONSTRAINT `fk_cambio_reemplazo` FOREIGN KEY (`id_empleado_reemplazo`) REFERENCES `empleados` (`id_empleado`) ON DELETE CASCADE,
  CONSTRAINT `fk_cambio_turno_orig` FOREIGN KEY (`id_turno_original`) REFERENCES `turnos` (`id_turno`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cambio_turno_nuevo` FOREIGN KEY (`id_turno_nuevo`) REFERENCES `turnos` (`id_turno`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cambio_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================================================
-- 10. TABLA: LICENCIAS_VACACIONES (INCIDENCIAS Y NOVEDADES DE PERSONAL)
-- Controla períodos de ausencia legal: vacaciones, bajas médicas, permisos y comisiones.
-- =====================================================================================
DROP TABLE IF EXISTS `licencias_vacaciones`;
CREATE TABLE `licencias_vacaciones` (
  `id_licencia` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la licencia o vacación',
  `id_empleado` int NOT NULL COMMENT 'Empleado beneficiario de la licencia',
  `tipo_licencia` enum('Vacacion','Baja Medica','Permiso Especial','Comision','Otro') NOT NULL DEFAULT 'Vacacion' COMMENT 'Tipo de ausencia legal',
  `fecha_inicio` date NOT NULL COMMENT 'Fecha de inicio del período de ausencia',
  `fecha_fin` date NOT NULL COMMENT 'Fecha de finalización del período de ausencia',
  `motivo` text DEFAULT NULL COMMENT 'Detalles, justificación o diagnóstico médico',
  `documento_respaldo` varchar(255) DEFAULT NULL COMMENT 'Número de boleta, memorándum o archivo adjunto',
  `estado` enum('Solicitado','Aprobado','Rechazado') NOT NULL DEFAULT 'Aprobado' COMMENT 'Estado del trámite en Recursos Humanos',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_licencia`),
  KEY `id_empleado` (`id_empleado`),
  KEY `idx_licencia_fechas` (`fecha_inicio`,`fecha_fin`),
  CONSTRAINT `fk_licencias_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================================================
-- 11. TABLA: FERIADOS (CALENDARIO DE GUARDIAS FESTIVAS)
-- Días festivos oficiales para el cómputo de compensaciones o guardias diferenciadas.
-- =====================================================================================
DROP TABLE IF EXISTS `feriados`;
CREATE TABLE `feriados` (
  `id_feriado` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del feriado',
  `fecha` date NOT NULL COMMENT 'Fecha del feriado (YYYY-MM-DD)',
  `descripcion` varchar(150) NOT NULL COMMENT 'Motivo (ej. Año Nuevo, Día del Trabajo, 24 de Septiembre)',
  `tipo` enum('Nacional','Departamental','Institucional') NOT NULL DEFAULT 'Nacional' COMMENT 'Ámbito legal del feriado',
  PRIMARY KEY (`id_feriado`),
  UNIQUE KEY `fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================================
-- DATOS MAESTROS Y SEMILLAS INICIALES (SEEDS)
-- =====================================================================================

-- 1. Cargos institucionales
INSERT INTO `cargos` (`id_cargo`, `nombre_cargo`) VALUES
(1, 'Jefe de Servicio'),
(2, 'Operador / Especialista'),
(3, 'Administrador de Sistema'),
(4, 'Jefe de Area')
ON DUPLICATE KEY UPDATE `nombre_cargo` = VALUES(`nombre_cargo`);

-- 2. Departamentos / Servicios
INSERT INTO `departamentos_servicios` (`id_departamento`, `nombre_departmento`) VALUES
(1, 'Tecnología de la Información'),
(2, 'Enfermería'),
(3, 'Emergencias'),
(4, 'Pediatria')
ON DUPLICATE KEY UPDATE `nombre_departmento` = VALUES(`nombre_departmento`);

-- 3. Fuentes presupuestarias
INSERT INTO `fuentes_financiamiento` (`id_fuente`, `codigo_fuente`, `descripcion`) VALUES
(1, 'TGN', 'Tesoro General de la Nación (Ítems Ministeriales)'),
(2, 'IDH/HIPC', 'Recursos IDH / Fondo de Alivio a la Pobreza'),
(3, 'RP', 'Recursos Propios del Hospital Japonés'),
(4, 'GOB', 'Gobernación Departamental de Santa Cruz'),
(5, 'SUS', 'Sistema Único de Salud')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

-- 4. Tipos de turnos con sus horas computables
INSERT INTO `turnos` (`id_turno`, `codigo_turno`, `nombre_turno`, `hora_entrada`, `hora_salida`, `horas_computables`, `color_hex`) VALUES
(1, 'M', 'Mañana', '07:30:00', '13:30:00', 6.00, '#0284c7'),
(2, 'M*', 'Mañana extendido', '07:30:00', '15:30:00', 8.00, '#0d9488'),
(3, 'T', 'Tarde', '13:30:00', '19:30:00', 6.00, '#d97706'),
(4, 'N', 'Noche', '19:30:00', '07:30:00', 12.00, '#6366f1'),
(5, 'L', 'Libre', NULL, NULL, 0.00, '#94a3b8')
ON DUPLICATE KEY UPDATE 
  `nombre_turno` = VALUES(`nombre_turno`),
  `horas_computables` = VALUES(`horas_computables`),
  `color_hex` = VALUES(`color_hex`);

-- 5. Feriados oficiales de Bolivia y Santa Cruz
INSERT INTO `feriados` (`fecha`, `descripcion`, `tipo`) VALUES
('2026-01-01', 'Año Nuevo', 'Nacional'),
('2026-01-22', 'Día del Estado Plurinacional', 'Nacional'),
('2026-02-16', 'Carnaval (Lunes)', 'Nacional'),
('2026-02-17', 'Carnaval (Martes)', 'Nacional'),
('2026-04-03', 'Viernes Santo', 'Nacional'),
('2026-05-01', 'Día del Trabajo', 'Nacional'),
('2026-06-04', 'Corpus Christi', 'Nacional'),
('2026-06-21', 'Año Nuevo Aymara / Solsticio', 'Nacional'),
('2026-08-06', 'Día de la Independencia de Bolivia', 'Nacional'),
('2026-09-24', 'Efeméride Departamental de Santa Cruz', 'Departamental'),
('2026-11-02', 'Día de Todos los Santos', 'Nacional'),
('2026-12-25', 'Navidad', 'Nacional')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

-- 6. Personal médico y operativo (Empleados)
INSERT INTO `empleados` (`id_empleado`, `item_id`, `codigo_biometrico`, `telefono`, `nombre_completo`, `id_cargo`, `id_departamento`, `activo`) VALUES
(1, '100', 'ADM-001', '111111', 'Super Admin', 3, NULL, 1),
(2, '101', 'JEF-001', '222222', 'Jefe de Sistemas', 1, 1, 1),
(3, '102', 'JEF-002', '333333', 'Jefe de Enfermería', 1, 2, 1),
(4, '201', 'USR-TI-001', '44441', 'Usuario TI 1', 2, 1, 1),
(5, '202', 'USR-TI-002', '44442', 'Usuario TI 2', 2, 1, 1),
(6, '203', 'USR-TI-003', '44443', 'Usuario TI 3', 2, 1, 1),
(7, '301', 'USR-ENF-001', '55551', 'Usuario Enfermería 1', 2, 2, 1),
(8, '302', 'USR-ENF-002', '55552', 'Usuario Enfermería 2', 2, 2, 1),
(9, '303', 'USR-ENF-003', '55553', 'Usuario Enfermería 3', 2, 2, 1),
(10, '304', 'USR-ENF-004', '55554', 'Usuario Enfermería 4', 2, 2, 1),
(11, '103', 'JEF-003', '666666', 'Jefe de Emergencias', 1, 3, 1),
(12, '401', 'USR-EMG-001', '77771', 'Usuario Emergencias 1', 2, 3, 1),
(13, '402', 'USR-EMG-002', '77772', 'Usuario Emergencias 2', 2, 3, 1),
(14, '403', 'USR-EMG-003', '77773', 'Usuario Emergencias 3', 2, 3, 1),
(15, '404', 'USR-EMG-004', '77774', 'Usuario Emergencias 4', 2, 3, 1),
(16, '901', 'USR-NUEVO-001', '88881', 'Empleado Nuevo 1', 2, 1, 1),
(17, '902', 'USR-NUEVO-002', '88882', 'Empleado Nuevo 2', 2, 3, 1),
(18, '903', 'USR-NUEVO-003', '88883', 'Empleado Nuevo 3', 2, NULL, 1),
(19, '904', 'USR-NUEVO-004', '88884', 'Empleado Nuevo 4', 2, NULL, 1),
(20, NULL, 'JEF-004', NULL, 'Jefe 4', 4, 4, 1),
(21, NULL, 'JEF-005', NULL, 'Jefe 5', 4, NULL, 1)
ON DUPLICATE KEY UPDATE `nombre_completo` = VALUES(`nombre_completo`);

-- 7. Cuentas de usuarios para el acceso web
INSERT INTO `usuarios` (`id_usuario`, `nombre_usuario`, `correo`, `password_hash`, `rol`, `id_empleado`) VALUES
(1, 'admin', 'admin@hospital.com', '$2y$12$TxklhOhlgIlyxfUPFBpADudNZVriJ6M0mH/rtClyYLkalUMP2gFP6', 'Administrador', 1),
(2, 'jefeti', 'jefeti@hospital.com', '$2y$12$mUJu4Q2Xb5wT8a/Y59RO0./zekxNba9rDIb4KPXq0X7xhU4CWVv1e', 'Jefe de Area', 2),
(3, 'jefeenf', 'jefeenf@hospital.com', '$2y$12$O2oUMH2dCVsfxcKbDxTQXufThxPZjugbQj4nhA9lWi453JCyaYWZG', 'Jefe de Area', 3),
(4, 'userti1', 'user_ti_1@hospital.com', '$2y$12$DMlxfET6/i56IPauZEJage9I10s0.bIPwgXKujXUILJu0j7h717fG', 'Operador', 4),
(5, 'userti2', 'user_ti_2@hospital.com', '$2y$12$sbT.nYIJCNo2KXB/4oGFg.1H0zLrqcqUHt9fZNbuyY2M/p/nXnTPC', 'Operador', 5),
(6, 'userti3', 'user_ti_3@hospital.com', '$2y$12$yUG92Fg0v3pzNPfyUb/Fg.DKD1UwUkD9ZImgh.Eh2e2qbOAemg4Ca', 'Operador', 6),
(7, 'userenf1', 'user_enf_1@hospital.com', '$2y$12$pMXQyr.akzWKEKN3x8uL0ubQraflaJK0sj59JAvgCcJhfe5Rjocwe', 'Operador', 7),
(8, 'userenf2', 'user_enf_2@hospital.com', '$2y$12$F8bktLexdWgfhk3ro1nwyOOwhmxmsbBJjM9KUBbwEyy1Uo.a.2UTC', 'Operador', 8),
(9, 'userenf3', 'user_enf_3@hospital.com', '$2y$12$t.UV0ytO8x68YeNN6DqXXu/VVvSX0IwmErhzSsR8QC3oNFjx3F/02', 'Operador', 9),
(10, 'userenf4', 'user_enf_4@hospital.com', '$2y$12$.IbSlVCw1Jkt66x.Po4wpuM9.S8QuKWDHSapgkpL52nA6CUSoNNki', 'Operador', 10),
(11, 'jefeemerg', 'jefeemerg@hospital.com', '$2y$12$y/06GGZMVgSvL.IQCe7kruPYkJrnyl/AsrCvEap5R57bvlBM/flea', 'Jefe de Area', 11),
(12, 'useremg1', 'user_emg_1@hospital.com', '$2y$12$Mc4RuCw.Gk8lBRE/fbO8yOYuOYnUA4uErBh5iW51/ATxdEZa8Mi2q', 'Operador', 12),
(13, 'useremg2', 'user_emg_2@hospital.com', '$2y$12$qCGSKflFLtTlmDtTmFhplebfz6QpviKb1C.AiEf6f5Gfo0hD8ZZA.', 'Operador', 13),
(14, 'useremg3', 'user_emg_3@hospital.com', '$2y$12$VmEkJMoAsOXh9U2Zt96Kh.POr6xL.L.BO.UFYPoN9jaC4pQTxiYXO', 'Operador', 14),
(15, 'useremg4', 'user_emg_4@hospital.com', '$2y$12$v4JjnnkiWfRsCdTrFEXqveZUt5G4DqJeun5eYt/fbN9lzq8y3DJ/i', 'Operador', 15),
(16, 'nuevo1', 'nuevo_1@hospital.com', '$2y$12$5e6Ak38iA6Tb.cRS5x6I1.U2ldodKFch/wmF/pUeMiemhMGMWT4pO', 'Operador', 16),
(17, 'nuevo2', 'nuevo_2@hospital.com', '$2y$12$.xo4i84ns0Hp.y399V7j6eyHWTEIDuOG70SJ2Zc1xVu7hiPnS.fuS', 'Operador', 17),
(18, 'nuevo3', 'nuevo_3@hospital.com', '$2y$12$VS7hMyO/N.2n9wvwFjFbXOsYo9blINnqDxtINyxXWS0NpTNKV4MdS', 'Operador', 18),
(19, 'nuevo4', 'nuevo_4@hospital.com', '$2y$12$1uea05OJTGjwCwQ2o2ahuuOeOmo5VW9BLBO55gB/qhEPZZcl380gm', 'Operador', 19),
(20, 'jefe4', 'jefe4@hospital.test', '$2y$12$gP1PztQNzQbwWkKYLn7hcetYYX4VIatSdmcQtKEdUd.0lOx3dkAna', 'Jefe de Area', 20),
(21, 'jefe5', 'jefe5@hospital.test', '$2y$12$NF4SJJEvsFWTHAhcSprkKuodX7sakc4D7bfSocO9/.3uIji8ujuim', 'Jefe de Area', 21)
ON DUPLICATE KEY UPDATE `correo` = VALUES(`correo`);

-- =====================================================================================
-- CONSULTAS SQL DE EJEMPLO PARA EL CONTROL DE TURNOS
-- =====================================================================================

-- 1. CÁLCULO DE TOTAL DE HORAS MENSUALES TRABAJADAS POR CADA EMPLEADO:
-- SELECT 
--     e.codigo_biometrico,
--     e.nombre_completo,
--     d.nombre_departmento,
--     SUM(t.horas_computables) AS total_horas_mes
-- FROM empleados e
-- JOIN asignacion_turnos_diarios a ON e.id_empleado = a.id_empleado
-- JOIN turnos t ON a.id_turno = t.id_turno
-- LEFT JOIN departamentos_servicios d ON e.id_departamento = d.id_departamento
-- WHERE a.fecha BETWEEN '2026-10-01' AND '2026-10-31'
-- GROUP BY e.id_empleado
-- ORDER BY total_horas_mes DESC;

-- 2. VERIFICACIÓN DE PERSONAL DE TURNO DE NOCHE HOY:
-- SELECT 
--     e.codigo_biometrico,
--     e.nombre_completo,
--     d.nombre_departmento
-- FROM asignacion_turnos_diarios a
-- JOIN empleados e ON a.id_empleado = e.id_empleado
-- JOIN turnos t ON a.id_turno = t.id_turno
-- LEFT JOIN departamentos_servicios d ON e.id_departamento = d.id_departamento
-- WHERE a.fecha = CURDATE() AND t.codigo_turno = 'N';

-- 3. PERMUTAS O REEMPLAZOS PENDIENTES DE APROBACIÓN POR JEFATURA:
-- SELECT 
--     c.id_cambio,
--     es.nombre_completo AS titular,
--     er.nombre_completo AS reemplazo,
--     c.fecha_turno,
--     t_orig.nombre_turno AS turno_original,
--     t_nuevo.nombre_turno AS turno_cubierto,
--     c.motivo
-- FROM cambios_turnos c
-- JOIN empleados es ON c.id_empleado_solicitante = es.id_empleado
-- JOIN empleados er ON c.id_empleado_reemplazo = er.id_empleado
-- JOIN turnos t_orig ON c.id_turno_original = t_orig.id_turno
-- JOIN turnos t_nuevo ON c.id_turno_nuevo = t_nuevo.id_turno
-- WHERE c.estado = 'Pendiente';
