<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include 'conexion.php'; // Clase Conexion que retorna instancia PDO

class Usuarios
{
    public function Registro()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $name = $data['correo'] ?? '';
        $password = $data['contrasena'] ?? '';
        $role = 'Collaborator';
        $department_id = $data ['department_id'];

        if ($name && $password && $role)
        {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            try 
            {
                $pdo = new Conexion();
                $sql = $pdo->prepare("INSERT INTO users (name, role, password, department_id) VALUES (:name, :role, :password, :department_id)");
                $sql->execute(['name' => $name, 'role' => $role, 'password' => $hash, 'department_id' =>$department_id]);

                echo json_encode(['status' => 'ok', 'name' => $name, 'role' => $role]);
            } 
            catch (PDOException $e) 
            {
                echo json_encode(['status' => 'error', 'message' => 'Usuario ya registrado']);
            }
        }

        else 
        {
            echo json_encode(['status' => 'error', 'message' => 'Faltan campos']);
        }
    }


        public function Login()
        {
            $data = json_decode(file_get_contents("php://input"), true);
            $name = $data['correo'] ?? '';
            $password = $data['contrasena'] ?? '';

            if ($name && $password) 
            {
                $pdo = new Conexion();
                $sql = $pdo->prepare("SELECT password, role FROM users WHERE name = :name");
                $sql->execute(['name' => $name]);
                $user_verification = $sql->fetch(PDO::FETCH_ASSOC);

                if ($user_verification && password_verify($password, $user_verification['password'])) 
                {
                    echo json_encode(['status' => 'ok', 'name' => $name, 'role' => $user_verification['role']]);
                } 

                else 
                {
                    echo json_encode(['status' => 'error', 'message' => 'Credenciales incorrectas']);
                }
            } 

            else 
            {
                echo json_encode(['status' => 'error', 'message' => 'Faltan campos']);
            }
        }


        public function Departamentos()
        {
            try 
            {
                $pdo = new Conexion();
                $sql = $pdo->query("SELECT department_id, area FROM departments");
                $roles = $sql->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($roles);
            } 

            catch (PDOException $e) 
            {
                echo json_encode(['Error al obtener roles']);
            }
        }
}

// Ejecutar acción
$usuarios = new Usuarios();
$accion = $_GET['accion'] ?? '';

switch ($accion) 
{
    case 'registro':
        $usuarios->Registro();
        break;

    case 'login':
        $usuarios->Login();
        break;
    
    case 'departamentos':
        $usuarios->Departamentos();
        break;

    default:
        echo json_encode(['Accion no valida']);
        break;
}
?>