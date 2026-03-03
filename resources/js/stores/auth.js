import { defineStore } from 'pinia';
import { api, ensureCsrfCookie } from '../bootstrap';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        targets: null,
        initialized: false,
    }),
    getters: {
        isAuthenticated: (state) => !!state.user,
    },
    actions: {
        async bootstrap() {
            try {
                await api.get('/me');
                // If /me succeeds, user will be filled via a later enhancement.
                // For now, just mark initialized.
            } catch {
                // Not authenticated; ignore.
            } finally {
                this.initialized = true;
            }
        },
        async register(payload) {
            await ensureCsrfCookie();

            const { data } = await api.post('/register', payload);

            this.user = data.user;
            this.targets = data.targets ?? null;
        },
        async login(payload) {
            await ensureCsrfCookie();

            const { data } = await api.post('/login', payload);

            this.user = data.user;
            this.targets = data.targets ?? null;
        },
        async logout() {
            await api.post('/logout');
            this.user = null;
            this.targets = null;
        },
    },
});

