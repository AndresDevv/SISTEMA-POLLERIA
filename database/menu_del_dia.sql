-- =========================================================
--  Menú del día
--
--  El menú era "Compras" (historial y proveedores) y pasa a
--  ser la carta del día: los platos y bebidas que hay, con
--  su stock, y la marca de "agotado" cuando se termina.
--
--  1) La marca de agotado en productos
--     Es aparte del stock a propósito: un plato puede quedarse
--     sin.existir por la cocina (agotado) aunque todavía queden
--     unidades contadas en el inventario.
--
--  2) fecha_actualizacion en productos
--     El motor de tiempo real compara una huella de la base
--     cada 5 segundos. Sin esta columna, marcar un plato como
--     agotado no cambiaría la huella y los demás no se
--     enterarían hasta recargar.
-- =========================================================

-- Marca de "agotado" (1 = ya no se ofrece, 0 = disponible)
ALTER TABLE productos
    ADD COLUMN agotado TINYINT(1) NOT NULL DEFAULT 0 AFTER stock_minimo;

-- Huella de cambios para el tiempo real
ALTER TABLE productos
    ADD COLUMN fecha_actualizacion DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP;

CREATE INDEX idx_productos_agotado ON productos (agotado);

-- La huella del pulso tiene que mirar esa columna, o el cambio
-- de "agotado" no llega a tiempo real. Se hace desde PHP al final
-- de esta migracion (ver models/MenuDelDia.php), porque el archivo
-- de configuracion del pulso es codigo y no SQL.

-- =========================================================
--  Permisos
--
--  El menú del día lo ve todo el mundo del salón (mesero y
--  cocina), pero solo el mesero y el administrador pueden
--  marcar un plato como agotado.
-- =========================================================

INSERT INTO permisos (nombre, descripcion)
VALUES
    ('menu_dia',        'Ver el menú del día con los platos y sus existencias.'),
    ('menu_dia_editar', 'Marcar un plato como agotado cuando ya no queda.')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- El administrador puede verlo y cambiarlo
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r, permisos p
WHERE r.nombre = 'Administrador'
  AND p.nombre IN ('menu_dia', 'menu_dia_editar')
  AND NOT EXISTS (
      SELECT 1 FROM rol_permiso rp WHERE rp.rol_id = r.id AND rp.permiso_id = p.id
  );

-- El mesero puede verlo y marcar lo que se acaba
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r, permisos p
WHERE r.nombre = 'Mesero'
  AND p.nombre IN ('menu_dia', 'menu_dia_editar')
  AND NOT EXISTS (
      SELECT 1 FROM rol_permiso rp WHERE rp.rol_id = r.id AND rp.permiso_id = p.id
  );

-- La cocina solo lo consulta: necesita saber qué hay antes de
-- preparar, pero no marca nada.
INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r, permisos p
WHERE r.nombre = 'Cocina'
  AND p.nombre = 'menu_dia'
  AND NOT EXISTS (
      SELECT 1 FROM rol_permiso rp WHERE rp.rol_id = r.id AND rp.permiso_id = p.id
  );

-- El permiso viejo de "Registrar compras" ya no lo usa nadie:
-- la pantalla de compras se reemplaza por el menú del día.
DELETE FROM rol_permiso WHERE permiso_id = (
    SELECT id FROM permisos WHERE nombre = 'compras'
);
DELETE FROM permisos WHERE nombre = 'compras';
