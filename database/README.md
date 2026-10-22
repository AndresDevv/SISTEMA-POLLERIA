# Base de datos — Sistema de Pollería MAKAKO'S

Esta carpeta tiene **un solo archivo que importa**: `instalar.sql`.

---

## Instalar

`instalar.sql` crea la base de datos completa: las 25 tablas y todo lo
necesario para empezar a trabajar.

### Opción 1 — Línea de comandos (un paso)

Abre el Símbolo del sistema en la carpeta del proyecto y ejecuta:

```
mysql -u root --default-character-set=utf8mb4 < database\instalar.sql
```

**No debe mostrar nada.** Si muestra errores, mira *Problemas frecuentes* más abajo.

### Opción 2 — phpMyAdmin

1. Abre XAMPP y arranca **Apache** y **MySQL**.
2. Entra a `http://localhost/phpmyadmin`
3. Pestaña **Importar** → elige `database\instalar.sql` → **Continuar**

No hace falta crear la base antes: el archivo la crea.

### Qué deja instalado

- 25 tablas con sus relaciones
- 5 roles: Administrador, Mesero, Cocina, Cajero y Control de acceso
- 5 usuarios de acceso
- La carta: 5 categorías y 13 platos y bebidas
- 16 mesas del salón
- 5 trabajadores
- 4 formas de pago y los datos del negocio

> El historial de pedidos y ventas **no** viene: eso se genera con el uso.

### Si ya hay una base `polleria`

El archivo borra las tablas que encuentre y las vuelve a crear, así que se
lleva por delante los datos que hubiera. Si quieres conservarlos, saca antes
una copia (ver *Respaldos*).

---

## Usuarios

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| `admin` | `123456` | Administrador |
| `mesero` | `123456` | Mesero |
| `cocina` | `123456` | Cocina |
| `cajero` | `123456` | Cajero |
| `control_acceso` | `123456` | Control de acceso |

**Cámbialas antes de usar el sistema en el local:** entra con el administrador,
ve a **Configuración → Usuarios**.

---

## Respaldos

**Sacar una copia:**
```
mysqldump -u root --default-character-set=utf8mb4 polleria > mi_respaldo.sql
```

**Restaurarla:**
```
mysql -u root --default-character-set=utf8mb4 polleria < mi_respaldo.sql
```

Conviene hacer un respaldo antes de cualquier cambio grande.

---

## Problemas frecuentes

**"Error de conexión" al entrar al sistema**

MySQL no está arrancado. Revisa en XAMPP que el botón **Start** de MySQL esté
pulsado y en verde.

Si MySQL sí está arrancado, probablemente sea la contraseña. En una
instalación normal de XAMPP el usuario es `root` y no tiene clave, pero si en
esa máquina es distinto, no hay que tocar el código: crea en la raíz del
proyecto un archivo `database.local.php` con esto:

```php
<?php
return [
    'servidor' => 'localhost',
    'base'     => 'polleria',
    'usuario'  => 'root',
    'clave'    => 'la contraseña de esa máquina',
];
```

**Las tildes se ven raras**

El archivo se cargó con otra codificación. Vuelve a hacerlo con el comando de
consola, que ya lleva `--default-character-set=utf8mb4`.

**"La tabla no existe" o "Base de datos desconocida"**

Falta instalar. Corre `database/instalar.sql`.

**Pide un archivo SQL de una base que ya existe**

Es normal: el instalador siempre crea la base desde cero.

---

## Para dar soporte

Si algo no funciona, mira primero el mensaje de error en pantalla y después la
sección *Problemas frecuentes* de este README y de `INSTALACION.md`, en la
raíz del proyecto.
