<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/ServicePhotoService.php";

/**
 * F3.1 — Gestão das fotos do catálogo (só o GESTOR).
 *
 * Ficheiro + linha de `servico_foto`: a de `destaque` é a imagem do card, as outras
 * alimentam o carousel dos detalhes.
 */
class ServicePhotoController extends BaseController {
    private ServicePhotoService $photoService;

    public function __construct() {
        $this->photoService = new ServicePhotoService();
    }

    public function list(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->photoService->listByService($this->extractServiceId());
    }

    /** Listagem pública (a galeria do modal de detalhes, no site). */
    public function publicList(): array {
        return $this->photoService->listByService($this->extractServiceId());
    }

    public function upload(): array {
        Session::requireProfileApi(["gestor"]);

        $featured = filter_var($this->getRequestData()["featured"] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $this->photoService->upload(
            $this->extractServiceId(),
            $this->getUploadedFile("photo"),
            $featured
        );
    }

    public function setFeatured(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->photoService->setFeatured($this->extractPhotoId());
    }

    public function remove(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->photoService->remove($this->extractPhotoId());
    }

    private function extractServiceId(): int {
        $data = $this->getRequestData();
        $serviceId = (int)($data["serviceId"] ?? $_GET["serviceId"] ?? 0);

        if ($serviceId <= 0) {
            throw new Exception("Identificador de serviço inválido.", 422);
        }

        return $serviceId;
    }

    private function extractPhotoId(): int {
        $data = $this->getRequestData();
        $photoId = (int)($data["photoId"] ?? $data["id"] ?? $_GET["photoId"] ?? 0);

        if ($photoId <= 0) {
            throw new Exception("Identificador de fotografia inválido.", 422);
        }

        return $photoId;
    }
}