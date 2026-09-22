<?php
require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/BookingService.php";

class BookingController extends BaseController {
    private BookingService $bookingService;

    public function __construct() {
        $this->bookingService = new BookingService();
    }

    public function serviceList(): array {
        return $this->bookingService->listActiveServices();
    }

    public function availability(): array {
        return $this->bookingService->findAvailability($_GET);
    }

    public function otpRequest(): array {
        $customerId = $this->requireCustomer();
        return $this->bookingService->requestOtp($customerId);
    }

    public function createStoreBooking(): array {
        $customerId = $this->requireCustomer();
        return $this->bookingService->createStoreBooking($customerId, $this->getRequestData());
    }

    public function createAmbulatoryBooking(): array {
        $customerId = $this->requireCustomer();
        return $this->bookingService->createAmbulatoryBooking($customerId, $this->getRequestData());
    }

    public function myBookings(): array {
        $customerId = $this->requireCustomer();
        return $this->bookingService->findCustomerBookings($customerId);
    }
}
