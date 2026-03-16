<?php

/**
 * Clase modelo para la gestión de usuarios del TPV.
 * 
 * @author Alberto Méndez
 * @version 1.2 (02/03/2026)
 */

// Requerimos el fichero de conexión a la base de datos
require_once(__DIR__ . '/../core/conexionDB.php');

class Usuario
{

    private $id;
    private $nombre;
    private $email;
    private $password;
    private $rol; // 'admin' o 'empleado'
    private $fechaAlta;
    private $activo;
    private $totalDescansos;
    private $totalTurnos;
    private $permisos;

    // ======================== GETTERS ========================

    public function getId()
    {
        return $this->id;
    }

    public function getNombre()
    {
        return $this->nombre;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getRol()
    {
        return $this->rol;
    }

    public function getFechaAlta()
    {
        return $this->fechaAlta;
    }

    public function getActivo()
    {
        return $this->activo;
    }

    public function getTotalDescansos()
    {
        return $this->totalDescansos;
    }

    public function getTotalTurnos()
    {
        return $this->totalTurnos;
    }

    public function getPermisos()
    {
        return $this->permisos;
    }

    // ======================== SETTERS ========================

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function setRol($rol)
    {
        $this->rol = $rol;
    }

    public function setFechaAlta($fechaAlta)
    {
        $this->fechaAlta = $fechaAlta;
    }

    public function setActivo($activo)
    {
        $this->activo = $activo;
    }

    public function setTotalDescansos($totalDescansos)
    {
        $this->totalDescansos = $totalDescansos;
    }

    public function setTotalTurnos($totalTurnos)
    {
        $this->totalTurnos = $totalTurnos;
    }

    public function setPermisos($permisos)
    {
        $this->permisos = $permisos;
    }

    /**
     * Verifica si el usuario tiene un permiso específico.
     * @param string $permiso El permiso a verificar (ej: 'crear_productos')
     * @return bool
     */
    public function tienePermiso($permiso)
    {
        if ($this->permisos === null || $this->permisos === '') {
            return false;
        }
        // Los permisos están almacenados como una cadena separada por comas
        $permisosArray = explode(',', $this->permisos);
        return in_array($permiso, $permisosArray);
    }

    // ======================== MÉTODOS CRUD ========================

    /**
     * Busca un usuario por su email.
     * @param string $email
     * @return Usuario|null
     */
    public static function buscarPorEmail($email)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE email = :email");
        // Vinculamos los parámetros
        $stmt->bindParam(':email', $email);
        // Ejecutamos la consulta
        $stmt->execute();
        // Obtenemos la fila
        $fila = $stmt->fetch();
        // Si se encuentra la fila, creamos un nuevo usuario
        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        // Si no se encuentra la fila, devolvemos null
        return null;
    }

    /**
     * Busca un usuario por su nombre.
     * @param string $nombre
     * @return Usuario|null
     */
    public static function buscarPorNombre($nombre)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE nombre = :nombre");
        // Vinculamos los parámetros
        $stmt->bindParam(':nombre', $nombre);
        // Ejecutamos la consulta
        $stmt->execute();
        // Obtenemos la fila
        $fila = $stmt->fetch();
        // Si se encuentra la fila, creamos un nuevo usuario
        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        // Si no se encuentra la fila, devolvemos null
        return null;
    }

    /**
     * Busca usuarios por nombre (búsqueda parcial).
     * @param string $nombre
     * @return array
     */
    public static function buscarPorNombreParcial($nombre)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta con LIKE para búsqueda parcial
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE nombre LIKE :nombre ORDER BY nombre");
        // Vinculamos el parámetro con comodines para búsqueda parcial
        $busqueda = '%' . $nombre . '%';
        $stmt->bindParam(':nombre', $busqueda);
        // Ejecutamos la consulta
        $stmt->execute();
        // Creamos un array para guardar los usuarios
        $usuarios = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos un nuevo usuario y lo añadimos al array
            $usuarios[] = self::crearDesdeArray($fila);
        }
        // Devolvemos los usuarios
        return $usuarios;
    }

    /**
     * Busca un usuario por su ID.
     * @param int $id
     * @return Usuario|null
     */
    public static function buscarPorId($id)
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = :id");
        // Vinculamos los parámetros
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        $stmt->execute();
        // Obtenemos la fila
        $fila = $stmt->fetch();
        // Si se encuentra la fila, creamos un nuevo usuario
        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        // Si no se encuentra la fila, devolvemos null
        return null;
    }

    /**
     * Obtiene todos los usuarios.
     * @return array
     */
    public static function obtenerTodos()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->query("SELECT * FROM usuarios ORDER BY nombre");
        // Creamos un array para guardar los usuarios
        $usuarios = [];
        // Recorremos las filas
        while ($fila = $stmt->fetch()) {
            // Creamos un nuevo usuario y lo añadimos al array
            $usuarios[] = self::crearDesdeArray($fila);
        }
        // Devolvemos los usuarios
        return $usuarios;
    }

    /**
     * Inserta un nuevo usuario en la base de datos.
     * @return bool
     */
    public function insertar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "INSERT INTO usuarios (nombre, email, password, rol, fechaAlta, activo, total_descansos, total_turnos, permisos) 
             VALUES (:nombre, :email, :password, :rol, :fechaAlta, :activo, :total_descansos, :total_turnos, :permisos)"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':rol', $this->rol);
        $stmt->bindParam(':fechaAlta', $this->fechaAlta);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':total_descansos', $this->totalDescansos, PDO::PARAM_INT);
        $stmt->bindParam(':total_turnos', $this->totalTurnos, PDO::PARAM_INT);
        $stmt->bindParam(':permisos', $this->permisos);
        // Ejecutamos la consulta
        $resultado = $stmt->execute();
        // Obtenemos el ID del nuevo usuario
        $this->id = $conexion->lastInsertId();
        // Devolvemos el resultado
        return $resultado;
    }

    /**
     * Actualiza los datos del usuario en la base de datos.
     * @return bool
     */
    public function actualizar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare(
            "UPDATE usuarios SET nombre = :nombre, email = :email, password = :password, 
             rol = :rol, activo = :activo, total_descansos = :total_descansos, 
             total_turnos = :total_turnos, permisos = :permisos WHERE id = :id"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':rol', $this->rol);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':total_descansos', $this->totalDescansos, PDO::PARAM_INT);
        $stmt->bindParam(':total_turnos', $this->totalTurnos, PDO::PARAM_INT);
        $stmt->bindParam(':permisos', $this->permisos);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        return $stmt->execute();
    }

    /**
     * Elimina el usuario de la base de datos.
     * @return bool
     */
    public function eliminar()
    {
        // Obtenemos la instancia de la conexión
        $conexion = ConexionDB::getInstancia()->getConexion();
        // Preparamos la consulta
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id");
        // Vinculamos los parámetros
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        // Ejecutamos la consulta
        return $stmt->execute();
    }

    /**
     * Verifica las credenciales de login.
     * @param string $nombre
     * @param string $password
     * @return Usuario|null
     */
    public static function login($nombre, $password)
    {
        // Buscamos el usuario por nombre
        $usuario = self::buscarPorNombre($nombre);
        // Verificamos que el usuario existe, que la contraseña es correcta y que el usuario está activo
        if ($usuario && password_verify($password, $usuario->getPassword()) && $usuario->getActivo()) {
            return $usuario;
        }
        // Si no se encuentra el usuario, devolvemos null
        return null;
    }

    /**
     * Incrementa el contador de descansos de un usuario.
     * @param int $id El ID del usuario.
     * @return bool
     */
    public static function incrementarDescansos($id)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("UPDATE usuarios SET total_descansos = total_descansos + 1 WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Incrementa el contador de turnos de un usuario.
     * @param int $id El ID del usuario.
     * @return bool
     */
    public static function incrementarTurnos($id)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("UPDATE usuarios SET total_turnos = total_turnos + 1 WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ======================== MÉTODOS AUXILIARES ========================

    /**
     * Crea un objeto Usuario a partir de un array asociativo (fila de la BD).
     * @param array $fila
     * @return Usuario
     */
    private static function crearDesdeArray($fila)
    {
        // Creamos un nuevo usuario
        $usuario = new Usuario();
        // Asignamos los valores de la fila al usuario
        $usuario->setId($fila['id']);
        $usuario->setNombre($fila['nombre']);
        $usuario->setEmail($fila['email']);
        $usuario->setPassword($fila['password']);
        $usuario->setRol($fila['rol']);
        $usuario->setFechaAlta($fila['fechaAlta']);
        $usuario->setActivo($fila['activo']);
        $usuario->setTotalDescansos(isset($fila['total_descansos']) ? $fila['total_descansos'] : 0);
        $usuario->setTotalTurnos(isset($fila['total_turnos']) ? $fila['total_turnos'] : 0);
        $usuario->setPermisos(isset($fila['permisos']) ? $fila['permisos'] : null);
        // Devolvemos el usuario
        return $usuario;
    }
}

?>