<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/CategoryService.php";

class CategoryController extends BaseController {
    private CategoryService $categoryService;

    public function __construct() {
        $this->categoryService = new CategoryService();
    }

    public function findAll(): array {
        return $this->categoryService->findAll();
    }
}
