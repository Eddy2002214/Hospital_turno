-- 2. CREACIÓN DE TABLAS MAESTRAS (CATÁLOGOS)
CREATE TABLE `Cargos` (
  `id_cargo` INT PRIMARY KEY AUTO_INCREMENT,
  `nombre_cargo` VARCHAR(150) NOT NULL
);

CREATE TABLE `Departamentos_Servicios` (
  `id_departamento` INT PRIMARY KEY AUTO_INCREMENT,
  `nombre_departmento` VARCHAR(150) NOT NULL
);

CREATE TABLE `Fuentes_Financiamiento` (
  `id_fuente` INT PRIMARY KEY AUTO_INCREMENT,
  `codigo_fuente` VARCHAR(50) NOT NULL,
  `descripcion` VARCHAR(255) NULL
);

CREATE TABLE `Turnos` (
  `id_turno` INT PRIMARY KEY AUTO_INCREMENT,
  `codigo_turno` CHAR(2) NOT NULL,
  `nombre_turno` VARCHAR(100) NOT NULL,
  `hora_entrada` TIME NULL,
  `hora_salida` TIME NULL
);

-- 3. CREACIÓN DE TABLAS DE ENTIDADES
CREATE TABLE `Empleados` (
  `id_empleado` INT PRIMARY KEY AUTO_INCREMENT,
  `item_id` VARCHAR(50) NULL,
  `codigo_biometrico` VARCHAR(100) NOT NULL UNIQUE,
  `telefono` VARCHAR(50) NULL,
  `nombre_completo` VARCHAR(255) NOT NULL,
  `id_cargo` INT,
  `id_departamento` INT,
  FOREIGN KEY (`id_cargo`) REFERENCES `Cargos`(`id_cargo`) ON DELETE SET NULL,
  FOREIGN KEY (`id_departamento`) REFERENCES `Departamentos_Servicios`(`id_departamento`) ON DELETE SET NULL
);

CREATE TABLE `Usuarios` (
  `id_usuario` INT PRIMARY KEY AUTO_INCREMENT,
  `nombre_usuario` VARCHAR(100) NOT NULL UNIQUE,
  `correo` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol` VARCHAR(50) NOT NULL DEFAULT 'Operador',
  `id_empleado` INT NULL,
  FOREIGN KEY (`id_empleado`) REFERENCES `Empleados`(`id_empleado`) ON DELETE SET NULL
);

-- 4. GESTIÓN DEL CALENDARIO Y ROLES MENSUALES
CREATE TABLE `Roles_Mensuales` (
  `id_rol_mensual` INT PRIMARY KEY AUTO_INCREMENT,
  `mes` INT NOT NULL,
  `anio` INT NOT NULL,
  `id_departamento` INT,
  `fecha_recepcion` DATE NULL,
  FOREIGN KEY (`id_departamento`) REFERENCES `Departamentos_Servicios`(`id_departamento`) ON DELETE CASCADE
);

-- 5. MATRIZ CENTRAL (REGISTRO DÍA POR DÍA VERTICAL)
CREATE TABLE `Asignacion_Turnos_Diarios` (
  `id_asignacion` INT PRIMARY KEY AUTO_INCREMENT,
  `id_rol_mensual` INT NOT NULL,
  `id_empleado` INT NOT NULL,
  `id_fuente` INT,
  `fecha` DATE NOT NULL,
  `id_turno` INT NOT NULL,
  FOREIGN KEY (`id_rol_mensual`) REFERENCES `Roles_Mensuales`(`id_rol_mensual`) ON DELETE CASCADE,
  FOREIGN KEY (`id_empleado`) REFERENCES `Empleados`(`id_empleado`) ON DELETE CASCADE,
  FOREIGN KEY (`id_fuente`) REFERENCES `Fuentes_Financiamiento`(`id_fuente`) ON DELETE SET NULL,
  FOREIGN KEY (`id_turno`) REFERENCES `Turnos`(`id_turno`) ON DELETE RESTRICT
);
