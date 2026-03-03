<template>
  <div class="space-y-8 font-sans text-slate-900 pb-12">
    <!-- Page Header -->
    <header>
      <h1 class="text-3xl font-bold text-slate-900 mb-2">Praćenje napretka</h1>
      <p class="text-slate-500 font-medium">Vidi kako napreduješ prema svom cilju.</p>
    </header>

    <!-- Progress Hero Card (Dark) -->
    <section class="bg-slate-900 rounded-[32px] p-8 text-white shadow-2xl shadow-slate-200">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-8">
        <div class="space-y-1">
          <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Početna težina</p>
          <p class="text-3xl font-bold tracking-tight">{{ user?.start_weight_kg }} <span class="text-lg font-medium text-slate-500">kg</span></p>
        </div>
        <div class="space-y-1">
          <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Trenutna težina</p>
          <p class="text-3xl font-bold tracking-tight">{{ user?.current_weight_kg }} <span class="text-lg font-medium text-slate-500">kg</span></p>
        </div>
        <div class="space-y-1">
          <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Ciljna težina</p>
          <p class="text-3xl font-bold tracking-tight">{{ user?.goal_weight_kg }} <span class="text-lg font-medium text-slate-500">kg</span></p>
        </div>
        <div class="space-y-1">
          <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Napredak</p>
          <p class="text-3xl font-bold tracking-tight">{{ progressPercent }}%</p>
        </div>
      </div>

      <div class="space-y-3">
        <div class="h-3 w-full bg-slate-800 rounded-full overflow-hidden">
          <div 
            class="h-full bg-emerald-400 rounded-full transition-all duration-1000 shadow-[0_0_15px_rgba(52,211,153,0.3)]"
            :style="{ width: progressPercent + '%' }"
          ></div>
        </div>
        <div class="flex justify-between text-xs font-bold uppercase tracking-widest">
          <span class="text-slate-400">{{ diffLabel }}: {{ totalDiff }} kg</span>
          <span class="text-slate-400">Preostalo: {{ remainingToGoal }} kg</span>
        </div>
      </div>
    </section>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left Column: Chart & History -->
      <div class="lg:col-span-2 space-y-8">
        <!-- Weight Chart -->
        <div class="bg-white rounded-[32px] p-8 shadow-xl shadow-slate-200/50 border border-white">
          <div class="flex items-center justify-between mb-8">
            <h2 class="text-xl font-bold">Graf težine</h2>
            <button 
              @click="showWeightModal = true"
              class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold shadow-lg shadow-slate-900/10 hover:bg-slate-800 transition-all flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
              Dodaj težinu
            </button>
          </div>
          <div class="h-80 w-full">
            <Line v-if="chartData" :data="chartData" :options="chartOptions" />
            <div v-else class="h-full flex flex-col items-center justify-center text-slate-400 gap-4">
               <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center">
                  <svg class="w-8 h-8 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
               </div>
               <p class="font-medium">Nema dovoljno podataka za grafikon.</p>
            </div>
          </div>
        </div>

        <!-- Measurement History -->
        <div class="bg-white rounded-[32px] p-8 shadow-xl shadow-slate-200/50 border border-white overflow-hidden">
          <h2 class="text-xl font-bold mb-6">Povijest mjerenja</h2>
          <div class="overflow-x-auto -mx-8 px-8">
            <table class="w-full text-left">
              <thead>
                <tr class="text-xs font-bold uppercase tracking-widest text-slate-400 border-b border-slate-50">
                  <th class="pb-4">Datum</th>
                  <th class="pb-4">Težina</th>
                  <th class="pb-4 text-center">Promjena</th>
                  <th class="pb-4">Napomena</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="(entry, index) in formattedHistory" :key="entry.id" class="group">
                  <td class="py-4 text-sm font-medium text-slate-500">{{ formatDate(entry.date) }}</td>
                  <td class="py-4 text-sm font-bold text-slate-900">{{ entry.weight }} kg</td>
                  <td class="py-4 text-sm font-bold text-center">
                    <span v-if="entry.change !== null" :class="entry.isSuccess ? 'text-emerald-500' : 'text-amber-500'">
                      {{ entry.change > 0 ? '+' : '' }}{{ entry.change }} kg
                    </span>
                    <span v-else class="text-slate-300">—</span>
                  </td>
                  <td class="py-4 text-sm font-medium text-slate-400 italic">
                    {{ entry.note || entry.autoNote }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Right Column: Stats -->
      <div class="space-y-8">
        <!-- Quick Stats -->
        <div class="bg-white rounded-[32px] p-8 shadow-xl shadow-slate-200/50 border border-white">
          <div class="flex items-center gap-3 mb-8">
            <div class="p-2 bg-emerald-50 rounded-lg text-emerald-500">
               <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            </div>
            <h2 class="text-xl font-bold">Brza statistika</h2>
          </div>
          
          <div class="space-y-6">
            <div class="flex justify-between items-center">
              <span class="text-sm font-semibold text-slate-400">Dana aktivnih</span>
              <span class="text-lg font-bold">{{ daysActive }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm font-semibold text-slate-400">Ukupno {{ diffLabel.toLowerCase() }}</span>
              <span class="text-lg font-bold text-emerald-500">{{ totalDiff }} kg</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm font-semibold text-slate-400">Prosječno tjedno</span>
              <span class="text-lg font-bold text-slate-900">{{ avgWeekly }} kg</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Weight Entry Dialog (Overlay) -->
    <div v-if="showWeightModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-[32px] p-8 shadow-2xl w-full max-w-md transform transition-all">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-2xl font-bold text-slate-900">Nova težina</h3>
          <button @click="showWeightModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>
        
        <form @submit.prevent="submitWeight" class="space-y-6">
          <div>
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Unesi težinu (kg)</label>
            <input
              v-model="weightForm.weight"
              type="number"
              step="0.1"
              placeholder="70.5"
              class="w-full rounded-2xl border-2 border-slate-100 bg-slate-50/50 px-4 py-4 text-lg font-bold focus:outline-none focus:border-slate-900 focus:bg-white transition-all text-center"
              required
            />
          </div>
          <div>
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Datum mjerenja</label>
            <input
              v-model="weightForm.date"
              type="date"
              class="w-full rounded-2xl border-2 border-slate-100 bg-slate-50/50 px-4 py-4 font-bold focus:outline-none focus:border-slate-900 focus:bg-white transition-all"
              required
            />
          </div>
          <button
            type="submit"
            class="w-full py-4 bg-slate-900 text-white rounded-2xl text-lg font-bold shadow-xl shadow-slate-900/10 hover:bg-slate-800 transition-all active:scale-95 disabled:opacity-50"
            :disabled="submitting"
          >
            {{ submitting ? 'Spremanje...' : 'Spremi mjerenje' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { api } from '../../bootstrap';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const auth = useAuthStore();
const user = computed(() => auth.user);

const weightHistory = ref([]);
const weightForm = reactive({
  weight: '',
  date: new Date().toISOString().split('T')[0]
});
const submitting = ref(false);
const showWeightModal = ref(false);

// Formatting history with change calculation
const formattedHistory = computed(() => {
   const isGainGoal = user.value?.goal_type === 'gain' || user.value?.goal_type === 'build';
   
   return [...weightHistory.value].reverse().map((h, i, arr) => {
      const prev = arr[i + 1];
      const change = prev ? Number((h.weight - prev.weight).toFixed(1)) : null;
      
      let isSuccess = false;
      let autoNote = '—';

      if (change !== null) {
         if (isGainGoal) {
            isSuccess = change >= 0;
            if (change >= 0.3) autoNote = 'Odličan napredak';
         } else {
            isSuccess = change <= 0;
            if (change <= -0.3) autoNote = 'Odličan napredak';
         }
      }

      return { ...h, change, isSuccess, autoNote };
   });
});

// Progress Calculations
const totalDiff = computed(() => {
  if (!user.value) return 0;
  const start = Number(user.value.start_weight_kg) || 0;
  const current = Number(user.value.current_weight_kg) || 0;
  
  if (user.value.goal_type === 'gain' || user.value.goal_type === 'build') {
    return Number((current - start).toFixed(1));
  }
  return Number((start - current).toFixed(1));
});

const diffLabel = computed(() => {
  if (user.value?.goal_type === 'gain' || user.value?.goal_type === 'build') {
    return 'Dobiveno';
  }
  return 'Izgubljeno';
});

const remainingToGoal = computed(() => {
  if (!user.value) return 0;
  const current = Number(user.value.current_weight_kg) || 0;
  const goal = Number(user.value.goal_weight_kg) || 0;
  
  if (user.value.goal_type === 'gain' || user.value.goal_type === 'build') {
    return Math.max(0, Number((goal - current).toFixed(1)));
  }
  return Math.max(0, Number((current - goal).toFixed(1)));
});

const progressPercent = computed(() => {
  if (!user.value) return 0;
  const start = Number(user.value.start_weight_kg) || 0;
  const goal = Number(user.value.goal_weight_kg) || 0;
  const current = Number(user.value.current_weight_kg) || 0;

  if (user.value.goal_type === 'maintain') return 100;

  let totalTask = 0;
  let accomplished = 0;

  if (user.value.goal_type === 'gain' || user.value.goal_type === 'build') {
     totalTask = goal - start;
     accomplished = current - start;
  } else {
     totalTask = start - goal;
     accomplished = start - current;
  }

  if (totalTask <= 0) return 0;
  const percent = (accomplished / totalTask) * 100;
  return Math.min(100, Math.max(0, Math.round(percent)));
});

// Dynamic Statistics
const daysActive = computed(() => {
  if (!user.value?.created_at) return 1;
  const start = new Date(user.value.created_at);
  const now = new Date();
  const diffTime = Math.abs(now - start);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays || 1;
});

const avgWeekly = computed(() => {
  const weeks = daysActive.value / 7;
  if (weeks <= 0) return '0.00';
  return (totalDiff.value / weeks).toFixed(2);
});

const chartData = computed(() => {
  if (weightHistory.value.length === 0) return null;

  return {
    labels: weightHistory.value.map(h => formatDateShort(h.date)),
    datasets: [
      {
        label: 'Težina (kg)',
        backgroundColor: 'rgba(52, 211, 153, 0.05)',
        borderColor: '#0f172a',
        borderWidth: 3,
        pointBackgroundColor: '#fff',
        pointBorderColor: '#0f172a',
        pointBorderWidth: 2,
        pointRadius: 6,
        pointHoverRadius: 8,
        fill: true,
        tension: 0.4,
        data: weightHistory.value.map(h => h.weight)
      }
    ]
  };
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      mode: 'index',
      intersect: false,
      padding: 12,
      backgroundColor: '#0f172a',
      cornerRadius: 12,
      titleFont: { size: 14, weight: 'bold' },
      bodyFont: { size: 13 },
      displayColors: false
    }
  },
  scales: {
    x: {
      grid: { display: false },
      border: { display: false },
      ticks: { color: '#94a3b8', font: { size: 11, weight: 'bold' } }
    },
    y: {
      beginAtZero: false,
      grid: { color: '#f8fafc' },
      border: { display: false },
      ticks: { color: '#94a3b8', font: { size: 11, weight: 'bold' } }
    }
  }
};

function formatDate(dateStr) {
  const d = new Date(dateStr);
  return d.toLocaleDateString('hr-HR', { day: '2-digit', month: '2-digit', year: 'numeric' }) + '.';
}

function formatDateShort(dateStr) {
  const d = new Date(dateStr);
  return d.toLocaleDateString('hr-HR', { day: 'numeric', month: 'short' });
}

async function fetchHistory() {
  try {
    const { data } = await api.get('/weight-history');
    weightHistory.value = data.weight_history;
  } catch (err) {
    console.error('Failed to fetch weight history', err);
  }
}

async function submitWeight() {
  submitting.value = true;
  try {
    const { data } = await api.post('/weight-entry', {
       weight: weightForm.weight,
       date: weightForm.date
    });
    auth.user = data.user;
    weightForm.weight = '';
    showWeightModal.value = false;
    await fetchHistory();
  } catch (err) {
    console.error('Failed to save weight', err);
  } finally {
    submitting.value = false;
  }
}

onMounted(fetchHistory);
</script>

<style scoped>
/* Custom Table Shadows & Rounding */
table {
  border-collapse: separate;
  border-spacing: 0;
}

input[type="date"]::-webkit-calendar-picker-indicator {
  cursor: pointer;
  filter: invert(0.1);
  padding: 5px;
}
</style>


