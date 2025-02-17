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

    public function addUser($userId,$discordId,$smurf) {
        $sql = "INSERT INTO users (id,discord_id,smurf) VALUES (:id,:discord_id,:smurf)";
        $stmt = $this->pdo->prepare($sql);
    
        try {
            $stmt->execute(['id' => $userId,'discord_id' => $discordId,'smurf' => $smurf]);
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
            $sql = "UPDATE
                        users
                    SET 
                        nombre = :nombre,
                        oval_irating = CASE 
                            WHEN :oval_irating > 0 OR COALESCE(oval_irating, 0) = 0 THEN :oval_irating 
                            ELSE oval_irating 
                        END,
                        sports_car_irating = CASE 
                            WHEN :sports_car_irating > 0 OR COALESCE(sports_car_irating, 0) = 0 THEN :sports_car_irating 
                            ELSE sports_car_irating 
                        END,
                        formula_car_irating = CASE 
                            WHEN :formula_car_irating > 0 OR COALESCE(formula_car_irating, 0) = 0 THEN :formula_car_irating 
                            ELSE formula_car_irating 
                        END,
                        dirt_oval_irating = CASE 
                            WHEN :dirt_oval_irating > 0 OR COALESCE(dirt_oval_irating, 0) = 0 THEN :dirt_oval_irating 
                            ELSE dirt_oval_irating 
                        END,
                        dirt_road_irating = CASE 
                            WHEN :dirt_road_irating > 0 OR COALESCE(dirt_road_irating, 0) = 0 THEN :dirt_road_irating 
                            ELSE dirt_road_irating 
                        END
                    WHERE id = :id";
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
        $sql = "UPDATE users SET sports_car_irating = 3500 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
    
        try {
            $stmt->execute([
                'id' => '531258'
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

    public function getHistoric($date) {
        $sql = "SELECT * FROM view_users_historic WHERE fecha = :fecha";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['fecha' => $date]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function addHistoric($date, $users) {
        /*
        users_historic: fecha, id, oval_irating, sports_car_irating, formula_car_irating, dirt_oval_irating, dirt_road_irating, 
        */
        $sql = "DELETE FROM users_historic WHERE fecha = :fecha";
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute(['fecha' => $date]);
        } catch (\PDOException $e) {
            return [false, "❌ Error al eliminar histórico: " . $e->getMessage()];
        }
        foreach ($users as $user) {
            $sql = "INSERT INTO users_historic (fecha, id, oval_irating, sports_car_irating, formula_car_irating, dirt_oval_irating, dirt_road_irating) VALUES (:fecha, :id, :oval_irating, :sports_car_irating, :formula_car_irating, :dirt_oval_irating, :dirt_road_irating)";
            $stmt = $this->pdo->prepare($sql);
            try {
                $stmt->execute([
                    'fecha' => $date,
                    'id' => $user['id'],
                    'oval_irating' => $user['oval_irating'],
                    'sports_car_irating' => $user['sports_car_irating'],
                    'formula_car_irating' => $user['formula_car_irating'],
                    'dirt_oval_irating' => $user['dirt_oval_irating'],
                    'dirt_road_irating' => $user['dirt_road_irating']
                ]);
                var_dump($stmt);
            } catch (\PDOException $e) {
                return [false, "❌ Error al agregar histórico: " . $e->getMessage()];
            }
        }
    }

    public function addRace($race) {
        /*
        CREATE TABLE "user_race" (
            "id" INTEGER,
            "fechahora" INTEGER,
            "fechahora_formated" TEXT,
            "series_name" TEXT,
            "track_id" INTEGER,
            "track_name" TEXT,
            "car_id" INTEGER,
            "car_name" TEXT,
            "car_class_id" INTEGER,
            "start_position" INTEGER,
            "finish_position" INTEGER,
            "incidents" integer,
            "subsession_id" INTEGER,
            "session_start_time" INTEGER,
            "session_start_time_formated" text,
            "strength_of_field" INTEGER,
            irating_new" INTEGER,
            "irating_change" INTEGER,
            PRIMARY KEY ("subsession_id", "id")
        );
        */
        $sql = "SELECT * FROM user_race WHERE id = :id AND subsession_id = :subsession_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $race['id'],
            'subsession_id' => $race['subsession_id']
        ]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($result) {
            return [false, "⚠️ La carrera ya está registrada."];
        }
        $sql = "INSERT INTO user_race (id, fechahora, fechahora_formated, series_name, track_id, track_name, car_id, car_name, car_class_id, start_position, finish_position, incidents, subsession_id, session_start_time, session_start_time_formated, strength_of_field, irating_new, irating_change) VALUES (:id, :fechahora, :fechahora_formated, :series_name, :track_id, :track_name, :car_id, :car_name, :car_class_id, :start_position, :finish_position, :incidents, :subsession_id, :session_start_time, :session_start_time_formated, :strength_of_field, :irating_new, :irating_change)";
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                'id' => $race['id'],
                'fechahora' => $race['fechahora'],
                'fechahora_formated' => $race['fechahora_formated'],
                'series_name' => $race['series_name'],
                'track_id' => $race['track_id'],
                'track_name' => $race['track_name'],
                'car_id' => $race['car_id'],
                'car_name' => $race['car_name'],
                'car_class_id' => $race['car_class_id'],
                'start_position' => $race['start_position'],
                'finish_position' => $race['finish_position'],
                'incidents' => $race['incidents'],
                'subsession_id' => $race['subsession_id'],
                'session_start_time' => $race['session_start_time'],
                'session_start_time_formated' => $race['session_start_time_formated'],
                'strength_of_field' => $race['strength_of_field'],
                'irating_new' => $race['irating_new'],
                'irating_change' => $race['irating_change']
            ]);
            return [true, "✅ Carrera agregada correctamente."];
        } catch (\PDOException $e) {
            return [false, "❌ Error al agregar carrera: " . $e->getMessage()];
        }
    }       
    
    public function getLastRacesByID($id, $limit = 10) {
        $sql = "SELECT * FROM user_race WHERE id = :id ORDER BY fechahora DESC LIMIT ".$limit;
        $stmt = $this->pdo->query($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
