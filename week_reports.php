<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/Conexion.php";

$conn = new Conexion();


$accion = $_GET['accion'] ?? '';

switch ($accion) {

   
    case 'get_weeks':
        getWeeks($conn);
        break;

    
    case 'get_week_reports':
        $week_id = $_GET['week_id'] ?? null;
        if ($week_id) {
            getWeekReports($conn, intval($week_id));
        } else {
            echo json_encode(["status" => "error", "message" => "Falta week_id"]);
        }
        break;

    
    case 'save_week_report':
        saveWeekReport($conn);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Acción no válida"]);
        break;
}

$conn->close();


// Obtener todas las semanas
function getWeeks($conn) {
    $sql = "SELECT * FROM weeks";
    $result = $conn->query($sql);

    $weeks = [];
    while ($row = $result->fetch_assoc()) {
        $weeks[] = $row;
    }

    echo json_encode(["status" => "ok", "weeks" => $weeks]);
}

// Obtener reportes de una semana
function getWeekReports($conn, $week_id) {
    $sql = "SELECT * FROM week_reports WHERE week_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $week_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }

    echo json_encode(["status" => "ok", "week_reports" => $reports]);
}

// Guardar un reporte semanal
function saveWeekReport($conn) {
    if (!isset($_POST['week_id']) || !isset($_FILES['report_file'])) {
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        return;
    }

    $week_id = intval($_POST['week_id']);
    $file = $_FILES['report_file'];

    // Carpeta destino
    $targetDir = __DIR__ . "/../reports/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Nombre del archivo
    $fileName = "WeekReport_" . $week_id . "_" . basename($file['name']);
    $targetPath = $targetDir . $fileName;

    // Intentar mover el archivo
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $relativePath = "/Reports/" . $fileName;

        // Guardar en la DB
        $sql = "INSERT INTO week_reports (week_id, week_report_path) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $week_id, $relativePath);

        if ($stmt->execute()) {
            echo json_encode([
                "status" => "ok",
                "message" => "Reporte subido correctamente",
                "path" => $relativePath
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al guardar en DB"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Error al mover archivo"]);
    }
}

?>
