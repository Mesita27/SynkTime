-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-06-2025 a las 08:48:48
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `synktime`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--

CREATE TABLE `asistencia` (
  `ID_ASISTENCIA` int(11) NOT NULL,
  `ID_EMPLEADO` int(11) NOT NULL,
  `FECHA` date NOT NULL,
  `TIPO` varchar(10) NOT NULL,
  `HORA` char(5) NOT NULL,
  `TARDANZA` char(1) DEFAULT 'N',
  `OBSERVACION` varchar(200) DEFAULT NULL,
  `REGISTRO_MANUAL` char(1) DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asistencia`
--

INSERT INTO `asistencia` (`ID_ASISTENCIA`, `ID_EMPLEADO`, `FECHA`, `TIPO`, `HORA`, `TARDANZA`, `OBSERVACION`, `REGISTRO_MANUAL`) VALUES
(1, 1, '2025-06-15', 'ENTRADA', '08:00', 'N', NULL, 'N'),
(2, 2, '2025-06-15', 'ENTRADA', '08:05', 'N', NULL, 'N'),
(3, 3, '2025-06-15', 'ENTRADA', '08:12', 'N', NULL, 'N'),
(4, 4, '2025-06-15', 'ENTRADA', '08:07', 'N', NULL, 'N'),
(5, 5, '2025-06-15', 'ENTRADA', '08:15', 'N', NULL, 'N'),
(6, 6, '2025-06-15', 'ENTRADA', '08:22', 'N', NULL, 'N'),
(7, 7, '2025-06-15', 'ENTRADA', '08:10', 'N', NULL, 'N'),
(8, 8, '2025-06-15', 'ENTRADA', '08:25', 'N', NULL, 'N'),
(9, 9, '2025-06-15', 'ENTRADA', '08:45', 'S', 'Tráfico intenso', 'N'),
(10, 1, '2025-06-15', 'SALIDA', '17:05', 'N', NULL, 'N'),
(11, 2, '2025-06-15', 'SALIDA', '17:12', 'N', NULL, 'N'),
(12, 3, '2025-06-15', 'SALIDA', '17:33', 'N', NULL, 'N'),
(13, 4, '2025-06-15', 'SALIDA', '18:02', 'N', NULL, 'N'),
(14, 5, '2025-06-15', 'SALIDA', '17:45', 'N', NULL, 'N'),
(15, 6, '2025-06-15', 'SALIDA', '18:15', 'N', NULL, 'N'),
(16, 7, '2025-06-15', 'SALIDA', '17:55', 'N', NULL, 'N'),
(17, 8, '2025-06-15', 'SALIDA', '18:30', 'N', NULL, 'N'),
(18, 9, '2025-06-15', 'SALIDA', '18:22', 'N', NULL, 'N'),
(19, 41, '2025-06-15', 'ENTRADA', '09:05', 'N', NULL, 'N'),
(20, 42, '2025-06-15', 'ENTRADA', '09:12', 'N', NULL, 'N'),
(21, 43, '2025-06-15', 'ENTRADA', '09:08', 'N', NULL, 'N'),
(22, 44, '2025-06-15', 'ENTRADA', '09:15', 'N', NULL, 'N'),
(23, 45, '2025-06-15', 'ENTRADA', '09:22', 'N', NULL, 'N'),
(24, 46, '2025-06-15', 'ENTRADA', '09:18', 'N', NULL, 'N'),
(25, 47, '2025-06-15', 'ENTRADA', '09:25', 'N', NULL, 'N'),
(26, 48, '2025-06-15', 'ENTRADA', '09:45', 'S', 'Problema con transporte público', 'N'),
(27, 49, '2025-06-15', 'ENTRADA', '10:15', 'S', 'Cita médica', 'N'),
(28, 41, '2025-06-15', 'SALIDA', '18:10', 'N', NULL, 'N'),
(29, 42, '2025-06-15', 'SALIDA', '18:25', 'N', NULL, 'N'),
(30, 43, '2025-06-15', 'SALIDA', '18:35', 'N', NULL, 'N'),
(31, 44, '2025-06-15', 'SALIDA', '18:45', 'N', NULL, 'N'),
(32, 45, '2025-06-15', 'SALIDA', '19:05', 'N', NULL, 'N'),
(33, 46, '2025-06-15', 'SALIDA', '18:55', 'N', NULL, 'N'),
(34, 47, '2025-06-15', 'SALIDA', '19:15', 'N', NULL, 'N'),
(35, 48, '2025-06-15', 'SALIDA', '19:20', 'N', NULL, 'N'),
(36, 49, '2025-06-15', 'SALIDA', '19:30', 'N', NULL, 'N'),
(37, 81, '2025-06-15', 'ENTRADA', '08:32', 'N', NULL, 'N'),
(38, 82, '2025-06-15', 'ENTRADA', '08:40', 'N', NULL, 'N'),
(39, 83, '2025-06-15', 'ENTRADA', '08:45', 'N', NULL, 'N'),
(40, 84, '2025-06-15', 'ENTRADA', '08:52', 'N', NULL, 'N'),
(41, 85, '2025-06-15', 'ENTRADA', '08:38', 'N', NULL, 'N'),
(42, 86, '2025-06-15', 'ENTRADA', '08:55', 'N', NULL, 'N'),
(43, 87, '2025-06-15', 'ENTRADA', '09:15', 'S', 'Reunión externa previa', 'N'),
(44, 88, '2025-06-15', 'ENTRADA', '09:25', 'S', 'Problemas familiares', 'N'),
(45, 89, '2025-06-15', 'ENTRADA', '09:45', 'S', 'Tráfico', 'N'),
(46, 81, '2025-06-15', 'SALIDA', '17:35', 'N', NULL, 'N'),
(47, 82, '2025-06-15', 'SALIDA', '17:45', 'N', NULL, 'N'),
(48, 83, '2025-06-15', 'SALIDA', '18:00', 'N', NULL, 'N'),
(49, 84, '2025-06-15', 'SALIDA', '18:15', 'N', NULL, 'N'),
(50, 85, '2025-06-15', 'SALIDA', '18:30', 'N', NULL, 'N'),
(51, 86, '2025-06-15', 'SALIDA', '18:10', 'N', NULL, 'N'),
(52, 87, '2025-06-15', 'SALIDA', '18:45', 'N', NULL, 'N'),
(53, 88, '2025-06-15', 'SALIDA', '18:55', 'N', NULL, 'N'),
(54, 89, '2025-06-15', 'SALIDA', '19:00', 'N', NULL, 'N'),
(55, 11, '2025-06-15', 'ENTRADA', '07:15', 'N', NULL, 'N'),
(56, 12, '2025-06-15', 'ENTRADA', '07:25', 'N', NULL, 'N'),
(57, 13, '2025-06-15', 'ENTRADA', '07:30', 'N', NULL, 'N'),
(58, 14, '2025-06-15', 'ENTRADA', '07:10', 'N', NULL, 'N'),
(59, 15, '2025-06-15', 'ENTRADA', '10:05', 'S', 'Permiso especial', 'N'),
(60, 16, '2025-06-15', 'ENTRADA', '10:15', 'S', 'Reunión externa previa', 'N'),
(61, 17, '2025-06-15', 'ENTRADA', '11:10', 'S', 'Horario especial autorizado', 'N'),
(62, 18, '2025-06-15', 'ENTRADA', '12:05', 'S', 'Medio tiempo', 'N'),
(63, 11, '2025-06-15', 'SALIDA', '16:30', 'N', NULL, 'N'),
(64, 12, '2025-06-15', 'SALIDA', '16:45', 'N', NULL, 'N'),
(65, 13, '2025-06-15', 'SALIDA', '16:55', 'N', NULL, 'N'),
(66, 14, '2025-06-15', 'SALIDA', '16:40', 'N', NULL, 'N'),
(67, 15, '2025-06-15', 'SALIDA', '19:15', 'N', NULL, 'N'),
(68, 16, '2025-06-15', 'SALIDA', '19:25', 'N', NULL, 'N'),
(69, 17, '2025-06-15', 'SALIDA', '20:10', 'N', NULL, 'N'),
(70, 18, '2025-06-15', 'SALIDA', '16:30', 'N', NULL, 'N');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dia_semana`
--

CREATE TABLE `dia_semana` (
  `ID_DIA` int(11) NOT NULL,
  `NOMBRE` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `dia_semana`
--

INSERT INTO `dia_semana` (`ID_DIA`, `NOMBRE`) VALUES
(7, 'Domingo'),
(4, 'Jueves'),
(1, 'Lunes'),
(2, 'Martes'),
(3, 'Miércoles'),
(6, 'Sábado'),
(5, 'Viernes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `ID_EMPLEADO` int(11) NOT NULL,
  `NOMBRE` varchar(100) NOT NULL,
  `APELLIDO` varchar(100) NOT NULL,
  `DNI` varchar(20) NOT NULL,
  `CORREO` varchar(100) DEFAULT NULL,
  `TELEFONO` varchar(20) DEFAULT NULL,
  `ID_ESTABLECIMIENTO` int(11) NOT NULL,
  `FECHA_INGRESO` date DEFAULT NULL,
  `ESTADO` char(1) DEFAULT 'A',
  `ACTIVO` char(1) DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`ID_EMPLEADO`, `NOMBRE`, `APELLIDO`, `DNI`, `CORREO`, `TELEFONO`, `ID_ESTABLECIMIENTO`, `FECHA_INGRESO`, `ESTADO`, `ACTIVO`) VALUES
(1, 'Juan', 'Pérez', '12345678', 'juan.perez@techsolutions.com', '987654321', 1, '2023-01-15', 'A', 'S'),
(2, 'María', 'García', '23456789', 'maria.garcia@techsolutions.com', '987654322', 1, '2023-02-10', 'A', 'S'),
(3, 'Carlos', 'López', '34567890', 'carlos.lopez@techsolutions.com', '987654323', 1, '2023-03-05', 'A', 'S'),
(4, 'Ana', 'Martínez', '45678901', 'ana.martinez@techsolutions.com', '987654324', 1, '2023-04-20', 'A', 'S'),
(5, 'Luis', 'Rodríguez', '56789012', 'luis.rodriguez@techsolutions.com', '987654325', 1, '2023-05-12', 'A', 'S'),
(6, 'Sofía', 'Hernández', '67890123', 'sofia.hernandez@techsolutions.com', '987654326', 1, '2023-06-08', 'A', 'S'),
(7, 'Diego', 'Torres', '78901234', 'diego.torres@techsolutions.com', '987654327', 1, '2023-07-15', 'A', 'S'),
(8, 'Valentina', 'Flores', '89012345', 'valentina.flores@techsolutions.com', '987654328', 1, '2023-08-22', 'A', 'S'),
(9, 'Gabriel', 'Rojas', '90123456', 'gabriel.rojas@techsolutions.com', '987654329', 1, '2023-09-30', 'A', 'S'),
(10, 'Camila', 'Vargas', '01234567', 'camila.vargas@techsolutions.com', '987654330', 1, '2023-10-15', 'A', 'S'),
(11, 'Jorge', 'Silva', '12345679', 'jorge.silva@techsolutions.com', '987654331', 2, '2023-01-20', 'A', 'S'),
(12, 'Lucía', 'Mendoza', '23456790', 'lucia.mendoza@techsolutions.com', '987654332', 2, '2023-02-15', 'A', 'S'),
(13, 'Ricardo', 'Gutiérrez', '34567891', 'ricardo.gutierrez@techsolutions.com', '987654333', 2, '2023-03-10', 'A', 'S'),
(14, 'Paula', 'Castro', '45678902', 'paula.castro@techsolutions.com', '987654334', 2, '2023-04-25', 'A', 'S'),
(15, 'Andrés', 'Díaz', '56789013', 'andres.diaz@techsolutions.com', '987654335', 2, '2023-05-18', 'A', 'S'),
(16, 'Daniela', 'Ruiz', '67890124', 'daniela.ruiz@techsolutions.com', '987654336', 2, '2023-06-12', 'A', 'S'),
(17, 'Sebastián', 'Morales', '78901235', 'sebastian.morales@techsolutions.com', '987654337', 2, '2023-07-20', 'A', 'S'),
(18, 'Valentina', 'Ortega', '89012346', 'valentina.ortega@techsolutions.com', '987654338', 2, '2023-08-28', 'A', 'S'),
(19, 'Mateo', 'Sánchez', '90123457', 'mateo.sanchez@techsolutions.com', '987654339', 2, '2023-09-05', 'A', 'S'),
(20, 'Isabella', 'Ramírez', '01234568', 'isabella.ramirez@techsolutions.com', '987654340', 2, '2023-10-20', 'A', 'S'),
(41, 'Martín', 'Ríos', '12345683', 'martin.rios@innovateperu.com', '987654371', 5, '2023-01-05', 'A', 'S'),
(42, 'Victoria', 'Acosta', '23456794', 'victoria.acosta@innovateperu.com', '987654372', 5, '2023-02-12', 'A', 'S'),
(43, 'Nicolás', 'Medina', '34567895', 'nicolas.medina@innovateperu.com', '987654373', 5, '2023-03-18', 'A', 'S'),
(44, 'Renata', 'Herrera', '45678906', 'renata.herrera@innovateperu.com', '987654374', 5, '2023-04-22', 'A', 'S'),
(45, 'Santiago', 'Suárez', '56789017', 'santiago.suarez@innovateperu.com', '987654375', 5, '2023-05-28', 'A', 'S'),
(46, 'Agustina', 'Pineda', '67890128', 'agustina.pineda@innovateperu.com', '987654376', 5, '2023-06-15', 'A', 'S'),
(47, 'Joaquín', 'Molina', '78901239', 'joaquin.molina@innovateperu.com', '987654377', 5, '2023-07-22', 'A', 'S'),
(48, 'Catalina', 'Ponce', '89012350', 'catalina.ponce@innovateperu.com', '987654378', 5, '2023-08-10', 'A', 'S'),
(49, 'Emilio', 'Cortés', '90123461', 'emilio.cortes@innovateperu.com', '987654379', 5, '2023-09-18', 'A', 'S'),
(50, 'Antonella', 'Navarro', '01234572', 'antonella.navarro@innovateperu.com', '987654380', 5, '2023-10-25', 'A', 'S'),
(81, 'Alejandro', 'Vega', '12345687', 'alejandro.vega@globalservices.com', '987654411', 9, '2023-01-10', 'A', 'S'),
(82, 'Romina', 'Campos', '23456798', 'romina.campos@globalservices.com', '987654412', 9, '2023-02-18', 'A', 'S'),
(83, 'Emmanuel', 'Guerra', '34567899', 'emmanuel.guerra@globalservices.com', '987654413', 9, '2023-03-25', 'A', 'S'),
(84, 'Constanza', 'Aguilar', '45678910', 'constanza.aguilar@globalservices.com', '987654414', 9, '2023-04-15', 'A', 'S'),
(85, 'Tomás', 'Peña', '56789021', 'tomas.pena@globalservices.com', '987654415', 9, '2023-05-22', 'A', 'S'),
(86, 'Francisca', 'Rivas', '67890132', 'francisca.rivas@globalservices.com', '987654416', 9, '2023-06-28', 'A', 'S'),
(87, 'Ignacio', 'Velasco', '78901243', 'ignacio.velasco@globalservices.com', '987654417', 9, '2023-07-12', 'A', 'S'),
(88, 'Josefina', 'Cárdenas', '89012354', 'josefina.cardenas@globalservices.com', '987654418', 9, '2023-08-18', 'A', 'S'),
(89, 'Felipe', 'Miranda', '90123465', 'felipe.miranda@globalservices.com', '987654419', 9, '2023-09-25', 'A', 'S'),
(90, 'Amanda', 'Escobar', '01234576', 'amanda.escobar@globalservices.com', '987654420', 9, '2023-10-10', 'A', 'S');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado_horario`
--

CREATE TABLE `empleado_horario` (
  `ID_EMPLEADO` int(11) NOT NULL,
  `ID_HORARIO` int(11) NOT NULL,
  `FECHA_DESDE` date NOT NULL,
  `FECHA_HASTA` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleado_horario`
--

INSERT INTO `empleado_horario` (`ID_EMPLEADO`, `ID_HORARIO`, `FECHA_DESDE`, `FECHA_HASTA`) VALUES
(1, 1, '2023-01-15', NULL),
(2, 1, '2023-02-10', NULL),
(3, 1, '2023-03-05', NULL),
(4, 1, '2023-04-20', NULL),
(5, 1, '2023-05-12', NULL),
(6, 1, '2023-06-08', NULL),
(7, 1, '2023-07-15', NULL),
(8, 1, '2023-08-22', NULL),
(9, 1, '2023-09-30', NULL),
(10, 1, '2023-10-15', NULL),
(11, 1, '2023-01-20', NULL),
(12, 1, '2023-02-15', NULL),
(13, 1, '2023-03-10', NULL),
(14, 1, '2023-04-25', NULL),
(15, 2, '2023-05-18', NULL),
(16, 3, '2023-06-12', NULL),
(17, 2, '2023-07-20', NULL),
(18, 3, '2023-08-28', NULL),
(20, 2, '2025-06-17', NULL),
(41, 4, '2023-01-05', NULL),
(42, 4, '2023-02-12', NULL),
(43, 4, '2023-03-18', NULL),
(44, 4, '2023-04-22', NULL),
(45, 4, '2023-05-28', NULL),
(46, 4, '2023-06-15', NULL),
(47, 4, '2023-07-22', NULL),
(48, 4, '2023-08-10', NULL),
(49, 4, '2023-09-18', NULL),
(50, 4, '2023-10-25', NULL),
(81, 5, '2023-01-10', NULL),
(82, 5, '2023-02-18', NULL),
(83, 5, '2023-03-25', NULL),
(84, 5, '2023-04-15', NULL),
(85, 5, '2023-05-22', NULL),
(86, 5, '2023-06-28', NULL),
(87, 5, '2023-07-12', NULL),
(88, 5, '2023-08-18', NULL),
(89, 5, '2023-09-25', NULL),
(90, 5, '2023-10-10', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa`
--

CREATE TABLE `empresa` (
  `ID_EMPRESA` int(11) NOT NULL,
  `NOMBRE` varchar(100) NOT NULL,
  `RUC` varchar(20) DEFAULT NULL,
  `DIRECCION` varchar(200) DEFAULT NULL,
  `ESTADO` char(1) DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresa`
--

INSERT INTO `empresa` (`ID_EMPRESA`, `NOMBRE`, `RUC`, `DIRECCION`, `ESTADO`) VALUES
(1, 'TechSolutions S.A.', '20123456789', 'Av. República 123, Lima', 'A'),
(2, 'InnovatePeru E.I.R.L.', '20987654321', 'Jr. Arequipa 456, Lima', 'A'),
(3, 'GlobalServices S.A.C.', '20567891234', 'Av. La Marina 789, Lima', 'A');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `establecimiento`
--

CREATE TABLE `establecimiento` (
  `ID_ESTABLECIMIENTO` int(11) NOT NULL,
  `NOMBRE` varchar(100) NOT NULL,
  `DIRECCION` varchar(200) DEFAULT NULL,
  `ID_SEDE` int(11) NOT NULL,
  `ESTADO` char(1) DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `establecimiento`
--

INSERT INTO `establecimiento` (`ID_ESTABLECIMIENTO`, `NOMBRE`, `DIRECCION`, `ID_SEDE`, `ESTADO`) VALUES
(1, 'Desarrollo de Software', 'Piso 1, Av. República 123', 1, 'A'),
(2, 'Área Administrativa', 'Piso 2, Av. República 123', 1, 'A'),
(3, 'Soporte Técnico', 'Módulo A, Av. Universitaria 567', 2, 'A'),
(4, 'Ventas Corporativas', 'Módulo B, Av. Universitaria 567', 2, 'A'),
(5, 'Investigación y Desarrollo', 'Piso 1, Jr. Arequipa 456', 3, 'A'),
(6, 'Marketing', 'Piso 2, Jr. Arequipa 456', 3, 'A'),
(7, 'Atención al Cliente', 'Local 101, Av. Benavides 789', 4, 'A'),
(8, 'Recursos Humanos', 'Local 102, Av. Benavides 789', 4, 'A'),
(9, 'Operaciones', 'Torre A, Av. La Marina 789', 5, 'A'),
(10, 'Finanzas', 'Torre B, Av. La Marina 789', 5, 'A'),
(11, 'Logística', 'Edificio 1, Av. Javier Prado 1234', 6, 'A'),
(12, 'Proyectos Especiales', 'Edificio 2, Av. Javier Prado 1234', 6, 'A');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario`
--

CREATE TABLE `horario` (
  `ID_HORARIO` int(11) NOT NULL,
  `ID_ESTABLECIMIENTO` int(11) NOT NULL,
  `NOMBRE` varchar(50) NOT NULL,
  `HORA_ENTRADA` char(5) NOT NULL,
  `HORA_SALIDA` char(5) NOT NULL,
  `TOLERANCIA` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horario`
--

INSERT INTO `horario` (`ID_HORARIO`, `ID_ESTABLECIMIENTO`, `NOMBRE`, `HORA_ENTRADA`, `HORA_SALIDA`, `TOLERANCIA`) VALUES
(1, 1, 'Horario Estándar', '08:00', '17:00', 15),
(2, 2, 'Horario Medio Día', '08:00', '13:00', 10),
(3, 3, 'Horario Tarde', '13:00', '22:00', 15),
(4, 4, 'Horario 9 a 6', '09:00', '18:00', 15),
(5, 5, 'Horario 8:30 a 5:30', '08:30', '17:30', 15);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario_dia`
--

CREATE TABLE `horario_dia` (
  `ID_HORARIO` int(11) NOT NULL,
  `ID_DIA` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horario_dia`
--

INSERT INTO `horario_dia` (`ID_HORARIO`, `ID_DIA`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(3, 2),
(3, 3),
(3, 7),
(4, 1),
(4, 2),
(4, 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `justificacion`
--

CREATE TABLE `justificacion` (
  `ID_JUSTIFICACION` int(11) NOT NULL,
  `ID_EMPLEADO` int(11) NOT NULL,
  `FECHA` date NOT NULL,
  `MOTIVO` varchar(200) DEFAULT NULL,
  `APROBADO` char(1) DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log`
--

CREATE TABLE `log` (
  `ID_LOG` int(11) NOT NULL,
  `ID_USUARIO` int(11) DEFAULT NULL,
  `FECHA_HORA` datetime DEFAULT current_timestamp(),
  `ACCION` varchar(100) DEFAULT NULL,
  `DETALLE` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `log`
--

INSERT INTO `log` (`ID_LOG`, `ID_USUARIO`, `FECHA_HORA`, `ACCION`, `DETALLE`) VALUES
(1, 4, '2025-06-15 22:30:00', 'LOGIN', 'Inicio de sesión exitoso: Mesita27'),
(2, 4, '2025-06-15 22:31:05', 'CONSULTA', 'Consulta de dashboard para empresa TechSolutions'),
(3, 4, '2025-06-15 22:32:15', 'ACCESO', 'Acceso a estadísticas de asistencia de Sede Central'),
(4, 4, '2025-06-15 22:33:40', 'CONSULTA', 'Consulta de empleados del área de Desarrollo de Software'),
(5, 4, '2025-06-15 22:35:00', 'ACCESO', 'Visualización de gráficos de asistencia'),
(6, 1, '2025-06-16 02:06:13', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(7, 1, '2025-06-16 02:11:13', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(8, 1, '2025-06-16 02:17:54', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(9, 1, '2025-06-16 02:18:41', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(10, 1, '2025-06-16 02:26:43', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(11, 3, '2025-06-16 02:27:02', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(12, 3, '2025-06-16 02:27:24', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(13, 1, '2025-06-16 02:27:38', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(14, 1, '2025-06-16 13:32:40', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(15, 1, '2025-06-16 13:32:51', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(16, 3, '2025-06-16 13:32:59', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(17, 3, '2025-06-16 13:33:18', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(18, 1, '2025-06-16 13:33:25', 'LOGIN_FAILED', 'Intento de login fallido - IP: ::1'),
(19, 1, '2025-06-16 13:33:32', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(20, 1, '2025-06-16 20:04:39', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(21, 1, '2025-06-16 20:54:23', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(22, 1, '2025-06-16 20:54:43', 'LOGIN_FAILED', 'Intento de login fallido - IP: ::1'),
(23, 1, '2025-06-16 20:54:49', 'LOGIN_FAILED', 'Intento de login fallido - IP: ::1'),
(24, 1, '2025-06-16 20:55:04', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(25, 1, '2025-06-16 21:32:34', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(26, 3, '2025-06-16 21:32:44', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(27, 3, '2025-06-16 21:41:08', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(28, 1, '2025-06-16 21:41:17', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(29, 1, '2025-06-16 23:25:51', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(30, 1, '2025-06-16 23:26:17', 'LOGIN_FAILED', 'Intento de login fallido - IP: ::1'),
(31, 1, '2025-06-16 23:26:30', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(32, 1, '2025-06-17 00:31:24', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(33, 1, '2025-06-17 00:31:49', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sede`
--

CREATE TABLE `sede` (
  `ID_SEDE` int(11) NOT NULL,
  `NOMBRE` varchar(100) NOT NULL,
  `DIRECCION` varchar(200) DEFAULT NULL,
  `ID_EMPRESA` int(11) NOT NULL,
  `ESTADO` char(1) DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sede`
--

INSERT INTO `sede` (`ID_SEDE`, `NOMBRE`, `DIRECCION`, `ID_EMPRESA`, `ESTADO`) VALUES
(1, 'Sede Central', 'Av. República 123, Lima', 1, 'A'),
(2, 'Sede Norte', 'Av. Universitaria 567, Los Olivos', 1, 'A'),
(3, 'Sede Principal', 'Jr. Arequipa 456, Lima', 2, 'A'),
(4, 'Sede Sur', 'Av. Benavides 789, Surco', 2, 'A'),
(5, 'Sede Corporativa', 'Av. La Marina 789, Lima', 3, 'A'),
(6, 'Sede Este', 'Av. Javier Prado 1234, La Molina', 3, 'A');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `ID_USUARIO` int(11) NOT NULL,
  `USERNAME` varchar(50) NOT NULL,
  `CONTRASENA` varchar(255) NOT NULL,
  `NOMBRE_COMPLETO` varchar(100) NOT NULL,
  `EMAIL` varchar(100) NOT NULL,
  `ROL` varchar(30) NOT NULL,
  `ID_EMPRESA` int(11) NOT NULL,
  `ESTADO` char(1) DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`ID_USUARIO`, `USERNAME`, `CONTRASENA`, `NOMBRE_COMPLETO`, `EMAIL`, `ROL`, `ID_EMPRESA`, `ESTADO`) VALUES
(1, 'admin_tech', '$2y$10$Arwap0w2XY1PEORkWKuQde5aHyvOa3DDusSjieVVHRFHp97EPKZ5y', 'Admin TechSolutions', 'admin@techsolutions.com', 'ADMINISTRADOR', 1, 'A'),
(2, 'admin_innovate', 'password_hash', 'Admin InnovatePeru', 'admin@innovateperu.com', 'ADMINISTRADOR', 2, 'A'),
(3, 'admin_global', '$2y$10$CpBgrcCQbUr.wEuqJ68sM.l3J627nxHjli1wVglF9v2Qgb6ab5DBa', 'Admin GlobalServices', 'admin@globalservices.com', 'ADMINISTRADOR', 3, 'A'),
(4, 'Mesita27', 'password_hash', 'Mesita User', 'mesita27@techsolutions.com', 'ADMINISTRADOR', 1, 'A');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_empleados_activos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_empleados_activos` (
`ID_EMPLEADO` int(11)
,`NOMBRE` varchar(100)
,`APELLIDO` varchar(100)
,`ACTIVO` char(1)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_empleados_activos`
--
DROP TABLE IF EXISTS `vw_empleados_activos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_empleados_activos`  AS SELECT `empleado`.`ID_EMPLEADO` AS `ID_EMPLEADO`, `empleado`.`NOMBRE` AS `NOMBRE`, `empleado`.`APELLIDO` AS `APELLIDO`, `empleado`.`ACTIVO` AS `ACTIVO` FROM `empleado` WHERE `empleado`.`ACTIVO` = 'S' ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`ID_ASISTENCIA`),
  ADD KEY `IDX_ASISTENCIA_EMPLEADO` (`ID_EMPLEADO`),
  ADD KEY `IDX_ASISTENCIA_FECHA` (`FECHA`);

--
-- Indices de la tabla `dia_semana`
--
ALTER TABLE `dia_semana`
  ADD PRIMARY KEY (`ID_DIA`),
  ADD UNIQUE KEY `NOMBRE` (`NOMBRE`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`ID_EMPLEADO`),
  ADD UNIQUE KEY `DNI` (`DNI`),
  ADD KEY `IDX_EMPLEADO_ESTABLECIMIENTO` (`ID_ESTABLECIMIENTO`),
  ADD KEY `IDX_EMPLEADO_ESTADO` (`ESTADO`),
  ADD KEY `IDX_EMPLEADO_ACTIVO` (`ACTIVO`);

--
-- Indices de la tabla `empleado_horario`
--
ALTER TABLE `empleado_horario`
  ADD PRIMARY KEY (`ID_EMPLEADO`,`ID_HORARIO`,`FECHA_DESDE`),
  ADD KEY `ID_HORARIO` (`ID_HORARIO`);

--
-- Indices de la tabla `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`ID_EMPRESA`),
  ADD UNIQUE KEY `NOMBRE` (`NOMBRE`),
  ADD KEY `IDX_EMPRESA_ESTADO` (`ESTADO`);

--
-- Indices de la tabla `establecimiento`
--
ALTER TABLE `establecimiento`
  ADD PRIMARY KEY (`ID_ESTABLECIMIENTO`),
  ADD KEY `IDX_ESTABLECIMIENTO_SEDE` (`ID_SEDE`);

--
-- Indices de la tabla `horario`
--
ALTER TABLE `horario`
  ADD PRIMARY KEY (`ID_HORARIO`),
  ADD KEY `IDX_HORARIO_ESTABLECIMIENTO` (`ID_ESTABLECIMIENTO`);

--
-- Indices de la tabla `horario_dia`
--
ALTER TABLE `horario_dia`
  ADD PRIMARY KEY (`ID_HORARIO`,`ID_DIA`),
  ADD KEY `ID_DIA` (`ID_DIA`);

--
-- Indices de la tabla `justificacion`
--
ALTER TABLE `justificacion`
  ADD PRIMARY KEY (`ID_JUSTIFICACION`),
  ADD KEY `IDX_JUSTIFICACION_EMPLEADO` (`ID_EMPLEADO`);

--
-- Indices de la tabla `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`ID_LOG`),
  ADD KEY `IDX_LOG_USUARIO` (`ID_USUARIO`);

--
-- Indices de la tabla `sede`
--
ALTER TABLE `sede`
  ADD PRIMARY KEY (`ID_SEDE`),
  ADD KEY `IDX_SEDE_EMPRESA` (`ID_EMPRESA`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`ID_USUARIO`),
  ADD UNIQUE KEY `USERNAME` (`USERNAME`),
  ADD KEY `IDX_USUARIO_EMPRESA` (`ID_EMPRESA`),
  ADD KEY `IDX_USUARIO_ESTADO` (`ESTADO`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `ID_ASISTENCIA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT de la tabla `dia_semana`
--
ALTER TABLE `dia_semana`
  MODIFY `ID_DIA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `ID_EMPLEADO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT de la tabla `empresa`
--
ALTER TABLE `empresa`
  MODIFY `ID_EMPRESA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `establecimiento`
--
ALTER TABLE `establecimiento`
  MODIFY `ID_ESTABLECIMIENTO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `horario`
--
ALTER TABLE `horario`
  MODIFY `ID_HORARIO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `justificacion`
--
ALTER TABLE `justificacion`
  MODIFY `ID_JUSTIFICACION` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `log`
--
ALTER TABLE `log`
  MODIFY `ID_LOG` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `sede`
--
ALTER TABLE `sede`
  MODIFY `ID_SEDE` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `ID_USUARIO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD CONSTRAINT `asistencia_ibfk_1` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado` (`ID_EMPLEADO`);

--
-- Filtros para la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD CONSTRAINT `empleado_ibfk_1` FOREIGN KEY (`ID_ESTABLECIMIENTO`) REFERENCES `establecimiento` (`ID_ESTABLECIMIENTO`);

--
-- Filtros para la tabla `empleado_horario`
--
ALTER TABLE `empleado_horario`
  ADD CONSTRAINT `empleado_horario_ibfk_1` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado` (`ID_EMPLEADO`),
  ADD CONSTRAINT `empleado_horario_ibfk_2` FOREIGN KEY (`ID_HORARIO`) REFERENCES `horario` (`ID_HORARIO`);

--
-- Filtros para la tabla `establecimiento`
--
ALTER TABLE `establecimiento`
  ADD CONSTRAINT `establecimiento_ibfk_1` FOREIGN KEY (`ID_SEDE`) REFERENCES `sede` (`ID_SEDE`);

--
-- Filtros para la tabla `horario_dia`
--
ALTER TABLE `horario_dia`
  ADD CONSTRAINT `horario_dia_ibfk_1` FOREIGN KEY (`ID_HORARIO`) REFERENCES `horario` (`ID_HORARIO`) ON DELETE CASCADE,
  ADD CONSTRAINT `horario_dia_ibfk_2` FOREIGN KEY (`ID_DIA`) REFERENCES `dia_semana` (`ID_DIA`) ON DELETE CASCADE;

--
-- Filtros para la tabla `justificacion`
--
ALTER TABLE `justificacion`
  ADD CONSTRAINT `justificacion_ibfk_1` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado` (`ID_EMPLEADO`);

--
-- Filtros para la tabla `log`
--
ALTER TABLE `log`
  ADD CONSTRAINT `log_ibfk_1` FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario` (`ID_USUARIO`);

--
-- Filtros para la tabla `sede`
--
ALTER TABLE `sede`
  ADD CONSTRAINT `sede_ibfk_1` FOREIGN KEY (`ID_EMPRESA`) REFERENCES `empresa` (`ID_EMPRESA`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`ID_EMPRESA`) REFERENCES `empresa` (`ID_EMPRESA`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
