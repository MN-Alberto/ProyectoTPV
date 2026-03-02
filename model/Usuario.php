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
            "INSERT INTO usuarios (nombre, email, password, rol, fechaAlta, activo) 
             VALUES (:nombre, :email, :password, :rol, :fechaAlta, :activo)"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':rol', $this->rol);
        $stmt->bindParam(':fechaAlta', $this->fechaAlta);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
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
             rol = :rol, activo = :activo WHERE id = :id"
        );
        // Vinculamos los parámetros
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':rol', $this->rol);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
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
        // Devolvemos el usuario
        return $usuario;
    }
}

?>