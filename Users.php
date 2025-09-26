<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/Conexion.php";

$conn = new Conexion();

$accion = $_GET['accion'] ?? '';

switch ($accion) {

    case 'get_users':
        getUsers($conn);
        break;

    case 'add_user':
        addUser($conn);
        break;

    case 'get_schedules':
        $user_id = $_GET['user_id'] ?? null;
        if ($user_id) {
            getSchedules($conn, intval($user_id));
        } else {
            echo json_encode(["status" => "error", "message" => "Falta user_id"]);
        }
        break;

    case 'save_schedules':
        saveSchedules($conn);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Acción no válida"]);
        break;
}

$conn->close();


// ================== USUARIOS ==================

// Obtener todos los usuarios con su rol
function getUsers($conn) {
    $sql = "SELECT user_id AS id_usuario, name AS nombre, role AS rol FROM users";
    $result = $conn->query($sql);

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode(["status" => "ok", "users" => $users]);
}

// Agregar usuario (por ahora sin department_id)
function addUser($conn) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !isset($data['name']) || !isset($data['password']) || !isset($data['role'])) {
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        return;
    }

    $name = $data['name'];
    $password = password_hash($data['password'], PASSWORD_BCRYPT); // seguridad
    $role = $data['role']; // "Collaborator", "DH", "Admin"

    $sql = "INSERT INTO users (name, password, role) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $password, $role);

    if ($stmt->execute()) {
        echo json_encode(["status" => "ok", "message" => "Usuario agregado correctamente"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al agregar usuario: " . $stmt->error]);
    }
}


// ================== SCHEDULES ==================

// Obtener horarios de un usuario
function getSchedules($conn, $user_id) {
    $sql = "SELECT * FROM schedules WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    echo json_encode(["status" => "ok", "schedules" => $schedules]);
}

// Guardar horarios (insertar/actualizar)
function saveSchedules($conn) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !isset($data['user_id']) || !isset($data['days'])) {
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        return;
    }

    $user_id = intval($data['user_id']);
    $success = 0;
    $errors = [];

    foreach ($data['days'] as $day => $values) {
        $check_in    = $values['check_in'] ?? null;
        $lunch_start = $values['lunch_break_start'] ?? null;
        $lunch_end   = $values['lunch_break_end'] ?? null;
        $check_out   = $values['check_out'] ?? null;

        if (!$check_in || !$lunch_start || !$lunch_end || !$check_out) {
            $errors[] = "Faltan campos en $day";
            continue;
        }

        $sql = "INSERT INTO schedules (user_id, work_day, check_in, lunch_break_start, lunch_break_end, check_out)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                check_in = VALUES(check_in),
                lunch_break_start = VALUES(lunch_break_start),
                lunch_break_end = VALUES(lunch_break_end),
                check_out = VALUES(check_out)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss", $user_id, $day, $check_in, $lunch_start, $lunch_end, $check_out);

        if ($stmt->execute()) {
            $success++;
        } else {
            $errors[] = "Error en $day: " . $stmt->error;
        }
    }

    echo json_encode([
        "status" => $success > 0 ? "ok" : "error",
        "message" => "Se guardaron $success registros",
        "errors" => $errors
    ]);
}
