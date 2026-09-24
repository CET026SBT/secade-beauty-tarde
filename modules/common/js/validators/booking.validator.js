const bookingValidators = ({ supportedCities = [] } = {}) => ({
    channel(val) {
        if (!val) return 'Escolha onde prefere ser atendido.';
    },
    addressChoice(val) {
        if (!val) return 'Selecione a morada de atendimento.';
    },
    cityName(val) {
        if (val === '') return 'A cidade é obrigatória.';
        if (supportedCities.length > 0 && !supportedCities.includes(val)) {
            return `De momento apenas atendemos nas cidades suportadas. (${supportedCities.join(', ')})`;
        }
    },
    street(val) {
        if (val === '') return 'A rua é obrigatória.';
    },
    doorNumber(val) {
        if (val === '') return 'O número da porta é obrigatório.';
    },
    zipCode(val) {
        if (val === '') return 'O código postal é obrigatório.';
        if (!this.isZipCode(val)) return 'Código postal inválido (formato 0000-000).';
    },
    otpCode(val) {
        if (!window.bookingOtpCode) return 'Solicite primeiro o envio do código OTP.';
        if (val === '') return 'O código OTP é obrigatório.';
        if (!/^\d{6}$/.test(String(val))) return 'O código OTP tem 6 dígitos.';
        if (String(window.bookingOtpCode) !== String(val)) return 'O código introduzido não corresponde ao código enviado.';
    },
    bookingDate(val) {
        if (val === '') return 'Escolha a data pretendida.';

        const date = new Date(`${val}T00:00:00`);
        if (isNaN(date.getTime())) return 'Data inválida.';

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (date <= today) return 'Escolha uma data futura.';

        const dayOfWeek = date.getDay(); // 0 = Domingo, 1 = Segunda
        if (dayOfWeek === 0 || dayOfWeek === 1) return 'Apenas é possível agendar de Terça a Sábado.';
    },
    termsAccepted(val) {
        if (!this.isAccepted(val)) return 'Deve aceitar as condições para continuar.';
    }
});