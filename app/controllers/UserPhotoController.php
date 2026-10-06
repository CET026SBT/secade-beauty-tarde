<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/UserPhotoService.php";

/**
 * Foto de perfil do utilizador autenticado (F9 · §4.6).
 *
 * Genérico de propósito: **cada um mexe só na sua** — o id sai sempre da sessão,
 * nunca do pedido (§18.6). A Área Cliente usa-os hoje; o RH tem o seu próprio
 * controlador porque lá o destinatário é escolhido pelo gestor.
 */
class UserPhotoController extends BaseController {
    private UserPhotoService $photoService;

    public function __construct() {
        $this->photoService = new UserPhotoService();
    }

    public function upload(): array {
        Session::requireLoginApi();

        return $this->photoService->upload(Session::userId(), $this->getUploadedFile("photo"));
    }

    public function remove(): array {
        Session::requireLoginApi();

        return $this->photoService->remove(Session::userId());
    }
}