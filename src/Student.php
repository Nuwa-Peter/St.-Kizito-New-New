<?php

class Student
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll()
    {
        // Join with streams and classes to get the full context
        $sql = "SELECT s.id, s.first_name, s.last_name, s.other_name, s.lin, s.profile_photo_path, st.name as stream_name, c.name as class_name, st.id as stream_id
                FROM students s
                JOIN streams st ON s.stream_id = st.id
                JOIN classes c ON st.class_id = c.id
                ORDER BY c.name, st.name, s.last_name, s.first_name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllByStream($streamId)
    {
        $sql = "SELECT s.id, s.first_name, s.last_name, s.lin, s.profile_photo_path, st.name as stream_name, c.name as class_name
                FROM students s
                JOIN streams st ON s.stream_id = st.id
                JOIN classes c ON st.class_id = c.id
                WHERE s.stream_id = ?
                ORDER BY s.last_name, s.first_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$streamId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($firstName, $lastName, $otherName, $lin, $streamId, $photoPath = null)
    {
        // Use a default placeholder if no photo is provided
        $photoPath = $photoPath ?? 'assets/images/default_avatar.png';
        $otherName = empty($otherName) ? null : $otherName;
        $stmt = $this->pdo->prepare(
            "INSERT INTO students (first_name, last_name, other_name, lin, stream_id, profile_photo_path) VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$firstName, $lastName, $otherName, $lin, $streamId, $photoPath]);
    }

    public function update($id, $firstName, $lastName, $otherName, $lin, $streamId, $photoPath = null)
    {
        $otherName = empty($otherName) ? null : $otherName;
        if ($photoPath) {
            $stmt = $this->pdo->prepare(
                "UPDATE students SET first_name = ?, last_name = ?, other_name = ?, lin = ?, stream_id = ?, profile_photo_path = ? WHERE id = ?"
            );
            return $stmt->execute([$firstName, $lastName, $otherName, $lin, $streamId, $photoPath, $id]);
        } else {
            // Don't update the photo if a new one isn't provided
            $stmt = $this->pdo->prepare(
                "UPDATE students SET first_name = ?, last_name = ?, other_name = ?, lin = ?, stream_id = ? WHERE id = ?"
            );
            return $stmt->execute([$firstName, $lastName, $otherName, $lin, $streamId, $id]);
        }
    }

    public function delete($id)
    {
        // Optional: Also delete the profile photo file from the server
        $student = $this->findById($id);
        if ($student && $student['profile_photo_path'] !== 'assets/images/default_avatar.png') {
            if (file_exists('public/' . $student['profile_photo_path'])) {
                unlink('public/' . $student['profile_photo_path']);
            }
        }

        $stmt = $this->pdo->prepare("DELETE FROM students WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
