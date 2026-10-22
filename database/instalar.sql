-- =========================================================
--  MAKAKO'S - Instalador de la base de datos
-- =========================================================
--
--  PARA QUE SIRVE
--  Instala el sistema completo en una maquina vacia.
--
--  QUE HACE EN EL SISTEMA
--  Crea la base "polleria" con las 25 tablas del sistema y la
--  deja lista para empezar a trabajar:
--
--    - 5 roles con sus permisos: administrador, mesero, cocina,
--      cajero y control de acceso
--    - 5 usuarios de acceso
--    - La carta: 5 categorias y 13 platos y bebidas
--    - 16 mesas del salon
--    - 5 trabajadores
--    - 4 formas de pago y los datos del local
--
--  Sin esta base el sistema no tiene nada que mostrar y ni
--  siquiera entra al login.
--
--  El historial de pedidos y ventas no viene: eso se genera con
--  el uso. La base queda como el dia uno.
--
--  -------------------------------------------------------------
--  COMO SE USA
--
--  Opcion 1 - Linea de comandos (un solo paso)
--  Abre el simbolo del sistema en la carpeta del proyecto y
--  ejecuta:
--
--      mysql -u root --default-character-set=utf8mb4 < database\instalar.sql
--
--  Opcion 2 - phpMyAdmin
--  1. Abre XAMPP y arranca Apache y MySQL
--  2. Entra a http://localhost/phpmyadmin
--  3. Pestana "Importar" -> elige este archivo -> "Continuar"
--
--  -------------------------------------------------------------
--  QUE NECESITA
--  - MySQL encendido (en XAMPP, el boton Start de MySQL).
--  - Que este archivo se cargue como UTF-8. El comando de
--    consola ya lleva charset=utf8mb4, y en phpMyAdmin es el
--    valor por defecto. Con otra codificacion las tildes y la
--    letra enye se guardarian mal.
--
--  -------------------------------------------------------------
--  SI YA HAY UNA BASE "polleria"
--  Este archivo borra las tablas que encuentre y las vuelve a
--  crear: se lleva por delante los datos que hubiera. Si no
--  quieres perderlos, saca antes una copia con mysqldump.
--
--  -------------------------------------------------------------
--  USUARIOS QUE QUEDAN CREADOS
--    admin          / 123456   Administrador
--    mesero         / 123456   Mesero
--    cocina         / 123456   Cocina
--    cajero         / 123456   Cajero
--    control_acceso / 123456   Control de acceso
--
--  Cambia esas contrasenas antes de usar el sistema en el
--  local: entra con el administrador, ve a Configuracion ->
--  Usuarios y cambialas.
--
--  -------------------------------------------------------------
--  COMO ESTA ORDENADO ESTE ARCHIVO
--
--  Seccion 1  Las 25 tablas del sistema. Cada una lleva arriba
--             una linea que explica para que sirve.
--  Seccion 2  Los datos que dejan el sistema listo para usar.
--  Seccion 3  Que menus y botones ve cada rol.
--
--  Para mas detalle, mira database\README.md e INSTALACION.md
--  en la raiz del proyecto.
-- =========================================================


CREATE DATABASE IF NOT EXISTS polleria
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE polleria;

SET NAMES utf8mb4;

-- Las claves foraneas se desactivan mientras se arma todo y se vuelven
-- a activar al final. Asi el orden en que aparecen las tablas da igual.
SET FOREIGN_KEY_CHECKS = 0;


-- =========================================================
--  SECCION 1 - LAS TABLAS DEL SISTEMA
-- =========================================================

-- ---------------------------------------------------------
--  Tabla: ingresos
--  Entradas de dinero que no vienen de las ventas.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS ingresos;

CREATE TABLE ingresos (
  id int(11) NOT NULL AUTO_INCREMENT,
  usuario_id int(11) DEFAULT NULL,
  concepto varchar(150) NOT NULL,
  descripcion varchar(255) DEFAULT NULL,
  monto decimal(10,2) NOT NULL,
  fecha datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY fk_ingreso_usuario (usuario_id),
  CONSTRAINT fk_ingreso_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: empleados
--  Los trabajadores: nombre, cargo, DNI, telefono y direccion.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS empleados;

CREATE TABLE empleados (
  id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(150) NOT NULL,
  usuario_id int(11) DEFAULT NULL,
  dni varchar(20) DEFAULT NULL,
  telefono varchar(30) DEFAULT NULL,
  direccion varchar(255) DEFAULT NULL,
  cargo varchar(100) DEFAULT NULL,
  fecha_ingreso date DEFAULT NULL,
  estado tinyint(1) NOT NULL DEFAULT 1,
  pin varchar(4) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY dni (dni),
  KEY fk_empleado_usuario (usuario_id),
  CONSTRAINT fk_empleado_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: configuracion_negocio
--  Datos del local: nombre, RUC, direccion y telefono. Salen al pie de los reportes.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS configuracion_negocio;

CREATE TABLE configuracion_negocio (
  id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(150) NOT NULL,
  ruc varchar(20) DEFAULT NULL,
  direccion varchar(255) DEFAULT NULL,
  telefono varchar(30) DEFAULT NULL,
  logo varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: metodos_pago
--  Formas de pago que salen al cobrar: efectivo, Yape, Plin, tarjeta.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS metodos_pago;

CREATE TABLE metodos_pago (
  id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(50) NOT NULL,
  estado tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY nombre (nombre)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: proveedores
--  Los proveedores. La tabla se conserva aunque la pantalla ya no exista.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS proveedores;

CREATE TABLE proveedores (
  id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(150) NOT NULL,
  documento varchar(30) DEFAULT NULL,
  telefono varchar(30) DEFAULT NULL,
  direccion varchar(255) DEFAULT NULL,
  estado tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: pedido_novedades
--  Que se le agrego a un pedido ya enviado a cocina, para poder avisarle.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS pedido_novedades;

CREATE TABLE pedido_novedades (
  id int(11) NOT NULL AUTO_INCREMENT,
  pedido_id int(11) NOT NULL,
  producto_id int(11) DEFAULT NULL,
  texto varchar(255) NOT NULL,
  cantidad int(11) NOT NULL DEFAULT 0,
  usuario_id int(11) DEFAULT NULL,
  fecha datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY fk_novedad_pedido (pedido_id),
  CONSTRAINT fk_novedad_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: productos
--  Los platos y bebidas de la carta, con precio, stock y la marca de agotado.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS productos;

CREATE TABLE productos (
  id int(11) NOT NULL AUTO_INCREMENT,
  categoria_id int(11) NOT NULL,
  nombre varchar(150) NOT NULL,
  descripcion varchar(255) DEFAULT NULL,
  precio decimal(10,2) NOT NULL DEFAULT 0.00,
  stock int(11) NOT NULL DEFAULT 0,
  stock_minimo int(11) NOT NULL DEFAULT 0,
  agotado tinyint(1) NOT NULL DEFAULT 0,
  estado tinyint(1) NOT NULL DEFAULT 1,
  fecha_creacion datetime NOT NULL DEFAULT current_timestamp(),
  fecha_actualizacion datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  KEY fk_productos_categoria (categoria_id),
  KEY idx_productos_agotado (agotado),
  CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: detalle_compras
--  Que productos llevo cada compra y por cuanto.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS detalle_compras;

CREATE TABLE detalle_compras (
  id int(11) NOT NULL AUTO_INCREMENT,
  compra_id int(11) NOT NULL,
  producto_id int(11) NOT NULL,
  cantidad decimal(10,2) NOT NULL,
  precio_unitario decimal(10,2) NOT NULL,
  subtotal decimal(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY fk_detalle_compra (compra_id),
  KEY fk_detalle_compra_producto (producto_id),
  CONSTRAINT fk_detalle_compra FOREIGN KEY (compra_id) REFERENCES compras (id) ON DELETE CASCADE,
  CONSTRAINT fk_detalle_compra_producto FOREIGN KEY (producto_id) REFERENCES productos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: pagos
--  Los pagos con que se cobra cada venta.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS pagos;

CREATE TABLE pagos (
  id int(11) NOT NULL AUTO_INCREMENT,
  venta_id int(11) NOT NULL,
  metodo_pago_id int(11) NOT NULL,
  monto decimal(10,2) NOT NULL,
  monto_recibido decimal(10,2) DEFAULT NULL,
  vuelto decimal(10,2) DEFAULT NULL,
  fecha datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY fk_pagos_venta (venta_id),
  KEY fk_pagos_metodo (metodo_pago_id),
  CONSTRAINT fk_pagos_metodo FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago (id),
  CONSTRAINT fk_pagos_venta FOREIGN KEY (venta_id) REFERENCES ventas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: servicios
--  Servicios basicos del local (luz, agua, internet) y sus vencimientos.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS servicios;

CREATE TABLE servicios (
  id int(11) NOT NULL AUTO_INCREMENT,
  tipo enum('luz','agua','internet','otro') NOT NULL,
  descripcion varchar(255) DEFAULT NULL,
  monto decimal(10,2) NOT NULL,
  fecha_vencimiento date DEFAULT NULL,
  fecha_pago date DEFAULT NULL,
  estado enum('pendiente','pagado','vencido') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: pedidos
--  Las comandas. Guardan mesa, estado (pendiente, en cocina, listo, servido) y total.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS pedidos;

CREATE TABLE pedidos (
  id int(11) NOT NULL AUTO_INCREMENT,
  mesa_id int(11) NOT NULL,
  usuario_id int(11) NOT NULL,
  estado enum('pendiente','preparando','preparado','entregado') NOT NULL DEFAULT 'pendiente',
  total decimal(10,2) NOT NULL DEFAULT 0.00,
  fecha_creacion datetime NOT NULL DEFAULT current_timestamp(),
  fecha_actualizacion datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  KEY fk_pedidos_mesa (mesa_id),
  KEY fk_pedidos_usuario (usuario_id),
  CONSTRAINT fk_pedidos_mesa FOREIGN KEY (mesa_id) REFERENCES mesas (id),
  CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: compras
--  Compras a proveedores. El historial se conserva aunque la pantalla ya no exista.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS compras;

CREATE TABLE compras (
  id int(11) NOT NULL AUTO_INCREMENT,
  proveedor_id int(11) DEFAULT NULL,
  usuario_id int(11) DEFAULT NULL,
  total decimal(10,2) NOT NULL DEFAULT 0.00,
  estado enum('registrada','anulada') NOT NULL DEFAULT 'registrada',
  fecha datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY fk_compras_proveedor (proveedor_id),
  KEY fk_compras_usuario (usuario_id),
  CONSTRAINT fk_compras_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores (id) ON DELETE SET NULL,
  CONSTRAINT fk_compras_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: permisos
--  La lista de todo lo que se puede hacer en el sistema.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS permisos;

CREATE TABLE permisos (
  id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(100) NOT NULL,
  descripcion varchar(255) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY nombre (nombre)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: detalle_pedidos
--  Los platos de cada comanda, uno por fila.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS detalle_pedidos;

CREATE TABLE detalle_pedidos (
  id int(11) NOT NULL AUTO_INCREMENT,
  pedido_id int(11) NOT NULL,
  producto_id int(11) NOT NULL,
  cantidad decimal(10,2) NOT NULL,
  precio_unitario decimal(10,2) NOT NULL,
  subtotal decimal(10,2) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY pedido_producto (pedido_id,producto_id),
  KEY fk_detalle_pedido (pedido_id),
  KEY fk_detalle_producto (producto_id),
  CONSTRAINT fk_detalle_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE,
  CONSTRAINT fk_detalle_producto FOREIGN KEY (producto_id) REFERENCES productos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: mesas
--  Las mesas del salon, con su capacidad y si estan libres, ocupadas o reservadas.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS mesas;

CREATE TABLE mesas (
  id int(11) NOT NULL AUTO_INCREMENT,
  numero int(11) NOT NULL,
  capacidad int(11) NOT NULL DEFAULT 4,
  estado enum('libre','ocupada','reservada') NOT NULL DEFAULT 'libre',
  reservado_por varchar(100) DEFAULT NULL,
  reservado_hora time DEFAULT NULL,
  reservado_fecha date DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY numero (numero)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: ventas
--  Una venta por cada comanda cobrada.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS ventas;

CREATE TABLE ventas (
  id int(11) NOT NULL AUTO_INCREMENT,
  pedido_id int(11) NOT NULL,
  usuario_id int(11) DEFAULT NULL,
  subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
  descuento decimal(10,2) NOT NULL DEFAULT 0.00,
  total decimal(10,2) NOT NULL DEFAULT 0.00,
  estado enum('pagada','anulada') NOT NULL DEFAULT 'pagada',
  fecha datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY fk_ventas_pedido (pedido_id),
  KEY fk_ventas_usuario (usuario_id),
  CONSTRAINT fk_ventas_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id),
  CONSTRAINT fk_ventas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: categorias
--  Los grupos de la carta: pollos, platos, guarniciones, bebidas y postres.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS categorias;

CREATE TABLE categorias (
  id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(100) NOT NULL,
  descripcion varchar(255) DEFAULT NULL,
  estado tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY nombre (nombre)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: rol_permiso
--  Que permiso tiene cada rol. Es lo que decide los menus que ve cada persona.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS rol_permiso;

CREATE TABLE rol_permiso (
  id int(11) NOT NULL AUTO_INCREMENT,
  rol_id int(11) NOT NULL,
  permiso_id int(11) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY rol_id (rol_id,permiso_id),
  KEY fk_rol_permiso_permiso (permiso_id),
  CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (permiso_id) REFERENCES permisos (id) ON DELETE CASCADE,
  CONSTRAINT fk_rol_permiso_rol FOREIGN KEY (rol_id) REFERENCES roles (id) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: movimientos_inventario
--  Cada entrada, salida y ajuste de stock, con el stock antes y despues.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS movimientos_inventario;

CREATE TABLE movimientos_inventario (
  id int(11) NOT NULL AUTO_INCREMENT,
  producto_id int(11) NOT NULL,
  usuario_id int(11) DEFAULT NULL,
  tipo enum('entrada','salida','ajuste') NOT NULL,
  cantidad decimal(10,2) NOT NULL,
  stock_anterior decimal(10,2) NOT NULL,
  stock_nuevo decimal(10,2) NOT NULL,
  motivo varchar(255) DEFAULT NULL,
  fecha datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY fk_movimiento_producto (producto_id),
  KEY fk_movimiento_usuario (usuario_id),
  CONSTRAINT fk_movimiento_producto FOREIGN KEY (producto_id) REFERENCES productos (id),
  CONSTRAINT fk_movimiento_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: egresos
--  Compras de insumos del negocio: una fila por producto comprado.
--  El monto es siempre cantidad por precio unitario.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS egresos;

CREATE TABLE egresos (
  id int(11) NOT NULL AUTO_INCREMENT,
  usuario_id int(11) DEFAULT NULL,
  concepto varchar(150) NOT NULL,
  cantidad decimal(10,2) DEFAULT NULL,
  precio_unitario decimal(10,2) DEFAULT NULL,
  unidad varchar(20) DEFAULT NULL,
  descripcion varchar(255) DEFAULT NULL,
  monto decimal(10,2) NOT NULL,
  fecha datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY fk_egreso_usuario (usuario_id),
  CONSTRAINT fk_egreso_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: usuarios
--  Los usuarios que pueden entrar al sistema, con su rol y su contrasena.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id int(11) NOT NULL AUTO_INCREMENT,
  rol_id int(11) NOT NULL,
  nombre varchar(100) NOT NULL,
  usuario varchar(50) NOT NULL,
  password varchar(255) NOT NULL,
  estado tinyint(1) NOT NULL DEFAULT 1,
  fecha_creacion datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY usuario (usuario),
  KEY fk_usuarios_rol (rol_id),
  CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles (id)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: asistencias
--  Registro de entrada y salida de los trabajadores, dia por dia.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS asistencias;

CREATE TABLE asistencias (
  id int(11) NOT NULL AUTO_INCREMENT,
  empleado_id int(11) NOT NULL,
  fecha date NOT NULL,
  hora_entrada time DEFAULT NULL,
  hora_salida time DEFAULT NULL,
  estado enum('presente','tardanza','falta') NOT NULL DEFAULT 'presente',
  PRIMARY KEY (id),
  UNIQUE KEY empleado_id (empleado_id,fecha),
  CONSTRAINT fk_asistencia_empleado FOREIGN KEY (empleado_id) REFERENCES empleados (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: asistencias_log
--  Auditoria de cada marcaje (entrada/salida) en Control de acceso.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS asistencias_log;

CREATE TABLE asistencias_log (
  id int(11) NOT NULL AUTO_INCREMENT,
  empleado_id int(11) NOT NULL,
  accion enum('entrada','salida') NOT NULL,
  fecha_hora datetime NOT NULL DEFAULT current_timestamp(),
  ip varchar(45) DEFAULT NULL,
  user_agent varchar(255) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY fk_asistencia_log_empleado (empleado_id),
  CONSTRAINT fk_asistencia_log_empleado FOREIGN KEY (empleado_id) REFERENCES empleados (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: pagos_personal
--  Sueldos pagados a los trabajadores, por periodo.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS pagos_personal;

CREATE TABLE pagos_personal (
  id int(11) NOT NULL AUTO_INCREMENT,
  empleado_id int(11) NOT NULL,
  periodo varchar(50) NOT NULL,
  monto decimal(10,2) NOT NULL,
  fecha_pago date NOT NULL,
  observacion varchar(255) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY fk_pago_personal_empleado (empleado_id),
  CONSTRAINT fk_pago_personal_empleado FOREIGN KEY (empleado_id) REFERENCES empleados (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------
--  Tabla: roles
--  Los roles del sistema: administrador, mesero, cocina y cajero.
-- ---------------------------------------------------------
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
  id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(50) NOT NULL,
  descripcion varchar(255) DEFAULT NULL,
  estado tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY nombre (nombre)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =========================================================

-- =========================================================
--  SECCION 2 - LOS DATOS QUE DEJAN EL SISTEMA LISTO
--
--  Con esto ya se puede entrar al sistema y empezar a trabajar.
--
--  Van en este orden porque cada uno depende del anterior: los
--  usuarios necesitan un rol, la carta necesita sus categorias, y
--  los permisos necesitan las dos cosas.
-- =========================================================


-- ---------------------------------------------------------
--  roles
--  Los cuatro roles. Se crean primero porque cada usuario pertenece a un rol.
-- ---------------------------------------------------------
INSERT INTO roles (id, nombre, descripcion, estado) VALUES
  (1, 'Administrador', 'Control total del sistema', 1),
  (2, 'Mesero', 'Atención del salón: toma los pedidos', 1),
  (3, 'Cocina', 'Preparación de los pedidos', 1),
  (4, 'Cajero', 'Cobra las mesas y entrega el vuelto', 1),
  (5, 'Control de acceso', 'Marcaje de entrada y salida de personal', 1);


-- ---------------------------------------------------------
--  usuarios
--  Cinco usuarios de acceso. La contrasena es 123456; cambiala antes de usar el sistema.
-- ---------------------------------------------------------
INSERT INTO usuarios (id, rol_id, nombre, usuario, password, estado, fecha_creacion) VALUES
  (1, 1, 'Administrador', 'admin', '$2y$10$fOqlaJ4nmjjfHoJtu91w4.r1MUXIcyIHZBnz0f7b/0Lby1vzkwagS', 1, '2026-10-15 10:36:18'),
  (2, 2, 'Mesero', 'mesero', '$2y$10$fOqlaJ4nmjjfHoJtu91w4.r1MUXIcyIHZBnz0f7b/0Lby1vzkwagS', 1, '2026-10-15 10:36:18'),
  (3, 3, 'Cocina', 'cocina', '$2y$10$fOqlaJ4nmjjfHoJtu91w4.r1MUXIcyIHZBnz0f7b/0Lby1vzkwagS', 1, '2026-10-15 10:36:18'),
  (4, 4, 'Jorge Ramirez', 'cajero', '$2y$10$fOqlaJ4nmjjfHoJtu91w4.r1MUXIcyIHZBnz0f7b/0Lby1vzkwagS', 1, '2026-10-15 10:36:18'),
  (5, 5, 'Control de Acceso', 'control_acceso', '$2y$10$fOqlaJ4nmjjfHoJtu91w4.r1MUXIcyIHZBnz0f7b/0Lby1vzkwagS', 1, '2026-10-15 10:36:18');


-- ---------------------------------------------------------
--  metodos_pago
--  Las formas de pago que salen al cobrar una mesa.
-- ---------------------------------------------------------
INSERT INTO metodos_pago (id, nombre, estado) VALUES
  (1, 'Efectivo', 1),
  (2, 'Yape', 1),
  (3, 'Plin', 1),
  (4, 'Tarjeta', 1);


-- ---------------------------------------------------------
--  categorias
--  Los cinco grupos en los que se ordena la carta.
-- ---------------------------------------------------------
INSERT INTO categorias (id, nombre, descripcion, estado) VALUES
  (1, 'Pollos', 'Pollos a la brasa, al ají y rostizados', 1),
  (2, 'Platos', 'Platos típicos de la casa', 1),
  (3, 'Guarniciones', 'Papas, arroz y acompañamientos', 1),
  (4, 'Bebidas', 'Gaseosas, jugos y limonadas', 1),
  (5, 'Postres', 'Crema volteada y suspiro limeño', 1);


-- ---------------------------------------------------------
--  productos
--  La carta: 13 platos y bebidas con su precio y su stock.
-- ---------------------------------------------------------
INSERT INTO productos (id, categoria_id, nombre, descripcion, precio, stock, stock_minimo, agotado, estado, fecha_creacion, fecha_actualizacion) VALUES
  (1, 1, 'Pollo a la brasa', 'Pollo entero a la brasa', 28.00, 18, 10, 0, 1, '2026-10-15 10:36:18', '2026-10-15 10:36:18'),
  (2, 1, 'Pollo a la parrilla', 'Pollo a la parrilla', 30.00, 0, 8, 0, 1, '2026-10-15 10:36:18', '2026-10-15 10:36:18'),
  (3, 1, 'Pollo rostizado', 'Pollo rostizado al horno', 35.00, 0, 8, 0, 1, '2026-10-15 10:36:18', '2026-10-15 10:36:18'),
  (4, 2, 'Causa Brahmana', 'Causa con ají amarillo', 18.00, 0, 12, 0, 1, '2026-10-15 10:36:18', '2026-10-15 10:36:18'),
  (5, 2, 'Ceviche clásico', 'Ceviche con camote', 26.00, 0, 10, 0, 1, '2026-10-15 10:36:18', '2026-10-15 10:36:18'),
  (6, 2, 'Ají de gallina', 'Ají de gallina criollo', 200.00, 0, 10, 0, 1, '2026-10-15 10:36:18', '2026-10-15 10:36:18'),
  (7, 3, 'Papa frita', 'Porción de papa frita', 7.00, 0, 12, 0, 1, '2026-10-15 10:36:18', '2026-10-15 10:36:18'),
  (8, 3, 'Arroz chaufa', 'Arroz con pollo', 9.00, 0, 10, 0, 1, '2026-10-15 10:36:18', '2026-10-15 10:36:18'),
  (9, 4, 'Gaseosa 500ml', 'Gaseosa personal', 5.00, 0, 24, 0, 1, '2026-10-15 10:36:18', '2026-10-15 10:36:18'),
  (10, 4, 'Chicha morada', 'Chicha morada 1L', 8.00, 0, 6, 0, 1, '2026-10-15 10:36:18', '2026-10-15 10:36:18'),
  (11, 4, 'Limonada frozen', 'Limonada frozen', 10.00, 0, 8, 0, 1, '2026-10-15 10:36:18', '2026-10-15 10:36:18'),
  (12, 5, 'Crema volteada', 'Crema volteada', 6.00, 0, 6, 0, 1, '2026-10-15 10:36:18', '2026-10-15 10:36:18'),
  (13, 5, 'Suspiro limeño', 'Suspiro limeño', 6.50, 0, 6, 0, 1, '2026-10-15 10:36:18', '2026-10-15 10:36:18');


-- ---------------------------------------------------------
--  mesas
--  Las 16 mesas del salon, todas libres al empezar.
-- ---------------------------------------------------------
INSERT INTO mesas (id, numero, capacidad, estado, reservado_por, reservado_hora, reservado_fecha) VALUES
  (1, 1, 2, 'libre', NULL, NULL, NULL),
  (2, 2, 4, 'libre', NULL, NULL, NULL),
  (3, 3, 4, 'libre', NULL, NULL, NULL),
  (4, 4, 2, 'libre', NULL, NULL, NULL),
  (5, 5, 4, 'libre', NULL, NULL, NULL),
  (6, 6, 2, 'libre', NULL, NULL, NULL),
  (7, 7, 2, 'libre', NULL, NULL, NULL),
  (8, 8, 4, 'libre', NULL, NULL, NULL),
  (9, 9, 6, 'libre', NULL, NULL, NULL),
  (10, 10, 2, 'libre', NULL, NULL, NULL),
  (11, 11, 6, 'libre', NULL, NULL, NULL),
  (12, 12, 2, 'libre', NULL, NULL, NULL),
  (13, 13, 6, 'libre', NULL, NULL, NULL),
  (14, 14, 2, 'libre', NULL, NULL, NULL),
  (15, 15, 2, 'libre', NULL, NULL, NULL),
  (16, 16, 2, 'libre', NULL, NULL, NULL);


-- ---------------------------------------------------------
--  empleados
--  Los cinco trabajadores con su cargo.
-- ---------------------------------------------------------
INSERT INTO empleados (id, nombre, usuario_id, dni, telefono, direccion, cargo, fecha_ingreso, estado) VALUES
  (1, 'Lino Javier', NULL, 70123456, 987654321, 'Av.Principal 123', 'administrador', '2026-01-15', 1),
  (2, 'Ana Quispe', NULL, 71234567, 976543210, 'Av.Principal 124', 'mesero', '2026-02-01', 1),
  (3, 'Carlos Rojas', NULL, 72345678, 965432101, 'Av.Principal 125', 'cocinero', '2026-02-10', 1),
  (4, 'Maria Diaz', NULL, 73456789, 954321010, 'Av.Principal 126', 'mesero', '2026-03-01', 1),
  (5, 'Jorge Ramirez', 4, 74567890, 943210109, 'Av.Principal 127', 'cajero', '2026-03-15', 1);


-- ---------------------------------------------------------
--  configuracion_negocio
--  Los datos del local, que salen al pie de los reportes.
-- ---------------------------------------------------------
INSERT INTO configuracion_negocio (id, nombre, ruc, direccion, telefono, logo) VALUES
  (1, 'Makako''s Pollería Restaurante', 20512345678, 'Av. Brasil 1458', '01 456-7890', NULL);


-- =========================================================
--  SECCION 3 - QUE VE CADA ROL
--
--  El sistema no tiene los roles escritos en el codigo: los lee de
--  aca. Al entrar, cada persona ve en el menu solo los modulos que
--  su rol tiene permitido, y los botones que no le corresponden ni
--  siquiera aparecen.
--
--    Administrador  todo
--    Mesero         mesas, pedidos, inventario y menu. Toma y envia
--                   pedidos, pero ya no cobra: de eso se encarga el cajero
--    Cocina         mesas, pedidos, inventario y menu. Mueve el
--                   estado de los pedidos, pero no registra ni cobra
--    Cajero         cobra las mesas y entrega el vuelto. Ve las mesas y
--                   los pedidos, y entra a "Cobrar" para cerrar las cuentas
--    Control de acceso  solo entra a la pantalla de Control de acceso para
--                       marcar su entrada y salida con PIN
-- =========================================================

-- ---------------------------------------------------------
--  permisos
--  La lista completa de permisos que puede tener un rol.
-- ---------------------------------------------------------
INSERT INTO permisos (id, nombre, descripcion) VALUES
  (1, 'dashboard', 'Ver el tablero de resumen'),
  (2, 'ventas', 'Ver ventas, historial y caja'),
  (3, 'cobrar', 'Cobrar las mesas y entregar el vuelto'),
  (4, 'mesas', 'Ver las mesas del salón'),
  (5, 'mesas_reservar', 'Reservar mesas y quitar la reserva'),
  (6, 'mesas_editar', 'Agregar y eliminar mesas'),
  (7, 'pedidos_ver', 'Ver los pedidos existentes'),
  (8, 'pedidos_crear', 'Registrar pedidos y enviarlos a cocina'),
  (9, 'pedidos_editar', 'Modificar o eliminar pedidos'),
  (10, 'pedidos_estado', 'Aceptar el pedido, marcarlo listo y servirlo'),
  (11, 'menu_dia', 'Ver el menú del día y el stock de cada plato'),
  (12, 'menu_dia_editar', 'Marcar un plato como agotado cuando ya se terminó'),
  (13, 'inventario', 'Ver productos y stock'),
  (14, 'inventario_editar', 'Crear productos y ajustar stock'),
  (15, 'finanzas', 'Ver ingresos y registrar compras de insumos'),
  (16, 'personal', 'Ver trabajadores, asistencia y pagos'),
  (17, 'servicios', 'Ver servicios básicos y vencimientos'),
  (18, 'reportes', 'Ver y exportar reportes'),
  (19, 'configuracion', 'Administrar usuarios, roles y datos del negocio'),
  (20, 'control_acceso_usar', 'Usar la pantalla de Control de acceso');


-- ---------------------------------------------------------
--  rol_permiso
--  Que permiso tiene cada rol, para que cada persona vea solo lo que le corresponde.
-- ---------------------------------------------------------
INSERT INTO rol_permiso (id, rol_id, permiso_id) VALUES
  (1, 1, 3),
  (2, 1, 19),
  (3, 1, 1),
  (4, 1, 15),
  (5, 1, 13),
  (6, 1, 14),
  (7, 1, 11),
  (8, 1, 12),
  (9, 1, 4),
  (10, 1, 6),
  (11, 1, 5),
  (12, 1, 8),
  (13, 1, 9),
  (14, 1, 10),
  (15, 1, 7),
  (16, 1, 16),
  (17, 1, 18),
  (18, 1, 17),
  (19, 1, 2),
  (32, 2, 13),
  (33, 2, 11),
  (34, 2, 12),
  (35, 2, 4),
  (36, 2, 5),
  (37, 2, 8),
  (38, 2, 7),
  (47, 3, 13),
  (48, 3, 11),
  (49, 3, 4),
  (50, 3, 10),
  (51, 3, 7),
  (56, 4, 3),
  (57, 5, 20);

SET FOREIGN_KEY_CHECKS = 1;

