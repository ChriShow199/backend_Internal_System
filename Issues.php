<?php

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

    public function AgregarIssue(int )
    {
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