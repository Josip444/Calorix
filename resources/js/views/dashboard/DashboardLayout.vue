<template>
  <div class="min-h-screen flex flex-col bg-slate-100 font-sans text-slate-900">
    <!-- Premium Dashboard Navbar -->
    <header class="w-full pt-6 px-4">
      <nav class="max-w-6xl mx-auto bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-white px-6 py-2 flex items-center justify-between">
        <!-- Logo -->
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-slate-900 rounded-lg flex items-center justify-center text-white font-bold text-xl">
            C
          </div>
          <span class="text-xl font-bold tracking-tight">Calorix</span>
        </div>
        
        <!-- Navigation Links -->
        <div class="hidden md:flex items-center gap-2">
          <RouterLink
            :to="{ name: 'dashboard' }"
            class="px-5 py-2 rounded-xl text-sm font-bold transition-all"
            :class="isActive('dashboard') ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900'"
          >
            Dashboard
          </RouterLink>
          <RouterLink
            :to="{ name: 'meal-plans' }"
            class="px-5 py-2 rounded-xl text-sm font-bold transition-all"
            :class="isActive('meal-plans') ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900'"
          >
            Plan prehrane
          </RouterLink>
          <RouterLink
            :to="{ name: 'profile' }"
            class="px-5 py-2 rounded-xl text-sm font-bold transition-all"
            :class="isActive('profile') ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:text-slate-900'"
          >
            Profil
          </RouterLink>
        </div>

        <!-- User Info & Actions -->
        <div class="flex items-center gap-6">
          <!-- Notification Bell -->
          <button class="relative text-slate-300 hover:text-slate-900 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
          </button>

          <!-- User Profile -->
          <div class="flex items-center gap-3 pl-6 border-l border-slate-100">
            <div class="text-right hidden sm:block">
              <p class="text-sm font-bold leading-none">{{ auth.user?.name }}</p>
              <p class="text-[11px] font-medium text-slate-400 mt-1">{{ auth.user?.email }}</p>
            </div>
            <div class="w-10 h-10 bg-slate-900 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-lg shadow-slate-900/20">
              {{ getUserInitials() }}
            </div>
            <button @click="onLogout" class="text-slate-400 hover:text-red-500 transition-colors ml-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            </button>
          </div>
        </div>
      </nav>
    </header>

    <main class="flex-1">
      <div class="max-w-6xl mx-auto px-4 py-8">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

function isActive(name) {
  return route.name === name;
}

function getUserInitials() {
  if (!auth.user?.name) return '??';
  return auth.user.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
}

async function onLogout() {
  await auth.logout();
  router.push({ name: 'login' });
}
</script>

