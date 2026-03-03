<template>
  <div class="min-h-screen flex flex-col bg-slate-100">
    <!-- Premium Navbar -->
    <header class="w-full pt-6 px-4">
      <nav class="max-w-4xl mx-auto bg-white/80 backdrop-blur-md rounded-2xl shadow-xl shadow-slate-200/50 border border-white px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-slate-900 rounded-lg flex items-center justify-center text-white font-bold text-xl">
            C
          </div>
          <span class="text-xl font-bold tracking-tight">Calorix</span>
        </div>
        
        <div class="hidden md:flex items-center gap-8">
          <a href="#" class="text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors">Kako funkcionira</a>
          <a href="#" class="text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors">O nama</a>
          <a href="#" class="text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors">Kontakt</a>
        </div>

        <div class="flex items-center gap-4">
          <RouterLink to="/login" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition-colors">Prijava</RouterLink>
          <RouterLink
            to="/register"
            class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold shadow-lg shadow-slate-900/20 hover:bg-slate-800 transition-all active:scale-95"
          >
            Registracija
          </RouterLink>
        </div>
      </nav>
    </header>

    <main class="flex-1 flex items-center justify-center px-4 py-10">
      <div class="max-w-lg w-full bg-white rounded-2xl shadow-sm p-8">
        <h1 class="text-2xl font-semibold mb-2">Prijava</h1>
        <p class="text-sm text-slate-500 mb-6">
          Ulogiraj se u svoj Calorix račun.
        </p>

        <form @submit.prevent="onSubmit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Email adresa</label>
            <input
              v-model="form.email"
              type="email"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
            />
            <p v-if="errors.email" class="mt-1 text-xs text-red-600">
              {{ errors.email }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Lozinka</label>
            <input
              v-model="form.password"
              type="password"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
            />
            <p v-if="errors.password" class="mt-1 text-xs text-red-600">
              {{ errors.password }}
            </p>
          </div>

          <p v-if="errors.message" class="text-xs text-red-600">
            {{ errors.message }}
          </p>

          <button
            type="submit"
            class="w-full mt-4 inline-flex items-center justify-center rounded-full bg-slate-900 text-white text-sm font-medium py-2.5 disabled:opacity-60"
            :disabled="loading"
          >
            {{ loading ? 'Prijava...' : 'Prijava' }}
          </button>
        </form>
      </div>
    </main>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const auth = useAuthStore();

const form = reactive({
  email: '',
  password: '',
});

const errors = reactive({});
const loading = ref(false);

async function onSubmit() {
  loading.value = true;
  Object.keys(errors).forEach((k) => delete errors[k]);

  try {
    await auth.login(form);
    router.push({ name: 'dashboard' });
  } catch (error) {
    if (error.response?.status === 422) {
      Object.assign(errors, error.response.data.errors ?? {});
    } else if (error.response?.status === 401) {
      errors.message = error.response.data.message ?? 'Neispravni podaci za prijavu.';
    } else {
      errors.message = 'Došlo je do greške. Pokušaj ponovno.';
    }
  } finally {
    loading.value = false;
  }
}
</script>

