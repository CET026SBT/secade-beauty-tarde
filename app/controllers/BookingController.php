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
        Session::requireProfileApi(["cliente"]);
        return $this->bookingService->requestOtp(Session::userId());
    }

    public function createStoreBooking(): array {
        Session::requireProfileApi(["cliente"]);
        return $this->bookingService->createStoreBooking(Session::userId(), $this->getRequestData());
    }

    public function createAmbulatoryBooking(): array {
        Session::requireProfileApi(["cliente"]);
        return $this->bookingService->createAmbulatoryBooking(Session::userId(), $this->getRequestData());
    }

    public function myBookings(): array {
        Session::requireProfileApi(["cliente"]);
        return $this->bookingService->findCustomerBookings(Session::userId());
    }
}
