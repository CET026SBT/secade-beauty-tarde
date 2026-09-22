<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/CategoryRepository.php";

class CategoryService extends BaseService {
    private CategoryRepository $categoryRepository;

    public function __construct() {
        parent::__construct();
        $this->categoryRepository = new CategoryRepository();
    }

    public function findAll(): array {
        return [
            "categories" => $this->categoryRepository->find()
        ];
    }
}
