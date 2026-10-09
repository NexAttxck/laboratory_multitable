<?php
// classes/Database.php
// PDO wrapper implementing the Singleton pattern.
class Database extends PDO
{
    private static $instance = null;

    private function __construct($dsn, $user, $pass)
    {
        parent::__construct($dsn, $user, $pass);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public static function getInstance($dsn, $user = null, $pass = null)
    {
        if (self::$instance === null)
        {
            self::$instance = new Database($dsn, $user, $pass);
        }
        return self::$instance;
    }

    public function insert($table, array $data)
    {
        $cols = implode(", ", array_keys($data));
        $phs  = ":" . implode(", :", array_keys($data));
        $sql  = "INSERT INTO $table ($cols) VALUES ($phs)";
        $stmt = $this->prepare($sql);
        $stmt->execute($data);
        return $this->lastInsertId();
    }

    public function getRows($sql, array $params = [])
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getRow($sql, array $params = [])
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
