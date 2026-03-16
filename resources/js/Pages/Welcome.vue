<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { applyApiToken } from '@/bootstrap';

const mode = ref('login');
const loading = ref(false);
const error = ref('');
const success = ref('');

const loginForm = ref({
  email: 'client1@example.com',
  password: 'password',
});

const registerForm = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  role: 'client',
});

const demoAccounts = [
  { label: 'Admin', email: 'admin@embroidery.com', password: 'password' },
  { label: 'Owner', email: 'owner2@example.com', password: 'password' },
  { label: 'HR', email: 'hr1@example.com', password: 'password' },
  { label: 'Staff', email: 'staff1@example.com', password: 'password' },
  { label: 'Client', email: 'client1@example.com', password: 'password' },
];

const heading = computed(() => mode.value === 'login' ? 'Sign in to your embroidery workspace' : 'Create a client or owner account');

function useDemo(account) {
  loginForm.value = { email: account.email, password: account.password };
  mode.value = 'login';
}

async function submit() {
  loading.value = true;
  error.value = '';
  success.value = '';
  try {
    if (mode.value === 'login') {
      const { data } = await window.axios.post('/api/auth/login', loginForm.value);
      window.localStorage.setItem('embro_token', data.token);
      applyApiToken(data.token);
      window.location.href = data.redirect_role === 'owner' ? '/owner-dashboard' : '/dashboard';
      return;
    }

    const { data } = await window.axios.post('/api/auth/register', registerForm.value);
    window.localStorage.setItem('embro_token', data.token);
    applyApiToken(data.token);
    window.location.href = data.redirect_role === 'owner' ? '/owner-dashboard' : '/dashboard';
  } catch (err) {
    error.value = err?.response?.data?.message || 'Unable to continue. Check the form and try again.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <Head title="Embroidery Platform" />

  <div class="min-h-screen bg-stone-50 text-stone-900">
    <div class="mx-auto grid min-h-screen max-w-7xl gap-10 px-6 py-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
      <section class="space-y-8">
        <div class="inline-flex items-center rounded-full border border-stone-300 bg-white px-4 py-1.5 text-sm font-medium text-stone-600 shadow-sm">
          Embroidery operations platform
        </div>

        <div class="space-y-4">
          <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-stone-900 md:text-6xl">
            A cleaner way to run orders, production, revisions, and fulfillment.
          </h1>
          <p class="max-w-2xl text-lg leading-8 text-stone-600">
            Sign in to a focused workspace built for embroidery operations. The frontend is wired to your live Laravel backend and prioritizes clear actions over clutter.
          </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
          <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="text-sm font-semibold text-stone-900">Client workflow</div>
            <p class="mt-2 text-sm leading-6 text-stone-600">Create orders, pay, request revisions, and track fulfillment from one place.</p>
          </div>
          <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="text-sm font-semibold text-stone-900">Shop operations</div>
            <p class="mt-2 text-sm leading-6 text-stone-600">Manage assignments, revisions, fulfillment actions, and order exceptions with role-based access.</p>
          </div>
          <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="text-sm font-semibold text-stone-900">Operational insight</div>
            <p class="mt-2 text-sm leading-6 text-stone-600">View risk, recommendations, and shop metrics without leaving the dashboard.</p>
          </div>
        </div>

        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
          <div class="flex items-center justify-between gap-4">
            <div>
              <h2 class="text-base font-semibold text-stone-900">Quick role access</h2>
              <p class="mt-1 text-sm text-stone-500">Use a demo account to enter the correct workspace fast.</p>
            </div>
            <div class="hidden rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-500 sm:block">Function-first UI</div>
          </div>
          <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <button
              v-for="account in demoAccounts"
              :key="account.label"
              type="button"
              class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-4 text-left transition hover:border-stone-400 hover:bg-white"
              @click="useDemo(account)"
            >
              <div class="text-sm font-semibold text-stone-900">{{ account.label }}</div>
              <div class="mt-1 text-xs text-stone-500">{{ account.email }}</div>
            </button>
          </div>
        </div>
      </section>

      <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-xl shadow-stone-200/60 sm:p-8">
        <div class="flex rounded-2xl bg-stone-100 p-1 text-sm font-medium text-stone-600">
          <button class="flex-1 rounded-xl px-4 py-2.5 transition" :class="mode === 'login' ? 'bg-white text-stone-900 shadow-sm' : ''" @click="mode = 'login'">Login</button>
          <button class="flex-1 rounded-xl px-4 py-2.5 transition" :class="mode === 'register' ? 'bg-white text-stone-900 shadow-sm' : ''" @click="mode = 'register'">Register</button>
        </div>

        <div class="mt-6 space-y-2">
          <h2 class="text-2xl font-semibold text-stone-900">{{ heading }}</h2>
          <p class="text-sm leading-6 text-stone-500">Secure token-based access for admin, owner, HR, staff, and client roles.</p>
        </div>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
          <template v-if="mode === 'login'">
            <div>
              <label class="mb-2 block text-sm font-medium text-stone-700">Email</label>
              <input v-model="loginForm.email" type="email" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 outline-none transition focus:border-stone-500" required>
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-stone-700">Password</label>
              <input v-model="loginForm.password" type="password" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 outline-none transition focus:border-stone-500" required>
            </div>
          </template>

          <template v-else>
            <div>
              <label class="mb-2 block text-sm font-medium text-stone-700">Full name</label>
              <input v-model="registerForm.name" type="text" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 outline-none transition focus:border-stone-500" required>
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-stone-700">Email</label>
              <input v-model="registerForm.email" type="email" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 outline-none transition focus:border-stone-500" required>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-2 block text-sm font-medium text-stone-700">Phone</label>
                <input v-model="registerForm.phone" type="text" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 outline-none transition focus:border-stone-500">
              </div>
              <div>
                <label class="mb-2 block text-sm font-medium text-stone-700">Role</label>
                <select v-model="registerForm.role" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 outline-none transition focus:border-stone-500">
                  <option value="client">Client</option>
                  <option value="owner">Owner</option>
                </select>
              </div>
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-stone-700">Password</label>
              <input v-model="registerForm.password" type="password" minlength="8" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 outline-none transition focus:border-stone-500" required>
            </div>
          </template>

          <div v-if="error" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</div>
          <div v-if="success" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ success }}</div>

          <button type="submit" :disabled="loading" class="w-full rounded-2xl bg-stone-900 px-4 py-3.5 text-sm font-semibold text-white transition hover:bg-stone-800 disabled:cursor-not-allowed disabled:opacity-60">
            {{ loading ? 'Please wait...' : (mode === 'login' ? 'Enter workspace' : 'Create account') }}
          </button>
        </form>
      </section>
    </div>
  </div>
</template>
