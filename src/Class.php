<?php

class SchoolClass
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll()
    {
        $stmt = $this->pdo->query("SELECT * FROM classes ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM classes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($name)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO classes (name) VALUES (?)");
            return $stmt->execute([$name]);
        } catch (PDOException $e) {
            // Handle duplicate entry
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    public function update($id, $name)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE classes SET name = ? WHERE id = ?");
            return $stmt->execute([$name, $id]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM classes WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
