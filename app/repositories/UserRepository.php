<?php

require_once __DIR__ . '/BaseRepository.php';

class UserRepository extends BaseRepository {
    
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM utilizador WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    public function create(array $data, string $passwordHash): int {
        $stmt = $this->db->prepare("
            INSERT INTO utilizador (nome, email, password_hash, telemovel, tipo_perfil) 
            VALUES (:nome, :email, :password_hash, :telemovel, :tipo_perfil)
        ");
        
        $stmt->execute([
            'nome' => $data['nome'],
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'telemovel' => $data['telemovel'],
            'tipo_perfil' => $data['tipoPerfil'] ?? 'cliente'
        ]);

        return $this->db->lastInsertId();
    }
}