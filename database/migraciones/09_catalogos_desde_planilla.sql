-- Conserva ubicaciones físicas y nombres de centro de costo del Excel en los
-- catálogos operativos. Las sedes solo se asignan cuando el origen las menciona.
-- Ejecutar después de 08_sedes_cecar.sql.
USE gestion_activos;

ALTER TABLE ubicacion MODIFY sede_id BIGINT UNSIGNED NULL;

DROP TEMPORARY TABLE IF EXISTS tmp_catalogo_dependencia_excel;
CREATE TEMPORARY TABLE tmp_catalogo_dependencia_excel AS
SELECT nombre,
       CASE WHEN COUNT(DISTINCT sede_codigo) = 1 THEN MAX(sede_codigo) ELSE NULL END AS sede_codigo
FROM (
    SELECT TRIM(f.nombre_centro_costo) AS nombre,
           CASE
             WHEN LOWER(CONCAT_WS(' ', f.nombre_centro_costo, f.ubicacion_fisica)) LIKE '%cartagena%' THEN 'CARTAGENA'
             WHEN LOWER(CONCAT_WS(' ', f.nombre_centro_costo, f.ubicacion_fisica)) LIKE '%monter%' THEN 'MONTERIA'
             WHEN LOWER(CONCAT_WS(' ', f.nombre_centro_costo, f.ubicacion_fisica)) LIKE '%sincelejo%' THEN 'SINCELEJO'
             ELSE NULL
           END AS sede_codigo
    FROM fila_planilla_activo f
    WHERE f.nombre_centro_costo IS NOT NULL AND TRIM(f.nombre_centro_costo) <> ''
) origen
GROUP BY nombre;

INSERT INTO dependencia (sede_id, codigo, nombre, tipo, estado)
SELECT s.id,
       CONCAT('XLSX-', UPPER(LEFT(SHA2(d.nombre, 256), 12))),
       d.nombre,
       'CENTRO_COSTO_EXCEL',
       'ACTIVA'
FROM tmp_catalogo_dependencia_excel d
LEFT JOIN sede s ON BINARY s.codigo = BINARY d.sede_codigo
WHERE NOT EXISTS (
    SELECT 1 FROM dependencia existente
    WHERE BINARY existente.codigo = BINARY CONCAT('XLSX-', UPPER(LEFT(SHA2(d.nombre, 256), 12)))
      AND BINARY existente.nombre = BINARY d.nombre
);

DROP TEMPORARY TABLE IF EXISTS tmp_catalogo_ubicacion_excel;
CREATE TEMPORARY TABLE tmp_catalogo_ubicacion_excel AS
SELECT nombre,
       CASE WHEN COUNT(DISTINCT sede_codigo) = 1 THEN MAX(sede_codigo) ELSE NULL END AS sede_codigo
FROM (
    SELECT TRIM(f.ubicacion_fisica) AS nombre,
           CASE
             WHEN LOWER(CONCAT_WS(' ', f.ubicacion_fisica, f.nombre_centro_costo)) LIKE '%cartagena%' THEN 'CARTAGENA'
             WHEN LOWER(CONCAT_WS(' ', f.ubicacion_fisica, f.nombre_centro_costo)) LIKE '%monter%' THEN 'MONTERIA'
             WHEN LOWER(CONCAT_WS(' ', f.ubicacion_fisica, f.nombre_centro_costo)) LIKE '%sincelejo%' THEN 'SINCELEJO'
             ELSE NULL
           END AS sede_codigo
    FROM fila_planilla_activo f
    WHERE f.ubicacion_fisica IS NOT NULL AND TRIM(f.ubicacion_fisica) <> ''
) origen
GROUP BY nombre;

INSERT INTO ubicacion (sede_id, dependencia_id, codigo, nombre, tipo, ubicacion_padre_id, estado)
SELECT s.id,
       NULL,
       CONCAT('XLSX-', UPPER(LEFT(SHA2(u.nombre, 256), 12))),
       u.nombre,
       'UBICACION_EXCEL',
       NULL,
       'ACTIVA'
FROM tmp_catalogo_ubicacion_excel u
LEFT JOIN sede s ON BINARY s.codigo = BINARY u.sede_codigo
WHERE NOT EXISTS (
    SELECT 1 FROM ubicacion existente
    WHERE BINARY existente.codigo = BINARY CONCAT('XLSX-', UPPER(LEFT(SHA2(u.nombre, 256), 12)))
      AND BINARY existente.nombre = BINARY u.nombre
);

DROP TEMPORARY TABLE tmp_catalogo_dependencia_excel;
DROP TEMPORARY TABLE tmp_catalogo_ubicacion_excel;
