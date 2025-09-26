<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');  
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'localhost';
$db   = 'sis_int_prueba'; 
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error', 'message'=>'Error conexión base de datos']);
    exit;
}

if (!isset($_GET['accion'])) {
    echo json_encode(['status'=>'error', 'message'=>'No se indicó acción']);
    exit;
}

$accion = $_GET['accion'];
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// LOGIN
if ($accion === 'login') {
    if (!$input || empty($input['name']) || empty($input['contrasena'])) {
        echo json_encode(['status'=>'error', 'message'=>'Datos incompletos']);
        exit;
    }

    $name = $input['name'];
    $password = $input['contrasena'];

    $stmt = $pdo->prepare("SELECT user_id, name, password, role FROM users WHERE name = ?");
    $stmt->execute([$name]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($password, $usuario['password'])) {
        echo json_encode([
            'status' => 'ok',
            'name' => $usuario['name'],
            'rol' => $usuario['role'],
            'user_id' => $usuario['user_id']
        ]);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Usuario o contraseña incorrectos']);
    }
    exit;
}

// REGISTRO
if ($accion === 'registro') {
    if (!$input || empty($input['name']) || empty($input['contrasena'])) {
        echo json_encode(['status'=>'error', 'message'=>'Datos incompletos']);
        exit;
    }

    $name = $input['name'];
    $password = $input['contrasena'];
    $role = 'colaborador';

    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        echo json_encode(['status'=>'error', 'message'=>'El usuario ya está registrado']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, password, role) VALUES (?, ?, ?)");
    $res = $stmt->execute([$name, $hash, $role]);

    if ($res) {
        // Obtener el ID del usuario insertado
        $lastId = $pdo->lastInsertId();

        echo json_encode([
            'status' => 'ok',
            'name' => $name,
            'rol' => $role,
            'user_id' => $lastId  // <- aquí está la diferencia
        ]);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Error al registrar usuario']);
    }
    exit;
}


// EDITAR USUARIO
if ($accion === 'editar') {
    if (!$input || empty($input['user_id']) || empty($input['name'])) {
        echo json_encode(['status'=>'error', 'message'=>'Datos incompletos para editar']);
        exit;
    }

    $user_id = intval($input['user_id']);
    $name = trim($input['name']);
    $password = $input['contrasena'] ?? null;  // Puede venir null o ''

    try {
        if ($password && $password !== '****') {
            // Actualizar nombre y contraseña (hash)
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ? WHERE user_id = ?");
            $stmt->execute([$name, $hash, $user_id]);
        } else {
            // Actualizar solo nombre
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE user_id = ?");
            $stmt->execute([$name, $user_id]);
        }
        echo json_encode(['status'=>'ok', 'message'=>'Usuario actualizado']);
    } catch (\PDOException $e) {
        echo json_encode(['status'=>'error', 'message'=>'Error al actualizar usuario']);
    }
    exit;
}

echo json_encode(['status'=>'error', 'message'=>'Acción no válida']);
