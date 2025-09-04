<?php

class User
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUsername($username)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function getAll()
    {
        // Also fetch stream name for teachers
        $sql = "SELECT u.id, u.first_name, u.last_name, u.username, u.role, u.stream_id, s.name as stream_name
                FROM users u
                LEFT JOIN streams s ON u.stream_id = s.id
                ORDER BY u.role, u.last_name, u.first_name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($firstName, $lastName, $username, $password, $role = 'teacher')
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (first_name, last_name, username, password, role) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$firstName, $lastName, $username, $hashedPassword, $role]);
    }

    public function login($username, $password)
    {
        $user = $this->findByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function updateUser($id, $firstName, $lastName, $username, $role, $streamId)
    {
        // If the role is not 'teacher', the stream_id should be null
        $streamId = ($role === 'teacher') ? $streamId : null;

        $stmt = $this->pdo->prepare(
            "UPDATE users SET first_name = ?, last_name = ?, username = ?, role = ?, stream_id = ? WHERE id = ?"
        );
        return $stmt->execute([$firstName, $lastName, $username, $role, $streamId, $id]);
    }

    public function deleteUser($id)
    {
        // To prevent deleting the main superadmin (user id 1)
        if ($id == 1) {
            return false;
        }
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
