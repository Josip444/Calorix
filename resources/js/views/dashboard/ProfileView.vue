<template>
  <div class="max-w-2xl space-y-6">
    <h1 class="text-xl font-semibold">Profil</h1>

    <form @submit.prevent="onSubmit" class="space-y-4 bg-white rounded-2xl shadow-sm p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Ime</label>
          <input
            v-model="form.first_name"
            type="text"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Prezime</label>
          <input
            v-model="form.last_name"
            type="text"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
          />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input
          v-model="form.email"
          type="email"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
        />
        <p v-if="errors.email" class="mt-1 text-xs text-red-600">
          {{ errors.email }}
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Dob</label>
          <input
            v-model="form.age"
            type="number"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Spol</label>
          <select
            v-model="form.sex"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white"
          >
            <option value="male">Muško</option>
            <option value="female">Žensko</option>
            <option value="other">Drugo</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Visina (cm)</label>
          <input
            v-model="form.height_cm"
            type="number"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Trenutna težina (kg)</label>
          <input
            v-model="form.current_weight_kg"
            type="number"
            step="0.1"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Ciljana težina (kg)</label>
          <input
            v-model="form.goal_weight_kg"
            type="number"
            step="0.1"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Aktivnost</label>
          <select
            v-model="form.activity_level"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white"
          >
            <option value="sedentary">Sjedeći stil života</option>
            <option value="light">Blaga aktivnost</option>
            <option value="moderate">Umjerena aktivnost</option>
            <option value="active">Aktivan</option>
            <option value="very_active">Vrlo aktivan</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Cilj</label>
          <select
            v-model="form.goal_type"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 bg-white"
          >
            <option value="lose">Mršavljenje</option>
            <option value="maintain">Održavanje</option>
            <option value="gain">Dobivanje na težini</option>
            <option value="build">Izgradnja mišića</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Broj obroka dnevno</label>
          <input
            v-model="form.meals_per_day"
            type="number"
            min="1"
            max="8"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
          />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Alergije / namirnice koje ne voliš</label>
        <textarea
          v-model="form.allergies_text"
          rows="3"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
        ></textarea>
      </div>

      <p v-if="errors.message" class="text-xs text-red-600">
        {{ errors.message }}
      </p>

      <button
        type="submit"
        class="inline-flex items-center px-5 py-2.5 rounded-full bg-slate-900 text-white text-sm font-medium disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Spremanje...' : 'Spremi promjene' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { api } from '../../bootstrap';

const auth = useAuthStore();

const form = reactive({
  name: '',
  first_name: '',
  last_name: '',
  email: '',
  sex: '',
  age: '',
  height_cm: '',
  current_weight_kg: '',
  goal_weight_kg: '',
  activity_level: '',
  goal_type: '',
  meals_per_day: '',
  allergies_text: '',
});

const errors = reactive({});
const loading = ref(false);

onMounted(async () => {
  try {
    const { data } = await api.get('/profile');
    Object.assign(form, data.user);
    auth.user = data.user;
  } catch {
    // ignore for now
  }
});

async function onSubmit() {
  loading.value = true;
  Object.keys(errors).forEach((k) => delete errors[k]);

  try {
    const { data } = await api.put('/profile', form);
    auth.user = data.user;
  } catch (error) {
    if (error.response?.status === 422) {
      Object.assign(errors, error.response.data.errors ?? {});
    } else {
      errors.message = 'Došlo je do greške. Pokušaj ponovno.';
    }
  } finally {
    loading.value = false;
  }
}
</script>

