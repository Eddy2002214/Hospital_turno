-- =====================================================
-- ESQUEMA DE BASE DE DATOS: control_turnos_hospital
-- Base de Datos limpia para Sistema de Control de Turnos
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Tabla: departamentos_servicios
-- -----------------------------------------------------
CREATE TABLE `departamentos_servicios` (
  `id_departamento` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del departamento o servicio',
  `nombre_departmento` varchar(150) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Nombre del servicio o unidad (ej. Pediatría, Enfermería)',
  PRIMARY KEY (`id_departamento`),
  UNIQUE KEY `nombre_departmento` (`nombre_departmento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------
-- Tabla: cargos
-- -----------------------------------------------------
CREATE TABLE `cargos` (
  `id_cargo` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del cargo',
  `nombre_cargo` varchar(150) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Nombre del cargo (ej. Jefe de Servicio, Operador, etc.)',
  PRIMARY KEY (`id_cargo`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------
-- Tabla: fuentes_financiamiento
-- -----------------------------------------------------
CREATE TABLE `fuentes_financiamiento` (
  `id_fuente` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la fuente de financiamiento',
  `codigo_fuente` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Acrónimo o código (ej. TGN, IDH, RP, GOB)',
  `descripcion` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL COMMENT 'Descripción detallada del origen presupuestario',
  PRIMARY KEY (`id_fuente`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------
-- Tabla: turnos
-- -----------------------------------------------------
CREATE TABLE `turnos` (
  `id_turno` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del turno',
  `codigo_turno` char(2) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Código corto mostrado en la planilla (M, M*, T, N, L)',
  `nombre_turno` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Nombre descriptivo (Mañana, Tarde, Noche, Libre)',
  `hora_entrada` time DEFAULT NULL COMMENT 'Hora límite de ingreso programado',
  `hora_salida` time DEFAULT NULL COMMENT 'Hora de salida programada',
  `horas_computables` decimal(4,2) NOT NULL DEFAULT '6.00' COMMENT 'Horas laborales que suma a la carga horaria mensual (ej. 6.00, 8.00, 12.00, 0.00)',
  `color_hex` varchar(20) COLLATE utf8mb4_spanish_ci DEFAULT '#0284c7' COMMENT 'Color hexadecimal para visualización en la interfaz y reportes',
  PRIMARY KEY (`id_turno`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------
-- Tabla: feriados
-- -----------------------------------------------------
CREATE TABLE `feriados` (
  `id_feriado` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del feriado',
  `fecha` date NOT NULL COMMENT 'Fecha del feriado (YYYY-MM-DD)',
  `descripcion` varchar(150) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Motivo (ej. Año Nuevo, Día del Trabajo, 24 de Septiembre)',
  `tipo` enum('Nacional','Departamental','Institucional') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Nacional' COMMENT 'Ámbito del feriado',
  PRIMARY KEY (`id_feriado`),
  UNIQUE KEY `fecha` (`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------
-- Tabla: empleados
-- -----------------------------------------------------
CREATE TABLE `empleados` (
  `id_empleado` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del empleado',
  `item_id` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL COMMENT 'Número de ítem presupuestario asignado',
  `codigo_biometrico` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Código único del reloj biométrico o tarjeta RFID',
  `ci` varchar(30) COLLATE utf8mb4_spanish_ci DEFAULT NULL COMMENT 'Cédula de identidad (CI) del funcionario',
  `telefono` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL COMMENT 'Teléfono o celular de contacto',
  `nombre_completo` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Nombres y apellidos completos',
  `id_cargo` int DEFAULT NULL COMMENT 'Cargo que desempeña',
  `id_departamento` int DEFAULT NULL COMMENT 'Departamento o servicio asignado (null = sin área/flotante)',
  `activo` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1: Funcionario activo; 0: De baja/retirado',
  `profesion_especialidad` varchar(150) COLLATE utf8mb4_spanish_ci DEFAULT NULL COMMENT 'Especialidad médica o grado académico',
  PRIMARY KEY (`id_empleado`),
  UNIQUE KEY `codigo_biometrico` (`codigo_biometrico`),
  KEY `id_cargo` (`id_cargo`),
  KEY `id_departamento` (`id_departamento`),
  CONSTRAINT `empleados_ibfk_1` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id_cargo`) ON DELETE SET NULL,
  CONSTRAINT `empleados_ibfk_2` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos_servicios` (`id_departamento`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1000000 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------
-- Tabla: usuarios
-- -----------------------------------------------------
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del usuario',
  `nombre_usuario` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Nombre de usuario para el login',
  `correo` varchar(150) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Correo electrónico institucional',
  `password_hash` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Contraseña cifrada con algoritmo bcrypt',
  `rol` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Operador' COMMENT 'Nivel de acceso: Administrador, Jefe de Area, Jefe de Servicio, Operador',
  `id_empleado` int DEFAULT NULL COMMENT 'Relación 1 a 1 con el perfil de empleado (opcional)',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------
-- Tabla: roles_mensuales
-- -----------------------------------------------------
CREATE TABLE `roles_mensuales` (
  `id_rol_mensual` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la planilla mensual',
  `mes` int NOT NULL COMMENT 'Mes del rol (1 = Enero ... 12 = Diciembre)',
  `anio` int NOT NULL COMMENT 'Año del rol (ej. 2026)',
  `id_departamento` int DEFAULT NULL COMMENT 'Departamento al que corresponde este rol (null = consolidado global)',
  `fecha_recepcion` date DEFAULT NULL COMMENT 'Fecha en que RRHH recibió el rol',
  `estado` enum('Borrador','Enviado','Aprobado','Rechazado','Cerrado') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Borrador' COMMENT 'Estado del flujo administrativo de aprobación',
  `aprobado_por` int DEFAULT NULL COMMENT 'Usuario de RRHH o Dirección que dio el visto bueno',
  `fecha_aprobacion` datetime DEFAULT NULL COMMENT 'Fecha y hora exacta de aprobación',
  `observaciones` text COLLATE utf8mb4_spanish_ci COMMENT 'Notas, correcciones solicitadas por RRHH o justificaciones',
  PRIMARY KEY (`id_rol_mensual`),
  KEY `id_departamento` (`id_departamento`),
  KEY `fk_roles_aprobador` (`aprobado_por`),
  CONSTRAINT `fk_roles_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL,
  CONSTRAINT `roles_mensuales_ibfk_1` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos_servicios` (`id_departamento`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------
-- Tabla: asignacion_turnos_diarios
-- -----------------------------------------------------
CREATE TABLE `asignacion_turnos_diarios` (
  `id_asignacion` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la asignación diaria',
  `id_rol_mensual` int NOT NULL COMMENT 'Rol mensual al que pertenece esta asignación',
  `id_empleado` int NOT NULL COMMENT 'Empleado que tiene el turno asignado',
  `id_fuente` int DEFAULT NULL COMMENT 'Fuente de financiamiento del ítem en este período (opcional)',
  `fecha` date NOT NULL COMMENT 'Fecha calendario del turno (YYYY-MM-DD)',
  `id_turno` int NOT NULL COMMENT 'Turno asignado (M, M*, T, N, L)',
  PRIMARY KEY (`id_asignacion`),
  KEY `id_rol_mensual` (`id_rol_mensual`),
  KEY `id_empleado` (`id_empleado`),
  KEY `id_fuente` (`id_fuente`),
  KEY `id_turno` (`id_turno`),
  KEY `idx_empleado_fecha` (`id_empleado`,`fecha`),
  CONSTRAINT `asignacion_turnos_diarios_ibfk_1` FOREIGN KEY (`id_rol_mensual`) REFERENCES `roles_mensuales` (`id_rol_mensual`) ON DELETE CASCADE,
  CONSTRAINT `asignacion_turnos_diarios_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE CASCADE,
  CONSTRAINT `asignacion_turnos_diarios_ibfk_3` FOREIGN KEY (`id_fuente`) REFERENCES `fuentes_financiamiento` (`id_fuente`) ON DELETE SET NULL,
  CONSTRAINT `asignacion_turnos_diarios_ibfk_4` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id_turno`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------
-- Tabla: cambios_turnos
-- -----------------------------------------------------
CREATE TABLE `cambios_turnos` (
  `id_cambio` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la solicitud de cambio',
  `id_empleado_solicitante` int NOT NULL COMMENT 'Empleado titular que no puede asistir',
  `id_empleado_reemplazo` int NOT NULL COMMENT 'Empleado colega que cubre el turno',
  `fecha_turno` date NOT NULL COMMENT 'Fecha en la que se realiza la permuta',
  `id_turno_original` int NOT NULL COMMENT 'Turno programado originalmente',
  `id_turno_nuevo` int NOT NULL COMMENT 'Turno acordado para la cobertura',
  `motivo` text COLLATE utf8mb4_spanish_ci COMMENT 'Razón de la permuta o solicitud',
  `estado` enum('Pendiente','Aprobado','Rechazado') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Pendiente' COMMENT 'Estado de la autorización por Jefatura',
  `aprobado_por` int DEFAULT NULL COMMENT 'Jefe de Servicio o Administrador que aprobó',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cambio`),
  KEY `id_empleado_solicitante` (`id_empleado_solicitante`),
  KEY `id_empleado_reemplazo` (`id_empleado_reemplazo`),
  KEY `id_turno_original` (`id_turno_original`),
  KEY `id_turno_nuevo` (`id_turno_nuevo`),
  KEY `aprobado_por` (`aprobado_por`),
  CONSTRAINT `fk_cambio_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL,
  CONSTRAINT `fk_cambio_reemplazo` FOREIGN KEY (`id_empleado_reemplazo`) REFERENCES `empleados` (`id_empleado`) ON DELETE CASCADE,
  CONSTRAINT `fk_cambio_solicitante` FOREIGN KEY (`id_empleado_solicitante`) REFERENCES `empleados` (`id_empleado`) ON DELETE CASCADE,
  CONSTRAINT `fk_cambio_turno_nuevo` FOREIGN KEY (`id_turno_nuevo`) REFERENCES `turnos` (`id_turno`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cambio_turno_orig` FOREIGN KEY (`id_turno_original`) REFERENCES `turnos` (`id_turno`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------
-- Tabla: licencias_vacaciones
-- -----------------------------------------------------
CREATE TABLE `licencias_vacaciones` (
  `id_licencia` int NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la licencia',
  `id_empleado` int NOT NULL COMMENT 'Empleado con licencia o vacación',
  `tipo_licencia` enum('Vacacion','Baja Medica','Permiso Especial','Comision','Otro') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Vacacion' COMMENT 'Clasificación del permiso',
  `fecha_inicio` date NOT NULL COMMENT 'Primer día de la licencia',
  `fecha_fin` date NOT NULL COMMENT 'Último día de la licencia',
  `motivo` text COLLATE utf8mb4_spanish_ci COMMENT 'Justificación o diagnóstico (si aplica)',
  `documento_respaldo` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL COMMENT 'Número o ruta de memorándum/boleta de baja médica',
  `estado` enum('Solicitado','Aprobado','Rechazado') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Aprobado' COMMENT 'Estado del trámite en RRHH',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_licencia`),
  KEY `id_empleado` (`id_empleado`),
  KEY `idx_licencia_fechas` (`fecha_inicio`,`fecha_fin`),
  CONSTRAINT `fk_licencias_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

SET FOREIGN_KEY_CHECKS = 1;
