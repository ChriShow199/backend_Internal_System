<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$project = new Projects();
$issues = new Issues();
$accion = $_GET['accion'] ?? '';

switch ($accion) {

    case 'get_users':
        getUsers($conn);
        break;

    case 'add_user':
        i
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

    case 'nuevoproyecto':
        $project -> NuevoProyecto();
        break;

    case 'proyectoobtenido':
        $project -> ObtenerProyectos();
        break;

    case 'eliminarproyecto':
        if (isset($_POST['project_id'])) {
            $project_id = $_POST['project_id'];
            $project->EliminarProyecto($project_id);
        } else {
            echo "No se mando project_id"
        }

        break;

    case 'cargarusuarios':
        $issues -> ObtenerUsuarios();
        break;

    case 'issues_obtener':
        $issues -> ObtenerIssues();
        break;

    case 'issues_agregar':
        $issues -> AgregarIssue();
        break;

    case 'issues_projects':
        $project_id = $_GET['project_id'] ?? null;
        if ($project_id) 
        {
            $issues->ObtenerIssuesPorProyecto($project_id);
        } 
        else 
        {
            echo json_encode(['status' => 'error', 'message' => 'Falta project_id']);
        }
        break;
    
    case 'filtro_front':
        $issues -> UsuariosFiltrados_Front();
        break;

    case 'filtro_back':
        $issues -> UsuariosFiltrados_Backend();
        break;

    case 'filtro_qa':
        $issues -> UsuariosFiltrados_QA();
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Acción no válida"]);
        break;
}