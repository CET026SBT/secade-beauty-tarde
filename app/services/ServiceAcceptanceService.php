<?php

require_once __DIR__ . "/BaseService.php";
require_once __DIR__ . "/GreenReceiptService.php";
require_once APP_PATH . "/repositories/BookingServiceRepository.php";
require_once APP_PATH . "/repositories/BookingRepository.php";
require_once APP_PATH . "/repositories/CategoryRepository.php";
require_once APP_PATH . "/repositories/CityRepository.php";
require_once APP_PATH . "/repositories/EmployeeRepository.php";
require_once APP_PATH . "/utils/Session.php";

/**
 * Fase 3 (reformulada na F4) — alocação/aceitação individual de serviços de ambulatório.
 *
 * Regras (F4 · §3.6 · §4.4):
 *   - O **gestor aloca** (escolhe o funcionário efetivo); o **funcionário (RV) aceita** por si.
 *   - A percentagem aplicada é **`funcionario.percentagem_comissao`** (§7.6 · C-09/C-10).
 *   - **#2 (R-ALOC):** um funcionário não pode estar em **duas cidades no mesmo dia**.
 *   - **#1 (R-CONF):** o choque de janela (mesma cidade) passa a ser validado na
 *     **confirmação da rota** (`RotaService`) e **não** ao consolidar o agendamento.
 */
class ServiceAcceptanceService extends BaseService {

    private const CONSOLIDATED_STATE = "totalmente_alocado";

    private BookingServiceRepository $bookingServiceRepository;
    private BookingRepository $bookingRepository;
    private CategoryRepository $categoryRepository;
    private CityRepository $cityRepository;
    private EmployeeRepository $employeeRepository;
    private GreenReceiptService $greenReceiptService;

    public function __construct() {
        parent::__construct();
        $this->bookingServiceRepository = new BookingServiceRepository();
        $this->bookingRepository = new BookingRepository();
        $this->categoryRepository = new CategoryRepository();
        $this->cityRepository = new CityRepository();
        $this->employeeRepository = new EmployeeRepository();
        $this->greenReceiptService = new GreenReceiptService();
    }

    // ------------------------------------------------------------------
    // Listagens
    // ------------------------------------------------------------------

    /**
     * Serviços de ambulatório por alocar/aceitar. Cada serviço vem com o aviso de
     * conflito de janela (#1). Para o gestor acrescem os funcionários efetivos que
     * pode alocar — por serviço, com a option bloqueada quando #2 se aplica.
     */
    public function listPendingServices(array $filters = [], bool $forManager = false, ?int $employeeId = null): array {
        $services = $this->bookingServiceRepository->findPending($filters);
        $cityCache = [];

        foreach ($services as &$service) {
            $bookingId = (int)$service["bookingId"];
            $cityId = (int)($service["cityId"] ?? 0);
            $date = substr((string)($service["dateTime"] ?? ""), 0, 10);

            // #1 — aviso de conflito de janela (rota já confirmada na mesma cidade).
            $service["windowConflict"] = $this->hasConfirmedWindowConflict($bookingId, $cityId);

            // #2 — para o RV: já está noutra cidade nesse dia? (botão `disabled`).
            $service["acceptBlocked"] = false;
            if (!$forManager && $employeeId !== null) {
                $key = $employeeId . "|" . $date;
                if (!array_key_exists($key, $cityCache)) {
                    $cityCache[$key] = $this->bookingServiceRepository->findEmployeeCityIdsOnDate($employeeId, $date);
                }
                foreach ($cityCache[$key] as $c) {
                    if ($c !== $cityId) { $service["acceptBlocked"] = true; break; }
                }
            }
        }
        unset($service);

        $response = [
            "services"   => $services,
            "categories" => $this->categoryRepository->find(),
            "cities"     => $this->cityRepository->find(),
            "config"     => $this->greenReceiptService->findActiveConfig()
        ];

        if ($forManager) {
            $response["employeeOptions"] = $this->buildEmployeeOptions($services);
        }

        return $response;
    }

    /**
     * Serviços já alocados/aceites. Sem `$employeeId` = visão do gestor (todos);
     * com `$employeeId` = visão do funcionário (só os seus).
     */
    public function listAcceptedServices(?int $employeeId, array $filters = []): array {
        $services = $this->bookingServiceRepository->findAllocated($employeeId, $filters);

        $totalEmployee = 0.0;
        $totalPlatform = 0.0;

        foreach ($services as $service) {
            $totalEmployee += (float)($service["greenReceiptEmployee"] ?? 0);
            $totalPlatform += (float)($service["greenReceiptPlatform"] ?? 0);
        }

        return [
            "services"  => $services,
            "cities"    => $this->cityRepository->find(),
            "employees" => $this->employeeRepository->findActiveEffective(),
            "totals"    => [
                "employee" => round($totalEmployee, 2),
                "platform" => round($totalPlatform, 2),
                "count"    => count($services)
            ],
            "config"    => $this->greenReceiptService->findActiveConfig()
        ];
    }

    // ------------------------------------------------------------------
    // Operações
    // ------------------------------------------------------------------

    /** ALOCAÇÃO pelo GESTOR: escolhe o funcionário efetivo que executa o serviço (C-08). */
    public function assignService(int $managerId, int $bookingServiceId, ?int $bookingId, int $employeeId): array {
        return $this->executeTransactional(function() use ($bookingServiceId, $bookingId, $employeeId) {
            if ($employeeId <= 0) {
                throw new Exception("Escolha o funcionário a alocar.", 422);
            }

            $employee = $this->employeeRepository->find($employeeId);
            if (!$employee || ($employee["contractType"] ?? null) !== "efetivo_contratado") {
                throw new Exception("Só é possível alocar a um funcionário efetivo.", 422);
            }

            $service = $this->resolveService($bookingServiceId, $bookingId);
            $booking = $this->requireBooking((int)$service["bookingId"]);

            $this->assertAcceptableBooking($booking);
            $this->assertEmployeeAvailableInCity($employeeId, (int)$booking["id"]);

            $percentage = (float)($employee["commissionPercentage"] ?? 0.0);
            $this->bookingServiceRepository->accept($bookingServiceId, $employeeId, $percentage);

            return [
                "bookingServiceId" => $bookingServiceId,
                "bookingId"        => (int)$booking["id"],
                "assigned"         => true,
                "greenReceipt"     => $this->greenReceiptService->simulate((float)$service["price"], $percentage),
                "message"          => "Serviço alocado a " . ($employee["name"] ?? "") . "."
            ];
        });
    }

    /**
     * ACEITAÇÃO pelo FUNCIONÁRIO (RV). Aceitar um serviço já aceito por outro
     * funcionário equivale a TROCAR.
     */
    public function acceptService(int $employeeId, int $bookingServiceId, ?int $bookingId = null): array {
        return $this->executeTransactional(function() use ($employeeId, $bookingServiceId, $bookingId) {
            $service = $this->resolveService($bookingServiceId, $bookingId);
            $booking = $this->requireBooking((int)$service["bookingId"]);

            $this->assertAcceptableBooking($booking);

            // #2 (R-ALOC) é regra de ALOCAÇÃO (gestor). Para o RV é apenas um aviso
            // na listagem (botão `disabled`) — não bloqueia a aceitação no servidor.
            $percentage = $this->employeePercentage($employeeId);
            $isSwap = !empty($service["employeeId"]) && (int)$service["employeeId"] !== $employeeId;

            $this->bookingServiceRepository->accept($bookingServiceId, $employeeId, $percentage);
            $consolidated = $this->consolidateIfComplete((int)$booking["id"]);

            return [
                "bookingServiceId" => $bookingServiceId,
                "bookingId"        => (int)$booking["id"],
                "isSwap"           => $isSwap,
                "consolidated"     => $consolidated,
                "greenReceipt"     => $this->greenReceiptService->simulate((float)$service["price"], $percentage),
                "message"          => $isSwap
                        ? "Serviço transferido para si com sucesso."
                        : "Serviço aceite com sucesso."
            ];
        });
    }

    /**
     * Desfaz a aceitação/alocação de um serviço (bloqueado se o agendamento estiver
     * consolidado). `$employeeId` nulo = o GESTOR pode desfazer qualquer alocação.
     */
    public function unacceptService(?int $employeeId, int $bookingServiceId, ?int $bookingId = null): array {
        return $this->executeTransactional(function() use ($employeeId, $bookingServiceId, $bookingId) {
            $service = $this->resolveService($bookingServiceId, $bookingId);
            $booking = $this->requireBooking((int)$service["bookingId"]);

            if ($booking["status"] === self::CONSOLIDATED_STATE) {
                throw new Exception(
                    "O agendamento já está totalmente alocado e não permite desfazer nem trocar.",
                    409
                );
            }

            if ($employeeId !== null && (int)($service["employeeId"] ?? 0) !== $employeeId) {
                throw new Exception("Só pode desfazer serviços que aceitou.", 403);
            }

            $this->bookingServiceRepository->unaccept($bookingServiceId);

            return [
                "bookingServiceId" => $bookingServiceId,
                "bookingId"        => (int)$booking["id"],
                "message"          => "Aceitação desfeita. O serviço voltou a ficar pendente."
            ];
        });
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    private function resolveService(int $bookingServiceId, ?int $bookingId): array {
        $service = $bookingId !== null
            ? $this->bookingServiceRepository->findByIdAndBooking($bookingServiceId, $bookingId)
            : $this->bookingServiceRepository->findById($bookingServiceId);

        if (!$service) {
            throw new Exception("Serviço de agendamento não encontrado.", 404);
        }

        return $service;
    }

    private function requireBooking(int $bookingId): array {
        $booking = $this->bookingRepository->find($bookingId);

        if (!$booking) {
            throw new Exception("Agendamento não encontrado.", 404);
        }

        return $booking;
    }

    private function assertAcceptableBooking(array $booking): void {
        if ($booking["local"] !== "carrinha_ambulante") {
            throw new Exception("Apenas serviços de ambulatório requerem aceitação por funcionários.", 409);
        }

        if (in_array($booking["status"], ["cancelado", "recusado", "executado", "concluido"], true)) {
            throw new Exception("Este agendamento já não está disponível para aceitação (estado: {$booking['status']}).", 409);
        }
    }

    /**
     * F4 · §4.4: consolidar (todos os serviços alocados) deixa de bloquear a janela.
     * O bloqueio passou para a CONFIRMAÇÃO da rota (R-CONF).
     */
    private function consolidateIfComplete(int $bookingId): bool {
        if ($this->bookingServiceRepository->countPendingByBooking($bookingId) > 0) {
            return false;
        }

        $this->bookingRepository->updateEstado($bookingId, self::CONSOLIDATED_STATE);

        return true;
    }

    /** Percentagem aplicável ao funcionário — a sua própria `percentagem_comissao` (§7.6). */
    private function employeePercentage(int $employeeId): float {
        $employee = $this->employeeRepository->find($employeeId);

        if (!$employee) {
            throw new Exception("Funcionário não encontrado.", 404);
        }

        return (float)($employee["commissionPercentage"] ?? 0.0);
    }

    /**
     * #2 (R-ALOC): o funcionário não pode ficar em duas cidades no mesmo dia.
     * Se já tiver serviços aceites noutra cidade nesse dia, a alocação é recusada.
     */
    private function assertEmployeeAvailableInCity(int $employeeId, int $bookingId): void {
        $cityId = $this->bookingRepository->findCityId($bookingId);
        if ($cityId === null) return;

        $booking = $this->bookingRepository->find($bookingId);
        $date = substr((string)($booking["dateTime"] ?? ""), 0, 10);
        if ($date === "") return;

        $cities = $this->bookingServiceRepository->findEmployeeCityIdsOnDate($employeeId, $date);

        foreach ($cities as $c) {
            if ($c !== $cityId) {
                throw new Exception(
                    "O funcionário já está alocado a outra cidade neste dia e não pode estar em duas rotas.",
                    409
                );
            }
        }
    }

    /**
     * #1 (aviso na listagem): existe outra rota JÁ CONFIRMADA, na mesma cidade, a
     * sobrepor-se a esta janela? Serve para desativar o botão/dropdown por antecipação.
     */
    private function hasConfirmedWindowConflict(int $bookingId, int $cityId): bool {
        if ($cityId <= 0) return false;

        $booking = $this->bookingRepository->find($bookingId);
        if (!$booking) return false;

        $duration = $this->bookingServiceRepository->totalDurationByBooking($bookingId);

        return $this->bookingRepository->countConfirmedWindowConflict(
            (string)$booking["dateTime"],
            max($duration, 1),
            $cityId,
            [$bookingId]
        ) > 0;
    }

    /**
     * Opções de funcionário por serviço (visão do gestor). A option de um funcionário
     * que já esteja noutra cidade nesse dia vem marcada como `blocked` (#2).
     */
    private function buildEmployeeOptions(array $services): array {
        $employees = $this->employeeRepository->findActiveEffective();
        $cache = [];
        $options = [];

        foreach ($services as $service) {
            $bookingId = (int)$service["bookingId"];
            $cityId = (int)($service["cityId"] ?? 0);
            $date = substr((string)($service["dateTime"] ?? ""), 0, 10);

            $list = [];
            foreach ($employees as $employee) {
                $employeeId = (int)$employee["id"];
                $key = $employeeId . "|" . $date;

                if (!array_key_exists($key, $cache)) {
                    $cache[$key] = $this->bookingServiceRepository->findEmployeeCityIdsOnDate($employeeId, $date);
                }

                $blocked = false;
                foreach ($cache[$key] as $c) {
                    if ($c !== $cityId) { $blocked = true; break; }
                }

                $list[] = [
                    "id"      => $employeeId,
                    "name"    => (string)($employee["name"] ?? ""),
                    "blocked" => $blocked
                ];
            }

            $options[$bookingId] = $list;
        }

        return $options;
    }
}
