-- Carga en activo las filas válidas de la planilla oficial.
-- Conserva las filas fuente y excluye placas vacías y repetidas.
-- Los valores de categoría, estado, centro de costo o ubicación que falten
-- quedan marcados de forma explícita para revisión, sin reemplazar el origen.
USE gestion_activos;

SET @importacion_planilla = (
    SELECT MAX(i.id)
    FROM importacion i
    WHERE LOWER(i.nombre_archivo) LIKE '%.xlsx'
      AND EXISTS (SELECT 1 FROM fila_planilla_activo f WHERE f.importacion_id = i.id)
);

START TRANSACTION;

INSERT INTO categoria_activo (codigo, nombre, estado)
SELECT 'XLSX-SIN-CLASIFICAR', 'Sin clasificar (planilla CECAR)', 'ACTIVA'
WHERE NOT EXISTS (
    SELECT 1 FROM categoria_activo WHERE BINARY codigo = BINARY 'XLSX-SIN-CLASIFICAR'
);

INSERT INTO dependencia (sede_id, codigo, nombre, tipo, estado)
SELECT NULL, 'XLSX-SIN-CC', 'Centro de costo sin identificar (planilla CECAR)', 'CENTRO_COSTO_EXCEL', 'ACTIVA'
WHERE NOT EXISTS (
    SELECT 1 FROM dependencia WHERE BINARY codigo = BINARY 'XLSX-SIN-CC'
);

INSERT INTO ubicacion (sede_id, dependencia_id, codigo, nombre, tipo, ubicacion_padre_id, estado)
SELECT NULL, NULL, 'XLSX-SIN-UBI', 'Ubicación sin identificar (planilla CECAR)', 'UBICACION_EXCEL', NULL, 'ACTIVA'
WHERE NOT EXISTS (
    SELECT 1 FROM ubicacion WHERE BINARY codigo = BINARY 'XLSX-SIN-UBI'
);

INSERT INTO activo (placa, descripcion, categoria_id, estado, ubicacion_id, dependencia_id)
SELECT TRIM(f.placa),
       TRIM(f.descripcion),
       c.id,
       'REGISTRADO',
       COALESCE(u.id, u_sin.id),
       COALESCE(d.id, d_sin.id)
FROM fila_planilla_activo f
JOIN categoria_activo c ON BINARY c.codigo = BINARY 'XLSX-SIN-CLASIFICAR'
JOIN ubicacion u_sin ON BINARY u_sin.codigo = BINARY 'XLSX-SIN-UBI'
JOIN dependencia d_sin ON BINARY d_sin.codigo = BINARY 'XLSX-SIN-CC'
LEFT JOIN ubicacion u
       ON BINARY u.codigo = BINARY CONCAT('XLSX-', UPPER(LEFT(SHA2(TRIM(f.ubicacion_fisica), 256), 12)))
      AND BINARY u.nombre = BINARY TRIM(f.ubicacion_fisica)
LEFT JOIN dependencia d
       ON BINARY d.codigo = BINARY CONCAT('XLSX-', UPPER(LEFT(SHA2(TRIM(f.nombre_centro_costo), 256), 12)))
      AND BINARY d.nombre = BINARY TRIM(f.nombre_centro_costo)
WHERE f.importacion_id = @importacion_planilla
  AND f.resultado = 'PENDIENTE_MAPEO'
  AND f.conversion_estado = 'PENDIENTE'
  AND f.placa IS NOT NULL AND TRIM(f.placa) <> ''
  AND f.descripcion IS NOT NULL AND TRIM(f.descripcion) <> ''
  AND NOT EXISTS (SELECT 1 FROM activo existente WHERE existente.placa = TRIM(f.placa));

UPDATE fila_planilla_activo f
JOIN activo a ON a.placa = TRIM(f.placa)
SET f.resultado = 'CONVERTIDO',
    f.conversion_estado = 'CONVERTIDO',
    f.activo_id = a.id,
    f.conversion_observacion = 'Categoría y estado operativo inicial requieren validación institucional.'
WHERE f.importacion_id = @importacion_planilla
  AND f.resultado = 'PENDIENTE_MAPEO'
  AND f.conversion_estado = 'PENDIENTE';

COMMIT;
