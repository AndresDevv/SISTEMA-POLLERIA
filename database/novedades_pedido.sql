-- Novedades de un pedido: qué se le agregó después de crearlo.
--
-- Cuando el mesero suma productos a un pedido que ya está en cocina, no se
-- crea un pedido nuevo (se suma al anterior). Entonces cocina no ve ningún
-- pedido nuevo que le avise, y podría no notar que le agregaron algo. Esta
-- tabla guarda qué se sumó y cuándo, para poder avisarle exactamente qué
-- apareció en esa comanda.

CREATE TABLE IF NOT EXISTS pedido_novedades (
    id          INT(11) NOT NULL AUTO_INCREMENT,
    pedido_id   INT(11) NOT NULL,
    producto_id INT(11) DEFAULT NULL,
    texto       VARCHAR(255) NOT NULL,
    cantidad    INT(11) NOT NULL DEFAULT 0,
    usuario_id  INT(11) DEFAULT NULL,
    fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY fk_novedad_pedido (pedido_id),
    CONSTRAINT fk_novedad_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Para leer solo lo que aún no se le mostró a cocina
CREATE INDEX idx_novedad_pedido ON pedido_novedades (pedido_id, id);
