<?php
namespace Src;

class Database {
    private $pdo;

    public function __construct($dbFile) {
        try {
            $this->pdo = new \PDO("sqlite:" . $dbFile);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->initializeTables();
        } catch (\PDOException $e) {
            die("❌ Error conectando a SQLite: " . $e->getMessage());
        }
    }

    private function initializeTables() {
        /*
            CREATE TABLE "users" (
            "id" INTEGER NOT NULL,
            "nombre" TEXT,
            "oval_irating" integer DEFAULT 0,
            "sports_car_irating" integer DEFAULT 0,
            "formula_car_irating" integer DEFAULT 0,
            "dirt_oval_irating" integer DEFAULT 0,
            "dirt_road_irating" integer DEFAULT 0,
            PRIMARY KEY ("id")
            );
        */
    }

    public function getUser($userId) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
    
        try {
            $stmt->execute(['id' => $userId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return null;
        }
    }

    public function addUser($userId) {
        $sql = "INSERT INTO users (id) VALUES (:id)";
        $stmt = $this->pdo->prepare($sql);
    
        try {
            $stmt->execute(['id' => $userId]);
            return [true, "✅ Usuario agregado correctamente."];
        } catch (\PDOException $e) {
            if ($e->getCode() == "23000") { // Código SQL para "clave duplicada"
                return [false, "⚠️ El usuario ya está registrado."];
            }
            return [false, "❌ Error al agregar el usuario"];
        }
    }

    public function delUser($userId) {
        $user = $this->getUser($userId);
        if (!$user) {
            return [false, "⚠️ El usuario no está registrado."];
        }
        else {
            $sql = "DELETE FROM users WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
        
            try {
                $stmt->execute(['id' => $userId]);
                return [true, "✅ Usuario eliminado correctamente."];
            } catch (\PDOException $e) {
                return [false, "❌ Error al eliminar el usuario: " . $e->getMessage()];
            }
        }
    }

    public function updateUser($userId, $nombre, $oval_irating, $sports_car_irating, $formula_car_irating, $dirt_oval_irating, $dirt_road_irating) {
        $user = $this->getUser($userId);
        if (!$user) {
            return [false, "⚠️ El usuario no está registrado."];
        }
        else {
            $sql = "UPDATE users SET nombre = :nombre, oval_irating = :oval_irating, sports_car_irating = :sports_car_irating, formula_car_irating = :formula_car_irating, dirt_oval_irating = :dirt_oval_irating, dirt_road_irating = :dirt_road_irating WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
        
            try {
                $stmt->execute([
                    'id' => $userId,
                    'nombre' => $nombre,
                    'oval_irating' => $oval_irating,
                    'sports_car_irating' => $sports_car_irating,
                    'formula_car_irating' => $formula_car_irating,
                    'dirt_oval_irating' => $dirt_oval_irating,
                    'dirt_road_irating' => $dirt_road_irating
                ]);
                return [true, "✅ Usuario actualizado correctamente."];
            } catch (\PDOException $e) {
                return [false, "❌ Error al actualizar el usuario: " . $e->getMessage()];
            }
        }
    }

    public function test() {
        $sql = "UPDATE users SET sports_car_irating = :sports_car_irating";
        $stmt = $this->pdo->prepare($sql);
    
        try {
            $stmt->execute([
                'sports_car_irating' => rand(2000,9000)
            ]);
        } catch (\PDOException $e) {
            return [false, "❌ Error al actualizar el usuario: " . $e->getMessage()];
        }
    }

    public function getUsers() {
        $sql = "SELECT * FROM users";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    

}
