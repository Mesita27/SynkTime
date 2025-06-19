-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 19-06-2025 a las 17:43:08
-- Versión del servidor: 8.0.42-0ubuntu0.24.04.1
-- Versión de PHP: 8.2.28

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
CREATE DATABASE IF NOT EXISTS `synktime` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `synktime`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ASISTENCIA`
--

CREATE TABLE `ASISTENCIA` (
  `ID_ASISTENCIA` int NOT NULL,
  `ID_EMPLEADO` int NOT NULL,
  `FECHA` date NOT NULL,
  `TIPO` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `HORA` char(5) COLLATE utf8mb4_general_ci NOT NULL,
  `TARDANZA` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `OBSERVACION` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `FOTO` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `REGISTRO_MANUAL` char(1) COLLATE utf8mb4_general_ci DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ASISTENCIA`
--

INSERT INTO `ASISTENCIA` (`ID_ASISTENCIA`, `ID_EMPLEADO`, `FECHA`, `TIPO`, `HORA`, `TARDANZA`, `OBSERVACION`, `FOTO`, `REGISTRO_MANUAL`) VALUES
(1, 1, '2025-06-15', 'ENTRADA', '08:00', 'N', NULL, NULL, 'N'),
(2, 2, '2025-06-15', 'ENTRADA', '08:05', 'N', NULL, NULL, 'N'),
(3, 3, '2025-06-15', 'ENTRADA', '08:12', 'N', NULL, NULL, 'N'),
(4, 4, '2025-06-15', 'ENTRADA', '08:07', 'N', NULL, NULL, 'N'),
(5, 5, '2025-06-15', 'ENTRADA', '08:15', 'N', NULL, NULL, 'N'),
(6, 6, '2025-06-15', 'ENTRADA', '08:22', 'N', NULL, NULL, 'N'),
(7, 7, '2025-06-15', 'ENTRADA', '08:10', 'N', NULL, NULL, 'N'),
(8, 8, '2025-06-15', 'ENTRADA', '08:25', 'N', NULL, NULL, 'N'),
(9, 9, '2025-06-15', 'ENTRADA', '08:45', 'S', 'Tráfico intenso', NULL, 'N'),
(10, 1, '2025-06-15', 'SALIDA', '17:05', 'N', NULL, NULL, 'N'),
(11, 2, '2025-06-15', 'SALIDA', '17:12', 'N', NULL, NULL, 'N'),
(12, 3, '2025-06-15', 'SALIDA', '17:33', 'N', NULL, NULL, 'N'),
(13, 4, '2025-06-15', 'SALIDA', '18:02', 'N', NULL, NULL, 'N'),
(14, 5, '2025-06-15', 'SALIDA', '17:45', 'N', NULL, NULL, 'N'),
(15, 6, '2025-06-15', 'SALIDA', '18:15', 'N', NULL, NULL, 'N'),
(16, 7, '2025-06-15', 'SALIDA', '17:55', 'N', NULL, NULL, 'N'),
(17, 8, '2025-06-15', 'SALIDA', '18:30', 'N', NULL, NULL, 'N'),
(18, 9, '2025-06-15', 'SALIDA', '18:22', 'N', NULL, NULL, 'N'),
(19, 41, '2025-06-15', 'ENTRADA', '09:05', 'N', NULL, NULL, 'N'),
(20, 42, '2025-06-15', 'ENTRADA', '09:12', 'N', NULL, NULL, 'N'),
(21, 43, '2025-06-15', 'ENTRADA', '09:08', 'N', NULL, NULL, 'N'),
(22, 44, '2025-06-15', 'ENTRADA', '09:15', 'N', NULL, NULL, 'N'),
(23, 45, '2025-06-15', 'ENTRADA', '09:22', 'N', NULL, NULL, 'N'),
(24, 46, '2025-06-15', 'ENTRADA', '09:18', 'N', NULL, NULL, 'N'),
(25, 47, '2025-06-15', 'ENTRADA', '09:25', 'N', NULL, NULL, 'N'),
(26, 48, '2025-06-15', 'ENTRADA', '09:45', 'S', 'Problema con transporte público', NULL, 'N'),
(27, 49, '2025-06-15', 'ENTRADA', '10:15', 'S', 'Cita médica', NULL, 'N'),
(28, 41, '2025-06-15', 'SALIDA', '18:10', 'N', NULL, NULL, 'N'),
(29, 42, '2025-06-15', 'SALIDA', '18:25', 'N', NULL, NULL, 'N'),
(30, 43, '2025-06-15', 'SALIDA', '18:35', 'N', NULL, NULL, 'N'),
(31, 44, '2025-06-15', 'SALIDA', '18:45', 'N', NULL, NULL, 'N'),
(32, 45, '2025-06-15', 'SALIDA', '19:05', 'N', NULL, NULL, 'N'),
(33, 46, '2025-06-15', 'SALIDA', '18:55', 'N', NULL, NULL, 'N'),
(34, 47, '2025-06-15', 'SALIDA', '19:15', 'N', NULL, NULL, 'N'),
(35, 48, '2025-06-15', 'SALIDA', '19:20', 'N', NULL, NULL, 'N'),
(36, 49, '2025-06-15', 'SALIDA', '19:30', 'N', NULL, NULL, 'N'),
(37, 81, '2025-06-15', 'ENTRADA', '08:32', 'N', NULL, NULL, 'N'),
(38, 82, '2025-06-15', 'ENTRADA', '08:40', 'N', NULL, NULL, 'N'),
(39, 83, '2025-06-15', 'ENTRADA', '08:45', 'N', NULL, NULL, 'N'),
(40, 84, '2025-06-15', 'ENTRADA', '08:52', 'N', NULL, NULL, 'N'),
(41, 85, '2025-06-15', 'ENTRADA', '08:38', 'N', NULL, NULL, 'N'),
(42, 86, '2025-06-15', 'ENTRADA', '08:55', 'N', NULL, NULL, 'N'),
(43, 87, '2025-06-15', 'ENTRADA', '09:15', 'S', 'Reunión externa previa', NULL, 'N'),
(44, 88, '2025-06-15', 'ENTRADA', '09:25', 'S', 'Problemas familiares', NULL, 'N'),
(45, 89, '2025-06-15', 'ENTRADA', '09:45', 'S', 'Tráfico', NULL, 'N'),
(46, 81, '2025-06-15', 'SALIDA', '17:35', 'N', NULL, NULL, 'N'),
(47, 82, '2025-06-15', 'SALIDA', '17:45', 'N', NULL, NULL, 'N'),
(48, 83, '2025-06-15', 'SALIDA', '18:00', 'N', NULL, NULL, 'N'),
(49, 84, '2025-06-15', 'SALIDA', '18:15', 'N', NULL, NULL, 'N'),
(50, 85, '2025-06-15', 'SALIDA', '18:30', 'N', NULL, NULL, 'N'),
(51, 86, '2025-06-15', 'SALIDA', '18:10', 'N', NULL, NULL, 'N'),
(52, 87, '2025-06-15', 'SALIDA', '18:45', 'N', NULL, NULL, 'N'),
(53, 88, '2025-06-15', 'SALIDA', '18:55', 'N', NULL, NULL, 'N'),
(54, 89, '2025-06-15', 'SALIDA', '19:00', 'N', NULL, NULL, 'N'),
(55, 11, '2025-06-15', 'ENTRADA', '07:15', 'N', NULL, NULL, 'N'),
(56, 12, '2025-06-15', 'ENTRADA', '07:25', 'N', NULL, NULL, 'N'),
(57, 13, '2025-06-15', 'ENTRADA', '07:30', 'N', NULL, NULL, 'N'),
(58, 14, '2025-06-15', 'ENTRADA', '07:10', 'N', NULL, NULL, 'N'),
(59, 15, '2025-06-15', 'ENTRADA', '10:05', 'S', 'Permiso especial', NULL, 'N'),
(60, 16, '2025-06-15', 'ENTRADA', '10:15', 'S', 'Reunión externa previa', NULL, 'N'),
(61, 17, '2025-06-15', 'ENTRADA', '11:10', 'S', 'Horario especial autorizado', NULL, 'N'),
(62, 18, '2025-06-15', 'ENTRADA', '12:05', 'S', 'Medio tiempo', NULL, 'N'),
(63, 11, '2025-06-15', 'SALIDA', '16:30', 'N', NULL, NULL, 'N'),
(64, 12, '2025-06-15', 'SALIDA', '16:45', 'N', NULL, NULL, 'N'),
(66, 14, '2025-06-15', 'SALIDA', '16:40', 'N', NULL, NULL, 'N'),
(67, 15, '2025-06-15', 'SALIDA', '19:15', 'N', NULL, NULL, 'N'),
(68, 16, '2025-06-15', 'SALIDA', '19:25', 'N', NULL, NULL, 'N'),
(69, 17, '2025-06-15', 'SALIDA', '20:10', 'N', NULL, NULL, 'N'),
(70, 18, '2025-06-15', 'SALIDA', '16:30', 'N', NULL, NULL, 'N'),
(142, 11, '2025-06-18', 'SALIDA', '04:48', 'N', NULL, NULL, 'N'),
(146, 17, '2025-06-19', 'ENTRADA', '17:22', 'S', NULL, 'entrada_17_20250619_172250.jpg', 'N'),
(147, 17, '2025-06-19', 'SALIDA', '17:39', 'S', NULL, NULL, 'N');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `DIA_SEMANA`
--

CREATE TABLE `DIA_SEMANA` (
  `ID_DIA` int NOT NULL,
  `NOMBRE` varchar(15) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `DIA_SEMANA`
--

INSERT INTO `DIA_SEMANA` (`ID_DIA`, `NOMBRE`) VALUES
(7, 'Domingo'),
(4, 'Jueves'),
(1, 'Lunes'),
(2, 'Martes'),
(3, 'Miércoles'),
(6, 'Sábado'),
(5, 'Viernes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `EMPLEADO`
--

CREATE TABLE `EMPLEADO` (
  `ID_EMPLEADO` int NOT NULL,
  `NOMBRE` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `APELLIDO` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `DNI` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `CORREO` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `TELEFONO` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ID_ESTABLECIMIENTO` int NOT NULL,
  `FECHA_INGRESO` date DEFAULT NULL,
  `ESTADO` char(1) COLLATE utf8mb4_general_ci DEFAULT 'A',
  `ACTIVO` char(1) COLLATE utf8mb4_general_ci DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `EMPLEADO`
--

INSERT INTO `EMPLEADO` (`ID_EMPLEADO`, `NOMBRE`, `APELLIDO`, `DNI`, `CORREO`, `TELEFONO`, `ID_ESTABLECIMIENTO`, `FECHA_INGRESO`, `ESTADO`, `ACTIVO`) VALUES
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
(90, 'Amanda', 'Escobar', '01234576', 'amanda.escobar@globalservices.com', '987654420', 9, '2023-10-10', 'A', 'S'),
(100, 'Cristian', 'Meza', '1142917010', 'cm417196@gmail.com', '3042844477', 3, '2025-06-10', 'A', 'S'),
(102, 'Julio', 'Rodriguez Pinedo', '1066269543', 'jRodriguez2@gmail.com', '3004192177', 2, '2025-06-16', 'A', 'S');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `EMPLEADO_HORARIO`
--

CREATE TABLE `EMPLEADO_HORARIO` (
  `ID_EMPLEADO` int NOT NULL,
  `ID_HORARIO` int NOT NULL,
  `FECHA_DESDE` date NOT NULL,
  `FECHA_HASTA` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `EMPLEADO_HORARIO`
--

INSERT INTO `EMPLEADO_HORARIO` (`ID_EMPLEADO`, `ID_HORARIO`, `FECHA_DESDE`, `FECHA_HASTA`) VALUES
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
(90, 5, '2023-10-10', NULL),
(100, 9, '2025-06-17', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `EMPRESA`
--

CREATE TABLE `EMPRESA` (
  `ID_EMPRESA` int NOT NULL,
  `NOMBRE` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `RUC` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `DIRECCION` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ESTADO` char(1) COLLATE utf8mb4_general_ci DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `EMPRESA`
--

INSERT INTO `EMPRESA` (`ID_EMPRESA`, `NOMBRE`, `RUC`, `DIRECCION`, `ESTADO`) VALUES
(1, 'TechSolutions S.A.', '20123456789', 'Av. República 123, Lima', 'A'),
(2, 'InnovatePeru E.I.R.L.', '20987654321', 'Jr. Arequipa 456, Lima', 'A'),
(3, 'GlobalServices S.A.C.', '20567891234', 'Av. La Marina 789, Lima', 'A');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ESTABLECIMIENTO`
--

CREATE TABLE `ESTABLECIMIENTO` (
  `ID_ESTABLECIMIENTO` int NOT NULL,
  `NOMBRE` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `DIRECCION` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ID_SEDE` int NOT NULL,
  `ESTADO` char(1) COLLATE utf8mb4_general_ci DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ESTABLECIMIENTO`
--

INSERT INTO `ESTABLECIMIENTO` (`ID_ESTABLECIMIENTO`, `NOMBRE`, `DIRECCION`, `ID_SEDE`, `ESTADO`) VALUES
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
-- Estructura de tabla para la tabla `HORARIO`
--

CREATE TABLE `HORARIO` (
  `ID_HORARIO` int NOT NULL,
  `ID_ESTABLECIMIENTO` int NOT NULL,
  `NOMBRE` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `HORA_ENTRADA` char(5) COLLATE utf8mb4_general_ci NOT NULL,
  `HORA_SALIDA` char(5) COLLATE utf8mb4_general_ci NOT NULL,
  `TOLERANCIA` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `HORARIO`
--

INSERT INTO `HORARIO` (`ID_HORARIO`, `ID_ESTABLECIMIENTO`, `NOMBRE`, `HORA_ENTRADA`, `HORA_SALIDA`, `TOLERANCIA`) VALUES
(1, 1, 'Horario Estándar', '08:00', '17:00', 15),
(2, 2, 'Horario Medio Día', '08:00', '13:00', 10),
(3, 3, 'Horario Tarde', '13:00', '22:00', 15),
(4, 4, 'Horario 9 a 6', '09:00', '18:00', 15),
(5, 5, 'Horario 8:30 a 5:30', '08:30', '17:30', 15),
(9, 3, 'PRUEBA', '04:57', '05:00', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `HORARIO_DIA`
--

CREATE TABLE `HORARIO_DIA` (
  `ID_HORARIO` int NOT NULL,
  `ID_DIA` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `HORARIO_DIA`
--

INSERT INTO `HORARIO_DIA` (`ID_HORARIO`, `ID_DIA`) VALUES
(1, 1),
(2, 1),
(4, 1),
(9, 1),
(1, 2),
(2, 2),
(3, 2),
(4, 2),
(9, 2),
(1, 3),
(2, 3),
(3, 3),
(9, 3),
(1, 4),
(2, 4),
(9, 4),
(1, 5),
(2, 5),
(9, 5),
(9, 6),
(3, 7),
(4, 7),
(9, 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `JUSTIFICACION`
--

CREATE TABLE `JUSTIFICACION` (
  `ID_JUSTIFICACION` int NOT NULL,
  `ID_EMPLEADO` int NOT NULL,
  `FECHA` date NOT NULL,
  `MOTIVO` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `APROBADO` char(1) COLLATE utf8mb4_general_ci DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `LOG`
--

CREATE TABLE `LOG` (
  `ID_LOG` int NOT NULL,
  `ID_USUARIO` int DEFAULT NULL,
  `FECHA_HORA` datetime DEFAULT CURRENT_TIMESTAMP,
  `ACCION` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `DETALLE` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `LOG`
--

INSERT INTO `LOG` (`ID_LOG`, `ID_USUARIO`, `FECHA_HORA`, `ACCION`, `DETALLE`) VALUES
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
(33, 1, '2025-06-17 00:31:49', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(34, 1, '2025-06-17 04:39:42', 'LOGOUT', 'Cierre de sesión - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(35, 1, '2025-06-17 04:41:12', 'LOGIN', 'Inicio de sesión exitoso - IP: ::1 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(36, 1, '2025-06-17 11:17:27', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(37, 1, '2025-06-17 11:51:37', 'LOGOUT', 'Cierre de sesión - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(38, 1, '2025-06-17 11:51:59', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(39, 1, '2025-06-17 13:29:41', 'LOGOUT', 'Cierre de sesión - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(40, 4, '2025-06-17 13:29:49', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(41, 4, '2025-06-17 13:32:57', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(42, 4, '2025-06-17 14:02:34', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.223.203 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(43, 4, '2025-06-17 14:03:26', 'LOGOUT', 'Cierre de sesión - IP: 190.131.223.203 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(44, 2, '2025-06-17 14:03:55', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.223.203 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(45, 2, '2025-06-17 14:04:16', 'LOGOUT', 'Cierre de sesión - IP: 190.131.223.203 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(46, 4, '2025-06-17 14:04:26', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.223.203 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(47, 4, '2025-06-17 14:06:15', 'LOGOUT', 'Cierre de sesión - IP: 190.131.223.203 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(48, 4, '2025-06-17 14:07:47', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(49, 1, '2025-06-17 14:27:35', 'LOGIN_FAILED', 'Intento de login fallido - IP: 190.131.206.210'),
(50, 1, '2025-06-17 14:27:42', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(51, 1, '2025-06-17 14:31:09', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(52, 2, '2025-06-17 14:31:27', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(53, 2, '2025-06-17 14:31:46', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(54, 3, '2025-06-17 14:31:59', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(55, 3, '2025-06-17 14:33:03', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(56, 4, '2025-06-17 14:34:45', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(57, 4, '2025-06-17 14:40:53', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: PostmanRuntime/7.44.0'),
(58, 4, '2025-06-17 14:44:45', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(59, 4, '2025-06-17 14:45:06', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(60, 4, '2025-06-17 14:53:30', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(61, 4, '2025-06-17 14:53:48', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: PostmanRuntime/7.44.0'),
(62, 4, '2025-06-17 14:57:59', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: PostmanRuntime/7.44.0'),
(63, 4, '2025-06-17 14:58:38', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(64, 4, '2025-06-17 19:53:40', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(65, 4, '2025-06-17 19:54:14', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(66, 4, '2025-06-17 19:54:29', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(67, 4, '2025-06-17 20:16:57', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(68, 4, '2025-06-17 20:17:14', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(69, 4, '2025-06-17 20:31:33', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(70, 4, '2025-06-17 20:32:55', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(71, 4, '2025-06-17 20:47:26', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(72, 4, '2025-06-17 20:47:45', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(73, 4, '2025-06-17 20:57:16', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.99.9.123 - User Agent: okhttp/4.9.0'),
(74, 4, '2025-06-17 21:05:37', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.0'),
(75, 4, '2025-06-17 21:10:07', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.0'),
(76, 4, '2025-06-17 21:12:21', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.0'),
(77, 4, '2025-06-17 21:17:30', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.86.239 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1'),
(78, 4, '2025-06-17 21:18:52', 'LOGOUT', 'Cierre de sesión - IP: 186.82.86.239 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1'),
(79, 4, '2025-06-17 21:20:48', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.0'),
(80, 4, '2025-06-17 21:25:31', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.0'),
(81, 4, '2025-06-17 21:33:00', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.0'),
(82, 4, '2025-06-17 21:45:49', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(83, 4, '2025-06-17 21:46:10', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(84, 4, '2025-06-17 21:49:38', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(85, 4, '2025-06-17 21:52:36', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(86, 4, '2025-06-17 21:52:37', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(87, 4, '2025-06-17 21:52:45', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(88, 4, '2025-06-17 21:52:52', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(89, 4, '2025-06-17 21:52:57', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(90, 4, '2025-06-17 21:53:06', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(91, 4, '2025-06-17 22:11:58', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(92, 4, '2025-06-17 22:22:00', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(93, 4, '2025-06-17 22:22:17', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(94, 4, '2025-06-17 22:24:16', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(95, 4, '2025-06-17 22:27:11', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(96, 4, '2025-06-17 22:27:34', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(97, 4, '2025-06-17 22:30:43', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(98, 4, '2025-06-17 22:31:02', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(99, 4, '2025-06-17 22:35:12', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(100, 4, '2025-06-17 22:41:19', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(101, 4, '2025-06-17 22:44:40', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(102, 4, '2025-06-17 22:54:58', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(103, 4, '2025-06-17 23:04:48', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(104, 4, '2025-06-17 23:06:35', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(105, 4, '2025-06-17 23:13:47', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(106, 4, '2025-06-17 23:19:16', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(107, 4, '2025-06-17 23:29:24', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(108, 4, '2025-06-17 23:30:03', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(109, 4, '2025-06-17 23:32:38', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(110, 4, '2025-06-17 23:32:53', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(111, 4, '2025-06-17 23:40:06', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(112, 4, '2025-06-17 23:40:13', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(113, 4, '2025-06-17 23:44:58', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(114, 4, '2025-06-17 23:45:20', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(115, 4, '2025-06-17 23:45:22', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(116, 4, '2025-06-17 23:49:35', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(117, 4, '2025-06-17 23:52:15', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(118, 4, '2025-06-17 23:55:24', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(119, 4, '2025-06-17 23:58:23', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(120, 4, '2025-06-18 00:18:20', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(121, 4, '2025-06-18 00:47:41', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(122, 4, '2025-06-18 00:48:58', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(123, 4, '2025-06-18 00:49:01', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(124, 4, '2025-06-18 01:18:09', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(125, 4, '2025-06-18 01:45:39', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(126, 4, '2025-06-18 01:58:10', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(127, 4, '2025-06-18 02:02:42', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(128, 4, '2025-06-18 02:09:08', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(129, 4, '2025-06-18 02:15:10', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(130, 4, '2025-06-18 02:27:47', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(131, 4, '2025-06-18 02:28:57', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(132, 4, '2025-06-18 02:54:05', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(133, 4, '2025-06-18 03:22:28', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(134, 4, '2025-06-18 03:23:30', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(135, 4, '2025-06-18 03:27:15', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(136, 4, '2025-06-18 03:27:44', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(137, 4, '2025-06-18 03:32:04', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(138, 4, '2025-06-18 03:49:57', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(139, 4, '2025-06-18 04:13:59', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(140, 4, '2025-06-18 04:18:30', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(141, 4, '2025-06-18 04:19:35', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(142, 4, '2025-06-18 04:37:09', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(143, 4, '2025-06-18 04:44:09', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(144, 4, '2025-06-18 05:08:31', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(145, 4, '2025-06-18 05:13:33', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(146, 4, '2025-06-18 05:22:41', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(147, 3, '2025-06-18 05:28:28', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(148, 4, '2025-06-18 09:56:36', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(149, 4, '2025-06-18 10:04:35', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(150, 4, '2025-06-18 10:38:33', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(151, 4, '2025-06-18 10:46:12', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(152, 4, '2025-06-18 11:11:02', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(153, 4, '2025-06-18 11:20:27', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(154, 4, '2025-06-18 11:20:36', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(155, 4, '2025-06-18 11:28:28', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(156, 4, '2025-06-18 11:33:33', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(157, 4, '2025-06-18 11:33:47', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(158, 3, '2025-06-18 11:34:03', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(159, 3, '2025-06-18 11:34:14', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(160, 4, '2025-06-18 11:51:17', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(161, 4, '2025-06-18 11:52:14', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(162, 4, '2025-06-18 11:54:00', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(163, 4, '2025-06-18 11:59:49', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(164, 4, '2025-06-18 12:23:32', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(165, 4, '2025-06-18 12:25:02', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(166, 4, '2025-06-18 12:27:27', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(167, 4, '2025-06-18 12:27:35', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(168, 4, '2025-06-18 12:27:41', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(169, 4, '2025-06-18 12:42:25', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(170, 4, '2025-06-18 12:43:56', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(171, 4, '2025-06-18 12:44:08', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(172, 4, '2025-06-18 12:44:29', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(173, 4, '2025-06-18 12:44:41', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(174, 4, '2025-06-18 13:10:40', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(175, 4, '2025-06-18 13:18:46', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(176, 4, '2025-06-18 13:24:12', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(177, 4, '2025-06-18 13:31:38', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(178, 4, '2025-06-18 13:36:54', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(179, 4, '2025-06-18 13:40:49', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(180, 4, '2025-06-18 13:43:45', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(181, 4, '2025-06-18 13:45:41', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(182, 4, '2025-06-18 13:56:54', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(183, 4, '2025-06-18 13:59:09', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(184, 4, '2025-06-18 13:59:13', 'LOGOUT', 'Cierre de sesión - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(185, 4, '2025-06-18 14:13:50', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.168.172.249 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(186, 4, '2025-06-18 14:27:47', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(187, 4, '2025-06-18 14:40:52', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(188, 4, '2025-06-18 14:49:52', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(189, 4, '2025-06-18 10:08:38', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(190, 4, '2025-06-18 10:14:14', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(191, 4, '2025-06-18 10:26:09', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(192, 4, '2025-06-18 10:29:47', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(193, 4, '2025-06-18 10:30:34', 'LOGIN_FAILED', 'Intento de login fallido - IP: 190.131.206.210'),
(194, 4, '2025-06-18 10:30:38', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(195, 4, '2025-06-18 10:35:50', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(196, 4, '2025-06-18 10:38:16', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.171.2.6 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(197, 4, '2025-06-18 10:42:57', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(198, 4, '2025-06-18 10:44:10', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(199, 4, '2025-06-18 10:52:05', 'LOGOUT', 'Cierre de sesión - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(200, 4, '2025-06-18 10:53:39', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(201, 4, '2025-06-18 10:55:46', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: okhttp/4.9.3'),
(202, 4, '2025-06-18 10:56:58', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36'),
(203, 4, '2025-06-18 10:59:25', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.171.2.6 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(204, 4, '2025-06-18 10:59:54', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36'),
(205, 4, '2025-06-18 15:17:08', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.116.193.136 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(206, 4, '2025-06-18 18:22:07', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(207, 4, '2025-06-18 18:22:22', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(208, 4, '2025-06-18 20:00:50', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(209, 4, '2025-06-18 21:41:11', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(210, 4, '2025-06-18 21:42:03', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(211, 4, '2025-06-18 21:42:06', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(212, 4, '2025-06-18 21:42:11', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(213, 4, '2025-06-18 21:44:56', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(214, 4, '2025-06-18 21:45:50', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(215, 4, '2025-06-18 21:46:56', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(216, 4, '2025-06-18 21:49:28', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(217, 4, '2025-06-18 21:54:02', 'LOGOUT', 'Cierre de sesión - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(218, 4, '2025-06-18 21:54:25', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(219, 4, '2025-06-18 21:56:26', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(220, 4, '2025-06-18 22:01:13', 'LOGOUT', 'Cierre de sesión - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(221, 4, '2025-06-18 22:01:27', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(222, 4, '2025-06-18 22:01:48', 'LOGOUT', 'Cierre de sesión - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(223, 4, '2025-06-18 22:01:58', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(224, 4, '2025-06-18 22:05:14', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.116.193.136 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(225, 4, '2025-06-18 22:08:20', 'LOGOUT', 'Cierre de sesión - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(226, 4, '2025-06-18 23:30:27', 'LOGIN_FAILED', 'Intento de login fallido - IP: 186.82.85.234'),
(227, 4, '2025-06-18 23:30:33', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(228, 4, '2025-06-18 23:33:56', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(229, 4, '2025-06-18 23:34:20', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(230, 4, '2025-06-18 23:34:56', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(231, 4, '2025-06-18 23:41:41', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.116.193.136 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(232, 4, '2025-06-18 23:44:08', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(233, 4, '2025-06-18 23:45:30', 'LOGOUT', 'Cierre de sesión - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(234, 4, '2025-06-18 23:45:40', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(235, 4, '2025-06-18 23:46:19', 'LOGOUT', 'Cierre de sesión - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(236, 4, '2025-06-18 23:46:32', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(237, 4, '2025-06-18 23:48:20', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(238, 4, '2025-06-18 23:49:23', 'LOGOUT', 'Cierre de sesión - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0'),
(239, 4, '2025-06-19 00:50:54', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.116.193.136 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(240, 4, '2025-06-19 00:54:05', 'LOGOUT', 'Cierre de sesión - IP: 186.116.193.136 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(241, 4, '2025-06-19 00:54:11', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.116.193.136 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(242, 4, '2025-06-19 01:02:03', 'LOGOUT', 'Cierre de sesión - IP: 186.116.193.136 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(243, 4, '2025-06-19 01:02:19', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.116.193.136 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(244, 4, '2025-06-19 02:03:40', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.116.193.136 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(245, 4, '2025-06-19 08:16:43', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.116.193.136 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(246, 4, '2025-06-19 08:20:27', 'LOGOUT', 'Cierre de sesión - IP: 186.116.193.136 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(247, 4, '2025-06-19 09:17:15', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.116.193.136 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(248, 4, '2025-06-19 10:58:26', 'LOGIN', 'Inicio de sesión exitoso - IP: 190.131.206.210 - User Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1'),
(249, 4, '2025-06-19 11:37:35', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.171.5.33 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(250, 4, '2025-06-19 11:47:45', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(251, 4, '2025-06-19 11:53:09', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(252, 4, '2025-06-19 11:59:36', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(253, 4, '2025-06-19 12:02:04', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3'),
(254, 4, '2025-06-19 12:16:44', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.116.193.136 - User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(255, 4, '2025-06-19 12:22:09', 'LOGIN', 'Inicio de sesión exitoso - IP: 186.82.85.234 - User Agent: okhttp/4.9.3');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `SEDE`
--

CREATE TABLE `SEDE` (
  `ID_SEDE` int NOT NULL,
  `NOMBRE` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `DIRECCION` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ID_EMPRESA` int NOT NULL,
  `ESTADO` char(1) COLLATE utf8mb4_general_ci DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `SEDE`
--

INSERT INTO `SEDE` (`ID_SEDE`, `NOMBRE`, `DIRECCION`, `ID_EMPRESA`, `ESTADO`) VALUES
(1, 'Sede Central', 'Av. República 123, Lima', 1, 'A'),
(2, 'Sede Norte', 'Av. Universitaria 567, Los Olivos', 1, 'A'),
(3, 'Sede Principal', 'Jr. Arequipa 456, Lima', 2, 'A'),
(4, 'Sede Sur', 'Av. Benavides 789, Surco', 2, 'A'),
(5, 'Sede Corporativa', 'Av. La Marina 789, Lima', 3, 'A'),
(6, 'Sede Este', 'Av. Javier Prado 1234, La Molina', 3, 'A');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `USUARIO`
--

CREATE TABLE `USUARIO` (
  `ID_USUARIO` int NOT NULL,
  `USERNAME` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `CONTRASENA` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `NOMBRE_COMPLETO` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `EMAIL` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `ROL` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `ID_EMPRESA` int NOT NULL,
  `ESTADO` char(1) COLLATE utf8mb4_general_ci DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `USUARIO`
--

INSERT INTO `USUARIO` (`ID_USUARIO`, `USERNAME`, `CONTRASENA`, `NOMBRE_COMPLETO`, `EMAIL`, `ROL`, `ID_EMPRESA`, `ESTADO`) VALUES
(1, 'gerente_tech', '$2y$10$Arwap0w2XY1PEORkWKuQde5aHyvOa3DDusSjieVVHRFHp97EPKZ5y', 'Admin TechSolutions', 'admin@techsolutions.com', 'GERENTE', 1, 'A'),
(2, 'gerente_innovate', '$2y$10$KbJpi8dPFPVP6nyb1hGfbO0eNVIjt87PwPrYcfWjqz10DxElxN4fC', 'Admin InnovatePeru', 'admin@innovateperu.com', 'GERENTE', 2, 'A'),
(3, 'gerente_global', '$2y$10$CpBgrcCQbUr.wEuqJ68sM.l3J627nxHjli1wVglF9v2Qgb6ab5DBa', 'Admin GlobalServices', 'admin@globalservices.com', 'GERENTE', 3, 'A'),
(4, 'MezaGerente', '$2y$10$xPt9vgic25E0gYhsoILNXubupSluTtNlEeOiyk9Y/m.mEHglFf7h2', 'Mesita User', 'mesita27@techsolutions.com', 'GERENTE', 1, 'A');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_empleados_activos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_empleados_activos` (
);

-- --------------------------------------------------------

--
-- Estructura para la vista de `vw_empleados_activos` exportada como una tabla
--
DROP TABLE IF EXISTS `vw_empleados_activos`;
CREATE TABLE`vw_empleados_activos`(

);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `ASISTENCIA`
--
ALTER TABLE `ASISTENCIA`
  ADD PRIMARY KEY (`ID_ASISTENCIA`),
  ADD KEY `IDX_ASISTENCIA_EMPLEADO` (`ID_EMPLEADO`),
  ADD KEY `IDX_ASISTENCIA_FECHA` (`FECHA`),
  ADD KEY `IDX_ASISTENCIA_FOTO` (`FOTO`);

--
-- Indices de la tabla `DIA_SEMANA`
--
ALTER TABLE `DIA_SEMANA`
  ADD PRIMARY KEY (`ID_DIA`),
  ADD UNIQUE KEY `NOMBRE` (`NOMBRE`);

--
-- Indices de la tabla `EMPLEADO`
--
ALTER TABLE `EMPLEADO`
  ADD PRIMARY KEY (`ID_EMPLEADO`),
  ADD UNIQUE KEY `DNI` (`DNI`),
  ADD KEY `IDX_EMPLEADO_ESTABLECIMIENTO` (`ID_ESTABLECIMIENTO`),
  ADD KEY `IDX_EMPLEADO_ESTADO` (`ESTADO`),
  ADD KEY `IDX_EMPLEADO_ACTIVO` (`ACTIVO`);

--
-- Indices de la tabla `EMPLEADO_HORARIO`
--
ALTER TABLE `EMPLEADO_HORARIO`
  ADD PRIMARY KEY (`ID_EMPLEADO`,`ID_HORARIO`,`FECHA_DESDE`),
  ADD KEY `ID_HORARIO` (`ID_HORARIO`);

--
-- Indices de la tabla `EMPRESA`
--
ALTER TABLE `EMPRESA`
  ADD PRIMARY KEY (`ID_EMPRESA`),
  ADD UNIQUE KEY `NOMBRE` (`NOMBRE`),
  ADD KEY `IDX_EMPRESA_ESTADO` (`ESTADO`);

--
-- Indices de la tabla `ESTABLECIMIENTO`
--
ALTER TABLE `ESTABLECIMIENTO`
  ADD PRIMARY KEY (`ID_ESTABLECIMIENTO`),
  ADD KEY `IDX_ESTABLECIMIENTO_SEDE` (`ID_SEDE`);

--
-- Indices de la tabla `HORARIO`
--
ALTER TABLE `HORARIO`
  ADD PRIMARY KEY (`ID_HORARIO`),
  ADD KEY `IDX_HORARIO_ESTABLECIMIENTO` (`ID_ESTABLECIMIENTO`);

--
-- Indices de la tabla `HORARIO_DIA`
--
ALTER TABLE `HORARIO_DIA`
  ADD PRIMARY KEY (`ID_HORARIO`,`ID_DIA`),
  ADD KEY `ID_DIA` (`ID_DIA`);

--
-- Indices de la tabla `JUSTIFICACION`
--
ALTER TABLE `JUSTIFICACION`
  ADD PRIMARY KEY (`ID_JUSTIFICACION`),
  ADD KEY `IDX_JUSTIFICACION_EMPLEADO` (`ID_EMPLEADO`);

--
-- Indices de la tabla `LOG`
--
ALTER TABLE `LOG`
  ADD PRIMARY KEY (`ID_LOG`),
  ADD KEY `IDX_LOG_USUARIO` (`ID_USUARIO`);

--
-- Indices de la tabla `SEDE`
--
ALTER TABLE `SEDE`
  ADD PRIMARY KEY (`ID_SEDE`),
  ADD KEY `IDX_SEDE_EMPRESA` (`ID_EMPRESA`);

--
-- Indices de la tabla `USUARIO`
--
ALTER TABLE `USUARIO`
  ADD PRIMARY KEY (`ID_USUARIO`),
  ADD UNIQUE KEY `USERNAME` (`USERNAME`),
  ADD KEY `IDX_USUARIO_EMPRESA` (`ID_EMPRESA`),
  ADD KEY `IDX_USUARIO_ESTADO` (`ESTADO`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ASISTENCIA`
--
ALTER TABLE `ASISTENCIA`
  MODIFY `ID_ASISTENCIA` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT de la tabla `DIA_SEMANA`
--
ALTER TABLE `DIA_SEMANA`
  MODIFY `ID_DIA` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `EMPLEADO`
--
ALTER TABLE `EMPLEADO`
  MODIFY `ID_EMPLEADO` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT de la tabla `EMPRESA`
--
ALTER TABLE `EMPRESA`
  MODIFY `ID_EMPRESA` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `ESTABLECIMIENTO`
--
ALTER TABLE `ESTABLECIMIENTO`
  MODIFY `ID_ESTABLECIMIENTO` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `HORARIO`
--
ALTER TABLE `HORARIO`
  MODIFY `ID_HORARIO` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `JUSTIFICACION`
--
ALTER TABLE `JUSTIFICACION`
  MODIFY `ID_JUSTIFICACION` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `LOG`
--
ALTER TABLE `LOG`
  MODIFY `ID_LOG` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=256;

--
-- AUTO_INCREMENT de la tabla `SEDE`
--
ALTER TABLE `SEDE`
  MODIFY `ID_SEDE` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `USUARIO`
--
ALTER TABLE `USUARIO`
  MODIFY `ID_USUARIO` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `ASISTENCIA`
--
ALTER TABLE `ASISTENCIA`
  ADD CONSTRAINT `ASISTENCIA_ibfk_1` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `EMPLEADO` (`ID_EMPLEADO`);

--
-- Filtros para la tabla `EMPLEADO`
--
ALTER TABLE `EMPLEADO`
  ADD CONSTRAINT `EMPLEADO_ibfk_1` FOREIGN KEY (`ID_ESTABLECIMIENTO`) REFERENCES `ESTABLECIMIENTO` (`ID_ESTABLECIMIENTO`);

--
-- Filtros para la tabla `EMPLEADO_HORARIO`
--
ALTER TABLE `EMPLEADO_HORARIO`
  ADD CONSTRAINT `EMPLEADO_HORARIO_ibfk_1` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `EMPLEADO` (`ID_EMPLEADO`),
  ADD CONSTRAINT `EMPLEADO_HORARIO_ibfk_2` FOREIGN KEY (`ID_HORARIO`) REFERENCES `HORARIO` (`ID_HORARIO`);

--
-- Filtros para la tabla `ESTABLECIMIENTO`
--
ALTER TABLE `ESTABLECIMIENTO`
  ADD CONSTRAINT `ESTABLECIMIENTO_ibfk_1` FOREIGN KEY (`ID_SEDE`) REFERENCES `SEDE` (`ID_SEDE`);

--
-- Filtros para la tabla `HORARIO_DIA`
--
ALTER TABLE `HORARIO_DIA`
  ADD CONSTRAINT `HORARIO_DIA_ibfk_1` FOREIGN KEY (`ID_HORARIO`) REFERENCES `HORARIO` (`ID_HORARIO`) ON DELETE CASCADE,
  ADD CONSTRAINT `HORARIO_DIA_ibfk_2` FOREIGN KEY (`ID_DIA`) REFERENCES `DIA_SEMANA` (`ID_DIA`) ON DELETE CASCADE;

--
-- Filtros para la tabla `JUSTIFICACION`
--
ALTER TABLE `JUSTIFICACION`
  ADD CONSTRAINT `JUSTIFICACION_ibfk_1` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `EMPLEADO` (`ID_EMPLEADO`);

--
-- Filtros para la tabla `LOG`
--
ALTER TABLE `LOG`
  ADD CONSTRAINT `LOG_ibfk_1` FOREIGN KEY (`ID_USUARIO`) REFERENCES `USUARIO` (`ID_USUARIO`);

--
-- Filtros para la tabla `SEDE`
--
ALTER TABLE `SEDE`
  ADD CONSTRAINT `SEDE_ibfk_1` FOREIGN KEY (`ID_EMPRESA`) REFERENCES `EMPRESA` (`ID_EMPRESA`);

--
-- Filtros para la tabla `USUARIO`
--
ALTER TABLE `USUARIO`
  ADD CONSTRAINT `USUARIO_ibfk_1` FOREIGN KEY (`ID_EMPRESA`) REFERENCES `EMPRESA` (`ID_EMPRESA`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
