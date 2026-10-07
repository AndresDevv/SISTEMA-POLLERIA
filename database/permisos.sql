-- =========================================================
--  LOS GOMEZ - Permisos por rol
--  Ejecutar: mysql -u root --default-character-set=utf8mb4 polleria < database/permisos.sql
-- =========================================================

-- ---------------------------------------------------------
-- Catálogo de permisos
-- ---------------------------------------------------------
INSERT INTO permisos (nombre, descripcion) VALUES
('dashboard',        'Ver el tablero de resumen'),
('ventas',           'Ver ventas, historial y caja'),
('cobrar',           'Cobrar los pedidos desde Mesas y Pedidos'),
('mesas',            'Ver las mesas del salón'),
('mesas_reservar',   'Reservar mesas y quitar la reserva'),
('mesas_editar',     'Agregar y eliminar mesas'),
('pedidos_ver',      'Ver los pedidos existentes'),
('pedidos_crear',    'Registrar pedidos y enviarlos a cocina'),
('pedidos_editar',   'Modificar o eliminar pedidos'),
('pedidos_estado',   'Cambiar el estado de un pedido (aceptar, marcar listo, servir)'),
('inventario',       'Ver productos y stock'),
('inventario_editar','Crear productos y ajustar stock'),
('compras',          'Registrar compras y proveedores'),
('finanzas',         'Ver ingresos, egresos y gastos'),
('personal',         'Ver colaboradores, asistencia y pagos'),
('servicios',        'Ver servicios básicos y vencimientos'),
('reportes',         'Ver y exportar reportes'),
('configuracion',    'Administrar usuarios, roles y datos del negocio')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- ---------------------------------------------------------
-- Administrador: acceso total
-- ---------------------------------------------------------
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 1, id FROM permisos
ON DUPLICATE KEY UPDATE rol_id = rol_id;

-- ---------------------------------------------------------
-- Mesero: salón, pedidos, cobro e inventario. Nada más.
-- Sin dashboard ni ventas, para que el menú muestre solo dos secciones.
-- ---------------------------------------------------------
DELETE FROM rol_permiso WHERE rol_id = 2;

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 2, id FROM permisos WHERE nombre IN
('mesas', 'mesas_reservar', 'pedidos_ver', 'pedidos_crear', 'cobrar', 'inventario')
ON DUPLICATE KEY UPDATE rol_id = rol_id;

-- ---------------------------------------------------------
-- Cocina: ve mesas y pedidos e inventario, y avanza el estado
-- de los pedidos porque es parte de su trabajo (aceptar, marcar
-- listo, servir). No registra pedidos, no cobra y no edita.
-- ---------------------------------------------------------
DELETE FROM rol_permiso WHERE rol_id = 3;

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT 3, id FROM permisos WHERE nombre IN
('mesas', 'pedidos_ver', 'pedidos_estado', 'inventario')
ON DUPLICATE KEY UPDATE rol_id = rol_id;