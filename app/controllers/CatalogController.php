<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/ServicePhotoService.php";

/**
 * F3.1 — gestão das fotografias do catálogo. Só o gestor (§3.4.4 · §4.6).
 */
class CatalogController extends BaseController {

    private ServicePhotoService $photoService;

    public function __construct() {
        $this->photoService = new ServicePhotoService();
    }

    public function list(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->photoService->catalog();
    }

    public function servicePhotos(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->photoService->listForService($this->requireServiceId());
    }

    public function uploadPhoto(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->photoService->upload($this->requireServiceId(), $_FILES["photo"] ?? null);
    }

    public function setFeaturedPhoto(): array {
        Session::requireProfileApi(["gestor"]);
        $data = $this->getRequestData();
        return $this->photoService->setFeatured($this->requireServiceId(), (int)($data["photoId"] ?? 0));
    }

    public function deletePhoto(): array {
        Session::requireProfileApi(["gestor"]);
        $data = $this->getRequestData();
        return $this->photoService->delete($this->requireServiceId(), (int)($data["photoId"] ?? 0));
    }

    private function requireServiceId(): int {
        $data = $this->getRequestData();
        $serviceId = (int)($data["serviceId"] ?? $_GET["serviceId"] ?? 0);

        if ($serviceId <= 0) {
            throw new Exception("Identificador de serviço inválido.", 422);
        }

        return $serviceId;
    }
}
