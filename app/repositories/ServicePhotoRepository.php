<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/ServicePhotoMapper.php";

/**
 * Fotos dos serviços — `servico_foto` (F3.1 · §3.4.4 · §4.6).
 *
 * A imagem PRINCIPAL do card é a linha com `destaque = 1`; as restantes
 * alimentam a galeria dos detalhes, por `ordem_exibicao` (D-25/D-13).
 */
class ServicePhotoRepository extends BaseRepository {

    protected ?string $mapper = ServicePhotoMapper::class;

    public function findByService(int $serviceId): array {
        $sql = "SELECT id, servico_id, url_foto, destaque, ordem_exibicao
                FROM servico_foto
                WHERE servico_id = :servico_id
                ORDER BY destaque DESC, ordem_exibicao ASC, id ASC";

        $rows = $this->fetchAll($sql, ["servico_id" => $serviceId]);
        return is_array($rows) ? $rows : [];
    }

    public function findById(int $photoId): ?array {
        $sql = "SELECT id, servico_id, url_foto, destaque, ordem_exibicao
                FROM servico_foto WHERE id = :id LIMIT 1";

        $row = $this->fetch($sql, ["id" => $photoId]);
        return is_array($row) ? $row : null;
    }

    /**
     * URLs agrupadas por serviço — enriquece o catálogo público sem N+1 consultas.
     * Devolve `[servicoId => [ {url, featured}, ... ]]`.
     */
    public function findGroupedByServices(array $serviceIds): array {
        $serviceIds = array_values(array_unique(array_map("intval", $serviceIds)));
        if (empty($serviceIds)) return [];

        $placeholders = [];
        $params = [];
        foreach ($serviceIds as $i => $id) {
            $key = "s" . $i;
            $placeholders[] = ":" . $key;
            $params[$key] = $id;
        }

        $sql = "SELECT servico_id, url_foto, destaque
                FROM servico_foto
                WHERE servico_id IN (" . implode(",", $placeholders) . ")
                ORDER BY servico_id ASC, destaque DESC, ordem_exibicao ASC, id ASC";

        $rows = $this->fetchAllRaw($sql, $params);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int)$row["servico_id"]][] = [
                "url"      => (string)$row["url_foto"],
                "featured" => (bool)$row["destaque"]
            ];
        }

        return $grouped;
    }

    public function create(int $serviceId, string $urlFoto, bool $featured, int $displayOrder): int {
        $sql = "INSERT INTO servico_foto (servico_id, url_foto, destaque, ordem_exibicao)
                VALUES (:servico_id, :url_foto, :destaque, :ordem)";

        $this->execute($sql, [
            "servico_id" => $serviceId,
            "url_foto"   => $urlFoto,
            "destaque"   => $featured ? 1 : 0,
            "ordem"      => $displayOrder
        ]);

        return (int)$this->lastInsertId();
    }

    public function countByService(int $serviceId): int {
        $row = $this->fetchRaw("SELECT COUNT(*) AS total FROM servico_foto WHERE servico_id = :servico_id", ["servico_id" => $serviceId]);
        return (int)($row["total"] ?? 0);
    }

    public function clearFeatured(int $serviceId): void {
        $this->execute("UPDATE servico_foto SET destaque = 0 WHERE servico_id = :servico_id", ["servico_id" => $serviceId]);
    }

    public function setFeatured(int $photoId): void {
        $this->execute("UPDATE servico_foto SET destaque = 1 WHERE id = :id", ["id" => $photoId]);
    }

    public function delete(int $photoId): void {
        $this->execute("DELETE FROM servico_foto WHERE id = :id", ["id" => $photoId]);
    }
}
