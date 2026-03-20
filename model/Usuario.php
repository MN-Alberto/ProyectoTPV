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

    /** 
     * @var int|null Identificador único autoincremental del usuario. 
     */
    private $id;
    /** 
     * @var string|null Nombre completo o alias del empleado. 
     */
    private $nombre;
    /** 
     * @var string|null Correo electrónico que sirve como identificador de inicio de sesión. 
     */
    private $email;
    /** 
     * @var string|null Hash de la contraseña del usuario (cifrada con BCRYPT). 
     */
    private $password;
    /** 
     * @var string|null Rol del sistema que determina los permisos generales ('admin' o 'empleado'). 
     */
    private $rol; // 'admin' o 'empleado'
    /** 
     * @var string|null Fecha y hora en la que el usuario fue registrado en el sistema. 
     */
    private $fechaAlta;
    /** 
     * @var bool|int Estado de cuenta (1 si el usuario puede entrar al sistema, 0 si está baneado/desactivado). 
     */
    private $activo;
    /** 
     * @var int|null Contador acumulado de pausas o descansos registrados por el empleado. 
     */
    private $totalDescansos;
    /** 
     * @var int|null Contador acumulado de turnos de caja completados. 
     */
    private $totalTurnos;
    /** 
     * @var string|null Cadena con permisos específicos separados por comas (ej: 'crear_productos,editar_precios'). 
     */
    private $permisos;

    // ======================== GETTERS ========================

    /** 
     * Obtiene el identificador numérico del usuario.
     * @return int|null 
     */
    public function getId()
    {
        return $this->id;
    }

    /** 
     * Obtiene el nombre completo o alias del usuario.
     * @return string|null 
     */
    public function getNombre()
    {
        return $this->nombre;
    }

    /** 
     * Obtiene la dirección de correo electrónico del usuario.
     * @return string|null 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /** 
     * Obtiene el hash de la contraseña almacenada.
     * @return string|null 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /** 
     * Obtiene el rol asignado al usuario (admin/empleado).
     * @return string|null 
     */
    public function getRol()
    {
        return $this->rol;
    }

    /** 
     * Obtiene la fecha y hora de registro del usuario.
     * @return string|null 
     */
    public function getFechaAlta()
    {
        return $this->fechaAlta;
    }

    /** 
     * Comprueba si el usuario se encuentra actualmente activo.
     * @return bool|int 
     */
    public function getActivo()
    {
        return $this->activo;
    }

    /** 
     * Obtiene el número total de descansos realizados.
     * @return int|null 
     */
    public function getTotalDescansos()
    {
        return $this->totalDescansos;
    }

    /** 
     * Obtiene el número total de turnos de caja finalizados.
     * @return int|null 
     */
    public function getTotalTurnos()
    {
        return $this->totalTurnos;
    }

    /** 
     * Obtiene la cadena que contiene los permisos específicos del usuario.
     * @return string|null 
     */
    public function getPermisos()
    {
        return $this->permisos;
    }

    // ======================== SETTERS ========================

    /** 
     * Cambia el ID del usuario (usar con precaución).
     * @param int $id El nuevo identificador.
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /** 
     * Define el nombre completo o alias del empleado.
     * @param string $nombre El nombre a mostrar en el sistema.
     */
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }

    /** 
     * Establece el correo electrónico para el inicio de sesión.
     * @param string $email Dirección de correo válida.
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /** 
     * Almacena el hash de la contraseña (debe estar ya cifrada).
     * @param string $password El hash de la contraseña.
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /** 
     * Asigna un rol al usuario para determinar sus capacidades básicas.
     * @param string $rol Debe ser 'admin' o 'empleado'.
     */
    public function setRol($rol)
    {
        $this->rol = $rol;
    }

    /** 
     * Define la fecha de registro inicial en el sistema.
     * @param string $fechaAlta Formato Y-m-d H:i:s.
     */
    public function setFechaAlta($fechaAlta)
    {
        $this->fechaAlta = $fechaAlta;
    }

    /** 
     * Activa o desactiva la cuenta de usuario.
     * @param bool|int $activo 1 para activo, 0 para inactivo.
     */
    public function setActivo($activo)
    {
        $this->activo = $activo;
    }

    /** 
     * Establece el contador total de descansos realizados.
     * @param int $totalDescansos Número entero de descansos.
     */
    public function setTotalDescansos($totalDescansos)
    {
        $this->totalDescansos = $totalDescansos;
    }

    /** 
     * Establece el número total de turnos de caja completados.
     * @param int $totalTurnos Cifra total de turnos.
     */
    public function setTotalTurnos($totalTurnos)
    {
        $this->totalTurnos = $totalTurnos;
    }

    /** 
     * Asigna la lista de permisos específicos del usuario.
     * @param string|null $permisos Cadena separada por comas de los permisos.
     */
    public function setPermisos($permisos)
    {
        $this->permisos = $permisos;
    }

    /**
     * Comprueba si el usuario actual posee un permiso determinado en su lista.
     * 
     * @param string $permiso El nombre técnico del permiso (ej: 'registrar_venta').
     * @return bool True si lo tiene concedido, false en caso contrario.
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
     * Obtiene todos los usuarios con paginación eficiente.
     * @param int $pagina Número de página (1-based)
     * @param int $porPagina Registros por página
     * @return array ['usuarios' => array, 'total' => int, 'pagina' => int, 'porPagina' => int, 'totalPaginas' => int]
     */
    public static function obtenerTodosPaginados($pagina = 1, $porPagina = 6)
    {
        $conexion = ConexionDB::getInstancia()->getConexion();

        // Obtener total de registros (usando índice)
        $stmtCount = $conexion->query("SELECT COUNT(*) as total FROM usuarios");
        $total = (int)$stmtCount->fetch()['total'];

        // Calcular OFFSET
        $offset = ($pagina - 1) * $porPagina;

        // Consulta optimizada con LIMIT y ORDER BY usando índice
        $stmt = $conexion->prepare("SELECT * FROM usuarios ORDER BY id LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $usuarios = [];
        while ($fila = $stmt->fetch()) {
            $usuarios[] = self::crearDesdeArray($fila);
        }

        return [
            'usuarios' => $usuarios,
            'total' => $total,
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'totalPaginas' => (int)ceil($total / $porPagina)
        ];
    }

    /**
     * Busca usuarios por nombre con paginación.
     * @param string $nombre Texto a buscar
     * @param int $pagina Número de página
     * @param int $porPagina Registros por página
     * @return array
     */
    public static function buscarPorNombreParcialPaginado($nombre, $pagina = 1, $porPagina = 6)
    {
        $conexion = ConexionDB::getInstancia();
        $pdo = $conexion->getConexion();
        $busqueda = '%' . $nombre . '%';

        // Contar resultados con índice
        $stmtCount = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE nombre LIKE :nombre");
        $stmtCount->bindParam(':nombre', $busqueda);
        $stmtCount->execute();
        $total = (int)$stmtCount->fetch()['total'];

        $offset = ($pagina - 1) * $porPagina;

        // Consulta paginada con índice
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre LIKE :nombre ORDER BY nombre LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':nombre', $busqueda);
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $usuarios = [];
        while ($fila = $stmt->fetch()) {
            $usuarios[] = self::crearDesdeArray($fila);
        }

        return [
            'usuarios' => $usuarios,
            'total' => $total,
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'totalPaginas' => (int)ceil($total / $porPagina)
        ];
    }

    /**
     * Obtiene todos los usuarios (sin paginación - para backward compatibility).
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