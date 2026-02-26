<?php

/*
 * Autor: Alberto Méndez 
 * Fecha de actualización: 24/02/2026
 * 
 * Clase modelo para la gestión de usuarios del TPV.
 */

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
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $fila = $stmt->fetch();

        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        return null;
    }

    /**
     * Busca un usuario por su nombre.
     * @param string $nombre
     * @return Usuario|null
     */
    public static function buscarPorNombre($nombre)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE nombre = :nombre");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->execute();
        $fila = $stmt->fetch();

        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        return null;
    }

    /**
     * Busca un usuario por su ID.
     * @param int $id
     * @return Usuario|null
     */
    public static function buscarPorId($id)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $fila = $stmt->fetch();

        if ($fila) {
            return self::crearDesdeArray($fila);
        }
        return null;
    }

    /**
     * Obtiene todos los usuarios.
     * @return array
     */
    public static function obtenerTodos()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->query("SELECT * FROM usuarios ORDER BY nombre");
        $usuarios = [];
        while ($fila = $stmt->fetch()) {
            $usuarios[] = self::crearDesdeArray($fila);
        }
        return $usuarios;
    }

    /**
     * Inserta un nuevo usuario en la base de datos.
     * @return bool
     */
    public function insertar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "INSERT INTO usuarios (nombre, email, password, rol, fechaAlta, activo) 
             VALUES (:nombre, :email, :password, :rol, :fechaAlta, :activo)"
        );
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':rol', $this->rol);
        $stmt->bindParam(':fechaAlta', $this->fechaAlta);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
        $resultado = $stmt->execute();
        $this->id = $conexion->lastInsertId();
        return $resultado;
    }

    /**
     * Actualiza los datos del usuario en la base de datos.
     * @return bool
     */
    public function actualizar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare(
            "UPDATE usuarios SET nombre = :nombre, email = :email, password = :password, 
             rol = :rol, activo = :activo WHERE id = :id"
        );
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':rol', $this->rol);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Elimina el usuario de la base de datos.
     * @return bool
     */
    public function eliminar()
    {
        $conexion = ConexionDB::getInstancia()->getConexion();
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
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
        $usuario = self::buscarPorNombre($nombre);
        if ($usuario && password_verify($password, $usuario->getPassword()) && $usuario->getActivo()) {
            return $usuario;
        }
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
        $usuario = new Usuario();
        $usuario->setId($fila['id']);
        $usuario->setNombre($fila['nombre']);
        $usuario->setEmail($fila['email']);
        $usuario->setPassword($fila['password']);
        $usuario->setRol($fila['rol']);
        $usuario->setFechaAlta($fila['fechaAlta']);
        $usuario->setActivo($fila['activo']);
        return $usuario;
    }
}

?>