<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/ServicePhotoRepository.php";
require_once APP_PATH . "/repositories/ServiceRepository.php";
require_once APP_PATH . "/utils/Session.php";

/**
 * Fotos dos serviços (F3.1 · §3.4.4 · §4.6).
 *
 * O ficheiro vai para `uploads/services/` (raiz do projeto, fora de `modules/`) e é
 * **re-codificado com GD** — o que normaliza a imagem e destrói qualquer payload
 * embutido no ficheiro (defesa em profundidade, §4.6). O nome nunca vem do utilizador:
 * é `service-{id}-{n}.{ext}`.
 *
 * Na BD, cada foto é uma linha de `servico_foto`; a de `destaque = 1` é a imagem do card.
 */
class ServicePhotoService extends BaseService {

    private const MAX_BYTES  = 2097152; // 2 MB
    private const MAX_WIDTH  = 4000;
    private const MAX_HEIGHT = 4000;

    /** Extensões/MIMEs aceites — SVG fica de fora (§4.6: pode conter script). */
    private const ALLOWED = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    private ServicePhotoRepository $photoRepository;
    private ServiceRepository $serviceRepository;

    public function __construct() {
        parent::__construct();
        $this->photoRepository   = new ServicePhotoRepository();
        $this->serviceRepository = new ServiceRepository();
    }

    /** Fotos de um serviço (para a gestão do catálogo). */
    public function listByService(int $serviceId): array {
        $this->requireService($serviceId);

        return [
            "serviceId" => $serviceId,
            "photos"    => $this->photoRepository->findByService($serviceId)
        ];
    }

    /**
     * Carrega uma foto para o serviço e grava a linha em `servico_foto`.
     * A primeira foto do serviço passa automaticamente a `destaque`.
     */
    public function upload(int $serviceId, array $file, bool $featured = false): array {
        $this->requireService($serviceId);

        return $this->executeTransactional(function() use ($serviceId, $file, $featured) {
            $extension = $this->validateFile($file);
            $this->assertImage($file["tmp_name"]);

            $isFirst  = $this->photoRepository->countByService($serviceId) === 0;
            $featured = $featured || $isFirst;

            $fileName = $this->nextFileName($serviceId, $extension);
            $diskPath = $this->uploadDirectory() . "/" . $fileName;

            $this->storeReencoded($file["tmp_name"], $extension, $diskPath);

            if ($featured) {
                $this->photoRepository->clearFeatured($serviceId);
            }

            $url = "uploads/services/" . $fileName;
            $photoId = $this->photoRepository->create(
                $serviceId,
                $url,
                $featured,
                $this->photoRepository->nextDisplayOrder($serviceId)
            );

            return [
                "photoId"   => $photoId,
                "serviceId" => $serviceId,
                "url"       => $url,
                "featured"  => $featured,
                "message"   => "Fotografia carregada com sucesso."
            ];
        });
    }

    /** Marca uma foto como a principal do card (as outras deixam de ser). */
    public function setFeatured(int $photoId): array {
        $photo = $this->photoRepository->findById($photoId);
        if (!$photo) {
            throw new Exception("Fotografia não encontrada.", 404);
        }

        return $this->executeTransactional(function() use ($photoId, $photo) {
            $serviceId = (int)$photo["serviceId"];

            $this->photoRepository->clearFeatured($serviceId);
            $this->photoRepository->setFeatured($photoId);

            return [
                "photoId"   => $photoId,
                "serviceId" => $serviceId,
                "message"   => "Fotografia definida como principal."
            ];
        });
    }

    /** Remove a foto (linha + ficheiro). Se era a principal, promove a seguinte. */
    public function remove(int $photoId): array {
        $photo = $this->photoRepository->findById($photoId);
        if (!$photo) {
            throw new Exception("Fotografia não encontrada.", 404);
        }

        return $this->executeTransactional(function() use ($photoId, $photo) {
            $serviceId = (int)$photo["serviceId"];
            $wasFeatured = (bool)$photo["featured"];

            $this->photoRepository->delete($photoId);
            $this->deleteFile((string)$photo["url"]);

            if ($wasFeatured) {
                $restantes = $this->photoRepository->findByService($serviceId);
                if (!empty($restantes)) {
                    $this->photoRepository->setFeatured((int)$restantes[0]["id"]);
                }
            }

            return [
                "photoId"   => $photoId,
                "serviceId" => $serviceId,
                "message"   => "Fotografia removida."
            ];
        });
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    private function requireService(int $serviceId): void {
        if ($serviceId <= 0) {
            throw new Exception("Serviço inválido.", 422);
        }

        if (!$this->serviceRepository->find($serviceId)) {
            throw new Exception("Serviço não encontrado.", 404);
        }
    }

    /** Whitelist de MIME real (não confia no `Content-Type` do browser) + limite de tamanho. */
    private function validateFile(array $file): string {
        if (empty($file) || !isset($file["error"])) {
            throw new Exception("Nenhum ficheiro recebido.", 422);
        }

        if ($file["error"] === UPLOAD_ERR_NO_FILE) {
            throw new Exception("Escolha uma fotografia para carregar.", 422);
        }

        if ($file["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("Falha ao receber o ficheiro (código {$file['error']}).", 422);
        }

        if ((int)$file["size"] <= 0 || (int)$file["size"] > self::MAX_BYTES) {
            throw new Exception("A fotografia tem de ter no máximo 2 MB.", 422);
        }

        if (!is_uploaded_file($file["tmp_name"])) {
            throw new Exception("Ficheiro inválido.", 422);
        }

        $mime = $this->detectMime($file["tmp_name"]);
        if (!isset(self::ALLOWED[$mime])) {
            throw new Exception("Formato não suportado. Use JPG, PNG ou WEBP.", 422);
        }

        return self::ALLOWED[$mime];
    }

    private function detectMime(string $path): string {
        if (function_exists("finfo_open")) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $path);
            finfo_close($finfo);
            if (is_string($mime) && $mime !== "") {
                return $mime;
            }
        }

        // Recurso: o `getimagesize` devolve o tipo real do ficheiro.
        $info = @getimagesize($path);
        return is_array($info) ? (string)($info["mime"] ?? "") : "";
    }

    private function assertImage(string $path): void {
        $info = @getimagesize($path);
        if (!is_array($info)) {
            throw new Exception("O ficheiro não é uma imagem válida.", 422);
        }

        $width  = (int)$info[0];
        $height = (int)$info[1];

        if ($width <= 0 || $height <= 0 || $width > self::MAX_WIDTH || $height > self::MAX_HEIGHT) {
            throw new Exception("Dimensões inválidas (máximo 4000 × 4000).", 422);
        }
    }

    /** Re-codifica com GD para o destino — limpa EXIF e neutraliza payload embutido. */
    private function storeReencoded(string $sourcePath, string $extension, string $diskPath): void {
        if (!extension_loaded("gd")) {
            throw new Exception("A extensão GD é necessária para processar imagens.", 500);
        }

        $source = match ($extension) {
            "jpg"  => @imagecreatefromjpeg($sourcePath),
            "png"  => @imagecreatefrompng($sourcePath),
            "webp" => @imagecreatefromwebp($sourcePath),
            default => false
        };

        if (!$source) {
            throw new Exception("Não foi possível processar a imagem.", 422);
        }

        $directory = dirname($diskPath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            imagedestroy($source);
            throw new Exception("Não foi possível preparar a pasta de destino.", 500);
        }

        $saved = match ($extension) {
            "jpg"  => imagejpeg($source, $diskPath, 88),
            "png"  => $this->savePng($source, $diskPath),
            "webp" => imagewebp($source, $diskPath, 88),
            default => false
        };

        imagedestroy($source);

        if (!$saved || !is_file($diskPath)) {
            throw new Exception("Não foi possível guardar a imagem.", 500);
        }
    }

    private function savePng($image, string $diskPath): bool {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        return imagepng($image, $diskPath, 6);
    }

    /** Próximo nome livre: `service-{id}-{n}.{ext}`. */
    private function nextFileName(int $serviceId, string $extension): string {
        $directory = $this->uploadDirectory();
        $index = 1;

        do {
            $candidate = sprintf("service-%d-%d.%s", $serviceId, $index, $extension);
            $index++;
        } while (is_file($directory . "/" . $candidate) && $index < 1000);

        return $candidate;
    }

    private function uploadDirectory(): string {
        return ROOT_PATH . "/uploads/services";
    }

    /** Apaga o ficheiro físico (só dentro de `uploads/services/`). */
    private function deleteFile(string $url): void {
        $normalized = str_replace("\\", "/", $url);

        if (!str_starts_with($normalized, "uploads/services/")) {
            return;
        }

        $fileName = basename($normalized);
        $diskPath = $this->uploadDirectory() . "/" . $fileName;

        if (is_file($diskPath)) {
            @unlink($diskPath);
        }
    }
}