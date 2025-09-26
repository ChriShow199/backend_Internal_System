<?php
header("Access-Control-Allow-Origin: *"); // o "*" si quieres todos
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') 
{
    // Para peticiones CORS preflight
    http_response_code(200);
    exit();
}

include 'conexion.php';

class Projects
{
    public function NuevoProyecto()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') 
        {
            // Verificar que venga el archivo
            if (!isset($_FILES['image']) || !isset($_POST['name']) || !isset($_POST['description'])) 
            {
                echo json_encode(['status' => 'error', 'message' => 'Faltan campos']);
                exit();
            }

            $name = $_POST['name'];
            $description = $_POST['description'];
            $image = $_FILES['image'];

            // Validar y mover archivo
            $targetDir = "imagenes/";
            if (!is_dir($targetDir)) 
            {
                mkdir($targetDir, 0755, true);
            }

            $fileName = uniqid() . "_" . basename($image["name"]);
            $targetFilePath = $targetDir . $fileName;

            $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($imageFileType, $allowedTypes)) 
            {
                echo json_encode(['status' => 'error', 'message' => 'Formato de imagen no permitido']);
                exit();
            }

            if (move_uploaded_file($image['tmp_name'], $targetFilePath)) 
            {
                // Guardar en DB
                try 
                {
                    $pdo = new Conexion();
                    $sql = $pdo->prepare("INSERT INTO projects (name, description, image_path) VALUES (:name, :description, :image_path)");
                    $sql->execute([
                        'name' => $name,
                        'description' => $description,
                        'image_path' => $targetFilePath
                    ]);

                    $project_id = $pdo->lastInsertId();
                    echo json_encode([
                        'status' => 'ok',
                        'message' => 'Proyecto guardado correctamente',
                        'image_path' => $targetFilePath,
                        'project_id' => $project_id
                    ]);
                } 

                catch (PDOException $e) 
                {
                    echo json_encode(['status' => 'error', 'message' => 'Error al guardar proyecto: ' . $e->getMessage()]);
                }
            } 
            
            else 
            {
                echo json_encode(['status' => 'error', 'message' => 'Error al subir imagen']);
            }
        }

        else 
        {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
        }
    }



    public function ObtenerProyectos()
    {
        try 
        {
            $pdo = new Conexion();
            $sql = $pdo->query("SELECT project_id, name, description, image_path FROM projects ORDER BY project_id ASC");
            $projects = $sql->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'ok', 'projects' => $projects]);
        } 

        catch (PDOException $e) 
        {
            echo json_encode(['status' => 'error', 'message' => 'Error al obtener proyectos']);
        }
    }

    


    public function EliminarProyecto(int $project_id)
    {
        try 
        {
            $pdo = new Conexion();
            // Primero obtenemos la ruta de la imagen para eliminarla
            $sql = $pdo->prepare("SELECT image_path FROM projects WHERE project_id = :id");
            $sql->execute(['id' => $project_id]);
            $proyecto = $sql->fetch(PDO::FETCH_ASSOC);

            if ($proyecto) {
                if (file_exists($proyecto['image_path'])) {
                    unlink($proyecto['image_path']); // eliminar archivo de imagen
                }

                $sqlDelete = $pdo->prepare("DELETE FROM projects WHERE project_id = :id");
                $sqlDelete->execute(['id' => $project_id]);

                echo json_encode(['status' => 'ok', 'message' => 'Proyecto eliminado correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Proyecto no encontrado']);
            }
        } 
        
        catch (PDOException $e) 
        {
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar proyecto: ' . $e->getMessage()]);
        }
    }
}

$project = new Projects();
$issues = new Issues();
$accion = $_GET['accion'] ?? '';

switch ($accion) 
{
    case 'nuevoproyecto':
        $project -> NuevoProyecto();
        break;

    case 'proyectoobtenido':
        $project -> ObtenerProyectos();
        break;

    case 'eliminarproyecto':
        $project -> EliminarProyecto();
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
        echo json_encode(['Accion no valida']);
        break;
}
?>