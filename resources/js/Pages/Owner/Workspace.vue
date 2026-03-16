<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import { applyApiToken } from '@/bootstrap';
import AppWorkspaceLayout from '@/Layouts/AppWorkspaceLayout.vue';
import WorkspaceSidebar from '@/Components/Workspace/WorkspaceSidebar.vue';
import WorkspaceHeader from '@/Components/Workspace/WorkspaceHeader.vue';
import WorkspaceRightSidebar from '@/Components/Workspace/WorkspaceRightSidebar.vue';
import SectionCard from '@/Components/Workspace/SectionCard.vue';
import StatCard from '@/Components/Workspace/StatCard.vue';

const token = ref(window.localStorage.getItem('embro_token') || '');
const loading = ref(true);
const saving = ref(false);
const user = ref(null);
const data = ref(null);
const active = ref('overview');
const flash = reactive({ type: 'info', text: '' });

const serviceForm = reactive({ service_name: '', category: '', base_price: 0, unit_price: 0, min_order_qty: 1, stitch_range: '', complexity_multiplier: 1, rush_fee_allowed: true });
const supplierForm = reactive({ name: '', contact_person: '', phone: '', email: '', address: '', materials_supplied: '', lead_time_days: 7, status: 'active' });
const materialForm = reactive({ material_name: '', category: '', color: '', unit: 'pcs', stock_quantity: 0, reorder_level: 0, cost_per_unit: 0, supplier_id: '' });
const scheduleForm = reactive({ user_id: '', shift_date: '', shift_start: '09:00', shift_end: '18:00', assignment_notes: '', is_day_off: false, is_overtime: false });
const disputeForm = reactive({ order_id: '', complainant_user_id: '', assigned_handler_user_id: '', dispute_type: 'wrong_design', issue_summary: '' });
const threadForm = reactive({ title: '', type: 'group', participant_user_ids_json: [] });
const threadMessage = reactive({});
const settingsForm = reactive({});

const navItems = computed(() => [
  { key: 'overview', label: 'Overview', badge: data.value?.overview?.stats?.pending_orders || null },
  { key: 'orders', label: 'Orders', badge: data.value?.orders?.length || null },
  { key: 'proofing', label: 'Design Proofing & Quote', badge: data.value?.design_proofing?.length || null },
  { key: 'pricing', label: 'Pricing', badge: data.value?.pricing?.length || null },
  { key: 'payments', label: 'Payments', badge: data.value?.payments?.length || null },
  { key: 'earnings', label: 'Earnings' },
  { key: 'production', label: 'Production Tracking' },
  { key: 'qc', label: 'Quality Control', badge: data.value?.quality_control?.length || null },
  { key: 'projects', label: 'Projects', badge: data.value?.projects?.length || null },
  { key: 'suppliers', label: 'Supplier Management', badge: data.value?.supplier_management?.length || null },
  { key: 'materials', label: 'Raw Materials', badge: data.value?.raw_materials?.length || null },
  { key: 'supply', label: 'Supply Chain', badge: data.value?.supply_chain?.length || null },
  { key: 'staff', label: 'Staff', badge: data.value?.staff?.length || null },
  { key: 'operations', label: 'Operations' },
  { key: 'schedule', label: 'Workforce Scheduling', badge: data.value?.workforce_scheduling?.length || null },
  { key: 'delivery', label: 'Delivery & Pickup' },
  { key: 'disputes', label: 'Dispute Resolution', badge: data.value?.dispute_resolution?.length || null },
  { key: 'messages', label: 'Messages', badge: data.value?.messages?.length || null },
  { key: 'marketplace', label: 'Marketplace' },
  { key: 'analytics', label: 'Analytics' },
  { key: 'preferences', label: 'Preferences' },
]);

const pageTitle = computed(() => navItems.value.find((i) => i.key === active.value)?.label || 'Owner Workspace');
const pageSubtitle = computed(() => ({
  overview: 'Owner-first control tower for orders, earnings, alerts, and low-stock visibility.',
  proofing: 'Client design requests, proofing workflow, and automated quote suggestions.',
  pricing: 'Service price list and pricing defaults powering the shop quotation flow.',
  preferences: 'Shop information, workflow automation, documents, security, and approvals.',
}[active.value] || 'Production-oriented owner workspace tied to live API data.'));

const overviewStats = computed(() => data.value?.overview?.stats || {});
const ordersByTab = computed(() => {
  const all = data.value?.orders || [];
  return {
    all,
    pending: all.filter((o) => ['pending', 'quoted', 'awaiting_payment', 'payment_pending'].includes(o.status)),
    accepted: all.filter((o) => ['accepted'].includes(o.status)),
    progress: all.filter((o) => ['in_progress', 'production', 'ready_for_qc', 'ready_for_delivery'].includes(o.status)),
    completed: all.filter((o) => o.status === 'completed'),
    cancelled: all.filter((o) => o.status === 'cancelled'),
  };
});
const orderTab = ref('all');
const selectedThreadId = ref(null);
const selectedThread = computed(() => (data.value?.messages || []).find((t) => t.id === selectedThreadId.value) || data.value?.messages?.[0] || null);

function setFlash(text, type = 'info') { flash.text = text; flash.type = type; }
function money(v) { return Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
function barWidth(item, source) { const max = Math.max(...(source || []).map((row) => Number(row.value || 0)), 1); return `${Math.max((Number(item.value || 0) / max) * 100, 6)}%`; }
function getError(err) { return err?.response?.data?.message || Object.values(err?.response?.data?.errors || {})?.[0]?.[0] || 'Request failed.'; }
async function api(method, url, payload) { applyApiToken(token.value); return window.axios({ method, url, data: payload }); }

async function load() {
  try {
    if (!token.value) { window.location.href = '/'; return; }
    applyApiToken(token.value);
    const me = await api('get', '/api/auth/me');
    user.value = me.data;
    if (user.value?.role !== 'owner') { window.location.href = '/dashboard'; return; }
    const workspace = await api('get', '/api/owner/workspace');
    data.value = workspace.data;
    selectedThreadId.value = data.value?.messages?.[0]?.id || null;
    Object.assign(settingsForm, data.value?.settings || {});
    if (!serviceForm.category) serviceForm.category = 'logo_embroidery';
    if (!disputeForm.order_id) disputeForm.order_id = data.value?.orders?.[0]?.id || '';
    if (!scheduleForm.user_id) scheduleForm.user_id = data.value?.staff?.[0]?.id || '';
  } catch (err) {
    setFlash(getError(err), 'error');
  } finally {
    loading.value = false;
  }
}

async function submit(url, payload, successText) {
  saving.value = true;
  try {
    await api('post', url, payload);
    setFlash(successText, 'success');
    await load();
  } catch (err) {
    setFlash(getError(err), 'error');
  } finally {
    saving.value = false;
  }
}

async function saveSettings() {
  saving.value = true;
  try {
    await api('put', '/api/owner/settings', settingsForm);
    setFlash('Owner preferences updated.', 'success');
    await load();
  } catch (err) {
    setFlash(getError(err), 'error');
  } finally {
    saving.value = false;
  }
}

async function createService() { await submit('/api/shop-services', { ...serviceForm, shop_id: user.value.shop_id }, 'Pricing row added.'); }
async function createSupplier() { await submit('/api/owner/suppliers', supplierForm, 'Supplier saved.'); }
async function createMaterial() { await submit('/api/owner/raw-materials', { ...materialForm, supplier_id: materialForm.supplier_id || null }, 'Material saved.'); }
async function createSchedule() { await submit('/api/owner/workforce-schedules', scheduleForm, 'Schedule added.'); }
async function createDispute() { await submit('/api/owner/disputes', { ...disputeForm, complainant_user_id: disputeForm.complainant_user_id || null, assigned_handler_user_id: disputeForm.assigned_handler_user_id || null }, 'Dispute case opened.'); }
async function createThread() { await submit('/api/owner/threads', threadForm, 'Conversation created.'); }
async function sendThreadMessage(thread) { await submit(`/api/owner/threads/${thread.id}/messages`, { message: threadMessage[thread.id] }, 'Message sent.'); threadMessage[thread.id] = ''; }
async function logout() { try { await api('post', '/api/auth/logout'); } catch {} window.localStorage.removeItem('embro_token'); window.location.href = '/'; }

onMounted(load);
</script>

<template>
  <Head title="Owner Workspace" />
  <AppWorkspaceLayout>
    <template #sidebar>
      <WorkspaceSidebar :items="navItems" :active-key="active" :user="user" @change="active = $event" />
    </template>

    <template #header>
      <WorkspaceHeader eyebrow="Owner side" :title="pageTitle" :subtitle="pageSubtitle" :user="user" @logout="logout" />
    </template>

    <template #right>
      <WorkspaceRightSidebar :notifications="data?.overview?.alerts || []" :selected-order="data?.orders?.[0] || null" :assignments="[]" :revisions="data?.quality_control || []" />
    </template>

    <div v-if="loading" class="rounded-3xl border border-stone-200 bg-white p-10 text-sm text-stone-500 shadow-sm">Loading owner workspace…</div>
    <div v-else class="space-y-4">
      <div v-if="flash.text" class="rounded-2xl border px-4 py-3 text-sm" :class="flash.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">{{ flash.text }}</div>

      <template v-if="active === 'overview'">
        <SectionCard title="Overview dashboard" description="Recent orders, business signals, and operational alerts in one view.">
          <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <StatCard label="Total orders" :value="overviewStats.total_orders || 0" hint="All orders for this shop" />
            <StatCard label="Pending orders" :value="overviewStats.pending_orders || 0" hint="Waiting on quote, payment, or review" />
            <StatCard label="Active orders" :value="overviewStats.active_orders || 0" hint="Currently in live execution" />
            <StatCard label="Completed orders" :value="overviewStats.completed_orders || 0" hint="Finished work" />
            <StatCard label="Total earnings" :value="`₱ ${money(overviewStats.total_earnings)}`" hint="Confirmed payment total" />
            <StatCard label="Low stock materials" :value="overviewStats.low_stock_materials || 0" hint="Below reorder threshold" />
          </div>
        </SectionCard>

        <div class="grid gap-4 xl:grid-cols-[1.4fr_1fr]">
          <SectionCard title="Recent orders" description="Short owner-level snapshot.">
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="text-left text-stone-500"><tr><th class="pb-3">Order</th><th class="pb-3">Client</th><th class="pb-3">Service</th><th class="pb-3">Status</th><th class="pb-3">Date</th><th class="pb-3 text-right">Amount</th></tr></thead>
                <tbody>
                  <tr v-for="order in data?.overview?.recent_orders || []" :key="order.id" class="border-t border-stone-100">
                    <td class="py-3 font-medium text-stone-900">{{ order.order_number }}</td>
                    <td class="py-3">{{ order.client?.name || '—' }}</td>
                    <td class="py-3">{{ order.service?.service_name || order.order_type || 'Custom' }}</td>
                    <td class="py-3 capitalize">{{ order.status }}</td>
                    <td class="py-3">{{ order.created_at ? new Date(order.created_at).toLocaleDateString() : '—' }}</td>
                    <td class="py-3 text-right">₱ {{ money(order.total_amount) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </SectionCard>

          <SectionCard title="Owner alerts" description="High-signal alerts and low-stock watchlist.">
            <div class="space-y-3">
              <div v-for="alert in data?.overview?.alerts || []" :key="alert.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-center justify-between gap-3"><div class="font-semibold text-stone-900">{{ alert.title }}</div><div class="text-xs uppercase tracking-[0.2em] text-stone-500">{{ alert.severity }}</div></div>
                <div class="mt-2 text-sm text-stone-600">{{ alert.message }}</div>
              </div>
              <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="text-sm font-semibold text-stone-900">Low stock watch</div>
                <div class="mt-3 space-y-2 text-sm text-stone-600">
                  <div v-for="item in data?.overview?.low_stock_items || []" :key="item.id" class="flex items-center justify-between gap-3"><span>{{ item.material_name }}</span><span>{{ item.stock_quantity }} {{ item.unit }}</span></div>
                </div>
              </div>
            </div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'orders'">
        <SectionCard title="Orders" description="Owner order management with status tabs.">
          <div class="flex flex-wrap gap-2">
            <button v-for="key in ['all','pending','accepted','progress','completed','cancelled']" :key="key" class="rounded-2xl px-4 py-2 text-sm" :class="orderTab === key ? 'bg-stone-900 text-white' : 'border border-stone-300 bg-white text-stone-700'" @click="orderTab = key">{{ ({all:'All',pending:'Pending',accepted:'Accepted',progress:'In Progress',completed:'Completed',cancelled:'Cancelled'})[key] }}</button>
          </div>
          <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="text-left text-stone-500"><tr><th class="pb-3">Order</th><th class="pb-3">Client</th><th class="pb-3">Type</th><th class="pb-3">Qty</th><th class="pb-3">Payment</th><th class="pb-3">Production</th><th class="pb-3">Due</th><th class="pb-3 text-right">Total</th></tr></thead>
              <tbody>
                <tr v-for="order in ordersByTab[orderTab]" :key="order.id" class="border-t border-stone-100">
                  <td class="py-3 font-medium">{{ order.order_number }}</td><td class="py-3">{{ order.client?.name || '—' }}</td><td class="py-3">{{ order.service?.service_name || order.order_type }}</td><td class="py-3">{{ order.items?.[0]?.quantity || '—' }}</td><td class="py-3 capitalize">{{ order.payment_status }}</td><td class="py-3 capitalize">{{ order.current_stage || order.status }}</td><td class="py-3">{{ order.due_date ? new Date(order.due_date).toLocaleDateString() : '—' }}</td><td class="py-3 text-right">₱ {{ money(order.total_amount) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'proofing'">
        <SectionCard title="Design proofing & price quote" description="Client proofing requests with automated suggested quotation.">
          <div class="space-y-4">
            <div v-for="item in data?.design_proofing || []" :key="item.id" class="rounded-3xl border border-stone-200 p-5">
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                  <div class="text-lg font-semibold text-stone-900">{{ item.name || 'Customization request' }}</div>
                  <div class="mt-1 text-sm text-stone-500">{{ item.user?.name || 'Client' }} · {{ item.garment_type }} · {{ item.placement_area }}</div>
                  <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4 text-sm text-stone-600">
                    <div>Colors: <span class="font-medium text-stone-900">{{ item.color_count }}</span></div>
                    <div>Complexity: <span class="font-medium text-stone-900">{{ item.complexity_level }}</span></div>
                    <div>Stitches: <span class="font-medium text-stone-900">{{ item.stitch_count_estimate || item.pricing_breakdown_preview?.stitch_count_estimate || '—' }}</span></div>
                    <div>Status: <span class="font-medium capitalize text-stone-900">{{ item.status }}</span></div>
                  </div>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm min-w-[220px]">
                  <div class="text-xs uppercase tracking-[0.2em] text-stone-500">Suggested quote</div>
                  <div class="mt-2 text-2xl font-semibold text-stone-900">₱ {{ money(item.suggested_quote) }}</div>
                  <div class="mt-3 space-y-1 text-stone-600">
                    <div>Base: ₱ {{ money(item.pricing_breakdown_preview?.base_unit_price) }}</div>
                    <div>Rush fee: ₱ {{ money(item.pricing_breakdown_preview?.rush_fee) }}</div>
                    <div>Material fee: ₱ {{ money(item.pricing_breakdown_preview?.material_fee) }}</div>
                    <div>Confidence: {{ item.pricing_breakdown_preview?.confidence_score || item.pricing_confidence_score || 0 }}%</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'pricing'">
        <div class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
          <SectionCard title="Main service price list" description="Logo, name, patch, uniform, cap, and custom design embroidery pricing.">
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="text-left text-stone-500"><tr><th class="pb-3">Service Name</th><th class="pb-3">Base Price</th><th class="pb-3">Minimum Qty</th><th class="pb-3">Unit Price</th><th class="pb-3">Stitch Range</th></tr></thead>
                <tbody>
                  <tr v-for="service in data?.pricing || []" :key="service.id" class="border-t border-stone-100">
                    <td class="py-3 font-medium">{{ service.service_name }}</td><td class="py-3">₱ {{ money(service.base_price) }}</td><td class="py-3">{{ service.min_order_qty }}</td><td class="py-3">₱ {{ money(service.unit_price) }}</td><td class="py-3">{{ service.stitch_range || '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </SectionCard>
          <SectionCard title="Add pricing row" description="Database-backed pricing rule for the owner’s shop.">
            <div class="grid gap-3">
              <input v-model="serviceForm.service_name" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Service Name">
              <select v-model="serviceForm.category" class="rounded-2xl border border-stone-300 px-4 py-3">
                <option value="logo_embroidery">Logo embroidery</option><option value="name_embroidery">Name embroidery</option><option value="patch_embroidery">Patch embroidery</option><option value="uniform_embroidery">Uniform embroidery</option><option value="cap_embroidery">Cap embroidery</option><option value="custom_design_embroidery">Custom design embroidery</option>
              </select>
              <div class="grid gap-3 sm:grid-cols-3"><input v-model="serviceForm.base_price" type="number" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Base Price"><input v-model="serviceForm.min_order_qty" type="number" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Min Qty"><input v-model="serviceForm.unit_price" type="number" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Unit Price"></div>
              <div class="grid gap-3 sm:grid-cols-2"><input v-model="serviceForm.stitch_range" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Stitch Range"><input v-model="serviceForm.complexity_multiplier" type="number" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Complexity Multiplier"></div>
              <button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="createService">Save pricing rule</button>
            </div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'payments'">
        <SectionCard title="Payments" description="Recorded payments that went through for the shop.">
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="text-left text-stone-500"><tr><th class="pb-3">Payment</th><th class="pb-3">Order</th><th class="pb-3">Client</th><th class="pb-3">Method</th><th class="pb-3">Status</th><th class="pb-3">Paid Date</th><th class="pb-3 text-right">Amount</th></tr></thead>
              <tbody><tr v-for="payment in data?.payments || []" :key="payment.id" class="border-t border-stone-100"><td class="py-3 font-medium">#{{ payment.id }}</td><td class="py-3">{{ payment.order?.order_number || payment.order_id }}</td><td class="py-3">{{ payment.client?.name || '—' }}</td><td class="py-3">{{ payment.payment_type }}</td><td class="py-3 capitalize">{{ payment.payment_status }}</td><td class="py-3">{{ payment.paid_at ? new Date(payment.paid_at).toLocaleString() : '—' }}</td><td class="py-3 text-right">₱ {{ money(payment.amount) }}</td></tr></tbody>
            </table>
          </div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'earnings'">
        <div class="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
          <SectionCard title="Revenue summary" description="Shop earnings snapshot.">
            <div class="grid gap-4 md:grid-cols-2">
              <StatCard label="Today" :value="`₱ ${money(data?.earnings?.summary?.today)}`" />
              <StatCard label="This week" :value="`₱ ${money(data?.earnings?.summary?.week)}`" />
              <StatCard label="This month" :value="`₱ ${money(data?.earnings?.summary?.month)}`" />
              <StatCard label="This year" :value="`₱ ${money(data?.earnings?.summary?.year)}`" />
              <StatCard label="Average order value" :value="`₱ ${money(data?.earnings?.summary?.average_order_value)}`" />
              <StatCard label="Pending receivables" :value="`₱ ${money(data?.earnings?.summary?.pending_receivables)}`" />
            </div>
          </SectionCard>
          <SectionCard title="Monthly revenue" description="Simple visual trend using live payment totals.">
            <div class="space-y-3">
              <div v-for="point in data?.earnings?.monthly_revenue || []" :key="point.label">
                <div class="mb-1 flex items-center justify-between text-sm"><span>{{ point.label }}</span><span>₱ {{ money(point.value) }}</span></div>
                <div class="h-3 rounded-full bg-stone-100"><div class="h-3 rounded-full bg-stone-900" :style="{ width: barWidth(point, data?.earnings?.monthly_revenue) }"></div></div>
              </div>
            </div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'production'">
        <SectionCard title="Production tracking" description="Live owner view of production updates.">
          <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-stone-500"><tr><th class="pb-3">Order</th><th class="pb-3">Client</th><th class="pb-3">Assigned Staff</th><th class="pb-3">Stage</th><th class="pb-3">Priority</th><th class="pb-3">Due</th><th class="pb-3">Last Update</th></tr></thead><tbody><tr v-for="row in data?.production_tracking || []" :key="row.order_id" class="border-t border-stone-100"><td class="py-3 font-medium">{{ row.order_number }}</td><td class="py-3">{{ row.client }}</td><td class="py-3">{{ row.assigned_staff || 'Unassigned' }}</td><td class="py-3 capitalize">{{ row.stage || row.status }}</td><td class="py-3 capitalize">{{ row.priority }}</td><td class="py-3">{{ row.due_date ? new Date(row.due_date).toLocaleDateString() : '—' }}</td><td class="py-3">{{ row.last_update ? new Date(row.last_update).toLocaleString() : '—' }}</td></tr></tbody></table></div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'qc'">
        <SectionCard title="Quality control" description="QC records and rework visibility.">
          <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3"><div v-for="qc in data?.quality_control || []" :key="qc.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4"><div class="flex items-center justify-between"><div class="font-semibold">{{ qc.order?.order_number || `Order #${qc.order_id}` }}</div><div class="text-xs uppercase tracking-[0.2em]">{{ qc.result }}</div></div><div class="mt-2 text-sm text-stone-600">{{ qc.issue_notes || 'No issue notes.' }}</div><div class="mt-3 text-xs text-stone-500">Checked by {{ qc.checker?.name || '—' }} · Rework {{ qc.rework_required ? 'required' : 'not required' }}</div></div></div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'projects'">
        <SectionCard title="Projects" description="Shop-posted projects available in the marketplace.">
          <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3"><div v-for="project in data?.projects || []" :key="project.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4"><div class="font-semibold text-stone-900">{{ project.title }}</div><div class="mt-2 text-sm text-stone-600">{{ project.description }}</div><div class="mt-3 text-xs text-stone-500">Base ₱ {{ money(project.base_price) }} · MOQ {{ project.min_order_qty }}</div></div></div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'suppliers'">
        <div class="grid gap-4 xl:grid-cols-[1fr_0.9fr]">
          <SectionCard title="Supplier management" description="Add supplier information and track all supplier records.">
            <div class="space-y-3"><div v-for="supplier in data?.supplier_management || []" :key="supplier.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4"><div class="font-semibold">{{ supplier.name }}</div><div class="mt-1 text-sm text-stone-600">{{ supplier.contact_person || 'No contact person' }} · {{ supplier.phone || 'No phone' }}</div><div class="mt-2 text-xs text-stone-500">{{ supplier.materials_supplied || 'No listed materials' }}</div></div></div>
          </SectionCard>
          <SectionCard title="Add supplier" description="Create supplier information for owner purchasing workflows.">
            <div class="grid gap-3"><input v-model="supplierForm.name" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Supplier name"><input v-model="supplierForm.contact_person" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Contact person"><input v-model="supplierForm.phone" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Phone"><input v-model="supplierForm.email" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Email"><textarea v-model="supplierForm.materials_supplied" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Materials supplied"></textarea><button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="createSupplier">Save supplier</button></div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'materials'">
        <div class="grid gap-4 xl:grid-cols-[1fr_0.9fr]">
          <SectionCard title="Raw materials" description="Inventory records, stock quantity, reorder level, and supplier source.">
            <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-stone-500"><tr><th class="pb-3">Material</th><th class="pb-3">Category</th><th class="pb-3">Color</th><th class="pb-3">Stock</th><th class="pb-3">Reorder</th><th class="pb-3">Supplier</th></tr></thead><tbody><tr v-for="item in data?.raw_materials || []" :key="item.id" class="border-t border-stone-100"><td class="py-3 font-medium">{{ item.material_name }}</td><td class="py-3">{{ item.category }}</td><td class="py-3">{{ item.color }}</td><td class="py-3">{{ item.stock_quantity }} {{ item.unit }}</td><td class="py-3">{{ item.reorder_level }}</td><td class="py-3">{{ item.supplier?.name || '—' }}</td></tr></tbody></table></div>
          </SectionCard>
          <SectionCard title="Add raw material" description="Low-stock automation uses the reorder level you set here.">
            <div class="grid gap-3"><input v-model="materialForm.material_name" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Material name"><div class="grid gap-3 sm:grid-cols-2"><input v-model="materialForm.category" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Category"><input v-model="materialForm.color" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Color"></div><div class="grid gap-3 sm:grid-cols-2"><input v-model="materialForm.stock_quantity" type="number" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Stock quantity"><input v-model="materialForm.reorder_level" type="number" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Reorder level"></div><div class="grid gap-3 sm:grid-cols-2"><input v-model="materialForm.cost_per_unit" type="number" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Cost per unit"><select v-model="materialForm.supplier_id" class="rounded-2xl border border-stone-300 px-4 py-3"><option value="">No supplier linked</option><option v-for="supplier in data?.supplier_management || []" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option></select></div><button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="createMaterial">Save material</button></div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'supply'">
        <SectionCard title="Supply chain" description="Purchase orders currently pending, ordered, in transit, or received.">
          <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-stone-500"><tr><th class="pb-3">PO</th><th class="pb-3">Supplier</th><th class="pb-3">Quantity</th><th class="pb-3">Total Cost</th><th class="pb-3">Expected Arrival</th><th class="pb-3">Status</th></tr></thead><tbody><tr v-for="po in data?.supply_chain || []" :key="po.id" class="border-t border-stone-100"><td class="py-3 font-medium">{{ po.po_number }}</td><td class="py-3">{{ po.supplier?.name }}</td><td class="py-3">{{ po.quantity_total }}</td><td class="py-3">₱ {{ money(po.total_cost) }}</td><td class="py-3">{{ po.expected_arrival_at ? new Date(po.expected_arrival_at).toLocaleDateString() : '—' }}</td><td class="py-3 capitalize">{{ po.status }}</td></tr></tbody></table></div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'staff'">
        <SectionCard title="Staff" description="Owner-facing HR and staff account overview for the shop.">
          <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3"><div v-for="member in data?.staff || []" :key="member.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4"><div class="font-semibold text-stone-900">{{ member.name }}</div><div class="mt-1 text-sm capitalize text-stone-600">{{ member.role }}</div><div class="mt-2 text-xs text-stone-500">{{ member.email }}</div></div></div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'operations'">
        <SectionCard title="Operations" description="Staff work operations snapshot.">
          <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3"><div v-for="member in data?.operations || []" :key="member.staff_id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4"><div class="flex items-center justify-between"><div class="font-semibold">{{ member.name }}</div><div class="text-xs uppercase tracking-[0.2em]">{{ member.role }}</div></div><div class="mt-3 grid grid-cols-2 gap-3 text-sm text-stone-600"><div>Active tasks <span class="font-medium text-stone-900">{{ member.active_tasks }}</span></div><div>Completed <span class="font-medium text-stone-900">{{ member.completed_tasks }}</span></div><div>Delayed <span class="font-medium text-stone-900">{{ member.delayed_tasks }}</span></div><div>Revision jobs <span class="font-medium text-stone-900">{{ member.revision_jobs }}</span></div></div></div></div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'schedule'">
        <div class="grid gap-4 xl:grid-cols-[1fr_0.9fr]">
          <SectionCard title="Workforce scheduling" description="Staff schedule visibility by shift date and time.">
            <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-stone-500"><tr><th class="pb-3">Staff</th><th class="pb-3">Date</th><th class="pb-3">Shift</th><th class="pb-3">Assignment</th><th class="pb-3">Flags</th></tr></thead><tbody><tr v-for="row in data?.workforce_scheduling || []" :key="row.id" class="border-t border-stone-100"><td class="py-3 font-medium">{{ row.user?.name }}</td><td class="py-3">{{ new Date(row.shift_date).toLocaleDateString() }}</td><td class="py-3">{{ row.shift_start || '—' }} - {{ row.shift_end || '—' }}</td><td class="py-3">{{ row.assignment_notes || '—' }}</td><td class="py-3">{{ row.is_day_off ? 'Day off' : '' }} {{ row.is_overtime ? 'Overtime' : '' }}</td></tr></tbody></table></div>
          </SectionCard>
          <SectionCard title="Create schedule" description="Assign shift windows and schedule notes.">
            <div class="grid gap-3"><select v-model="scheduleForm.user_id" class="rounded-2xl border border-stone-300 px-4 py-3"><option v-for="member in data?.staff || []" :key="member.id" :value="member.id">{{ member.name }} ({{ member.role }})</option></select><input v-model="scheduleForm.shift_date" type="date" class="rounded-2xl border border-stone-300 px-4 py-3"><div class="grid gap-3 sm:grid-cols-2"><input v-model="scheduleForm.shift_start" type="time" class="rounded-2xl border border-stone-300 px-4 py-3"><input v-model="scheduleForm.shift_end" type="time" class="rounded-2xl border border-stone-300 px-4 py-3"></div><textarea v-model="scheduleForm.assignment_notes" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Assigned production work"></textarea><button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="createSchedule">Save schedule</button></div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'delivery'">
        <SectionCard title="Delivery & Pickup" description="Courier selection, pickup schedule, and tracking overview.">
          <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-stone-500"><tr><th class="pb-3">Order</th><th class="pb-3">Customer</th><th class="pb-3">Courier</th><th class="pb-3">Delivery Type</th><th class="pb-3">Tracking</th><th class="pb-3">Status</th></tr></thead><tbody><tr v-for="row in data?.delivery_pickup || []" :key="row.order_id" class="border-t border-stone-100"><td class="py-3 font-medium">{{ row.order_number }}</td><td class="py-3">{{ row.customer }}</td><td class="py-3">{{ row.courier || data?.settings?.delivery_defaults_json?.preferred_courier || '—' }}</td><td class="py-3">{{ row.delivery_type }}</td><td class="py-3">{{ row.tracking_reference || '—' }}</td><td class="py-3 capitalize">{{ row.status }}</td></tr></tbody></table></div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'disputes'">
        <div class="grid gap-4 xl:grid-cols-[1fr_0.9fr]">
          <SectionCard title="Dispute resolution" description="Wrong design, damaged delivery, payment mismatch, supplier issue, and delay complaints.">
            <div class="space-y-3"><div v-for="caseItem in data?.dispute_resolution || []" :key="caseItem.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4"><div class="flex items-center justify-between"><div class="font-semibold">{{ caseItem.dispute_type }}</div><div class="text-xs uppercase tracking-[0.2em]">{{ caseItem.status }}</div></div><div class="mt-2 text-sm text-stone-600">{{ caseItem.issue_summary }}</div><div class="mt-3 text-xs text-stone-500">Order {{ caseItem.order?.order_number || '—' }} · Handler {{ caseItem.handler?.name || 'Unassigned' }}</div></div></div>
          </SectionCard>
          <SectionCard title="Open dispute" description="Owner-side dispute intake and assignment.">
            <div class="grid gap-3"><select v-model="disputeForm.order_id" class="rounded-2xl border border-stone-300 px-4 py-3"><option value="">No related order</option><option v-for="order in data?.orders || []" :key="order.id" :value="order.id">{{ order.order_number }}</option></select><select v-model="disputeForm.assigned_handler_user_id" class="rounded-2xl border border-stone-300 px-4 py-3"><option value="">Unassigned handler</option><option v-for="member in data?.staff || []" :key="member.id" :value="member.id">{{ member.name }}</option></select><select v-model="disputeForm.dispute_type" class="rounded-2xl border border-stone-300 px-4 py-3"><option value="wrong_design">Design is wrong</option><option value="delivery_damaged">Delivery damaged</option><option value="payment_mismatch">Payment mismatch</option><option value="delayed_order">Delayed order complaint</option><option value="supplier_issue">Supplier issue</option><option value="production_defect">Production defect blame</option></select><textarea v-model="disputeForm.issue_summary" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Issue summary"></textarea><button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="createDispute">Open dispute case</button></div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'messages'">
        <div class="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
          <SectionCard title="Conversations" description="Owner can create group chats for HR, staff, and client inquiry flows.">
            <div class="space-y-3"><button v-for="thread in data?.messages || []" :key="thread.id" class="w-full rounded-2xl border px-4 py-3 text-left" :class="selectedThread?.id === thread.id ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-200 bg-stone-50 text-stone-900'" @click="selectedThreadId = thread.id"><div class="font-semibold">{{ thread.title }}</div><div class="mt-1 text-xs opacity-70">{{ thread.type }} · {{ thread.messages?.length || 0 }} messages</div></button></div>
            <div class="mt-4 grid gap-3"><input v-model="threadForm.title" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="New group chat title"><button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="createThread">Create group chat</button></div>
          </SectionCard>
          <SectionCard :title="selectedThread?.title || 'Messages'" description="Threaded conversation for staff, HR, or client inquiries.">
            <div v-if="selectedThread" class="space-y-3"><div v-for="message in selectedThread.messages || []" :key="message.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4"><div class="text-sm font-semibold text-stone-900">{{ message.sender?.name || 'Unknown sender' }}</div><div class="mt-2 text-sm text-stone-600">{{ message.message }}</div></div><textarea v-model="threadMessage[selectedThread.id]" class="w-full rounded-2xl border border-stone-300 px-4 py-3" placeholder="Write a message"></textarea><button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving || !threadMessage[selectedThread.id]" @click="sendThreadMessage(selectedThread)">Send message</button></div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'marketplace'">
        <div class="grid gap-4 xl:grid-cols-2">
          <SectionCard title="Our posted projects" description="Projects posted by the shop.">
            <div class="space-y-3"><div v-for="project in data?.marketplace?.shop_projects || []" :key="project.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4"><div class="font-semibold">{{ project.title }}</div><div class="mt-2 text-sm text-stone-600">{{ project.description }}</div></div></div>
          </SectionCard>
          <SectionCard title="Client design requests" description="Posted requests from clients seeking design work.">
            <div class="space-y-3"><div v-for="request in data?.marketplace?.client_design_requests || []" :key="request.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4"><div class="font-semibold">{{ request.name || 'Design request' }}</div><div class="mt-2 text-sm text-stone-600">{{ request.notes || 'No notes provided.' }}</div><div class="mt-3 text-xs text-stone-500">Budget signal from system quote: ₱ {{ money(request.estimated_total_price) }}</div></div></div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'analytics'">
        <div class="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
          <SectionCard title="Business summary cards" description="Owner analytics cards for orders, revenue, quotes, and delays.">
            <div class="grid gap-4 md:grid-cols-2"><StatCard label="Total Orders" :value="data?.analytics?.cards?.total_orders || 0" /><StatCard label="Total Revenue" :value="`₱ ${money(data?.analytics?.cards?.total_revenue)}`" /><StatCard label="Pending Orders" :value="data?.analytics?.cards?.pending_orders || 0" /><StatCard label="Completed Orders" :value="data?.analytics?.cards?.completed_orders || 0" /><StatCard label="Quote Requests" :value="data?.analytics?.cards?.quote_requests || 0" /><StatCard label="Delayed Orders" :value="data?.analytics?.cards?.delayed_orders || 0" /></div>
          </SectionCard>
          <SectionCard title="Revenue analysis" description="Daily, weekly, and monthly order revenue visual summary.">
            <div class="space-y-3"><div v-for="point in data?.analytics?.daily_revenue || []" :key="point.label"><div class="mb-1 flex items-center justify-between text-sm"><span>{{ point.label }}</span><span>₱ {{ money(point.value) }}</span></div><div class="h-3 rounded-full bg-stone-100"><div class="h-3 rounded-full bg-stone-900" :style="{ width: barWidth(point, data?.analytics?.daily_revenue) }"></div></div></div></div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'preferences'">
        <SectionCard title="Preferences & control" description="Shop information, workflow automation, documents, approvals, delivery defaults, and security settings.">
          <div class="grid gap-4 xl:grid-cols-2">
            <div class="grid gap-3">
              <input v-model="settingsForm.shop_name" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Shop Name">
              <textarea v-model="settingsForm.address" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Address"></textarea>
              <div class="grid gap-3 sm:grid-cols-2"><input v-model="settingsForm.contact_number" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Contact Number"><input v-model="settingsForm.contact_email" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Email"></div>
              <input v-model="settingsForm.operating_hours" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Operating Hours">
              <div class="grid gap-3 sm:grid-cols-3"><input v-model="settingsForm.default_labor_rate" type="number" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Default labor rate"><input v-model="settingsForm.rush_fee_percent" type="number" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Rush fee %"><input v-model="settingsForm.default_profit_margin" type="number" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Profit margin %"></div>
              <div class="grid gap-3 sm:grid-cols-2"><input v-model="settingsForm.minimum_order_quantity" type="number" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Minimum order qty"><input v-model="settingsForm.max_rush_orders_per_day" type="number" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Max rush orders/day"></div>
            </div>
            <div class="grid gap-3">
              <label class="rounded-2xl border border-stone-300 bg-stone-50 px-4 py-3 text-sm"><input v-model="settingsForm.workflow_automation_settings_json.auto_move_order_after_payment" type="checkbox" class="mr-2">Auto move order after payment</label>
              <label class="rounded-2xl border border-stone-300 bg-stone-50 px-4 py-3 text-sm"><input v-model="settingsForm.workflow_automation_settings_json.auto_create_production_task" type="checkbox" class="mr-2">Auto create production task</label>
              <label class="rounded-2xl border border-stone-300 bg-stone-50 px-4 py-3 text-sm"><input v-model="settingsForm.workflow_automation_settings_json.auto_low_stock_alert" type="checkbox" class="mr-2">Auto low-stock alert</label>
              <label class="rounded-2xl border border-stone-300 bg-stone-50 px-4 py-3 text-sm"><input v-model="settingsForm.notification_settings_json.new_order" type="checkbox" class="mr-2">New order alert</label>
              <label class="rounded-2xl border border-stone-300 bg-stone-50 px-4 py-3 text-sm"><input v-model="settingsForm.notification_settings_json.payment_received" type="checkbox" class="mr-2">Payment received alert</label>
              <label class="rounded-2xl border border-stone-300 bg-stone-50 px-4 py-3 text-sm"><input v-model="settingsForm.notification_settings_json.low_stock" type="checkbox" class="mr-2">Low stock alert</label>
              <div class="grid gap-3 sm:grid-cols-2"><input v-model="settingsForm.delivery_defaults_json.preferred_courier" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Preferred courier"><input v-model="settingsForm.delivery_defaults_json.pickup_hours" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Pickup hours"></div>
              <div class="grid gap-3 sm:grid-cols-2"><input v-model="settingsForm.document_settings_json.invoice_format" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Invoice format"><input v-model="settingsForm.document_settings_json.quotation_format" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Quotation format"></div>
              <div class="grid gap-3 sm:grid-cols-2"><input v-model="settingsForm.approval_settings_json.discount_approver_role" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Discount approver"><input v-model="settingsForm.approval_settings_json.dispute_approver_role" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Dispute approver"></div>
              <button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="saveSettings">Save preferences</button>
            </div>
          </div>
        </SectionCard>
      </template>
    </div>
  </AppWorkspaceLayout>
</template>
