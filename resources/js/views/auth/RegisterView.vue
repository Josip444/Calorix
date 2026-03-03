<template>
  <div class="min-h-screen flex flex-col bg-slate-100 font-sans text-slate-900">
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

    <main class="flex-1 flex flex-col items-center justify-center px-4 py-12">
      <!-- Stepper with Lines -->
      <div class="w-full max-w-2xl mb-12 relative">
        <div class="flex items-center justify-between relative px-2">
          <!-- Connection Lines Background -->
          <div class="absolute top-1/2 left-0 w-full h-1 bg-slate-200 -translate-y-[14px] z-0 px-8">
             <div class="h-full bg-slate-900 transition-all duration-500" :style="{ width: (currentStep / (steps.length - 1)) * 100 + '%' }"></div>
          </div>

          <div
            v-for="(stepLabel, index) in steps"
            :key="stepLabel"
            class="relative z-10 flex flex-col items-center"
          >
            <button
              type="button"
              class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold transition-all duration-300 mb-3"
              :class="[
                currentStep >= index ? 'bg-slate-900 text-white' : 'bg-white text-slate-400 border-2 border-slate-200'
              ]"
              @click="goToStep(index)"
            >
              <span v-if="currentStep > index">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
              </span>
              <span v-else>{{ index + 1 }}</span>
            </button>
            <span 
              class="text-[11px] font-bold uppercase tracking-wider whitespace-nowrap"
              :class="currentStep >= index ? 'text-slate-900' : 'text-slate-400'"
            >
              {{ stepLabel }}
            </span>
          </div>
        </div>
      </div>

      <!-- Registration Card -->
      <div class="max-w-xl w-full bg-white rounded-[32px] shadow-2xl shadow-slate-200/60 p-10 border border-slate-50">
        <form @submit.prevent="onSubmit" class="space-y-8">
          
          <!-- Dynamic Form Content -->
          <div class="text-center space-y-2 mb-8">
             <h2 class="text-3xl font-bold text-slate-900 leading-tight">
                {{ currentStepTitle }}
             </h2>
             <p class="text-slate-500 font-medium">
                {{ currentStepSubtitle }}
             </p>
          </div>

          <!-- Step 1: Account -->
          <section v-if="currentStep === 0" class="space-y-4">
            <div class="space-y-4">
              <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Ime i prezime</label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="npr. Ivan Horvat"
                  class="w-full rounded-2xl border-2 border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-medium focus:outline-none focus:border-slate-900 focus:bg-white transition-all"
                />
                <p v-if="errors.name" class="mt-1 text-xs text-red-500 font-semibold">{{ errors.name }}</p>
              </div>

              <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Email adresa</label>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="ivan@primjer.com"
                  class="w-full rounded-2xl border-2 border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-medium focus:outline-none focus:border-slate-900 focus:bg-white transition-all"
                />
                <p v-if="errors.email" class="mt-1 text-xs text-red-500 font-semibold">{{ errors.email }}</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Lozinka</label>
                  <input
                    v-model="form.password"
                    type="password"
                    class="w-full rounded-2xl border-2 border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-medium focus:outline-none focus:border-slate-900 focus:bg-white transition-all"
                  />
                  <p v-if="errors.password" class="mt-1 text-xs text-red-500 font-semibold">{{ errors.password }}</p>
                </div>
                <div>
                  <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Potvrda</label>
                  <input
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full rounded-2xl border-2 border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-medium focus:outline-none focus:border-slate-900 focus:bg-white transition-all"
                  />
                </div>
              </div>
            </div>
          </section>

          <!-- Step 2: Basic data -->
          <section v-else-if="currentStep === 1" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Dob (godine)</label>
                <input
                  v-model.number="form.age"
                  type="number"
                  placeholder="25"
                  class="w-full rounded-2xl border-2 border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-medium focus:outline-none focus:border-slate-900 focus:bg-white transition-all"
                />
                <p v-if="errors.age" class="mt-1 text-xs text-red-500 font-semibold">{{ errors.age }}</p>
              </div>
              <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Spol</label>
                <select
                  v-model="form.sex"
                  class="w-full rounded-2xl border-2 border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-medium focus:outline-none focus:border-slate-900 focus:bg-white transition-all appearance-none"
                >
                  <option value="">Odaberi</option>
                  <option value="male">Muško</option>
                  <option value="female">Žensko</option>
                  <option value="other">Drugo</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Visina (cm)</label>
                <input
                  v-model.number="form.height_cm"
                  type="number"
                  placeholder="175"
                  class="w-full rounded-2xl border-2 border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-medium focus:outline-none focus:border-slate-900 focus:bg-white transition-all"
                />
              </div>
              <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Težina (kg)</label>
                <input
                  v-model.number="form.start_weight_kg"
                  type="number"
                  step="0.1"
                  placeholder="70"
                  class="w-full rounded-2xl border-2 border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-medium focus:outline-none focus:border-slate-900 focus:bg-white transition-all"
                />
              </div>
            </div>

            <!-- Hint Box -->
            <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-4 flex gap-3">
               <div class="w-5 h-5 flex-shrink-0 text-amber-500 mt-0.5">💡</div>
               <div>
                  <h4 class="text-xs font-bold text-slate-800 mb-0.5">Zašto nam ovo treba?</h4>
                  <p class="text-xs text-slate-600 leading-relaxed font-medium">Ovi podaci su potrebni za precizan izračun tvog optimalnog kalorijskog unosa.</p>
               </div>
            </div>
          </section>

          <!-- Step 3: Activity & goal -->
          <section v-else-if="currentStep === 2" class="space-y-8">
            <!-- Activity Level Cards -->
            <div class="space-y-3">
              <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Razina tjelesne aktivnosti</label>
              <div class="space-y-3">
                <div 
                  v-for="opt in [
                    { id: 'sedentary', title: 'Sjedilački', desc: 'Malo ili nimalo vježbanja' },
                    { id: 'light', title: 'Lagano aktivan', desc: '1-3 dana tjedno' },
                    { id: 'moderate', title: 'Umjereno aktivan', desc: '3-5 dana tjedno' },
                    { id: 'active', title: 'Vrlo aktivan', desc: '6-7 dana tjedno' },
                    { id: 'very_active', title: 'Ekstremno aktivan', desc: 'Intenzivno vježbanje 2x dnevno' }
                  ]" 
                  :key="opt.id"
                  @click="form.activity_level = opt.id"
                  class="relative flex items-center gap-4 p-4 rounded-2xl border-2 cursor-pointer transition-all"
                  :class="form.activity_level === opt.id ? 'border-slate-900 bg-white ring-4 ring-slate-900/5' : 'border-slate-100 bg-white hover:border-slate-200'"
                >
                  <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0"
                       :class="form.activity_level === opt.id ? 'border-slate-900' : 'border-slate-200'">
                    <div v-if="form.activity_level === opt.id" class="w-2.5 h-2.5 bg-slate-900 rounded-full"></div>
                  </div>
                  <div>
                    <h4 class="text-sm font-bold text-slate-900">{{ opt.title }}</h4>
                    <p class="text-[11px] font-medium text-slate-400">{{ opt.desc }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Goal Cards Grid -->
            <div class="space-y-3">
              <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Tvoj cilj</label>
              <div class="grid grid-cols-2 gap-4">
                <div 
                  v-for="goal in [
                    { id: 'lose', title: 'Smanjenje tjelesne mase', icon: '📉' },
                    { id: 'gain', title: 'Povećanje tjelesne mase', icon: '📈' },
                    { id: 'build', title: 'Gradnja mišića', icon: '💪' },
                    { id: 'maintain', title: 'Održavanje tjelesne mase', icon: '⚖️' }
                  ]" 
                  :key="goal.id"
                  @click="form.goal_type = goal.id"
                  class="flex flex-col items-center justify-center p-6 rounded-[28px] border-2 cursor-pointer transition-all text-center gap-3"
                  :class="form.goal_type === goal.id ? 'border-slate-900 bg-white ring-4 ring-slate-900/5' : 'border-slate-100 bg-white hover:border-slate-200'"
                >
                  <div class="text-3xl mb-1">{{ goal.icon }}</div>
                  <h4 class="text-[13px] font-bold leading-tight">{{ goal.title }}</h4>
                </div>
              </div>
            </div>

            <div v-if="form.goal_type !== 'maintain'" class="pt-2 animate-in fade-in slide-in-from-top-2">
              <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Ciljana težina (kg)</label>
              <input
                v-model.number="form.goal_weight_kg"
                type="number"
                step="0.1"
                placeholder="npr. 75"
                class="w-full rounded-2xl border-2 border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-medium focus:outline-none focus:border-slate-900 focus:bg-white transition-all text-center"
              />
            </div>
          </section>

          <!-- Step 4: Preferences -->
          <section v-else class="space-y-6">
            <div>
              <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Broj obroka dnevno</label>
              <div class="flex gap-3">
                 <button 
                  v-for="n in [2,3,4,5,6]" :key="n"
                  type="button"
                  @click="form.meals_per_day = n"
                  class="flex-1 py-3 rounded-2xl border-2 transition-all font-bold text-sm"
                  :class="form.meals_per_day === n ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-100 bg-slate-50 text-slate-400 hover:border-slate-200'"
                 >
                   {{ n }}
                 </button>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Alergije i ograničenja</label>
              <textarea
                v-model="form.allergies_text"
                rows="4"
                placeholder="npr. kikiriki, laktoza, ne volim brokulu..."
                class="w-full rounded-2xl border-2 border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-medium focus:outline-none focus:border-slate-900 focus:bg-white transition-all resize-none"
              ></textarea>
            </div>
          </section>

          <p v-if="errors.message" class="text-sm text-center text-red-600 font-bold bg-red-50 py-3 rounded-xl">
            ⚠️ {{ errors.message }}
          </p>

          <div class="flex items-center justify-between pt-4">
            <button
              v-if="currentStep > 0"
              type="button"
              class="flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-slate-900 transition-colors"
              @click="prevStep"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
              Natrag
            </button>
            <div v-else></div>

            <button
              v-if="currentStep < steps.length - 1"
              type="button"
              class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl bg-black text-white text-sm font-bold shadow-xl shadow-slate-200/50 hover:bg-slate-800 transition-all transform active:scale-95"
              @click="nextStep"
            >
              Nastavi
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
            </button>
            <button
              v-else
              type="submit"
              class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl bg-black text-white text-sm font-bold shadow-xl shadow-slate-200/50 hover:bg-slate-800 transition-all transform active:scale-95 disabled:opacity-50"
              :disabled="loading"
            >
              {{ loading ? 'Kreiranje računa...' : 'Završi registraciju' }}
            </button>
          </div>

          <div class="text-center pt-8 border-t border-slate-50">
             <p class="text-sm font-medium text-slate-400">
                Već imaš račun? <RouterLink to="/login" class="text-slate-900 font-bold hover:underline">Prijavi se</RouterLink>
             </p>
          </div>
        </form>
      </div>
      
      <RouterLink to="/" class="mt-8 text-sm font-bold text-slate-400 hover:text-slate-900 flex items-center gap-2 transition-colors">
         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
         Natrag na početnu
      </RouterLink>
    </main>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const auth = useAuthStore();

const steps = ['Račun', 'Osnovni podaci', 'Aktivnost i cilj', 'Preference'];
const currentStep = ref(0);

const currentStepTitle = computed(() => {
   return [
      'Kreiraj račun',
      'Osnovni podaci',
      'Aktivnost i cilj',
      'Preference'
   ][currentStep.value];
});

const currentStepSubtitle = computed(() => {
   return [
      'Započni svoju prehrambenu transformaciju.',
      'Reci nam nešto više o sebi.',
      'Pomozi nam da prilagodimo plan tvom cilju.',
      'Još samo par detalja za tvoj idealan plan.'
   ][currentStep.value];
});

const form = reactive({
  name: '',
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  sex: '',
  age: '',
  height_cm: '',
  start_weight_kg: '',
  current_weight_kg: '',
  goal_weight_kg: '',
  activity_level: '',
  goal_type: '',
  meals_per_day: 3,
  allergies_text: '',
});

const errors = reactive({});
const loading = ref(false);

function goToStep(index) {
  if (index >= 0 && index < steps.length) {
    currentStep.value = index;
  }
}

function nextStep() {
  if (currentStep.value < steps.length - 1) {
    currentStep.value++;
  }
}

function prevStep() {
  if (currentStep.value > 0) {
    currentStep.value--;
  }
}

async function onSubmit() {
  loading.value = true;
  Object.keys(errors).forEach((k) => delete errors[k]);

  try {
    if (!form.first_name || !form.last_name) {
      const parts = (form.name || '').trim().split(' ');
      form.first_name = form.first_name || parts[0] || '';
      form.last_name = form.last_name || parts.slice(1).join(' ') || '';
    }

    await auth.register(form);
    router.push({ name: 'dashboard' });
  } catch (error) {
    if (error.response?.status === 422) {
      Object.assign(errors, error.response.data.errors ?? {});
      const fieldOrder = [
        ['name', 'email', 'password'],
        ['age', 'sex', 'height_cm', 'start_weight_kg'],
        ['activity_level', 'goal_type', 'goal_weight_kg'],
        ['meals_per_day', 'allergies_text'],
      ];
      for (let i = 0; i < fieldOrder.length; i += 1) {
        if (fieldOrder[i].some((field) => errors[field])) {
          currentStep.value = i;
          break;
        }
      }
    } else {
      errors.message = 'Došlo je do greške. Pokušaj ponovno.';
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}
</style>

