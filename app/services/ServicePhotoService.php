<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/ServicePhotoRepository.php";
require_once APP_PATH . "/repositories/ServiceRepository.php";

/**
 * F3.1 — fotografias dos serviços (upload pelo gestor · §3.4.4 · §4.6).
 *
 * Defesa em profundidade no upload (§4.6): whitelist de extensão + MIME real
 * (`finfo`), re-encode com GD (destrói payloads embutidos e limpa o EXIF),
 * limites de tamanho/dimensões validados no servidor e gravação com nome NOSSO.
 */
class ServicePhotoService extends BaseService {

    private const MAX_BYTES     = 2097152;   // 2 MB
    private const MAX_DIMENSION = 4000;      // px por lado
    private const UPLOAD_RELDIR = "uploads/services";
    private const ALLOWED_MIME  = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    private ServicePhotoRepository $photoRepository;
    private ServiceRepository $serviceRepository;

    public function __construct() {
        parent::__construct();
        $this->photoRepository = new ServicePhotoRepository();
        $this->serviceRepository = new ServiceRepository();
    }

    /** Catálogo do gestor: serviços + fotografias. */
    public function catalog(): array {
        $services = $this->serviceRepository->find();
        $services = is_array($services) ? $services : [];

        $ids = array_map(fn($s) => (int)$s["id"], $services);
        $grouped = $this->photoRepository->findGroupedByServices($ids);

        $out = [];
        foreach ($services as $service) {
            $id = (int)$service["id"];
            $photos = $grouped[$id] ?? [];
            $featured = null;
            foreach ($photos as $photo) {
                if ($photo["featured"]) { $featured = $photo["url"]; break; }
            }

            $out[] = [
                "id"           => $id,
                "name"         => (string)$service["name"],
                "categoryName" => $service["categoryName"] ?? null,
                "featuredUrl"  => $featured,
                "photos"       => $photos
            ];
        }

        return ["services" => $out];
    }

    public function listForService(int $serviceId): array {
        return ["photos" => $this->photoRepository->findByService($serviceId)];
    }

    public function upload(int $serviceId, ?array $file): array {
        return $this->executeTransactional(function() use ($serviceId, $file) {
            $this->assertServiceExists($serviceId);
            $this->assertUploadOk($file);

            $size = (int)$file["size"];
            if ($size <= 0 || $size > self::MAX_BYTES) {
                throw new Exception("A fotografia excede o limite de 2 MB.", 422);
            }

            $mime = $this->detectMime((string)$file["tmp_name"]);
            if (!isset(self::ALLOWED_MIME[$mime])) {
                throw new Exception("Formato não suportado. Envie JPG, PNG ou WEBP.", 422);
            }

            $info = @getimagesize((string)$file["tmp_name"]);
            if ($info === false) {
                throw new Exception("O ficheiro enviado não é uma imagem válida.", 422);
            }
            if ($info[0] > self::MAX_DIMENSION || $info[1] > self::MAX_DIMENSION) {
                throw new Exception("Imagem demasiado grande (máx. " . self::MAX_DIMENSION . " px por lado).", 422);
            }

            $ext  = self::ALLOWED_MIME[$mime];
            $name = sprintf("service-%d-%s.%s", $serviceId, bin2hex(random_bytes(6)), $ext);
            $dir  = ROOT_PATH . "/" . self::UPLOAD_RELDIR;
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            $this->reencode((string)$file["tmp_name"], $dir . "/" . $name, $mime);

            $existing = $this->photoRepository->countByService($serviceId);
            $isFirst  = $existing === 0;
            $url      = self::UPLOAD_RELDIR . "/" . $name;

            $photoId = $this->photoRepository->create($serviceId, $url, $isFirst, $existing);

            return [
                "photoId" => $photoId,
                "url"     => $url,
                "message" => "Fotografia carregada com sucesso."
            ];
        });
    }

    public function setFeatured(int $serviceId, int $photoId): array {
        return $this->executeTransactional(function() use ($serviceId, $photoId) {
            $photo = $this->requirePhotoOfService($serviceId, $photoId);

            $this->photoRepository->clearFeatured($serviceId);
            $this->photoRepository->setFeatured((int)$photo["id"]);

            return ["message" => "Fotografia definida como principal."];
        });
    }

    public function delete(int $serviceId, int $photoId): array {
        return $this->executeTransactional(function() use ($serviceId, $photoId) {
            $photo = $this->requirePhotoOfService($serviceId, $photoId);

            $file = ROOT_PATH . "/" . (string)$photo["url"];
            if (is_file($file)) { @unlink($file); }

            $wasFeatured = (bool)$photo["featured"];
            $this->photoRepository->delete($photoId);

            // Se a principal foi removida, reeleger a primeira restante.
            if ($wasFeatured) {
                $remaining = $this->photoRepository->findByService($serviceId);
                if (!empty($remaining)) {
                    $this->photoRepository->setFeatured((int)$remaining[0]["id"]);
                }
            }

            return ["message" => "Fotografia removida."];
        });
    }

    // ------------------------------------------------------------------

    private function assertServiceExists(int $serviceId): void {
        if (!$this->serviceRepository->find($serviceId)) {
            throw new Exception("Serviço inexistente.", 404);
        }
    }

    private function assertUploadOk(?array $file): void {
        if (!$file || !isset($file["tmp_name"], $file["error"])) {
            throw new Exception("Nenhum ficheiro recebido.", 422);
        }
        if ((int)$file["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("A transferência do ficheiro falhou.", 422);
        }
        if (!is_uploaded_file((string)$file["tmp_name"])) {
            throw new Exception("Upload inválido.", 422);
        }
    }

    private function requirePhotoOfService(int $serviceId, int $photoId): array {
        $photo = $this->photoRepository->findById($photoId);
        if (!$photo || (int)$photo["serviceId"] !== $serviceId) {
            throw new Exception("Fotografia não encontrada para este serviço.", 404);
        }
        return $photo;
    }

    private function detectMime(string $path): string {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return (string)$finfo->file($path);
    }

    /**
     * Re-encode com GD — aplica o formato correto e destrói qualquer payload
     * embutido no ficheiro original (ex.: PHP dentro de um JPEG).
     */
    private function reencode(string $src, string $dest, string $mime): void {
        $image = match ($mime) {
            "image/jpeg" => @imagecreatefromjpeg($src),
            "image/png"  => @imagecreatefrompng($src),
            "image/webp" => @imagecreatefromwebp($src),
            default      => false
        };

        if (!$image) {
            throw new Exception("Não foi possível processar a imagem.", 422);
        }

        $ok = match ($mime) {
            "image/jpeg" => imagejpeg($image, $dest, 88),
            "image/png"  => imagepng($image, $dest, 6),
            "image/webp" => imagewebp($image, $dest, 88),
            default      => false
        };
        imagedestroy($image);

        if (!$ok) {
            @unlink($dest);
            throw new Exception("Falha ao gravar a imagem processada.", 500);
        }
    }
}
