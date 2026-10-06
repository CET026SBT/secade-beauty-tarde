<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/UserRepository.php";
require_once APP_PATH . "/utils/Session.php";

/**
 * Foto de perfil do utilizador (C-05 · §4.6) — reutilizado por RH (F7) e Área
 * Cliente (F9).
 *
 * O ficheiro vive em `uploads/users/<id>.<ext>` (raiz do projeto, fora de `modules/`)
 * e é **re-codificado com GD**, o que aplica o recorte e destrói qualquer payload
 * embutido. O nome **nunca** vem do utilizador — elimina o *path traversal* na origem.
 * `svg` fica de fora (§4.6: pode conter script).
 */
class UserPhotoService extends BaseService {

    private const MAX_BYTES  = 2097152; // 2 MB
    private const MAX_WIDTH  = 4000;
    private const MAX_HEIGHT = 4000;

    private const ALLOWED = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    private UserRepository $userRepository;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
    }

    /** Grava a foto do utilizador e devolve o caminho relativo. */
    public function upload(int $userId, array $file): array {
        if ($userId <= 0 || !$this->userRepository->find($userId)) {
            throw new Exception("Utilizador não encontrado.", 404);
        }

        return $this->executeTransactional(function() use ($userId, $file) {
            $extension = $this->validateFile($file);
            $this->assertImage($file["tmp_name"]);

            $fileName = "uploads/users/" . $userId . "." . $extension;
            $diskPath = ROOT_PATH . "/" . $fileName;

            $this->storeReencoded($file["tmp_name"], $extension, $diskPath, $userId);

            $this->userRepository->updatePhoto($userId, $fileName);

            return [
                "userId"  => $userId,
                "photo"   => $fileName,
                "message" => "Fotografia atualizada."
            ];
        });
    }

    public function remove(int $userId): array {
        if ($userId <= 0) {
            throw new Exception("Utilizador inválido.", 422);
        }

        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new Exception("Utilizador não encontrado.", 404);
        }

        $current = str_replace("\\", "/", (string)($user["photo"] ?? ""));

        if ($current !== "" && str_starts_with($current, "uploads/users/")) {
            $diskPath = ROOT_PATH . "/" . $current;
            if (is_file($diskPath)) {
                @unlink($diskPath);
            }
        }

        $this->userRepository->updatePhoto($userId, null);

        return ["userId" => $userId, "message" => "Fotografia removida."];
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    private function validateFile(array $file): string {
        if (empty($file) || !isset($file["error"])) {
            throw new Exception("Nenhum ficheiro recebido.", 422);
        }

        if ($file["error"] === UPLOAD_ERR_NO_FILE) {
            throw new Exception("Escolha uma fotografia.", 422);
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

    /**
     * Re-codifica com GD. Aplica também o **recorte quadrado central** (o avatar é
     * apresentado em círculo) — o crop do browser é só a pré-visualização; a verdade
     * faz-se aqui.
     */
    private function storeReencoded(string $sourcePath, string $extension, string $diskPath, int $userId): void {
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

        $width  = imagesx($source);
        $height = imagesy($source);
        $side   = min($width, $height);

        $square = imagecreatetruecolor($side, $side);
        imagealphablending($square, false);
        imagesavealpha($square, true);
        imagecopy($square, $source, 0, 0, (int)(($width - $side) / 2), (int)(($height - $side) / 2), $side, $side);

        $directory = dirname($diskPath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            imagedestroy($source);
            imagedestroy($square);
            throw new Exception("Não foi possível preparar a pasta de destino.", 500);
        }

        // Uma extensão nova substitui a antiga: evita ficheiros órfãos do mesmo id.
        $this->removeOtherExtensions($userId, $extension);

        $saved = match ($extension) {
            "jpg"  => imagejpeg($square, $diskPath, 88),
            "png"  => imagepng($square, $diskPath, 6),
            "webp" => imagewebp($square, $diskPath, 88),
            default => false
        };

        imagedestroy($source);
        imagedestroy($square);

        if (!$saved || !is_file($diskPath)) {
            throw new Exception("Não foi possível guardar a imagem.", 500);
        }
    }

    private function removeOtherExtensions(int $userId, string $keepExtension): void {
        foreach (array_unique(array_values(self::ALLOWED)) as $extension) {
            if ($extension === $keepExtension) continue;

            $candidate = ROOT_PATH . "/uploads/users/" . $userId . "." . $extension;
            if (is_file($candidate)) {
                @unlink($candidate);
            }
        }
    }
}