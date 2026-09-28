/**
 * Cliente do serviço de GEOCODIFICAÇÃO (OpenStreetMap / Nominatim).
 *
 * PORQUE EXISTE: esta era a **única** chamada a um serviço externo no código próprio
 * que não passava por um cliente dedicado — o `$.get('https://nominatim...')` e a
 * tradução da resposta estavam embutidos no componente de UI (`addressAutocomplete.js`),
 * pelo que o URL do fornecedor e o formato dele viviam numa view.
 *
 * Segue o padrão do `apiClient.js`/`api.js` (transporte + normalização num sítio), com
 * uma diferença: aponta a um serviço **externo** (URL absoluto), não às nossas `?action=`.
 * É a centralização prevista em §25.3 do backlog — e, por estar isolada aqui, uma
 * eventual troca de fornecedor (ou o seu uso para estimar distâncias, §25.3) passa a ser
 * uma alteração de um só ficheiro.
 */
class GeocodingClient {
    static ENDPOINT = 'https://nominatim.openstreetmap.org/search';
    static COUNTRY_CODES = 'pt';
    static LIMIT = 5;

    constructor(endpoint = GeocodingClient.ENDPOINT) {
        this.endpoint = endpoint;
    }

    /**
     * Procura moradas e resolve sempre com um array **normalizado** (nunca com o
     * formato do fornecedor). Em erro, a promise é rejeitada.
     */
    search(query, { limit = GeocodingClient.LIMIT } = {}) {
        return $.get(this.endpoint, {
            q: query,
            countrycodes: GeocodingClient.COUNTRY_CODES,
            format: 'json',
            addressdetails: 1,
            limit
        }).then(response => GeocodingClient.normalize(response, limit));
    }

    /** Traduz a resposta do fornecedor para o formato de morada do projecto. */
    static normalize(response, limit = GeocodingClient.LIMIT) {
        return (Array.isArray(response) ? response : [])
            .slice(0, limit)
            .map(item => GeocodingClient.toAddress(item));
    }

    /** Converte um resultado do Nominatim na morada que o formulário/BD usa. */
    static toAddress(item) {
        const addr = item.address || {};

        // Rua: preferir o nome da via; o nome do resultado é o fallback.
        const streetParts = [
            addr.road || addr.pedestrian || addr.square,
            addr.industrial,
            addr.leisure
        ].filter(Boolean);

        return {
            isSaved: false,
            street: [...new Set(streetParts)].join(', ') || item.name || '',
            doorNumber: addr.house_number || '',
            floor: '',
            zipCode: addr.postcode || '',
            cityName: addr.city || addr.town || addr.village || addr.hamlet || '',
            district: addr.county || '',
            raw: item
        };
    }
}

const geocodingApi = new GeocodingClient();