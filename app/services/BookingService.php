<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/BookingRepository.php";
require_once APP_PATH . "/repositories/BookingServiceRepository.php";
require_once APP_PATH . "/repositories/BookingPersonRepository.php";
require_once APP_PATH . "/repositories/ServiceRepository.php";
require_once __DIR__ . "/OTPService.php";

class BookingService extends BaseService {
    private const STORE_OPEN_HOUR  = 9;   // 09:00
    private const STORE_CLOSE_HOUR = 19;  // 19:00
    private const SLOT_STEP_MINUTES = 30;
    private const DEPOSIT_PERCENTAGE = 10;

    private BookingRepository $bookingRepository;
    private BookingServiceRepository $bookingServiceRepository;
    private BookingPersonRepository $bookingPersonRepository;
    private ServiceRepository $serviceRepository;
    private OTPService $otpService;

    public function __construct() {
        parent::__construct();
        $this->bookingRepository = new BookingRepository();
        $this->bookingServiceRepository = new BookingServiceRepository();
        $this->bookingPersonRepository = new BookingPersonRepository();
        $this->serviceRepository = new ServiceRepository();
        $this->otpService = new OTPService();
    }

    // ------------------------------------------------------------------
    // Validações
    // ------------------------------------------------------------------

    private function validateBookingDate(string $date, string $time): string {
        $dateTime = $date . " " . $time;

        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date) || !preg_match("/^\d{2}:\d{2}(:\d{2})?$/", $time)) {
            throw new Exception("Data ou hora inválida.", 422);
        }

        if (strtotime($dateTime) < time()) {
            throw new Exception("Não é possível agendar para uma data passada.", 422);
        }

        $dayOfWeek = (int)date("N", strtotime($date)); // 1=Seg ... 7=Dom
        if ($dayOfWeek < 2 || $dayOfWeek > 6) {
            throw new Exception("Apenas é possível agendar de Terça a Sábado.", 422);
        }

        return $dateTime;
    }

    private function validateStoreOpeningHours(string $date, string $time, int $durationMinutes): void {
        $slotStart = strtotime($date . " " . $time);
        $dayStart  = strtotime($date . " 00:00:00");

        if ($slotStart === false || $dayStart === false) {
            throw new Exception("Data ou hora inválida.", 422);
        }

        // Offsets em segundos desde a meia-noite (o horário da loja é relativo ao dia)
        $startOffset = $slotStart - $dayStart;
        $open        = self::STORE_OPEN_HOUR * 3600;
        $close       = self::STORE_CLOSE_HOUR * 3600;

        // O INÍCIO tem de estar dentro do horário de atendimento. A duração pode
        // ultrapassar o fecho — é o que a grelha de slots oferece (RF-21 / RN-02) e é
        // inevitável em serviços longos. Validar o fim aqui recusaria (422) precisamente
        // os horários que o wizard acabou de apresentar ao cliente.
        if ($startOffset < $open || $startOffset >= $close) {
            throw new Exception("O horário de atendimento é das 09:00 às 19:00.", 422);
        }
    }

    private function resolveServices(array $serviceIds): array {
        if (empty($serviceIds)) {
            throw new Exception("Selecione pelo menos um serviço.", 422);
        }

        $services = [];
        $totalAmount = 0.0;
        $totalDuration = 0;

        foreach (array_unique(array_map("intval", $serviceIds)) as $serviceId) {
            $service = $this->serviceRepository->find($serviceId);
            if (!$service) {
                throw new Exception("Serviço {$serviceId} não encontrado.", 404);
            }

            $services[] = $service;
            $totalAmount += $service["basePrice"];
            $totalDuration += $service["estimatedDurationMinutes"];
        }

        return [$services, round($totalAmount, 2), $totalDuration];
    }

    // ------------------------------------------------------------------
    // Disponibilidade
    // ------------------------------------------------------------------

    public function findAvailability(array $queryParams): array {
        $date = $queryParams["date"] ?? null;
        $durationMinutes = (int)($queryParams["duration"] ?? 60);
        $local = $queryParams["local"] ?? "loja_fisica";

        if (!$date || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
            throw new Exception("Data inválida.", 422);
        }

        $dayOfWeek = (int)date("N", strtotime($date));
        if ($dayOfWeek < 2 || $dayOfWeek > 6) {
            return ["date" => $date, "slots" => [], "message" => "Apenas Terça a Sábado."];
        }

        $slots = [];
        $openStart  = strtotime($date . " " . self::STORE_OPEN_HOUR . ":00");
        $closeStart = strtotime($date . " " . self::STORE_CLOSE_HOUR . ":00");

        // RF-21 / RN-02: a grelha de horários é 09:00–19:00 em passos de 30 min,
        // independentemente da duração pedida. A duração serve apenas para detetar
        // conflitos de janela. Exigir que a marcação inteira coubesse dentro do horário
        // deixava a grelha vazia para serviços longos (ex.: Box Braids 240 min, Faux Locs
        // 300 min) ou para vários serviços/pessoas — o cliente via sempre
        // "Sem horários disponíveis para esta data" e não conseguia agendar.
        for ($slotStart = $openStart; $slotStart < $closeStart; $slotStart += self::SLOT_STEP_MINUTES * 60) {
            if ($slotStart < time()) continue;

            $slotEnd = $slotStart + $durationMinutes * 60;

            $conflicts = $this->bookingRepository->countByDateWindow(
                date("Y-m-d H:i:s", $slotStart),
                $durationMinutes,
                $local
            );

            $slots[] = [
                "time"            => date("H:i", $slotStart),
                "available"       => $conflicts === 0,
                "conflicting"     => $conflicts,
                "endTime"         => date("H:i", $slotEnd),
                "overrunsClosing" => $slotEnd > $closeStart
            ];
        }

        return ["date" => $date, "duration" => $durationMinutes, "local" => $local, "slots" => $slots];
    }

    // ------------------------------------------------------------------
    // Criação — LOJA (aceitação automática; pendente de validação logística)
    // ------------------------------------------------------------------

    public function createStoreBooking(int $customerId, array $data): array {
        return $this->executeTransactional(function() use ($customerId, $data) {
            $this->validate($data, function($v) {
                $v  ->required("date", "A data é obrigatória.")
                    ->required("time", "A hora é obrigatória.");
            });

            $dateTime = $this->validateBookingDate($data["date"], $data["time"]);
            [$services, $totalAmount, $totalDuration] = $this->resolveServices($data["serviceIds"] ?? []);

            $this->validateStoreOpeningHours($data["date"], $data["time"], $totalDuration);

            if ($this->bookingRepository->countByDateWindow($dateTime, $totalDuration, "loja_fisica") > 0) {
                throw new Exception("Já existe um agendamento confirmado nesta janela horária. Escolha outro slot.", 409);
            }

            $bookingId = $this->bookingRepository->create([
                "customerId" => $customerId,
                "local"      => "loja_fisica",
                "dateTime"   => $dateTime,
                "estado"     => "pendente_validacao_logistica_loja",
                "totalAmount"=> $totalAmount,
                "sinalAmount"=> round($totalAmount * self::DEPOSIT_PERCENTAGE / 100, 2)
            ]);

            foreach ($services as $service) {
                $this->bookingServiceRepository->create(
                    $bookingId, null, $service["id"],
                    $service["basePrice"], $service["estimatedDurationMinutes"],
                    "aceite"
                );
            }

            return [
                "bookingId" => $bookingId,
                "message"   => "Agendamento em loja criado! Serviços automaticamente aceites, pendente de validação logística da loja.",
                "totalAmount" => $totalAmount
            ];
        });
    }

    // ------------------------------------------------------------------
    // Criação — AMBULATÓRIO (estrutura por pessoa; OTP; pendente de aceitação)
    // ------------------------------------------------------------------

    public function createAmbulatoryBooking(int $customerId, array $data): array {
        return $this->executeTransactional(function() use ($customerId, $data) {
            $this->validate($data, function($v) {
                $v  ->required("date", "A data é obrigatória.")
                    ->required("time", "A hora é obrigatória.")
                    ->required("addressId", "A morada é obrigatória.")
                    ->required("otpCode", "O código OTP é obrigatório.");
            });

            $dateTime = $this->validateBookingDate($data["date"], $data["time"]);

            if (!$this->otpService->verify($customerId, (string)$data["otpCode"])) {
                throw new Exception("Código OTP inválido ou expirado.", 422);
            }

            $people = $data["people"] ?? null;
            if (!is_array($people) || empty($people)) {
                throw new Exception("Indique pelo menos uma pessoa com serviços.", 422);
            }

            [$allServices, $totalAmount, $totalDuration] = $this->resolveServicesForPeople($people);

            $this->validateStoreOpeningHours($data["date"], $data["time"], $totalDuration);

            if ($this->bookingRepository->countByDateWindow($dateTime, $totalDuration, "carrinha_ambulante") > 0) {
                throw new Exception("A carrinha já tem um agendamento consolidado nesta janela horária. Escolha outro slot.", 409);
            }

            $bookingId = $this->bookingRepository->create([
                "customerId" => $customerId,
                "addressId"  => (int)$data["addressId"],
                "local"      => "carrinha_ambulante",
                "dateTime"   => $dateTime,
                "estado"     => "pendente_aceitacao_funcionarios",
                "totalAmount"=> $totalAmount
            ]);

            foreach ($people as $person) {
                $personId = $this->bookingPersonRepository->create(
                    $bookingId,
                    $person["name"] ?? "Pessoa",
                    $person["notes"] ?? null
                );

                $personServiceIds = array_unique(array_map("intval", $person["serviceIds"] ?? []));
                foreach ($personServiceIds as $serviceId) {
                    $service = $this->serviceRepository->find($serviceId);
                    $this->bookingServiceRepository->create(
                        $bookingId, $personId, $serviceId,
                        $service["basePrice"], $service["estimatedDurationMinutes"],
                        "pendente"
                    );
                }
            }

            return [
                "bookingId" => $bookingId,
                "message"   => "Agendamento de ambulatório criado! Aguarda aceitação dos funcionários.",
                "totalAmount" => $totalAmount
            ];
        });
    }

    /**
     * Resolve os serviços de todas as pessoas.
     * Nota: cada pessoa tem o seu próprio conjunto (sem duplicados dentro da mesma pessoa),
     * mas o mesmo serviço pode ser partilhado por várias pessoas — e é contabilizado
     * (e faturado) por pessoa, conforme o modelo "um registo por pessoa+serviço".
     */
    private function resolveServicesForPeople(array $people): array {
        $totalAmount = 0.0;
        $totalDuration = 0;
        $servicesCache = [];

        foreach ($people as $person) {
            $personServiceIds = $person["serviceIds"] ?? [];

            if (empty($personServiceIds)) {
                throw new Exception("Cada pessoa precisa de pelo menos um serviço.", 422);
            }

            foreach (array_unique(array_map("intval", $personServiceIds)) as $serviceId) {
                if (!isset($servicesCache[$serviceId])) {
                    $service = $this->serviceRepository->find($serviceId);
                    if (!$service) {
                        throw new Exception("Serviço {$serviceId} não encontrado.", 404);
                    }
                    $servicesCache[$serviceId] = $service;
                }

                $totalAmount += $servicesCache[$serviceId]["basePrice"];
                $totalDuration += $servicesCache[$serviceId]["estimatedDurationMinutes"];
            }
        }

        return [array_values($servicesCache), round($totalAmount, 2), $totalDuration];
    }

    // ------------------------------------------------------------------
    // Consulta (cliente)
    // ------------------------------------------------------------------

    public function findCustomerBookings(int $customerId): array {
        $bookings = $this->bookingRepository->findByCustomer($customerId);

        foreach ($bookings as &$booking) {
            $booking["services"] = $this->bookingServiceRepository->findByBooking($booking["id"]);
            $booking["people"] = $booking["local"] === "carrinha_ambulante"
                ? $this->bookingPersonRepository->findByBooking($booking["id"])
                : [];
        }

        return ["bookings" => $bookings];
    }

    public function requestOtp(int $customerId): array {
        return $this->otpService->request($customerId);
    }

    // ------------------------------------------------------------------
    // Backoffice (admin)
    // ------------------------------------------------------------------

    public function listBookings(array $query): array {
        $filters = [
            "local"  => $query["local"] ?? null,
            "status" => $query["status"] ?? null,
            "date"   => $query["date"] ?? null,
            "cityId" => $query["cityId"] ?? null
        ];

        $page    = max(1, (int)($query["page"] ?? 1));
        $perPage = min(50, max(1, (int)($query["perPage"] ?? 10)));

        $total    = $this->bookingRepository->countAllForAdmin($filters);
        $bookings = $this->bookingRepository->findAllForAdmin($filters, $perPage, ($page - 1) * $perPage);

        return [
            "total"    => $total,
            "page"     => $page,
            "perPage"  => $perPage,
            "pages"    => (int)ceil($total / $perPage),
            "bookings" => $bookings
        ];
    }

    public function cancelBooking(int $bookingId): array {
        $booking = $this->bookingRepository->find($bookingId);

        if (!$booking) {
            throw new Exception("Agendamento não encontrado.", 404);
        }

        if (in_array($booking["status"], ["cancelado", "executado", "concluido"], true)) {
            throw new Exception("Este agendamento não pode ser cancelado (estado atual: {$booking['status']}).", 409);
        }

        $this->bookingRepository->updateEstado($bookingId, "cancelado");

        return [
            "bookingId" => $bookingId,
            "message"   => "Agendamento cancelado com sucesso. O cliente será notificado (simulado)."
        ];
    }

    // ------------------------------------------------------------------
    // Serviços ativos (catálogo público)
    // ------------------------------------------------------------------

    public function listActiveServices(?int $categoryId = null): array {
        $services = $this->serviceRepository->findActive($categoryId);

        return [
            "services" => is_array($services) ? $services : []
        ];
    }
}
