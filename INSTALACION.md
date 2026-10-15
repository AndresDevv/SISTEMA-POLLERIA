# Cómo levantar el sistema en otra máquina

Guía paso a paso. Está pensada para alguien que recibe el sistema por primera
vez.

**Tiempo aproximado:** 15 minutos.

---

## Qué necesita la máquina

| Requisito | Versión |
|-----------|---------|
| XAMPP | 8.0 o superior (con Apache y MySQL) |
| MySQL | 5.7 o superior |
| PHP | **8.0 o superior** (viene incluido en XAMPP) |
| Navegador | Chrome, Edge o Firefox actual |

No hace falta instalar nada más: las librerías de JavaScript y los estilos ya
vienen dentro de la carpeta del proyecto.

---

## Paso 1 — Instalar XAMPP

1. Descarga XAMPP de [apachefriends.org](https://www.apachefriends.org/es/download.html).
2. Instálalo con los componentes **Apache** y **MySQL** marcados.
3. Ábrelo y deja el botón **Start** pulsado en los dos.

> Si el puerto 80 está ocupado por otro programa (Skype, IIS, otro servidor),
> el Apache no arrancará. En ese caso, cierra esos programas o deja el sistema
> en el puerto 8080 (ver la nota al final).

---

## Paso 2 — Copiar el proyecto

Copia la carpeta completa del sistema dentro de `htdocs`:

```
C:\xampp\htdocs\POLLERIA
```

Se puede copiar con el nombre que quieras, por ejemplo `polleria`. El sistema
calcula solo sus direcciones, así que no hay que cambiar nada dentro del código.

No copies solo algunos archivos: **copia la carpeta entera**.

---

## Paso 3 — Crear la base de datos

Abre el Símbolo del sistema (Símbolo de comandos) y ve a la carpeta del
proyecto:

```
cd C:\xampp\htdocs\POLLERIA
```

Ejecuta:

```
mysql -u root --default-character-set=utf8mb4 < database\instalar.sql
```

**No debe mostrar nada.** Si muestra errores, mira la sección *Problemas
frecuentes* más abajo.

### Alternativa con phpMyAdmin

Si prefieres no usar la consola:

1. Entra a `http://localhost/phpmyadmin`
2. Pestaña **Importar**
3. Elige el archivo `database\instalar.sql`
4. **Continuar**

El archivo crea la base `polleria` por su cuenta, no hace falta crearla antes.

---

## Paso 4 — Entrar al sistema

Abre el navegador en:

```
http://localhost/POLLERIA
```

Entra con:

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| `admin` | `123456` | Administrador |
| `mesero` | `123456` | Mesero |
| `cocina` | `123456` | Cocina |

Empieza con `admin` para que veas todo.

---

## Paso 5 — Cambiar las contraseñas

**Antes de empezar a usar el sistema de verdad**, cambia las contraseñas de los
tres usuarios:

1. Entra como `admin`.
2. Ve a **Configuración → Usuarios**.
3. Cambia la contraseña de cada usuario.

---

## Paso opcional — Si el MySQL pide contraseña

En una instalación normal de XAMPP el usuario es `root` y no tiene contraseña.
Si en esa máquina el MySQL tiene otra contraseña, **no hay que tocar el código**.

Crea un archivo llamado `database.local.php` en la raíz del proyecto (junto a
`index.php`, no dentro de `database/`) con este contenido:

```php
<?php
return [
    'servidor' => 'localhost',
    'base'     => 'polleria',
    'usuario'  => 'root',
    'clave'    => 'la contraseña de esa máquina',
];
```

El sistema usará esos datos en vez de los que vienen por defecto.

---

## Resumen en 4 líneas

1. Instalar y arrancar XAMPP (Apache + MySQL).
2. Copiar la carpeta en `C:\xampp\htdocs\`.
3. Correr `mysql -u root --default-character-set=utf8mb4 < database\instalar.sql`.
4. Abrir `http://localhost/POLLERIA` y entrar como `admin / 123456`.

---

## Problemas frecuentes

### "Error de conexión" al entrar al sistema

MySQL no está arrancado. Revisa en XAMPP que el botón **Start** de MySQL esté
pulsado y en verde.

Si MySQL sí está arrancado, probablemente sea lo de la contraseña: usa el
archivo `database.local.php` del paso opcional.

### El Apache no arranca y dice que el puerto 80 está ocupado

Cierra programas que usen ese puerto (Skype, otro servidor web) y vuelve a
pulsar Start. Si no puedes, deja el sistema en el puerto 8080: abre el
Administrador de archivos como administrador, entra a `C:\xampp\apache\conf`,
edita el archivo `httpd.conf`, cambia la línea

```
Listen 80
```

por

```
Listen 8080
```

y entra entonces a `http://localhost:8080/POLLERIA`.

### Las tildes se ven raras ("MesÃ¡s", "CÃ³digo")

El archivo SQL se cargó con la codificación equivocada. Vuelve a hacerlo
usando el comando de consola, que ya lleva `--default-character-set=utf8mb4`.
Si prefieres phpMyAdmin, elige UTF-8 en el desplegable de abajo al importar.

### "La tabla no existe" o "Base de datos desconocida"

Falta el paso 3. Corre `database/instalar.sql`.

### Pide el archivo SQL de una base que ya existe

Significa que ya había una base `polleria` y el archivo la está reemplazando.
Es normal: el instalador siempre crea la base desde cero. Si tenías datos que
quieres conservar, no sigas y avisa.

### ¿Dónde hago respaldos?

```
mysqldump -u root --default-character-set=utf8mb4 polleria > mi_respaldo.sql
```

Y para restaurarlo:

```
mysql -u root --default-character-set=utf8mb4 polleria < mi_respaldo.sql
```

Conviene hacer uno antes de cualquier cambio grande.

---

## Para dar soporte

En la carpeta `database/` está el detalle de cada archivo SQL y la guía de la
base de datos. En el resto del código, cada módulo tiene su encabezado
explicando qué hace.
