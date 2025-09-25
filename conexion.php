<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
class Conexion extends PDO
{
    private $host='localhost:3306';
    private $nombreBD='proyectochido';
    private $usuario='root';
    private $contraseña='root55';

    public function __construct()
    {
        try
        {
            parent::__construct('mysql:host='  .  $this->host . ';dbname=' . $this->nombreBD . ';charset=utf8',
            $this->usuario,  $this->contraseña, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        }
        catch(PDOException $e)
        {
            echo 'Error: '  .  $e->getMessage();
            exit;
        }
    }
}
?>