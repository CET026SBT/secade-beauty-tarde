<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/EmployeeRepository.php";
require_once APP_PATH . "/repositories/UserRepository.php";

/**
 * F7 — fotografia de perfil do funcionário (upload pelo gestor · §4.6).
 *
 * Mesma defesa em profundidade do catálogo (MIME real por `finfo` + re-encode com
 * GD + limites de tamanho/dimensões), mas o destino é `uploads/users/<id>.<ext>` e a
 * coluna `utilizador.foto`. Sobrescrever o mesmo `<id>` evita acumular lixo (a versão
 * `?v=` na URL resolve o cache do browser).
 */
class EmployeePhotoService extends BaseService {

    private const MAX_BYTES     = 2097152;   // 2 MB
    private const MAX_DIMENSION = 4000;      // px por lado
    private const UPLOAD_RELDIR = "uploads/users";
    private const ALLOWED_MIME  = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    private EmployeeRepository $employeeRepository;
    private UserRepository $userRepository;

    public function __construct() {
        parent::__construct();
        $this->employeeRepository = new EmployeeRepository();
        $this->userRepository = new UserRepository();
    }

    public function upload(int $employeeId, ?array $file): array {
        return $this->executeTransactional(function() use ($employeeId, $file) {
            if (!$this->employeeRepository->find($employeeId)) {
                throw new Exception("Funcionário não encontrado.", 404);
            }

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

            $ext = self::ALLOWED_MIME[$mime];
            $dir = ROOT_PATH . "/" . self::UPLOAD_RELDIR;
            if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
                throw new Exception("Não foi possível preparar a pasta de uploads.", 500);
            }

            // Remove versões antigas com outra extensão (evita ficheiros órfãos).
            foreach (self::ALLOWED_MIME as $old) {
                $oldFile = $dir . "/" . $employeeId . "." . $old;
                if ($old !== $ext && is_file($oldFile)) { @unlink($oldFile); }
            }

            $relative = self::UPLOAD_RELDIR . "/" . $employeeId . "." . $ext;
            $this->reencode((string)$file["tmp_name"], ROOT_PATH . "/" . $relative, $mime);
            $this->userRepository->setPhoto($employeeId, $relative);

            return [
                "employeeId" => $employeeId,
                "photo"      => $relative,
                "version"    => time(),
                "message"    => "Fotografia atualizada."
            ];
        });
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

    private function detectMime(string $path): string {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return (string)$finfo->file($path);
    }

    /** Re-encode com GD — aplica o formato correto e destrói payloads embutidos. */
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