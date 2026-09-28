/**
 * Validadores do formulário de fornecedores (Fase 6.1 · RF-85).
 *
 * Contrato de nomes: as chaves espelham EXACTAMENTE o `name` do campo no
 * formulário e o que a API espera (`app/services/SupplierService.php` · §18.5).
 */
const supplierValidators = {
    name(val) {
        const value = String(val || '').trim();

        if (value === '') return 'Indique o nome do fornecedor.';
        if (value.length < 2) return 'O nome deve ter pelo menos 2 caracteres.';
        if (value.length > 150) return 'O nome não pode exceder 150 caracteres.';
    },
    nif(val) {
        const value = String(val || '').trim();

        if (value === '') return;
        if (!/^[0-9A-Za-z]{1,20}$/.test(value)) return 'O NIF deve ter até 20 caracteres (números e letras).';
    },
    phone(val) {
        const value = String(val || '').trim();

        if (value === '') return;
        if (!/^[0-9+()\s\-]{6,20}$/.test(value)) return 'O telemóvel deve ter entre 6 e 20 caracteres.';
    },
    email(val) {
        const value = String(val || '').trim();

        if (value === '') return;
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return 'Endereço de email inválido.';
    }
};