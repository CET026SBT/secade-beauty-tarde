const API = ((baseApi) => ({
    auth: {
        register: (formData) => baseApi.post('?action=auth-register', formData),
        login: (formData) => baseApi.post('?action=auth-login', formData),
        logout: () => baseApi.post('?action=auth-logout')
    },
    cities: {
        getSupported: () => baseApi.get('?action=city-supported')
    },
    categories: {
        getAll: () => baseApi.get('?action=category-all')
    },
    stats: {
        summary: () => baseApi.get('?action=site-stats', 0)
    },
    booking: {
        services: () => baseApi.get('?action=booking-services'),
        servicePhotos: (serviceId) => baseApi.get(`?action=service-photos&serviceId=${serviceId}`),
        availability: (params) => baseApi.get(`?action=booking-availability&${$.param(params)}`, 0),
        requestOtp: () => baseApi.post('?action=booking-otp-request', {}),
        createStore: (data) => baseApi.post('?action=booking-create-store', data),
        createAmbulatory: (data) => baseApi.post('?action=booking-create-amb', data),
        myBookings: () => baseApi.get('?action=booking-my', 0),
        cancelBooking: (bookingId) => baseApi.post('?action=customer-booking-cancel', { bookingId })
    },
    customer: {
        profile: () => baseApi.get('?action=customer-profile', 0),
        addresses: () => baseApi.get('?action=customer-address-list', 0),
        createAddress: (data) => baseApi.post('?action=customer-address-store', data),
        setPrincipalAddress: (addressId) => baseApi.post('?action=customer-address-set-principal', { addressId }),
        deleteAddress: (addressId) => baseApi.post('?action=customer-address-delete', { addressId })
    },
    admin: {
        appointments: (params = {}) => baseApi.get(`?action=admin-appointments-list&${$.param(params)}`, 0),
        cancelAppointment: (bookingId) => baseApi.post('?action=admin-appointment-cancel', { bookingId }),
        appointmentDetails: (bookingId) => baseApi.get(`?action=admin-appointment-details&bookingId=${bookingId}`, 0),
        executeAppointment: (bookingId) => baseApi.post('?action=admin-appointment-execute', { bookingId }),
        routes: (params = {}) => baseApi.get(`?action=admin-routes-list&${$.param(params)}`, 0),
        decideRoute: (data) => baseApi.post('?action=admin-route-decide', data),
        dashboard: () => baseApi.get('?action=admin-dashboard-summary', 0),
        alerts: {
            summary: () => baseApi.get('?action=admin-alert-summary', 0),
            list: () => baseApi.get('?action=admin-alert-list', 0),
            markRead: () => baseApi.post('?action=admin-alert-read', {})
        },
        agenda: {
            month: (month) => baseApi.get(`?action=admin-employee-agenda-list&month=${month}`, 0)
        },
        suppliers: {
            list: (params = {}) => baseApi.get(`?action=admin-supplier-list&${$.param(params)}`, 0),
            store: (data) => baseApi.post('?action=admin-supplier-store', data),
            update: (data) => baseApi.post('?action=admin-supplier-update', data),
            setActive: (supplierId, active) => baseApi.post('?action=admin-supplier-set-active', { supplierId, active })
        },
        commissions: {
            list: (month) => baseApi.get(`?action=admin-commission-list&month=${month}`, 0)
        },
        services: {
            pending: (params = {}) => baseApi.get(`?action=admin-service-pending-list&${$.param(params)}`, 0),
            accepted: (params = {}) => baseApi.get(`?action=admin-service-accepted-list&${$.param(params)}`, 0),
            accept: (bookingServiceId, bookingId = null, employeeId = null) => baseApi.post('?action=admin-service-accept', { bookingServiceId, bookingId, employeeId }),
            unaccept: (bookingServiceId, bookingId = null, employeeId = null) => baseApi.post('?action=admin-service-unaccept', { bookingServiceId, bookingId, employeeId })
        },
        catalog: {
            // O catálogo público já traz `photoUrl` (destaque) por serviço.
            list: () => baseApi.get('?action=booking-services', 0),
            photos: (serviceId) => baseApi.get(`?action=admin-service-photo-list&serviceId=${serviceId}`, 0),
            uploadPhoto: (formData) => baseApi.post('?action=admin-service-photo-upload', formData),
            setPhotoFeatured: (photoId) => baseApi.post('?action=admin-service-photo-featured', { photoId }),
            removePhoto: (photoId) => baseApi.post('?action=admin-service-photo-remove', { photoId })
        },
        fiscal: {
            calendar: (params = {}) => baseApi.get(`?action=admin-fiscal-calendar-list&${$.param(params)}`, 0),
            alerts: () => baseApi.get('?action=admin-fiscal-alert-list', 0),
            createObligation: (data) => baseApi.post('?action=admin-fiscal-obligation-create', data),
            markPaid: (obligationId) => baseApi.post('?action=admin-fiscal-obligation-paid', { obligationId }),
            markAlertsRead: () => baseApi.post('?action=admin-fiscal-alert-read', {})
        },
        greenReceipt: {
            config: () => baseApi.get('?action=admin-green-receipt-config', 0),
            saveConfig: (data) => baseApi.post('?action=admin-green-receipt-config-save', data)
        }
    },
    feedback: {
        list: (limit = 6) => baseApi.get(`?action=feedback-list&limit=${limit}`, 0),
        myState: () => baseApi.get('?action=feedback-my', 0),
        create: (data) => baseApi.post('?action=feedback-create', data)
    }
}))(new ApiClient(`${BASE_URL ?? ''}/api`));
