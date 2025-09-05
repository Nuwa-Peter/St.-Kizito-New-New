<?php

class Subject
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll()
    {
        $stmt = $this->pdo->query("SELECT * FROM subjects ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM subjects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($name, $initials = null)
    {
        $initials = empty($initials) ? null : $initials;
        $stmt = $this->pdo->prepare("INSERT INTO subjects (name, teacher_initials) VALUES (?, ?)");
        return $stmt->execute([$name, $initials]);
    }

    public function update($id, $name, $initials = null)
    {
        $initials = empty($initials) ? null : $initials;
        $stmt = $this->pdo->prepare("UPDATE subjects SET name = ?, teacher_initials = ? WHERE id = ?");
        return $stmt->execute([$name, $initials, $id]);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM subjects WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
