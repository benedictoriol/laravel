<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { applyApiToken } from '@/bootstrap';

const mode = ref('login');
const loading = ref(false);
const error = ref('');

const loginForm = ref({
  email: '',
  password: '',
});

const registerForm = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  role: 'client',
});

const heading = computed(() => mode.value === 'login' ? 'Login' : 'Register');

async function submit() {
  loading.value = true;
  error.value = '';

  try {
    if (mode.value === 'login') {
      const { data } = await window.axios.post('/api/auth/login', loginForm.value);
      window.localStorage.setItem('embro_token', data.token);
      applyApiToken(data.token);
      window.location.href = data.redirect_role === 'owner' ? '/owner-dashboard' : '/client-dashboard';
      return;
    }

    const { data } = await window.axios.post('/api/auth/register', registerForm.value);
    window.localStorage.setItem('embro_token', data.token);
    applyApiToken(data.token);
    window.location.href = data.redirect_role === 'owner' ? '/owner-dashboard' : '/client-dashboard';
  } catch (err) {
    error.value = err?.response?.data?.message || 'Unable to continue. Check the form and try again.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <Head title="Embroidery Platform" />

  <div class="flex min-h-screen items-center justify-center bg-stone-100 px-4 py-8">
    <section class="w-full max-w-md rounded-[2rem] border border-stone-200 bg-white p-6 shadow-xl shadow-stone-200/70 sm:p-8">
      <div class="flex rounded-2xl bg-stone-100 p-1 text-sm font-medium text-stone-600">
        <button class="flex-1 rounded-xl px-4 py-2.5 transition" :class="mode === 'login' ? 'bg-white text-stone-900 shadow-sm' : ''" @click="mode = 'login'">Login</button>
        <button class="flex-1 rounded-xl px-4 py-2.5 transition" :class="mode === 'register' ? 'bg-white text-stone-900 shadow-sm' : ''" @click="mode = 'register'">Register</button>
      </div>

      <div class="mt-6">
        <h1 class="text-2xl font-semibold text-stone-900">{{ heading }}</h1>
      </div>

      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <template v-if="mode === 'login'">
          <div>
            <label class="mb-2 block text-sm font-medium text-stone-700">Email</label>
            <input v-model="loginForm.email" type="email" autocomplete="email" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 outline-none transition focus:border-stone-500" required>
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-stone-700">Password</label>
            <input v-model="loginForm.password" type="password" autocomplete="current-password" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 outline-none transition focus:border-stone-500" required>
          </div>
        </template>

        <template v-else>
          <div>
            <label class="mb-2 block text-sm font-medium text-stone-700">Full name</label>
            <input v-model="registerForm.name" type="text" autocomplete="name" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 outline-none transition focus:border-stone-500" required>
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-stone-700">Email</label>
            <input v-model="registerForm.email" type="email" autocomplete="email" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 outline-none transition focus:border-stone-500" required>
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-2 block text-sm font-medium text-stone-700">Phone</label>
              <input v-model="registerForm.phone" type="text" autocomplete="tel" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 outline-none transition focus:border-stone-500">
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
            <input v-model="registerForm.password" type="password" minlength="8" autocomplete="new-password" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 outline-none transition focus:border-stone-500" required>
          </div>
        </template>

        <div v-if="error" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</div>

        <button type="submit" :disabled="loading" class="w-full rounded-2xl bg-stone-900 px-4 py-3.5 text-sm font-semibold text-white transition hover:bg-stone-800 disabled:cursor-not-allowed disabled:opacity-60">
          {{ loading ? 'Please wait...' : (mode === 'login' ? 'Login' : 'Register') }}
        </button>
      </form>
    </section>
  </div>
</template>
