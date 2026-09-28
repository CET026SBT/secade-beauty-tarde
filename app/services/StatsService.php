<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/ServiceRepository.php";
require_once APP_PATH . "/repositories/CategoryRepository.php";
require_once APP_PATH . "/repositories/CityRepository.php";
require_once APP_PATH . "/repositories/EmployeeRepository.php";
require_once APP_PATH . "/repositories/CustomerRepository.php";
require_once APP_PATH . "/repositories/FeedbackRepository.php";

/**
 * Indicadores públicos do site (contadores do "Sobre nós" e da apresentação de serviços).
 *
 * Delega integralmente nos Repositories (§18.1–§18.2) — este Service não tem SQL.
 * Cada número é **contado na BD**; o consumidor decide o que fazer quando é 0.
 */
class StatsService extends BaseService {
    private ServiceRepository $serviceRepository;
    private CategoryRepository $categoryRepository;
    private CityRepository $cityRepository;
    private EmployeeRepository $employeeRepository;
    private CustomerRepository $customerRepository;
    private FeedbackRepository $feedbackRepository;

    public function __construct() {
        parent::__construct();
        $this->serviceRepository    = new ServiceRepository();
        $this->categoryRepository   = new CategoryRepository();
        $this->cityRepository       = new CityRepository();
        $this->employeeRepository   = new EmployeeRepository();
        $this->customerRepository   = new CustomerRepository();
        $this->feedbackRepository   = new FeedbackRepository();
    }

    public function siteStats(): array {
        return [
            "stats" => [
                "services"   => $this->serviceRepository->countActive(),
                "categories" => $this->categoryRepository->countAll(),
                "cities"     => $this->cityRepository->countAll(),
                "team"       => $this->employeeRepository->countActive(),
                "customers"  => $this->customerRepository->countAll(),
                "reviews"    => $this->feedbackRepository->countAll(),
                // Preço base mais baixo, **sem IVA** — o cliente vê-o com IVA (`vatUtils`, D-16).
                "minPriceNet" => $this->serviceRepository->findMinPrice()
            ]
        ];
    }
}