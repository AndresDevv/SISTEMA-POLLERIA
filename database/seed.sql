-- =========================================================
--  LOS GOMEZ - Datos de ejemplo
--  Ejecuta este archivo para poder ver el sistema con datos:
--  mysql -u root polleria < database/seed.sql
--
--  Ojo: inserta datos. Si tu base ya tiene registros,
--  no lo ejecutes.
-- =========================================================

-- ---------------------------------------------------------
-- Categorías
-- ---------------------------------------------------------
INSERT INTO categorias (nombre, descripcion) VALUES
('Pollos',        'Pollos a la brasa, al ají y rostizados'),
('Platos',        'Platos típicos de la casa'),
('Guarniciones',  'Papas, arroz y acompañamientos'),
('Bebidas',       'Gaseosas, jugos y limonadas'),
('Postres',       'Crema volteada y suspiro limeño')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- ---------------------------------------------------------
-- Mesas
-- ---------------------------------------------------------
INSERT INTO mesas (numero, capacidad, estado) VALUES
(1, 2, 'libre'),    (2, 4, 'libre'),    (3, 4, 'libre'),
(4, 2, 'libre'),    (5, 4, 'libre'),    (6, 2, 'libre'),
(7, 2, 'libre'),    (8, 4, 'libre'),    (9, 6, 'libre'),
(10, 2, 'libre'),   (11, 6, 'libre'),   (12, 2, 'libre'),
(13, 6, 'libre'),   (14, 2, 'libre'),   (15, 2, 'libre'),
(16, 2, 'libre')
ON DUPLICATE KEY UPDATE capacidad = VALUES(capacidad);

-- ---------------------------------------------------------
-- Productos
-- ---------------------------------------------------------
INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, stock_minimo) VALUES
((SELECT id FROM categorias WHERE nombre = 'Pollos'),        'Pollo a la brasa',    'Pollo entero a la brasa',   28.00, 24, 10),
((SELECT id FROM categorias WHERE nombre = 'Pollos'),        'Pollo al ají',        'Pollo al ají amarillo',    32.00,  6, 10),
((SELECT id FROM categorias WHERE nombre = 'Pollos'),        'Pollo rostizado',     'Pollo rostizado al horno', 35.00,  0,  8),
((SELECT id FROM categorias WHERE nombre = 'Pollos'),        'Pollo a la parrilla',  'Pollo a la parrilla',      30.00, 10,  8),
((SELECT id FROM categorias WHERE nombre = 'Platos'),        'Causa Brahmana',       'Causa con ají amarillo',   18.00, 18, 12),
((SELECT id FROM categorias WHERE nombre = 'Platos'),        'Ceviche clásico',      'Ceviche con camote',       26.00,  4, 10),
((SELECT id FROM categorias WHERE nombre = 'Platos'),        'Ají de gallina',       'Ají de gallina criollo',   22.00, 15, 10),
((SELECT id FROM categorias WHERE nombre = 'Guarniciones'),  'Papa frita',           'Porción de papa frita',     7.00,  8, 12),
((SELECT id FROM categorias WHERE nombre = 'Guarniciones'),  'Arroz chaufa',        'Arroz con pollo',           9.00, 14, 10),
((SELECT id FROM categorias WHERE nombre = 'Bebidas'),       'Gaseosa 500ml',        'Gaseosa personal',          5.00, 60, 24),
((SELECT id FROM categorias WHERE nombre = 'Bebidas'),       'Chicha morada',        'Chicha morada 1L',          8.00,  5,  6),
((SELECT id FROM categorias WHERE nombre = 'Bebidas'),       'Limonada frozen',      'Limonada frozen',          10.00,  0,  8),
((SELECT id FROM categorias WHERE nombre = 'Postres'),       'Crema volteada',       'Crema volteada',            6.00,  9,  6),
((SELECT id FROM categorias WHERE nombre = 'Postres'),       'Suspiro limeño',       'Suspiro limeño',            6.50,  2,  6);
