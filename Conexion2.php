<?php
class Conexion extends mysqli {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "sis_int_prueba";

    public function __construct() {
        parent::__construct($this->host, $this->user, $this->pass, $this->dbname);

        if ($this->connect_error) {
            die(json_encode(["status" => "error", "message" => "Error de conexión: " . $this->connect_error]));
        }
        $this->set_charset("utf8mb4");
    }
}
?>
