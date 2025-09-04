<?php

class Stream
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllByClass($classId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM streams WHERE class_id = ? ORDER BY name ASC");
        $stmt->execute([$classId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllWithClass()
    {
        $sql = "SELECT s.id, s.name as stream_name, c.name as class_name
                FROM streams s
                JOIN classes c ON s.class_id = c.id
                ORDER BY c.name, s.name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM streams WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($name, $classId)
    {
        $stmt = $this->pdo->prepare("INSERT INTO streams (name, class_id) VALUES (?, ?)");
        return $stmt->execute([$name, $classId]);
    }

    public function update($id, $name)
    {
        $stmt = $this->pdo->prepare("UPDATE streams SET name = ? WHERE id = ?");
        return $stmt->execute([$name, $id]);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM streams WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
