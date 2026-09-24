class ApiClient {
    baseUrl;
    #cache;

    constructor(baseUrl) {
        this.baseUrl = baseUrl;
        this.#cache = new Map();
    }

    get(endpoint, ttlSeconds=60) {
        const now = Date.now();

        if (this.#cache.has(endpoint)) {
            const cached = this.#cache.get(endpoint);

            if (now < cached.expiresAt) {
                return $.Deferred().resolve(cached.data).promise();
            }

            this.#cache.delete(endpoint);
        }

        return $.ajax({
                url: `${this.baseUrl}${endpoint}`,
                type: 'GET',
                dataType: 'json'
            })
            .done(res => {
                /*if (res.success) {
                    this.#cache.set(endpoint, {
                        data: res,
                        expiresAt: now + ttlSeconds * 1000
                    });
                }*/
            });
    }

    post(endpoint, data) {
        const isFormData = data instanceof FormData;

        return $.ajax({
            url: `${this.baseUrl}${endpoint}`,
            type: 'POST',
            data: isFormData ? data : JSON.stringify(data),
            processData: !isFormData,
            contentType: isFormData ? false : 'application/json; charset=utf-8',
            dataType: 'json'
        })
        .done(res => {
            /*if (res.success) {
                const actionMatch = endpoint.match(/[?&]action=([a-z-]+)-[a-z]+(&|$)/);
                
                if (actionMatch) {
                    const domain = actionMatch[1];
                    this.clearCacheByDomain(domain);
                }
            }*/
        });
    }
    
    clearCacheByDomain(domain) {
        const domainRegex = new RegExp(`[?&]action=${domain}-`);

        for (const key of this.#cache.keys()) {
            if (domainRegex.test(key)) {
                this.#cache.delete(key);
            }
        }
    }
}
