<?php
/* =============================================================================
   app/models/UsuarioModel.php  -  MODELO: consultas a la tabla "usuarios"
   =============================================================================
   Base de datos: database/pcam.db (SQLite).
   Si la tabla no existe la crea sola con 2 usuarios de ejemplo:
       admin   / Admin1234    rol 1 (Administrador)  IP 127.0.0.1
       usuario / Usuario1234  rol 2 (Usuario)        sin IP

   Roles:  1 = Administrador (ve Usuarios y Configuración)
           2 = Usuario       (ve Datos y Descarga)
           3 = Invitado      (ve Datos y Descarga)

   Todas las consultas son PREPARADAS (con ?) para evitar inyección SQL.
   ============================================================================= */

class UsuarioModel
{
    private static $pdo = null;

    /** Abre la conexión a SQLite (una sola vez) y crea la tabla si hace falta. */
    private static function conexion()
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite:' . DATABASE_PATH . '/pcam.db');
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::crearTabla();
        }
        return self::$pdo;
    }

    /** Crea la tabla usuarios y los usuarios de ejemplo (solo la primera vez). */
    private static function crearTabla()
    {
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id        INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre    TEXT NOT NULL,
                username  TEXT NOT NULL UNIQUE COLLATE NOCASE,
                password  TEXT NOT NULL,          -- cifrada con password_hash()
                email     TEXT DEFAULT '',
                ip        TEXT DEFAULT '',        -- IP del equipo para entrar sin login
                rol       INTEGER NOT NULL DEFAULT 2
            )
        ");

        $total = self::$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        if ($total == 0) {
            $st = self::$pdo->prepare('INSERT INTO usuarios (nombre, username, password, email, ip, rol) VALUES (?, ?, ?, ?, ?, ?)');
            $st->execute(['Administrador', 'admin', password_hash('Admin1234', PASSWORD_DEFAULT), 'admin@cfe.mx', '127.0.0.1', 1]);
            $st->execute(['Usuario de ejemplo', 'usuario', password_hash('Usuario1234', PASSWORD_DEFAULT), 'usuario@cfe.mx', '', 2]);
        }
    }

    // ------------------------------------------------------------- CONSULTAS

    /** Busca al usuario que tiene registrada esta IP. Devuelve el arreglo o false. */
    public static function buscarPorIp($ip)
    {
        $st = self::conexion()->prepare("SELECT * FROM usuarios WHERE ip = ? AND ip <> '' LIMIT 1");
        $st->execute([$ip]);
        return $st->fetch();
    }

    /** Busca un usuario por su nombre de usuario. Devuelve el arreglo o false. */
    public static function buscarPorUsuario($username)
    {
        $st = self::conexion()->prepare('SELECT * FROM usuarios WHERE username = ? LIMIT 1');
        $st->execute([$username]);
        return $st->fetch();
    }

    /** Busca un usuario por id. Devuelve el arreglo o false. */
    public static function buscarPorId($id)
    {
        $st = self::conexion()->prepare('SELECT * FROM usuarios WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch();
    }

    /** Lista todos los usuarios (sin la contraseña). */
    public static function listar()
    {
        return self::conexion()
            ->query('SELECT id, nombre, username, email, ip, rol FROM usuarios ORDER BY id')
            ->fetchAll();
    }

    /** Devuelve true si el usuario o la IP ya los tiene OTRO registro. */
    public static function existe($campo, $valor, $id)
    {
        if ($valor === '') return false;
        $campo = ($campo === 'ip') ? 'ip' : 'username'; // solo estas dos columnas
        $st = self::conexion()->prepare("SELECT COUNT(*) FROM usuarios WHERE $campo = ? AND id <> ?");
        $st->execute([$valor, $id]);
        return $st->fetchColumn() > 0;
    }

    /**
     * Crea (id = 0) o actualiza un usuario.
     * $d = ['id', 'nombre', 'username', 'password', 'email', 'ip', 'rol']
     * Al editar, si password viene vacío se conserva la contraseña actual.
     */
    public static function guardar($d)
    {
        $pdo = self::conexion();

        if ($d['id'] == 0) {
            $st = $pdo->prepare('INSERT INTO usuarios (nombre, username, password, email, ip, rol) VALUES (?, ?, ?, ?, ?, ?)');
            return $st->execute([$d['nombre'], $d['username'], password_hash($d['password'], PASSWORD_DEFAULT), $d['email'], $d['ip'], $d['rol']]);
        }

        $st = $pdo->prepare('UPDATE usuarios SET nombre = ?, username = ?, email = ?, ip = ?, rol = ? WHERE id = ?');
        $st->execute([$d['nombre'], $d['username'], $d['email'], $d['ip'], $d['rol'], $d['id']]);

        if ($d['password'] !== '') {
            $st = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
            $st->execute([password_hash($d['password'], PASSWORD_DEFAULT), $d['id']]);
        }
        return true;
    }

    /** Borra un usuario por id. */
    public static function borrar($id)
    {
        $st = self::conexion()->prepare('DELETE FROM usuarios WHERE id = ?');
        return $st->execute([$id]);
    }
}
