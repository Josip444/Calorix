<template>
  <div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Moji planovi prehrane</h1>
        <p class="text-sm text-slate-500">
          Personalizirani planovi generirani prema tvojim mjerama.
        </p>
      </div>
      <button
        type="button"
        class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-slate-900 text-white text-sm font-semibold shadow-lg shadow-slate-200 hover:bg-slate-800 transition-all disabled:opacity-60"
        :disabled="loading || isGeneratingAny"
        @click="generatePlan"
      >
        <span v-if="loading || isGeneratingAny" class="mr-2">
           <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
             <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
             <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
           </svg>
        </span>
        {{ loading || isGeneratingAny ? 'Generiram tvoj plan...' : 'Generiraj novi plan' }}
      </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- Sidebar Plan List -->
      <aside class="lg:col-span-3 space-y-4">
        <div class="bg-white rounded-3xl shadow-sm p-6 border border-slate-100">
          <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Povijest planova</p>
          <div class="space-y-3">
            <button
              v-for="plan in plans"
              :key="plan.id"
              class="w-full text-left p-4 rounded-2xl border transition-all"
              :class="plan.id === selectedPlanId ? 'border-slate-900 bg-slate-50 ring-1 ring-slate-900' : 'border-slate-100 bg-white hover:border-slate-300'"
              @click="selectPlan(plan.id)"
            >
              <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full"
                      :class="{
                        'bg-emerald-100 text-emerald-700': plan.status === 'generated',
                        'bg-amber-100 text-amber-700': plan.status === 'generating',
                        'bg-red-100 text-red-700': plan.status === 'failed'
                      }">
                  {{ getStatusLabel(plan) }}
                </span>
                <span class="text-[10px] text-slate-400">{{ formatDate(plan.created_at) }}</span>
              </div>
              <p class="font-semibold text-sm text-slate-900">{{ plan.start_date || 'Novi plan' }}</p>
              <p class="text-xs text-slate-500 truncate">{{ plan.daily_calories_target }} kcal dnevno</p>
              
              <div v-if="plan.status === 'generating'" class="mt-2 h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-slate-900 transition-all duration-500" :style="{ width: `${plan.progress_percentage}%` }"></div>
              </div>
            </button>
            <div v-if="!plans.length" class="text-center py-8">
               <p class="text-sm text-slate-400 italic">Nemaš aktivnih planova.</p>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Content Area -->
      <main class="lg:col-span-9 space-y-6">
        <div v-if="!activePlan" class="bg-white rounded-3xl shadow-sm p-12 text-center border border-slate-100">
           <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
             <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
           </div>
           <h3 class="font-semibold text-slate-900">Odaberi plan za prikaz</h3>
           <p class="text-sm text-slate-500 mt-1">Svi tvoji detaljni jelovnici pojavit će se ovdje.</p>
        </div>

        <div v-else-if="activePlan.status === 'generating'" class="bg-white rounded-3xl shadow-sm p-12 text-center border border-slate-100">
          <div class="max-w-md mx-auto">
            <div class="relative w-24 h-24 mx-auto mb-6">
              <svg class="w-full h-full transform -rotate-90">
                <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="8" fill="transparent" class="text-slate-100" />
                <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="8" fill="transparent"
                        class="text-slate-900 transition-all duration-1000"
                        :stroke-dasharray="251.2"
                        :stroke-dashoffset="251.2 - (251.2 * activePlan.progress_percentage / 100)" />
              </svg>
              <div class="absolute inset-0 flex items-center justify-center text-lg font-bold">
                {{ activePlan.progress_percentage }}%
              </div>
            </div>
            <h3 class="font-bold text-xl text-slate-900 mb-2">Generiramo tvoj 4-tjedni plan</h3>
            <p class="text-sm text-slate-500 mb-6">
              Umjetna inteligencija upravo sastavlja tvoje obroke. Ovo može potrajati oko minutu.
              <span v-if="activePlan.current_week_processing" class="block mt-2 font-semibold text-slate-900 italic animate-pulse">
                Trenutno se generira: Tjedan {{ activePlan.current_week_processing }}/4...
              </span>
            </p>
            <div class="flex flex-col items-center gap-4">
              <div class="flex justify-center gap-1">
                <div v-for="i in 4" :key="i" class="w-3 h-3 rounded-full"
                     :class="i <= (activePlan.progress_percentage / 25) ? 'bg-slate-900' : 'bg-slate-100'"></div>
              </div>
              <button @click="cancelPlan(activePlan.id)" 
                      class="text-xs font-semibold text-red-500 hover:text-red-700 transition-colors py-2 px-4 border border-red-100 rounded-full hover:bg-red-50">
                Otkaži generiranje
              </button>
            </div>
          </div>
        </div>

        <div v-else-if="activePlan.status === 'cancelled'" class="bg-white rounded-3xl shadow-sm p-12 text-center border border-slate-100">
           <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
             <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
           </div>
           <h3 class="font-semibold text-slate-900">Generiranje je otkazano</h3>
           <p class="text-sm text-slate-500 mt-1">Ovaj plan prehrane nije dovršen jer ste ga otkazali.</p>
           <button @click="generatePlan" class="mt-4 px-6 py-2 bg-slate-900 text-white rounded-full text-sm font-semibold">Generiraj novi ponovno</button>
        </div>

        <div v-else-if="activePlan.status === 'failed'" class="bg-white rounded-3xl shadow-sm p-12 text-center border border-red-100">
           <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
             <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
           </div>
           <h3 class="font-semibold text-slate-900">Došlo je do greške</h3>
           <p class="text-sm text-slate-500 mt-1">Nismo uspjeli generirati ovaj plan. Molimo pokušaj ponovno.</p>
           <button @click="generatePlan" class="mt-4 px-6 py-2 bg-slate-900 text-white rounded-full text-sm font-semibold">Pokušaj ponovno</button>
        </div>

        <template v-else>
          <!-- Plan Briefing -->
          <div class="bg-slate-900 rounded-3xl p-8 text-white flex flex-wrap items-center justify-between gap-6">
            <div>
              <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Makro ciljevi plana</p>
              <div class="flex items-center gap-6">
                 <div><span class="block text-2xl font-bold">{{ activePlan.daily_calories_target }}</span><span class="text-[10px] text-slate-500 uppercase">Kcal</span></div>
                 <div><span class="block text-2xl font-bold">{{ activePlan.protein_g_target }}g</span><span class="text-[10px] text-slate-500 uppercase">Proteina</span></div>
                 <div><span class="block text-2xl font-bold">{{ activePlan.carbs_g_target }}g</span><span class="text-[10px] text-slate-500 uppercase">Ugljikohidrata</span></div>
                 <div><span class="block text-2xl font-bold">{{ activePlan.fats_g_target }}g</span><span class="text-[10px] text-slate-500 uppercase">Masti</span></div>
              </div>
            </div>
            <div class="flex gap-2">
               <button v-for="w in activePlan.weeks" :key="w.id"
                       @click="selectedWeekId = w.id"
                       class="px-4 py-2 rounded-xl text-xs font-bold transition-all"
                       :class="selectedWeekId === w.id ? 'bg-white text-slate-900' : 'bg-slate-800 text-slate-400 hover:text-white'">
                 Tjedan {{ w.week_number }}
               </button>
            </div>
          </div>

          <!-- Week View -->
          <div v-if="activeWeek" class="space-y-6">
             <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
                <button v-for="d in activeWeek.days" :key="d.id"
                        @click="selectedDayId = d.id"
                        class="flex-shrink-0 w-24 p-3 rounded-2xl border text-center transition-all"
                        :class="selectedDayId === d.id ? 'bg-white border-slate-900 shadow-md' : 'bg-white border-slate-100 text-slate-400 hover:border-slate-300'">
                   <span class="block text-[10px] font-bold uppercase mb-1">{{ getDayName(d.date) }}</span>
                   <span class="block text-lg font-bold text-slate-900">{{ getDayNum(d.date) }}</span>
                </button>
             </div>

             <!-- Day View -->
             <div v-if="activeDay" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="meal in activeDay.meals" :key="meal.id"
                     class="bg-white rounded-3xl p-6 border border-slate-100 hover:shadow-md transition-all">
                   <div class="flex items-center justify-between mb-4">
                      <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-1 bg-slate-100 text-slate-600 rounded-lg">
                        {{ meal.meal_type }}
                      </span>
                      <span class="text-sm font-bold text-slate-900">{{ meal.total_calories }} kcal</span>
                   </div>
                   <div class="space-y-3">
                      <div v-for="item in meal.items" :key="item.id" class="flex justify-between items-center text-sm">
                         <span class="text-slate-700">{{ item.food_name }}</span>
                         <span class="text-slate-400 text-xs">{{ item.quantity || item.quantity_text }}{{ item.unit }}</span>
                      </div>
                   </div>
                   <div class="mt-6 pt-4 border-t border-slate-50 flex justify-between">
                      <div class="flex gap-3 text-[10px] text-slate-400 font-bold uppercase">
                         <span>P: {{ meal.total_protein_g }}g</span>
                         <span>U: {{ meal.total_carbs_g }}g</span>
                         <span>M: {{ meal.total_fats_g }}g</span>
                      </div>
                   </div>
                </div>
             </div>
          </div>
        </template>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch, onUnmounted } from 'vue';
import { api } from '../../bootstrap';

const plans = reactive([]);
const loading = ref(false);
const selectedPlanId = ref(null);
const fullPlanData = ref(null);

const selectedWeekId = ref(null);
const selectedDayId = ref(null);
const pollingInterval = ref(null);

const activePlan = computed(() => fullPlanData.value);

const activeWeek = computed(() => {
  if (!activePlan.value || !selectedWeekId.value) return null;
  return activePlan.value.weeks?.find(w => w.id === selectedWeekId.value);
});

const activeDay = computed(() => {
  if (!activeWeek.value || !selectedDayId.value) return null;
  return activeWeek.value.days?.find(d => d.id === selectedDayId.value);
});

const isGeneratingAny = computed(() => plans.some(p => p.status === 'generating'));

async function fetchPlans() {
  try {
    const { data } = await api.get('/meal-plans');
    plans.splice(0, plans.length, ...(data.meal_plans || []));
    
    const generatingPlan = plans.find(p => p.status === 'generating');
    if (generatingPlan) {
      startPolling(generatingPlan.id);
    }

    if (!selectedPlanId.value && plans.length) {
      selectPlan(plans[0].id);
    }
  } catch (err) {
    console.error('Failed to fetch plans', err);
  }
}

async function selectPlan(id) {
  selectedPlanId.value = id;
  try {
    const { data } = await api.get(`/meal-plans/${id}`);
    fullPlanData.value = data.meal_plan;

    if (fullPlanData.value?.status === 'generating') {
      startPolling(id);
    } else {
      stopPolling();
    }

    // Default to first week and its first day
    if (fullPlanData.value?.weeks?.length) {
       selectedWeekId.value = fullPlanData.value.weeks[0].id;
    }
  } catch (err) {
    console.error('Failed to fetch plan details', err);
  }
}

async function fetchWeekDetails(weekId) {
   if (!selectedPlanId.value || !fullPlanData.value?.weeks) return;
   try {
     const { data } = await api.get(`/meal-plans/${selectedPlanId.value}/weeks/${weekId}`);
     const weekIdx = fullPlanData.value.weeks.findIndex(w => w.id === weekId);
     if (weekIdx !== -1) {
       fullPlanData.value.weeks[weekIdx] = data.week;
       if (data.week.days?.length) {
          selectedDayId.value = data.week.days[0].id;
       }
     }
   } catch (err) {
     console.error('Failed to fetch week details', err);
   }
}

async function fetchDayDetails(dayId) {
   if (!selectedPlanId.value || !fullPlanData.value?.weeks) return;
   try {
     const { data } = await api.get(`/meal-plans/${selectedPlanId.value}/days/${dayId}`);
     for (const week of fullPlanData.value.weeks) {
        const dayIdx = week.days?.findIndex(d => d.id === dayId);
        if (dayIdx !== undefined && dayIdx !== -1) {
           week.days[dayIdx] = data.day;
           break;
        }
     }
   } catch (err) {
     console.error('Failed to fetch day details', err);
   }
}

function startPolling(id) {
  if (pollingInterval.value) return;
  pollingInterval.value = setInterval(async () => {
    try {
      const { data } = await api.get(`/meal-plans/${id}`);
      const updatedPlan = data.meal_plan;
      
      const idx = plans.findIndex(p => p.id === id);
      if (idx !== -1) plans[idx] = updatedPlan;
      
      if (selectedPlanId.value === id) {
        fullPlanData.value = updatedPlan;
      }

      if (updatedPlan.status !== 'generating') {
        stopPolling();
        if (updatedPlan.status === 'generated' && selectedPlanId.value === id) {
          selectPlan(id);
        }
      }
    } catch (err) {
      stopPolling();
    }
  }, 3000);
}

function stopPolling() {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value);
    pollingInterval.value = null;
  }
}

watch(selectedWeekId, (newId) => {
   if (newId) fetchWeekDetails(newId);
});

watch(selectedDayId, (newId) => {
   if (newId) fetchDayDetails(newId);
});

async function generatePlan() {
  loading.value = true;
  try {
    const { data } = await api.post('/meal-plans');
    await fetchPlans();
    if (data.meal_plan) {
      selectPlan(data.meal_plan.id);
      startPolling(data.meal_plan.id);
    }
  } catch (err) {
    console.error('Failed to generate plan', err);
  } finally {
    loading.value = false;
  }
}

async function cancelPlan(id) {
  if (!confirm('Jeste li sigurni da želite otkazati generiranje ovog plana?')) return;
  try {
    const { data } = await api.post(`/meal-plans/${id}/cancel`);
    stopPolling();
    // Update local plan state
    const idx = plans.findIndex(p => p.id === id);
    if (idx !== -1) plans[idx] = data.meal_plan;
    if (selectedPlanId.value === id) fullPlanData.value = data.meal_plan;
  } catch (err) {
    console.error('Failed to cancel plan', err);
  }
}

function getStatusLabel(plan) {
  if (plan.status === 'generated') return 'Spreman';
  if (plan.status === 'failed') return 'Greška';
  if (plan.status === 'cancelled') return 'Otkazano';
  return `U izradi (${plan.progress_percentage ?? 0}%)`;
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('hr-HR', { month: 'short', day: 'numeric' });
}

function getDayName(dateStr) {
  return new Date(dateStr).toLocaleDateString('hr-HR', { weekday: 'short' });
}

function getDayNum(dateStr) {
  return new Date(dateStr).getDate();
}

onMounted(fetchPlans);
onUnmounted(stopPolling);
</script>

