
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-11-2026 a las 12:51:36
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
create database periodismo;
use periodismo;
--
-- Base de datos: `periodismo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medios`
--

CREATE TABLE `medios` (
  `id_medio` int(11) NOT NULL,
  `id_tipo` int(11) DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `calle` varchar(100) DEFAULT NULL,
  `nro` int(11) DEFAULT NULL,
  `cod_postal` int(11) DEFAULT NULL,
  `localidad` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `medios`
--

INSERT INTO `medios` (`id_medio`, `id_tipo`, `nombre`, `calle`, `nro`, `cod_postal`, `localidad`) VALUES
(1, NULL, 'Infobae', 'Costa Rica', 5639, 1414, 'Argentina'),
(2, NULL, 'Telefe', 'Matheu', 1539, 1249, 'Argentina'),
(3, NULL, 'Radio Mitre', 'Mansilla', 2668, 1425, 'Argentina');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodistas`
--

CREATE TABLE `periodistas` (
  `id_periodista` int(11) NOT NULL,
  `apellidos` varchar(60) DEFAULT NULL,
  `nombre` varchar(20) DEFAULT NULL,
  `piso` int(10) DEFAULT NULL,
  `departamento` varchar(60) DEFAULT NULL,
  `sueldo` int(60) DEFAULT NULL,
  `id_medio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `periodistas`
--

INSERT INTO `periodistas` (`id_periodista`, `apellidos`, `nombre`, `piso`, `departamento`, `sueldo`, `id_medio`) VALUES
(1, 'Cirio', 'Martin', 11, 'Depto B', 2500000, 2),
(2, 'Martin', 'Ricardo', 8, 'Depto A', 3000000, 1),
(3, 'Castelli', 'Sabrina', 11, 'Depto A', 65000, 3),
(4, 'Rando', 'Rihana', 10, 'Depto C', 4000000, 1),
(5, 'Pagni', 'Carlos', 6, 'Depto A', 5000, 2),
(6, 'Cirio', 'Martin', 11, 'Depto B', 2500000, 2),
(7, 'Martin', 'Ricardo', 8, 'Depto A', 3000000, 1),
(8, 'Castelli', 'Sabrina', 11, 'Depto A', 65000, 3),
(9, 'Rando', 'Rihana', 10, 'Depto C', 4000000, 1),
(10, 'Pagni', 'Carlos', 6, 'Depto A', 5000, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos`
--

CREATE TABLE `tipos` (
  `id_tipo` int(11) NOT NULL,
  `descripcion` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos`
--

INSERT INTO `tipos` (`id_tipo`, `descripcion`) VALUES
(1, 'Plataformas basadas en Internet que difunden información mul'),
(2, 'Información en Internet con texto, video e interactividad in'),
(3, 'Info texto, video e interactividad inmediata.'),
(4, 'Información interactiva en portales web y redes sociales.'),
(5, 'Información interactiva en portales web y redes sociales.');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `medios`
--
ALTER TABLE `medios`
  ADD PRIMARY KEY (`id_medio`),
  ADD KEY `id_tipo` (`id_tipo`);

--
-- Indices de la tabla `periodistas`
--
ALTER TABLE `periodistas`
  ADD PRIMARY KEY (`id_periodista`),
  ADD KEY `id_medio` (`id_medio`);

--
-- Indices de la tabla `tipos`
--
ALTER TABLE `tipos`
  ADD PRIMARY KEY (`id_tipo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `medios`
--
ALTER TABLE `medios`
  MODIFY `id_medio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `periodistas`
--
ALTER TABLE `periodistas`
  MODIFY `id_periodista` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `tipos`
--
ALTER TABLE `tipos`
  MODIFY `id_tipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `medios`
--
ALTER TABLE `medios`
  ADD CONSTRAINT `medios_ibfk_1` FOREIGN KEY (`id_tipo`) REFERENCES `tipos` (`id_tipo`);

--
-- Filtros para la tabla `periodistas`
--
ALTER TABLE `periodistas`
  ADD CONSTRAINT `periodistas_ibfk_1` FOREIGN KEY (`id_medio`) REFERENCES `medios` (`id_medio`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
SELECT nombre, sueldo  FROM periodistas;
SELECT nombre, sueldo FROM periodistas WHERE sueldo>60000;
SELECT * FROM periodistas WHERE sueldo BETWEEN  60000 AND 75000;
SELECT * FROM medios WHERE cod_postal IN (1000, 1245);
SELECT * FROM medios WHERE nombre= "noticias" ;
SELECT * FROM medios WHERE nombre LIKE "%noticias%";
SELECT * FROM periodistas WHERE piso IS NULL;
SELECT * FROM periodistas WHERE apellidos LIKE "%ez";
SELECT * FROM medios ORDER BY nombre ASC;
SELECT * FROM periodistas WHERE length(nombre)>4; 
SELECT id_medio FROM periodistas GROUP BY id_medio having count(*)>1;
SELECT count(*)AS cantidad FROM periodistas WHERE apellidos LIKE "l%";
WHERE permite establecer condiciones.

LIKE permite buscar coincidencias de texto.

% representa cualquier cantidad de caracteres.

IS NULL busca valores vacíos o sin asignar.

BETWEEN busca valores dentro de un intervalo.

GROUP BY agrupa registros.

HAVING filtra grupos.

COUNT, MAX, MIN y AVG son funciones de agregación.0
