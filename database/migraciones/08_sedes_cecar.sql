-- Catálogo inicial de sedes confirmadas por CECAR.
-- Ejecutar después de crear el esquema. Es seguro ejecutarlo más de una vez.
USE gestion_activos;

INSERT INTO sede (codigo, nombre, direccion, estado)
SELECT 'MONTERIA', 'Montería', NULL, 'ACTIVA'
WHERE NOT EXISTS (SELECT 1 FROM sede WHERE codigo = 'MONTERIA');

INSERT INTO sede (codigo, nombre, direccion, estado)
SELECT 'SINCELEJO', 'Sincelejo', NULL, 'ACTIVA'
WHERE NOT EXISTS (SELECT 1 FROM sede WHERE codigo = 'SINCELEJO');

INSERT INTO sede (codigo, nombre, direccion, estado)
SELECT 'CARTAGENA', 'Cartagena', NULL, 'ACTIVA'
WHERE NOT EXISTS (SELECT 1 FROM sede WHERE codigo = 'CARTAGENA');
