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

const serviceForm = reactive({ service_name: '', category: '', base_price: '', unit_price: '', min_order_qty: '', stitch_range: '', complexity_multiplier: '', rush_multiplier: '', rush_fee_allowed: true });
const supplierForm = reactive({ name: '', contact_person: '', phone: '', email: '', address: '', materials_supplied: '', lead_time_days: '', status: 'active' });
const materialForm = reactive({ material_name: '', category: '', color: '', unit: 'pcs', stock_quantity: '', reorder_level: '', cost_per_unit: '', supplier_id: '' });
const projectForm = reactive({ title: '', starting_price: '', description: '', embroidery_size: '', canvas_used: '', category: 'custom_project', preview_image: null });
const courierForm = reactive({ name: '', contact_person: '', contact_number: '', service_type: 'delivery', coverage_area: '' });
const staffAccountForm = reactive({ name: '', email: '', password: '', phone: '', member_role: 'hr', position: '' });
const staffPromotionForm = reactive({ user_id: '', member_role: 'staff', position: '' });
const scheduleForm = reactive({ user_id: '', order_id: '', shift_date: '', shift_start: '', shift_end: '', deadline_at: '', assignment_notes: '', is_day_off: false, is_overtime: false });
const pricingSettingsForm = reactive({ minimum_order_quantity: '', minimum_billable_amount: '', max_manual_discount_percent: '', complexity_rules: [
  { level: 'simple', stitch_multiplier: '', labor_multiplier: '', digitizing_multiplier: '' },
  { level: 'moderate', stitch_multiplier: '', labor_multiplier: '', digitizing_multiplier: '' },
  { level: 'complex', stitch_multiplier: '', labor_multiplier: '', digitizing_multiplier: '' },
  { level: 'highly_complex', stitch_multiplier: '', labor_multiplier: '', digitizing_multiplier: '' },
], color_rules: { '1_3': { extra_cost_per_color: '', premium_thread_surcharge: '' }, '4_6': { extra_cost_per_color: '', premium_thread_surcharge: '' }, '7_plus': { extra_cost_per_color: '', premium_thread_surcharge: '' } }, size_rules: [{ size_category: '', max_dimensions: '', multiplier: '' }], material_surcharges: [{ key: 'metallic_thread', label: 'Metallic thread', amount: '' }, { key: 'glow_thread', label: 'Glow thread', amount: '' }, { key: 'patch_backing', label: 'Patch backing', amount: '' }, { key: 'premium_fabric', label: 'Premium fabric', amount: '' }], rush_rules: { '24_hour_rush': { multiplier: '' }, '48_hour_rush': { multiplier: '' }, 'weekend_rush': { multiplier: '' } }, discount_rules: { bulk_discounts: [{ min_qty: '', percent: '' }], repeat_client_discount_percent: '', promo_discount_percent: '' }, automation_controls: { use_system_suggested_price: true, allow_owner_override: true, auto_add_labor_estimate: true, auto_add_material_estimate: true, auto_add_rush_fee: true, auto_add_shipping_estimate: false } });
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
const pricingCenter = computed(() => data.value?.pricing_control_center || {});
const scheduleCalendar = computed(() => { const rows = data.value?.workforce_scheduling || []; const grouped = {}; rows.forEach((row) => { const key = row.shift_date ? new Date(row.shift_date).toLocaleDateString() : 'Unscheduled'; (grouped[key] ||= []).push(row); }); return grouped; });
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
const actionCenter = computed(() => data.value?.overview?.action_center || []);
const productionBoard = computed(() => data.value?.production_board || []);
const restockRecommendations = computed(() => data.value?.restock_recommendations || []);
const paymentFollowups = computed(() => data.value?.payment_followups || []);
const governanceRules = computed(() => data.value?.automation?.governance || []);
const notificationLifecycle = computed(() => data.value?.automation?.notification_lifecycle || {});

function setFlash(text, type = 'info') { flash.text = text; flash.type = type; }
function money(v) { return Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
function barWidth(item, source) { const max = Math.max(...(source || []).map((row) => Number(row.value || 0)), 1); return `${Math.max((Number(item.value || 0) / max) * 100, 6)}%`; }
function getError(err) { return err?.response?.data?.message || Object.values(err?.response?.data?.errors || {})?.[0]?.[0] || 'Request failed.'; }
async function api(method, url, payload) { applyApiToken(token.value); return window.axios({ method, url, data: payload }); }
async function generateProof(item) {
  saving.value = true;
  try {
    await api('post', `/api/design-customizations/${item.id}/owner-proof`, { notes: `Owner proof generated for ${item.name}.` });
    setFlash('Proof generated and sent to client.', 'success');
    await load();
  } catch (err) { setFlash(getError(err), 'error'); } finally { saving.value = false; }
}
async function lockApprovedDesign(item) {
  saving.value = true;
  try {
    await api('post', `/api/design-customizations/${item.id}/approve`, { lock: true });
    setFlash('Design approved and locked.', 'success');
    await load();
  } catch (err) { setFlash(getError(err), 'error'); } finally { saving.value = false; }
}
async function createVersionSnapshot(item) {
  saving.value = true;
  try {
    await api('post', `/api/design-customizations/${item.id}/versions`, { change_summary: 'Owner review checkpoint', notes: 'Snapshot captured from owner workspace.' });
    setFlash('Version checkpoint saved.', 'success');
    await load();
  } catch (err) { setFlash(getError(err), 'error'); } finally { saving.value = false; }
}

async function markReadyForDigitizing(item) {
  saving.value = true;
  try {
    await api('post', `/api/design-customizations/${item.id}/production-status`, { production_status: 'ready_for_digitizing', internal_note: `Prepared ${item.name} for digitizing.` });
    setFlash('Marked as ready for digitizing.', 'success');
    await load();
  } catch (err) { setFlash(getError(err), 'error'); } finally { saving.value = false; }
}
async function markReadyForProduction(item) {
  saving.value = true;
  try {
    await api('post', `/api/design-customizations/${item.id}/production-status`, { production_status: 'ready_for_production', internal_note: `Prepared ${item.name} for production.` });
    setFlash('Marked as ready for production.', 'success');
    await load();
  } catch (err) { setFlash(getError(err), 'error'); } finally { saving.value = false; }
}
async function createProductionPackage(item) {
  saving.value = true;
  try {
    await api('post', `/api/design-customizations/${item.id}/production-package`, { handoff: true, internal_note: `Production package created for ${item.name}.` });
    setFlash('Production package created.', 'success');
    await load();
  } catch (err) { setFlash(getError(err), 'error'); } finally { saving.value = false; }
}
async function unlockLockedDesign(item) {
  const reason = window.prompt('Enter override reason for unlocking this design:');
  if (!reason) return;
  saving.value = true;
  try {
    await api('post', `/api/design-customizations/${item.id}/unlock`, { override_reason: reason });
    setFlash('Design unlocked with override reason.', 'success');
    await load();
  } catch (err) { setFlash(getError(err), 'error'); } finally { saving.value = false; }
}

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
    const center = data.value?.pricing_control_center || {};
    pricingSettingsForm.minimum_order_quantity = center.minimum_order_quantity || '';
    pricingSettingsForm.minimum_billable_amount = center.minimum_billable_amount || '';
    pricingSettingsForm.max_manual_discount_percent = center.max_manual_discount_percent || '';
    if (center.rules?.complexity_rules) pricingSettingsForm.complexity_rules = center.rules.complexity_rules;
    if (center.rules?.color_rules) pricingSettingsForm.color_rules = center.rules.color_rules;
    if (center.rules?.size_rules?.length) pricingSettingsForm.size_rules = center.rules.size_rules;
    if (center.rules?.material_surcharges?.length) pricingSettingsForm.material_surcharges = center.rules.material_surcharges;
    if (center.rules?.rush_rules) pricingSettingsForm.rush_rules = center.rules.rush_rules;
    if (center.rules?.discount_rules) pricingSettingsForm.discount_rules = center.rules.discount_rules;
    if (center.automation_controls) pricingSettingsForm.automation_controls = center.automation_controls;
    if (!disputeForm.order_id) disputeForm.order_id = data.value?.orders?.[0]?.id || '';
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
async function createProject() {
  saving.value = true;
  try {
    const fd = new FormData();
    fd.append('title', projectForm.title);
    fd.append('description', projectForm.description);
    fd.append('base_price', projectForm.starting_price);
    fd.append('embroidery_size', projectForm.embroidery_size);
    fd.append('canvas_used', projectForm.canvas_used);
    fd.append('category', projectForm.category);
    if (projectForm.preview_image) fd.append('preview_image', projectForm.preview_image);
    await api('post', '/api/shop-projects', fd);
    setFlash('Project published.', 'success');
    await load();
  } catch (err) { setFlash(getError(err), 'error'); } finally { saving.value = false; }
}
async function createCourier() { await submit('/api/owner/couriers', courierForm, 'Courier added.'); }
async function createStaffAccount() { await submit('/api/shop-members', { ...staffAccountForm, shop_id: user.value.shop_id, mode: 'create_account' }, 'Staff account submitted.'); }
async function promoteClientAccount() { await submit('/api/shop-members', { ...staffPromotionForm, shop_id: user.value.shop_id, mode: 'promote_client' }, 'Client promotion submitted.'); }
async function reviewMember(member, approval_status) { await api('put', `/api/shop-members/${member.id}`, { approval_status, member_role: member.member_role, position: member.position, employment_status: approval_status === 'approved' ? 'active' : 'rejected' }); setFlash(`Staff request ${approval_status}.`, 'success'); await load(); }
async function savePricingControls() {
  const payload = {
    minimum_order_quantity: pricingSettingsForm.minimum_order_quantity || null,
    minimum_billable_amount: pricingSettingsForm.minimum_billable_amount || null,
    max_manual_discount_percent: pricingSettingsForm.max_manual_discount_percent || null,
    pricing_rules_json: {
      complexity_rules: pricingSettingsForm.complexity_rules,
      color_rules: pricingSettingsForm.color_rules,
      size_rules: pricingSettingsForm.size_rules.filter((row) => row.size_category || row.max_dimensions || row.multiplier),
      material_surcharges: pricingSettingsForm.material_surcharges,
      rush_rules: pricingSettingsForm.rush_rules,
      discount_rules: pricingSettingsForm.discount_rules,
    },
    quote_automation_controls_json: pricingSettingsForm.automation_controls,
  };
  saving.value = true;
  try { await api('put', '/api/owner/pricing', payload); setFlash('Pricing controls updated.', 'success'); await load(); } catch (err) { setFlash(getError(err), 'error'); } finally { saving.value = false; }
}
async function createSchedule() { await submit('/api/owner/workforce-schedules', { ...scheduleForm, order_id: scheduleForm.order_id || null, deadline_at: scheduleForm.deadline_at || null }, 'Schedule added.'); }
async function createDispute() { await submit('/api/owner/disputes', { ...disputeForm, complainant_user_id: disputeForm.complainant_user_id || null, assigned_handler_user_id: disputeForm.assigned_handler_user_id || null }, 'Dispute case opened.'); }
async function createThread() { await submit('/api/owner/threads', threadForm, 'Conversation created.'); }
async function sendThreadMessage(thread) { await submit(`/api/owner/threads/${thread.id}/messages`, { message: threadMessage[thread.id] }, 'Message sent.'); threadMessage[thread.id] = ''; }
async function actionPost(url, payload, successText) {
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

async function followUpPayment(order) {
  await actionPost(`/api/owner/actions/orders/${order.id}/follow-up-payment`, { extend_due_date: false }, `Payment follow-up sent for ${order.order_number}.`);
}
async function escalateOrder(order) {
  await actionPost(`/api/owner/actions/orders/${order.id}/escalate`, {}, `Order ${order.order_number} escalated.`);
}
async function approveProductionPlan(order) {
  await actionPost(`/api/owner/actions/orders/${order.id}/approve-production-plan`, {}, `Production plan approved for ${order.order_number}.`);
}
async function pauseProduction(order) {
  await actionPost(`/api/owner/actions/orders/${order.id}/pause`, {}, `Production paused for ${order.order_number}.`);
}
async function resumeProduction(order) {
  await actionPost(`/api/owner/actions/orders/${order.id}/resume`, {}, `Production resumed for ${order.order_number}.`);
}
async function resolveAlert(alert) {
  await actionPost(`/api/owner/actions/alerts/${alert.id}/resolve`, {}, 'Alert resolved.');
}
async function snoozeAlert(alert) {
  await actionPost(`/api/owner/actions/alerts/${alert.id}/snooze`, { hours: 6 }, 'Alert snoozed.');
}
async function createRestock(rec) {
  await actionPost('/api/owner/actions/restock-requests', { material_id: rec.id, supplier_id: rec.recommended_supplier_id || null, quantity: rec.suggested_restock_quantity }, `Restock request created for ${rec.material_name}.`);
}
async function recordQc(order, result) {
  await actionPost(`/api/owner/actions/orders/${order.id}/quality-checks`, { result, rework_required: result !== 'pass' }, `QC saved for ${order.order_number}.`);
}
async function markPackageReady(order) {
  await actionPost(`/api/owner/actions/orders/${order.order_id || order.id}/package-ready`, {}, `Package marked ready for ${order.order_number}.`);
}
async function assignCourier(order) {
  const courier = window.prompt('Courier name');
  if (!courier) return;
  const tracking = window.prompt('Tracking number (optional)') || '';
  await actionPost(`/api/owner/actions/orders/${order.order_id || order.id}/assign-courier`, { courier_name: courier, tracking_number: tracking }, `Courier assigned for ${order.order_number}.`);
}
async function runNotificationMaintenance() {
  await actionPost('/api/owner/actions/notifications/maintain', {}, 'Notification lifecycle maintenance completed.');
}

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

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <SectionCard title="Delay predictions" description="Orders likely to slip soon.">
            <div class="space-y-2 text-sm text-stone-600">
              <div v-for="risk in (data?.automation?.delay_predictions || []).slice(0,4)" :key="risk.order_id" class="rounded-2xl border border-stone-200 bg-stone-50 p-3">
                <div class="font-medium text-stone-900">Order #{{ risk.order_id }} · {{ risk.risk }}</div>
                <div class="mt-1">{{ risk.signals?.join(', ') || 'Healthy' }}</div>
              </div>
            </div>
          </SectionCard>
          <SectionCard title="Workforce recommendations" description="Automatic role suggestions based on current load.">
            <div class="space-y-2 text-sm text-stone-600">
              <div v-for="rec in (data?.automation?.workforce_recommendations || []).slice(0,4)" :key="rec.assignment_type" class="rounded-2xl border border-stone-200 bg-stone-50 p-3">
                <div class="font-medium capitalize text-stone-900">{{ rec.assignment_type.replace('_',' ') }}</div>
                <div class="mt-1">{{ rec.staff_name || 'No staff available' }}</div>
                <div class="mt-1 text-xs text-stone-500">{{ rec.reason }}</div>
              </div>
            </div>
          </SectionCard>
          <SectionCard title="Material intelligence" description="Predictive stock readiness for production.">
            <div class="space-y-2 text-sm text-stone-600">
              <div v-for="item in (data?.automation?.material_intelligence || []).slice(0,4)" :key="item.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-3">
                <div class="flex items-center justify-between gap-3"><span class="font-medium text-stone-900">{{ item.material_name }}</span><span class="uppercase text-xs">{{ item.status }}</span></div>
                <div class="mt-1">{{ item.stock_quantity }} / reorder {{ item.reorder_level || 0 }}</div>
              </div>
            </div>
          </SectionCard>
          <SectionCard title="Linked support queue" description="Support now links back to operational context.">
            <div class="space-y-2 text-sm text-stone-600">
              <div v-for="ticket in (data?.automation?.support_queue || []).slice(0,4)" :key="ticket.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-3">
                <div class="font-medium text-stone-900">{{ ticket.subject }}</div>
                <div class="mt-1 text-xs text-stone-500">Order {{ ticket.order?.order_number || '—' }} · {{ ticket.priority }} · {{ ticket.status }}</div>
              </div>
            </div>
          </SectionCard>
        </div>

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

        <SectionCard title="Action center" description="Execute the next best action without leaving the dashboard.">
          <div class="grid gap-3 xl:grid-cols-2">
            <div v-for="item in actionCenter" :key="`${item.type}-${item.reference_id}`" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
              <div class="flex items-center justify-between gap-3">
                <div class="font-semibold text-stone-900">{{ item.title }}</div>
                <div class="text-xs uppercase tracking-[0.2em] text-stone-500">{{ item.priority }}</div>
              </div>
              <div class="mt-2 text-sm text-stone-600">{{ item.description }}</div>
              <div class="mt-3 flex flex-wrap gap-2">
                <button v-if="item.type === 'alert'" class="rounded-2xl border border-emerald-300 px-3 py-2 text-xs text-emerald-700" :disabled="saving" @click="resolveAlert({ id: item.reference_id })">Resolve</button>
                <button v-if="item.type === 'alert'" class="rounded-2xl border border-stone-300 px-3 py-2 text-xs text-stone-700" :disabled="saving" @click="snoozeAlert({ id: item.reference_id })">Snooze</button>
                <button v-if="item.type === 'payment_followup'" class="rounded-2xl border border-stone-900 px-3 py-2 text-xs text-stone-900" :disabled="saving" @click="followUpPayment(data?.orders?.find((row) => row.id === item.reference_id) || { id: item.reference_id, order_number: item.title })">Follow up payment</button>
                <button v-if="item.type === 'restock'" class="rounded-2xl border border-stone-900 px-3 py-2 text-xs text-stone-900" :disabled="saving" @click="createRestock(restockRecommendations.find((row) => row.id === item.reference_id) || { id: item.reference_id, material_name: item.title })">Create restock request</button>
                <button v-if="item.type === 'quality_check'" class="rounded-2xl border border-stone-900 px-3 py-2 text-xs text-stone-900" :disabled="saving" @click="recordQc(data?.orders?.find((row) => row.id === item.reference_id) || { id: item.reference_id, order_number: item.title }, 'pass')">Record QC pass</button>
              </div>
            </div>
          </div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'orders'">
        <SectionCard title="Orders" description="Owner order management with cleaner contained cards and live status tabs.">
          <div class="flex flex-wrap gap-2">
            <button v-for="key in ['all','pending','accepted','progress','completed','cancelled']" :key="key" class="rounded-2xl px-4 py-2 text-sm" :class="orderTab === key ? 'bg-stone-900 text-white' : 'border border-stone-300 bg-white text-stone-700'" @click="orderTab = key">{{ ({all:'All',pending:'Pending',accepted:'Accepted',progress:'In Progress',completed:'Completed',cancelled:'Cancelled'})[key] }}</button>
          </div>
          <div class="mt-5 grid gap-4 xl:grid-cols-2">
            <article v-for="order in ordersByTab[orderTab]" :key="order.id" class="rounded-[28px] border border-stone-200 bg-gradient-to-br from-white to-stone-50 p-5 shadow-sm">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <div class="text-xs uppercase tracking-[0.24em] text-stone-500">{{ order.current_stage || order.status }}</div>
                  <h3 class="mt-2 text-lg font-semibold text-stone-900">{{ order.order_number }}</h3>
                  <p class="mt-1 text-sm text-stone-500">{{ order.client?.name || 'No client yet' }} · {{ order.service?.service_name || order.order_type }}</p>
                </div>
                <div class="rounded-2xl bg-stone-900 px-4 py-3 text-right text-white">
                  <div class="text-xs uppercase tracking-[0.2em] text-stone-300">Total</div>
                  <div class="mt-1 text-xl font-semibold">₱ {{ money(order.total_amount) }}</div>
                </div>
              </div>
              <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-stone-200 bg-white p-3"><div class="text-xs uppercase tracking-[0.18em] text-stone-400">Quantity</div><div class="mt-2 text-base font-semibold text-stone-900">{{ order.items?.[0]?.quantity || '—' }}</div></div>
                <div class="rounded-2xl border border-stone-200 bg-white p-3"><div class="text-xs uppercase tracking-[0.18em] text-stone-400">Payment</div><div class="mt-2 text-base font-semibold capitalize text-stone-900">{{ order.payment_status || '—' }}</div></div>
                <div class="rounded-2xl border border-stone-200 bg-white p-3"><div class="text-xs uppercase tracking-[0.18em] text-stone-400">Due</div><div class="mt-2 text-base font-semibold text-stone-900">{{ order.due_date ? new Date(order.due_date).toLocaleDateString() : '—' }}</div></div>
                <div class="rounded-2xl border border-stone-200 bg-white p-3"><div class="text-xs uppercase tracking-[0.18em] text-stone-400">Assignments</div><div class="mt-2 text-base font-semibold text-stone-900">{{ order.assignments?.length || 0 }}</div></div>
              </div>
              <div class="mt-4 flex flex-wrap gap-2">
                <button class="rounded-2xl border border-stone-300 px-3 py-2 text-sm text-stone-700" @click="followUpPayment(order)">Follow up payment</button>
                <button class="rounded-2xl border border-stone-300 px-3 py-2 text-sm text-stone-700" @click="approveProductionPlan(order)">Approve production</button>
                <button class="rounded-2xl border border-stone-300 px-3 py-2 text-sm text-stone-700" @click="escalateOrder(order)">Escalate</button>
              </div>
            </article>
          </div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'proofing'">
        <SectionCard title="Design proofing & price quote" description="Client proofing requests with automated suggested quotation.">
          <div class="space-y-4">
            <div v-for="item in data?.design_proofing || []" :key="item.id" class="rounded-3xl border border-stone-200 p-5">
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex-1">
                  <div class="flex flex-wrap items-center gap-2"><div class="text-lg font-semibold text-stone-900">{{ item.name || 'Customization request' }}</div><span class="rounded-full border border-stone-300 bg-white px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-600">{{ item.workflow_status || item.status }}</span></div>
                  <div class="mt-1 text-sm text-stone-500">{{ item.user?.name || 'Client' }} · {{ item.garment_type }} · {{ item.placement_area }}</div>
                  <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4 text-sm text-stone-600">
                    <div>Colors: <span class="font-medium text-stone-900">{{ item.color_count }}</span></div>
                    <div>Complexity: <span class="font-medium text-stone-900">{{ item.complexity_level }}</span></div>
                    <div>Stitches: <span class="font-medium text-stone-900">{{ item.stitch_count_estimate || item.pricing_breakdown_preview?.stitch_count_estimate || '—' }}</span></div>
                    <div>Status: <span class="font-medium capitalize text-stone-900">{{ item.status }}</span></div>
                    <div>Version: <span class="font-medium text-stone-900">#{{ item.current_version_no || item.snapshots?.length || 1 }}</span></div>
                    <div>Approved: <span class="font-medium text-stone-900">{{ item.approved_version_no ? `#${item.approved_version_no}` : '—' }}</span></div>
                    <div>Revisions: <span class="font-medium text-stone-900">{{ item.revision_count || 0 }}</span></div>
                    <div>Proofs: <span class="font-medium text-stone-900">{{ item.proof_history_count || item.proofs?.length || 0 }}</span></div>
                  </div>
                  <div v-if="item.latest_activity" class="mt-3 rounded-2xl border border-stone-200 bg-white p-3 text-sm text-stone-600">
                    <div class="font-medium text-stone-900">{{ item.latest_activity.summary }}</div>
                    <div class="mt-1">{{ item.latest_activity.actor?.name || 'System' }} · {{ new Date(item.latest_activity.created_at).toLocaleString() }}</div>
                    <div v-if="item.latest_activity.details" class="mt-1 text-stone-500">{{ item.latest_activity.details }}</div>
                  </div>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm min-w-[260px]">
                  <div class="text-xs uppercase tracking-[0.2em] text-stone-500">Suggested quote</div>
                  <div class="mt-2 text-2xl font-semibold text-stone-900">₱ {{ money(item.suggested_quote) }}</div>
                  <div class="mt-3 space-y-1 text-stone-600">
                    <div>Base: ₱ {{ money(item.pricing_breakdown_preview?.base_unit_price) }}</div>
                    <div>Rush fee: ₱ {{ money(item.pricing_breakdown_preview?.rush_fee) }}</div>
                    <div>Material fee: ₱ {{ money(item.pricing_breakdown_preview?.material_fee) }}</div>
                    <div>Digitizing fee: ₱ {{ money(item.suggested_quote_basis_json?.estimated_digitizing_fee) }}</div>
                    <div>Placement surcharge: ₱ {{ money(item.suggested_quote_basis_json?.placement_surcharge) }}</div>
                    <div>Confidence: {{ item.pricing_breakdown_preview?.confidence_score || item.pricing_confidence_score || 0 }}%</div>
                  </div>
                  <div class="mt-3 rounded-2xl border border-stone-200 bg-white p-3 text-xs text-stone-600">
                    <div class="font-semibold uppercase tracking-[0.16em] text-stone-500">Production readiness</div>
                    <div class="mt-2 flex flex-wrap gap-2">
                      <span class="rounded-full border border-stone-300 bg-stone-50 px-2.5 py-1 font-medium text-stone-700">{{ item.production_status || 'Not staged' }}</span>
                      <span class="rounded-full border border-stone-300 bg-stone-50 px-2.5 py-1 font-medium text-stone-700">Risk flags: {{ item.risk_flag_count || 0 }}</span>
                      <span class="rounded-full border border-stone-300 bg-stone-50 px-2.5 py-1 font-medium text-stone-700">Packages: {{ item.production_package_count || 0 }}</span>
                    </div>
                    <div v-if="item.color_mapping_json?.length" class="mt-2">Threads: {{ item.color_mapping_json.map((thread) => thread.thread_name).join(', ') }}</div>
                  </div>
                  <div class="mt-4 grid gap-2">
                    <button class="rounded-2xl border border-stone-300 px-3 py-2 text-sm text-stone-700" :disabled="saving" @click="createVersionSnapshot(item)">Save version checkpoint</button>
                    <button class="rounded-2xl bg-stone-900 px-3 py-2 text-sm font-medium text-white" :disabled="saving" @click="generateProof(item)">Generate proof for client</button>
                    <button class="rounded-2xl border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-900" :disabled="saving || !['approved','proof_ready'].includes(item.status)" @click="lockApprovedDesign(item)">Lock approved design</button>
                    <button class="rounded-2xl border border-stone-300 px-3 py-2 text-sm text-stone-700" :disabled="saving" @click="markReadyForDigitizing(item)">Mark ready for digitizing</button>
                    <button class="rounded-2xl border border-stone-300 px-3 py-2 text-sm text-stone-700" :disabled="saving" @click="markReadyForProduction(item)">Mark ready for production</button>
                    <button class="rounded-2xl border border-sky-300 bg-sky-50 px-3 py-2 text-sm font-medium text-sky-900" :disabled="saving" @click="createProductionPackage(item)">Create production package</button>
                    <button v-if="item.locked_at" class="rounded-2xl border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900" :disabled="saving" @click="unlockLockedDesign(item)">Unlock with override</button>
                  </div>
                </div>
              </div>
              <div v-if="item.snapshots?.length" class="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="version in item.snapshots.slice(0, 3)" :key="version.id" class="rounded-2xl border border-stone-200 bg-white p-3 text-sm text-stone-600">
                  <div class="font-medium text-stone-900">Version #{{ version.version_no }}</div>
                  <div class="mt-1">{{ version.change_summary }}</div>
                  <div class="mt-1 text-xs text-stone-500">{{ version.actor?.name || 'System' }} · {{ new Date(version.created_at).toLocaleString() }}</div>
                </div>
              </div>
            </div>
          </div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'pricing'">
        <div class="space-y-4">
          <div class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
            <SectionCard title="Main service price table" description="Database-backed services that feed quote automation and proofing suggestions.">
              <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                  <thead class="text-left text-stone-500"><tr><th class="pb-3">Service</th><th class="pb-3">Base</th><th class="pb-3">Min Qty</th><th class="pb-3">Unit</th><th class="pb-3">Rush</th><th class="pb-3">Complexity</th><th class="pb-3">Status</th></tr></thead>
                  <tbody>
                    <tr v-for="service in pricingCenter.services || []" :key="service.id" class="border-t border-stone-100">
                      <td class="py-3 font-medium">{{ service.service_name }}</td><td class="py-3">₱ {{ money(service.base_price) }}</td><td class="py-3">{{ service.min_order_qty }}</td><td class="py-3">₱ {{ money(service.unit_price) }}</td><td class="py-3">× {{ service.rush_multiplier || '1.15' }}</td><td class="py-3">× {{ service.complexity_multiplier || '1.00' }}</td><td class="py-3">{{ service.is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </SectionCard>
            <SectionCard title="Add service pricing" description="Create real pricing rows for the current shop.">
              <div class="grid gap-3">
                <input v-model="serviceForm.service_name" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Service name">
                <select v-model="serviceForm.category" class="rounded-2xl border border-stone-300 px-4 py-3"><option value="">Select service category</option><option value="logo_embroidery">Logo embroidery</option><option value="name_embroidery">Name embroidery</option><option value="patch_embroidery">Patch embroidery</option><option value="uniform_embroidery">Uniform embroidery</option><option value="cap_embroidery">Cap embroidery</option><option value="custom_design_embroidery">Custom design embroidery</option></select>
                <div class="grid gap-3 sm:grid-cols-2"><input v-model="serviceForm.base_price" type="number" min="0" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Base price"><input v-model="serviceForm.unit_price" type="number" min="0" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Unit price"></div>
                <div class="grid gap-3 sm:grid-cols-3"><input v-model="serviceForm.min_order_qty" type="number" min="1" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Minimum qty"><input v-model="serviceForm.rush_multiplier" type="number" min="0" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Rush multiplier"><input v-model="serviceForm.complexity_multiplier" type="number" min="0" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Complexity multiplier"></div>
                <button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="createService">Save service pricing</button>
              </div>
            </SectionCard>
          </div>
          <div class="grid gap-4 xl:grid-cols-2">
            <SectionCard title="Complexity pricing rules" description="Simple to highly complex labor and digitizing multipliers.">
              <div class="space-y-3">
                <div v-for="rule in pricingSettingsForm.complexity_rules" :key="rule.level" class="grid gap-3 rounded-2xl border border-stone-200 p-4 md:grid-cols-4">
                  <div class="font-medium capitalize text-stone-900">{{ rule.level.replace('_', ' ') }}</div><input v-model="rule.stitch_multiplier" type="number" min="0" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Stitch multiplier"><input v-model="rule.labor_multiplier" type="number" min="0" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Labor multiplier"><input v-model="rule.digitizing_multiplier" type="number" min="0" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Digitizing multiplier">
                </div>
              </div>
            </SectionCard>
            <SectionCard title="Color-based pricing rules" description="1–3, 4–6, and 7+ color bands.">
              <div class="space-y-3">
                <div v-for="(rule, key) in pricingSettingsForm.color_rules" :key="key" class="grid gap-3 rounded-2xl border border-stone-200 p-4 md:grid-cols-3">
                  <div class="font-medium text-stone-900">{{ key === '1_3' ? '1–3 colors' : key === '4_6' ? '4–6 colors' : '7+ colors' }}</div><input v-model="rule.extra_cost_per_color" type="number" min="0" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Extra cost per color"><input v-model="rule.premium_thread_surcharge" type="number" min="0" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Premium thread surcharge">
                </div>
              </div>
            </SectionCard>
            <SectionCard title="Size, material, rush, and minimums" description="Rules used by the suggestion engine.">
              <div class="space-y-4">
                <div class="grid gap-3 md:grid-cols-3"><input v-model="pricingSettingsForm.size_rules[0].size_category" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Size category"><input v-model="pricingSettingsForm.size_rules[0].max_dimensions" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Max dimensions"><input v-model="pricingSettingsForm.size_rules[0].multiplier" type="number" min="0" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Multiplier"></div>
                <div class="grid gap-3 md:grid-cols-2"><input v-model="pricingSettingsForm.rush_rules['24_hour_rush'].multiplier" type="number" min="0" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="24-hour rush multiplier"><input v-model="pricingSettingsForm.rush_rules['48_hour_rush'].multiplier" type="number" min="0" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="48-hour rush multiplier"></div>
                <div class="grid gap-3 md:grid-cols-2"><input v-model="pricingSettingsForm.rush_rules['weekend_rush'].multiplier" type="number" min="0" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Weekend rush multiplier"><input v-model="pricingSettingsForm.minimum_billable_amount" type="number" min="0" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Minimum billable amount"></div>
                <div class="grid gap-3 md:grid-cols-2"><input v-model="pricingSettingsForm.minimum_order_quantity" type="number" min="1" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Minimum order quantity"><input v-model="pricingSettingsForm.max_manual_discount_percent" type="number" min="0" max="100" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Max manual discount allowed"></div>
                <div class="grid gap-3 md:grid-cols-2"><input v-model="pricingSettingsForm.discount_rules.bulk_discounts[0].min_qty" type="number" min="1" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Bulk discount min qty"><input v-model="pricingSettingsForm.discount_rules.bulk_discounts[0].percent" type="number" min="0" max="100" step="0.01" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Bulk discount percent"></div>
              </div>
            </SectionCard>
            <SectionCard title="Quote automation controls and insights" description="Automation stays prioritized while keeping owner override.">
              <div class="space-y-4">
                <div class="grid gap-3 md:grid-cols-2">
                  <label v-for="(label, key) in {use_system_suggested_price:'Use system suggested price',allow_owner_override:'Allow owner override',auto_add_labor_estimate:'Auto-add labor estimate',auto_add_material_estimate:'Auto-add material estimate',auto_add_rush_fee:'Auto-add rush fee',auto_add_shipping_estimate:'Auto-add shipping estimate'}" :key="key" class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-700"><input v-model="pricingSettingsForm.automation_controls[key]" type="checkbox" class="rounded border-stone-300"><span>{{ label }}</span></label>
                </div>
                <div v-if="pricingCenter.insights" class="grid gap-3 md:grid-cols-2">
                  <div class="rounded-2xl border border-stone-200 p-4"><div class="text-xs uppercase tracking-[0.2em] text-stone-400">Average accepted quote</div><div class="mt-2 text-xl font-semibold text-stone-900">₱ {{ money(pricingCenter.insights.average_accepted_quote_price) }}</div></div>
                  <div class="rounded-2xl border border-stone-200 p-4"><div class="text-xs uppercase tracking-[0.2em] text-stone-400">Most common service price</div><div class="mt-2 text-xl font-semibold text-stone-900">₱ {{ money(pricingCenter.insights.most_common_service_price) }}</div></div>
                  <div class="rounded-2xl border border-stone-200 p-4"><div class="text-xs uppercase tracking-[0.2em] text-stone-400">Rejected quotes</div><div class="mt-2 text-xl font-semibold text-stone-900">{{ pricingCenter.insights.rejected_quote_count }}</div></div>
                  <div class="rounded-2xl border border-stone-200 p-4"><div class="text-xs uppercase tracking-[0.2em] text-stone-400">Discount usage trend</div><div class="mt-2 text-xl font-semibold text-stone-900">{{ pricingCenter.insights.discount_usage_rate }}%</div></div>
                </div>
                <button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="savePricingControls">Save pricing controls</button>
              </div>
            </SectionCard>
          </div>
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
        <div class="grid gap-4">
          <SectionCard title="Production board" description="Stage-based queue with direct action execution.">
            <div class="grid gap-4 xl:grid-cols-3">
              <div v-for="column in productionBoard" :key="column.label" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="mb-3 flex items-center justify-between"><div class="font-semibold text-stone-900">{{ column.label }}</div><div class="text-xs text-stone-500">{{ column.items?.length || 0 }}</div></div>
                <div class="space-y-3">
                  <div v-for="item in column.items || []" :key="item.id" class="rounded-2xl border border-stone-200 bg-white p-3">
                    <div class="flex items-center justify-between gap-2"><div class="font-medium text-stone-900">{{ item.order_number }}</div><div class="text-xs uppercase tracking-[0.2em] text-stone-500">{{ item.risk }}</div></div>
                    <div class="mt-1 text-sm text-stone-600">{{ item.client }}</div>
                    <div class="mt-2 text-xs text-stone-500">{{ item.current_stage }} · due {{ item.due_date || '—' }}</div>
                    <div v-if="item.blockers?.length" class="mt-2 text-xs text-amber-700">{{ item.blockers.join(' · ') }}</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                      <button class="rounded-2xl border border-stone-900 px-3 py-2 text-xs text-stone-900" :disabled="saving" @click="approveProductionPlan(data?.orders?.find((row) => row.id === item.id) || item)">Approve plan</button>
                      <button class="rounded-2xl border border-stone-300 px-3 py-2 text-xs text-stone-700" :disabled="saving" @click="escalateOrder(data?.orders?.find((row) => row.id === item.id) || item)">Escalate</button>
                      <button v-if="item.status !== 'on_hold'" class="rounded-2xl border border-stone-300 px-3 py-2 text-xs text-stone-700" :disabled="saving" @click="pauseProduction(data?.orders?.find((row) => row.id === item.id) || item)">Pause</button>
                      <button v-else class="rounded-2xl border border-stone-300 px-3 py-2 text-xs text-stone-700" :disabled="saving" @click="resumeProduction(data?.orders?.find((row) => row.id === item.id) || item)">Resume</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </SectionCard>
          <SectionCard title="Production tracking" description="Detailed live owner view of production updates.">
            <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-stone-500"><tr><th class="pb-3">Order</th><th class="pb-3">Client</th><th class="pb-3">Assigned Staff</th><th class="pb-3">Stage</th><th class="pb-3">Priority</th><th class="pb-3">Due</th><th class="pb-3">Last Update</th></tr></thead><tbody><tr v-for="row in data?.production_tracking || []" :key="row.order_id" class="border-t border-stone-100"><td class="py-3 font-medium">{{ row.order_number }}</td><td class="py-3">{{ row.client }}</td><td class="py-3">{{ row.assigned_staff || 'Unassigned' }}</td><td class="py-3 capitalize">{{ row.stage || row.status }}</td><td class="py-3 capitalize">{{ row.priority }}</td><td class="py-3">{{ row.due_date ? new Date(row.due_date).toLocaleDateString() : '—' }}</td><td class="py-3">{{ row.last_update ? new Date(row.last_update).toLocaleString() : '—' }}</td></tr></tbody></table></div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'qc'">
        <SectionCard title="Quality control" description="QC records and rework visibility.">
          <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3"><div v-for="qc in data?.quality_control || []" :key="qc.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4"><div class="flex items-center justify-between"><div class="font-semibold">{{ qc.order?.order_number || `Order #${qc.order_id}` }}</div><div class="text-xs uppercase tracking-[0.2em]">{{ qc.result }}</div></div><div class="mt-2 text-sm text-stone-600">{{ qc.issue_notes || 'No issue notes.' }}</div><div class="mt-3 text-xs text-stone-500">Checked by {{ qc.checker?.name || '—' }} · Rework {{ qc.rework_required ? 'required' : 'not required' }}</div></div></div>
        </SectionCard>
      </template>

      <template v-else-if="active === 'projects'">
        <div class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
          <SectionCard title="Your posted works" description="Public project posts created by the logged-in owner account.">
            <div class="space-y-3">
              <div v-for="project in data?.projects || []" :key="project.id" class="rounded-3xl border border-stone-200 bg-stone-50 p-5">
                <div class="flex items-start justify-between gap-4">
                  <div><div class="text-lg font-semibold text-stone-900">{{ project.title }}</div><div class="mt-1 text-sm text-stone-500">{{ project.embroidery_size || 'Size not set' }} · {{ project.canvas_used || 'Canvas not set' }}</div></div>
                  <div class="text-right"><div class="text-xs uppercase tracking-[0.2em] text-stone-400">Starting price</div><div class="mt-1 text-xl font-semibold text-stone-900">₱ {{ money(project.base_price) }}</div></div>
                </div>
                <div class="mt-3 text-sm text-stone-600">{{ project.description }}</div>
              </div>
            </div>
          </SectionCard>
          <SectionCard title="Post your works" description="Create a project post that becomes visible to the public.">
            <div class="grid gap-3">
              <input v-model="projectForm.title" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Project title">
              <input v-model="projectForm.starting_price" type="number" min="0" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Starting price">
              <textarea v-model="projectForm.description" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Description"></textarea>
              <div class="grid gap-3 md:grid-cols-2"><input v-model="projectForm.embroidery_size" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Embroidery size"><input v-model="projectForm.canvas_used" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Canvas used"></div>
              <input type="file" accept="image/*" class="rounded-2xl border border-dashed border-stone-300 px-4 py-3 text-sm" @change="projectForm.preview_image = $event.target.files?.[0] || null">
              <button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="createProject">Publish project</button>
            </div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'materials'">
        <div class="grid gap-4 xl:grid-cols-[1fr_0.85fr]">
          <SectionCard title="Raw materials" description="Live inventory and reorder readiness.">
            <div class="space-y-3"><div v-for="material in data?.raw_materials || []" :key="material.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4"><div class="flex items-center justify-between"><div class="font-semibold text-stone-900">{{ material.material_name }}</div><div class="text-xs uppercase tracking-[0.2em] text-stone-500">{{ material.category }}</div></div><div class="mt-2 text-sm text-stone-600">Stock {{ material.stock_quantity }} {{ material.unit }} · Reorder {{ material.reorder_level }}</div></div></div>
          </SectionCard>
          <SectionCard title="Add material" description="Category is now a controlled selection.">
            <div class="grid gap-3"><input v-model="materialForm.material_name" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Material name"><select v-model="materialForm.category" class="rounded-2xl border border-stone-300 px-4 py-3"><option value="">Select category</option><option value="thread">Thread</option><option value="fabric">Fabric</option><option value="stabilizer">Stabilizer</option><option value="backing">Backing</option><option value="needle">Needle</option><option value="packaging">Packaging</option><option value="other">Other</option></select><div class="grid gap-3 md:grid-cols-2"><input v-model="materialForm.stock_quantity" type="number" min="0" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Stock quantity"><input v-model="materialForm.reorder_level" type="number" min="0" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Reorder level"></div><button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="createMaterial">Save material</button></div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'staff'">
        <div class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
          <SectionCard title="Staff approval board" description="Owner can approve or reject hires initiated by HR, plus manage positions.">
            <div class="space-y-3">
              <div v-for="member in data?.staff_directory || []" :key="member.id" class="rounded-3xl border border-stone-200 bg-stone-50 p-5">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                  <div><div class="text-lg font-semibold text-stone-900">{{ member.user?.name || 'Unlinked account' }}</div><div class="mt-1 text-sm text-stone-500">{{ member.member_role }} · {{ member.position || 'No position yet' }} · {{ member.approval_status || 'approved' }}</div></div>
                  <div class="flex flex-wrap gap-2" v-if="member.approval_status === 'pending_owner_approval'">
                    <button class="rounded-2xl bg-stone-900 px-3 py-2 text-sm text-white" @click="reviewMember(member, 'approved')">Approve</button>
                    <button class="rounded-2xl border border-stone-300 px-3 py-2 text-sm text-stone-700" @click="reviewMember(member, 'rejected')">Reject</button>
                  </div>
                </div>
              </div>
            </div>
          </SectionCard>
          <div class="space-y-4">
            <SectionCard title="Create new HR or staff account" description="Owner-side account creation for official team members.">
              <div class="grid gap-3"><input v-model="staffAccountForm.name" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Full name"><input v-model="staffAccountForm.email" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Email"><input v-model="staffAccountForm.password" type="password" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Password"><div class="grid gap-3 md:grid-cols-2"><select v-model="staffAccountForm.member_role" class="rounded-2xl border border-stone-300 px-4 py-3"><option value="hr">HR</option><option value="staff">Staff</option></select><input v-model="staffAccountForm.position" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Position"></div><button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="createStaffAccount">Create account</button></div>
            </SectionCard>
            <SectionCard title="Promote existing client account" description="Convert a client account into HR or staff.">
              <div class="grid gap-3"><select v-model="staffPromotionForm.user_id" class="rounded-2xl border border-stone-300 px-4 py-3"><option value="">Select client account</option><option v-for="candidate in data?.staff_candidates || []" :key="candidate.id" :value="candidate.id">{{ candidate.name }} · {{ candidate.email }}</option></select><div class="grid gap-3 md:grid-cols-2"><select v-model="staffPromotionForm.member_role" class="rounded-2xl border border-stone-300 px-4 py-3"><option value="staff">Staff</option><option value="hr">HR</option></select><input v-model="staffPromotionForm.position" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Position"></div><button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="promoteClientAccount">Promote account</button></div>
            </SectionCard>
          </div>
        </div>
      </template>

      <template v-else-if="active === 'schedule'">
        <div class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
          <SectionCard title="Workforce calendar" description="See when orders started, which staff is working, and the deadline.">
            <div class="space-y-4">
              <div v-for="(items, day) in scheduleCalendar" :key="day" class="rounded-3xl border border-stone-200 p-5">
                <div class="text-sm font-semibold uppercase tracking-[0.2em] text-stone-500">{{ day }}</div>
                <div class="mt-3 grid gap-3">
                  <div v-for="row in items" :key="row.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                    <div class="flex items-start justify-between gap-4"><div><div class="font-semibold text-stone-900">{{ row.user?.name || 'No staff' }}</div><div class="mt-1 text-sm text-stone-500">{{ row.order?.order_number || 'No linked order' }} · {{ row.shift_start || '—' }} to {{ row.shift_end || '—' }}</div></div><div class="text-right text-sm text-stone-500">Deadline<br><span class="font-semibold text-stone-900">{{ row.deadline_at ? new Date(row.deadline_at).toLocaleDateString() : (row.order?.due_date ? new Date(row.order.due_date).toLocaleDateString() : '—') }}</span></div></div>
                    <div class="mt-2 text-sm text-stone-600">{{ row.assignment_notes || 'No notes yet.' }}</div>
                  </div>
                </div>
              </div>
            </div>
          </SectionCard>
          <SectionCard title="Add schedule entry" description="Assign staff, link an order, and set deadline visibility.">
            <div class="grid gap-3"><select v-model="scheduleForm.user_id" class="rounded-2xl border border-stone-300 px-4 py-3"><option value="">Select staff</option><option v-for="member in data?.staff || []" :key="member.id" :value="member.id">{{ member.name }} ({{ member.role }})</option></select><select v-model="scheduleForm.order_id" class="rounded-2xl border border-stone-300 px-4 py-3"><option value="">Link order</option><option v-for="order in data?.orders || []" :key="order.id" :value="order.id">{{ order.order_number }}</option></select><input v-model="scheduleForm.shift_date" type="date" class="rounded-2xl border border-stone-300 px-4 py-3"><div class="grid gap-3 sm:grid-cols-2"><input v-model="scheduleForm.shift_start" type="time" class="rounded-2xl border border-stone-300 px-4 py-3"><input v-model="scheduleForm.shift_end" type="time" class="rounded-2xl border border-stone-300 px-4 py-3"></div><input v-model="scheduleForm.deadline_at" type="date" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Deadline"><textarea v-model="scheduleForm.assignment_notes" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Assigned production work"></textarea><button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="createSchedule">Save schedule</button></div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="active === 'delivery'">
        <div class="space-y-4">
          <SectionCard title="Add courier" description="Register delivery partners before assigning them to orders.">
            <div class="grid gap-3 xl:grid-cols-5"><input v-model="courierForm.name" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Courier name"><input v-model="courierForm.contact_person" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Contact person"><input v-model="courierForm.contact_number" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Contact number"><select v-model="courierForm.service_type" class="rounded-2xl border border-stone-300 px-4 py-3"><option value="delivery">Delivery</option><option value="pickup">Pickup</option><option value="both">Both</option></select><input v-model="courierForm.coverage_area" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Coverage area"></div><button class="mt-3 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="createCourier">Save courier</button>
          </SectionCard>
          <SectionCard title="Delivery & Pickup" description="Courier selection, pickup schedule, and tracking overview.">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3"><div v-for="row in data?.delivery_pickup || []" :key="row.order_id" class="rounded-3xl border border-stone-200 bg-stone-50 p-5"><div class="font-semibold text-stone-900">{{ row.order_number }}</div><div class="mt-1 text-sm text-stone-500">{{ row.customer }}</div><div class="mt-3 space-y-1 text-sm text-stone-600"><div>Courier: {{ row.courier || '—' }}</div><div>Delivery type: {{ row.delivery_type }}</div><div>Tracking: {{ row.tracking_reference || '—' }}</div><div>Status: {{ row.status }}</div></div></div></div>
          </SectionCard>
        </div>
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
        <SectionCard title="Client design requests" description="Marketplace is now focused only on client design requests and shop interaction.">
          <div class="grid gap-4 xl:grid-cols-2">
            <div v-for="request in data?.marketplace?.client_design_requests || []" :key="request.id" class="rounded-3xl border border-stone-200 bg-stone-50 p-5">
              <div class="flex items-start justify-between gap-4"><div><div class="text-lg font-semibold text-stone-900">{{ request.name || 'Design request' }}</div><div class="mt-1 text-sm text-stone-500">{{ request.client?.name || 'Client' }}</div></div><div class="text-right"><div class="text-xs uppercase tracking-[0.2em] text-stone-400">Budget signal</div><div class="mt-1 text-xl font-semibold text-stone-900">₱ {{ money(request.estimated_total_price) }}</div></div></div>
              <div class="mt-3 text-sm text-stone-600">{{ request.notes || 'No notes provided.' }}</div>
            </div>
          </div>
        </SectionCard>
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
        <div class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
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
                <label v-for="rule in governanceRules" :key="rule.key" class="rounded-2xl border border-stone-300 bg-stone-50 px-4 py-3 text-sm">
                  <input v-model="settingsForm.workflow_automation_settings_json[rule.key]" type="checkbox" class="mr-2">{{ rule.label }}
                  <div class="mt-1 text-xs text-stone-500">{{ rule.explanation }}</div>
                </label>
                <div class="grid gap-3 sm:grid-cols-2"><input v-model="settingsForm.delivery_defaults_json.preferred_courier" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Preferred courier"><input v-model="settingsForm.delivery_defaults_json.pickup_hours" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Pickup hours"></div>
                <div class="grid gap-3 sm:grid-cols-2"><input v-model="settingsForm.document_settings_json.invoice_format" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Invoice format"><input v-model="settingsForm.document_settings_json.quotation_format" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Quotation format"></div>
                <div class="grid gap-3 sm:grid-cols-2"><input v-model="settingsForm.approval_settings_json.discount_approver_role" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Discount approver"><input v-model="settingsForm.approval_settings_json.dispute_approver_role" class="rounded-2xl border border-stone-300 px-4 py-3" placeholder="Dispute approver"></div>
                <div class="flex flex-wrap gap-2">
                  <button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-medium text-white" :disabled="saving" @click="saveSettings">Save preferences</button>
                  <button class="rounded-2xl border border-stone-300 px-4 py-3 text-sm font-medium text-stone-700" :disabled="saving" @click="runNotificationMaintenance">Maintain notifications</button>
                </div>
              </div>
            </div>
          </SectionCard>
          <SectionCard title="Automation governance" description="Notification hygiene and automation controls running in your shop.">
            <div class="space-y-3 text-sm text-stone-600">
              <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="font-semibold text-stone-900">Notification lifecycle</div>
                <div class="mt-2">Unread {{ notificationLifecycle.unread || 0 }} · High priority {{ notificationLifecycle.high_priority || 0 }} · Deduplicated {{ notificationLifecycle.deduplicated || 0 }}</div>
              </div>
              <div v-for="rule in governanceRules" :key="`gov-${rule.key}`" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-center justify-between gap-3"><div class="font-medium text-stone-900">{{ rule.label }}</div><div class="text-xs uppercase tracking-[0.2em] text-stone-500">{{ rule.enabled ? 'enabled' : 'disabled' }}</div></div>
                <div class="mt-2">{{ rule.explanation }}</div>
              </div>
            </div>
          </SectionCard>
        </div>
      </template>
    </div>
  </AppWorkspaceLayout>
</template>
