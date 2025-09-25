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

    


    public function EliminarProyecto()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
            exit();
        }

        $project_id = $_POST['project_id'] ?? null;

        if (!$project_id) 
        {
            echo json_encode(['status' => 'error', 'message' => 'ID de proyecto no proporcionado']);
            exit();
        }

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




//Clase Issue por programar
class Issues
{
    public function ObtenerIssues()
    {
        try 
        {
            $pdo = new Conexion();
            $sql = $pdo->query("SELECT issue_id, name, description, images_for_issue FROM issues ORDER BY issue_id ASC");
            $issues = $sql->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'ok', 'projects' => $issues]);
        } 

        catch (PDOException $e) 
        {
            echo json_encode(['status' => 'error', 'message' => 'Error al obtener proyectos']);
        }
    
    }




    public function AgregarIssue()
    {
        date_default_timezone_set('America/Mexico_City');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') 
        {
            // Para peticiones CORS preflight
            http_response_code(200);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') 
        {
            // Verificar que vengan todos los campos
            if (
                !isset($_FILES['image']) || 
                !isset($_POST['name']) || 
                !isset($_POST['description']) || 
                !isset($_POST['status']) || 
                !isset($_POST['estimed_time']) || 
                !isset($_POST['project_id'])
            ) {
                echo json_encode(['status' => 'error', 'message' => 'Faltan campos']);
                exit();
            }

            $name = $_POST['name'];
            $description = $_POST['description'];
            $imagenIssue = $_FILES['image'];
            $status = $_POST['status'];
            $estimed_time = $_POST['estimed_time'];
            $project_id = $_POST['project_id'];

            // Validar y mover archivo
            $targetDir = "imagenes_issues/";
            if (!is_dir($targetDir)) 
            {
                mkdir($targetDir, 0755, true);
            }

            $fileName = uniqid() . "_" . basename($imagenIssue["name"]);
            $targetFilePath = $targetDir . $fileName;

            $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($imageFileType, $allowedTypes)) 
            {
                echo json_encode(['status' => 'error', 'message' => 'Formato de imagen no permitido']);
                exit();
            }

            if (move_uploaded_file($imagenIssue['tmp_name'], $targetFilePath)) 
            {
                // Guardar en DB
                try 
                {
                    $pdo = new Conexion();
                    $sql = $pdo->prepare("INSERT INTO issues (project_id, name, description, images_for_issue, status, estimed_time, date_reported) 
                                        VALUES (:project_id, :name, :description, :images_for_issue, :status, :estimed_time, :date_reported)");
                    $sql->execute([
                        'project_id' => $project_id,
                        'name' => $name,
                        'description' => $description,
                        'images_for_issue' => $targetFilePath,
                        'status' => $status,
                        'estimed_time' => $estimed_time,
                        'date_reported' => date("Y-m-d")
                    ]);

                    $issue_id = $pdo->lastInsertId();
                    echo json_encode([
                        'status' => 'ok',
                        'message' => 'Issue guardado correctamente',
                        'images_for_issue' => $targetFilePath,
                        'issue_id' => $issue_id
                    ]);
                } 

                catch (PDOException $e) 
                {
                    echo json_encode(['status' => 'error', 'message' => 'Error al guardar issue: ' . $e->getMessage()]);
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






    public function ObtenerIssuesPorProyecto($project_id) 
    {
        try {
            $pdo = new Conexion();
            $sql = $pdo->prepare("SELECT * FROM issues WHERE project_id = :project_id ORDER BY issue_id ASC");
            $sql->execute(['project_id' => $project_id]);
            $issues = $sql->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'ok', 'issues' => $issues]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }






    public function ObtenerUsuarios()
    {
        try 
        {
            $pdo = new Conexion();
            $sql = $pdo->query("SELECT name FROM users ORDER BY user_id ASC");
            $users = $sql->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'ok', 'users' => $users]);
        } 

        catch (PDOException $e) 
        {
            echo json_encode(['status' => 'error', 'message' => 'Error al obtener usuarios php']);
        }
    
    }



    public function UsuariosFiltrados_Front()
    {
        try 
        {
            $pdo = new Conexion();
            $sql = $pdo->query("SELECT name FROM users WHERE department_id = 1 ORDER BY user_id ASC");
            $users_front = $sql->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'ok', 'users' => $users_front]);
        } 

        catch (PDOException $e) 
        {
            echo json_encode(['status' => 'error', 'message' => 'Error al obtener proyectos']);
        }
    }



    public function UsuariosFiltrados_Backend()
    {
        try 
        {
            $pdo = new Conexion();
            $sql = $pdo->query("SELECT name FROM users WHERE department_id = 2 ORDER BY user_id ASC");
            $users_front = $sql->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'ok', 'users' => $users_front]);
        } 

        catch (PDOException $e) 
        {
            echo json_encode(['status' => 'error', 'message' => 'Error al obtener proyectos']);
        }
    }


    public function UsuariosFiltrados_QA()
    {
        try 
        {
            $pdo = new Conexion();
            $sql = $pdo->query("SELECT name FROM users WHERE department_id = 3 ORDER BY user_id ASC");
            $users_front = $sql->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['status' => 'ok', 'users' => $users_front]);
        } 

        catch (PDOException $e) 
        {
            echo json_encode(['status' => 'error', 'message' => 'Error al obtener proyectos']);
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