<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { applyApiToken } from '@/bootstrap';
import AppWorkspaceLayout from '@/Layouts/AppWorkspaceLayout.vue';
import WorkspaceSidebar from '@/Components/Workspace/WorkspaceSidebar.vue';
import WorkspaceHeader from '@/Components/Workspace/WorkspaceHeader.vue';
import WorkspaceRightSidebar from '@/Components/Workspace/WorkspaceRightSidebar.vue';
import SectionCard from '@/Components/Workspace/SectionCard.vue';
import StatCard from '@/Components/Workspace/StatCard.vue';
import EmptyState from '@/Components/Workspace/EmptyState.vue';

const loading = ref(true);
const busy = ref(false);
const user = ref(null);
const workspace = ref(null);
const activeSection = ref('dashboard');
const selectedOrderId = ref(null);
const selectedProjectId = ref(null);
const selectedPostId = ref(null);
const selectedThreadId = ref(null);
const token = ref(window.localStorage.getItem('embro_token') || '');
const flash = reactive({ text: '', type: 'info' });
const activeOrderTab = ref('all');
const addressOptions = ref({ country: 'Philippines', province: 'Cavite', cities: {} });

const navItems = computed(() => [
  { key: 'dashboard', label: 'Dashboard' },
  { key: 'track-orders', label: 'Orders', badge: workspace.value?.track_orders?.orders?.length || null },
  { key: 'design-studio', label: 'Design Studio' },
  { key: 'proofing', label: 'Design Proofing & Price Quotation', badge: workspace.value?.design_proofing?.requests?.length || null },
  { key: 'marketplace', label: 'Marketplace', badge: workspace.value?.marketplace?.my_design_requests?.length || null },
  { key: 'projects', label: 'Projects', badge: workspace.value?.marketplace?.projects?.length || null },
  { key: 'message', label: 'Message', badge: workspace.value?.messages?.threads?.length || null },
  { key: 'profile', label: 'Profile' },
  { key: 'support', label: 'Support', badge: workspace.value?.support?.length || null },
  { key: 'notification', label: 'Notification', badge: workspace.value?.notifications?.summary?.unread_notifications || null },
]);

const pageTitle = computed(() => navItems.value.find((item) => item.key === activeSection.value)?.label || 'Client Workspace');
const sectionSubtitle = computed(() => ({
  dashboard: 'Order summary, owner projects, and quick action items in one dashboard.',
  'track-orders': 'Track every order by payment, processing, shipping, receiving, review, return, or cancellation state.',
  'design-studio': 'Prepare a design reference before posting publicly or requesting proofing from a shop.',
  proofing: 'Request design proofing and price quotation from a selected embroidery shop.',
  marketplace: 'Post a public design request with image upload and manage proposals inside your request details.',
  projects: 'Browse all owner-posted works, open details, and chat directly with the posting shop owner.',
  message: 'Project inquiries, order chats, and shop conversations.',
  profile: 'Personal, billing, delivery address, and payment preference settings.',
  support: 'Submit and monitor support concerns tied to your orders and requests.',
  notification: 'Review alerts, approvals, replies, and linked records.',
}[activeSection.value] || 'Client workspace.'));

const orderTabs = [
  { key: 'all', label: 'All' },
  { key: 'to_pay', label: 'To Pay' },
  { key: 'to_process', label: 'To Process' },
  { key: 'to_ship', label: 'To Ship' },
  { key: 'to_receive', label: 'To Receive' },
  { key: 'to_review', label: 'To Review' },
  { key: 'returns', label: 'Returns' },
  { key: 'cancellation', label: 'Cancellation' },
];

const designStudioForm = reactive({
  name: '', garment_type: '', placement_area: '', fabric_type: '', width_mm: '', height_mm: '', color_count: '', stitch_count_estimate: '', complexity_level: 'standard', quantity: '', design_type: 'logo_embroidery', notes: '',
});
const proofingForm = reactive({ design_id: '', shop_id: '', service_selection: 'logo_embroidery', description: '', upload_design_file: null });
const marketplaceForm = reactive({ title: '', description: '', design_type: 'logo', garment_type: '', quantity: '', target_budget: '', notes: '', image: null });
const messageForm = reactive({ shop_id: '', order_id: '', title: '', message: '', context_type: '', context_id: '' });
const supportForm = reactive({ shop_id: '', order_id: '', subject: '', category: 'support', priority: 'medium', message: '' });
const profileForm = reactive({ first_name: '', middle_name: '', last_name: '', email: '', phone_number: '', registration_date: '', billing_contact_name: '', billing_phone: '', billing_email: '', default_payment_method: '' });
const addressForm = reactive({ id: null, label: '', recipient_name: '', recipient_phone: '', city_municipality: '', barangay: '', house_street: '', other_house_information: '', postal_code: '', is_default: false });
const replyMessage = reactive({});

const orders = computed(() => workspace.value?.track_orders?.orders || []);
const selectedOrder = computed(() => orders.value.find((item) => item.id === selectedOrderId.value) || filteredOrders.value[0] || null);
const projects = computed(() => workspace.value?.marketplace?.projects || []);
const selectedProject = computed(() => projects.value.find((item) => item.id === selectedProjectId.value) || projects.value[0] || null);
const myPosts = computed(() => workspace.value?.marketplace?.my_design_requests || []);
const selectedPost = computed(() => myPosts.value.find((item) => item.id === selectedPostId.value) || myPosts.value[0] || null);
const threads = computed(() => workspace.value?.messages?.threads || []);
const selectedThread = computed(() => threads.value.find((item) => item.id === selectedThreadId.value) || threads.value[0] || null);
const payments = computed(() => workspace.value?.payment_methods || []);
const notifications = computed(() => workspace.value?.notifications?.items || []);
const filteredOrders = computed(() => {
  if (activeOrderTab.value === 'all') return orders.value;
  return orders.value.filter((order) => {
    const status = order.status || '';
    const paymentStatus = order.payment_status || '';
    const fulfillmentStatus = order.fulfillment?.status || '';
    switch (activeOrderTab.value) {
      case 'to_pay': return ['unpaid', 'partial'].includes(paymentStatus);
      case 'to_process': return ['pending', 'quoted', 'approved', 'in_production', 'on_hold'].includes(status);
      case 'to_ship': return ['ready', 'scheduled'].includes(fulfillmentStatus) || ['ready_for_pickup', 'shipped'].includes(status);
      case 'to_receive': return ['shipped', 'out_for_delivery'].includes(fulfillmentStatus);
      case 'to_review': return status === 'completed';
      case 'returns': return ['return_requested', 'returned'].includes(status);
      case 'cancellation': return status === 'cancelled';
      default: return true;
    }
  });
});
const currentBarangays = computed(() => addressOptions.value.cities?.[addressForm.city_municipality] || []);
const dashboardProjects = computed(() => workspace.value?.overview?.projects || projects.value);
const selectedProofRequest = computed(() => workspace.value?.design_proofing?.requests?.[0] || null);

watch(filteredOrders, (items) => {
  if (!items.length) {
    selectedOrderId.value = null;
    return;
  }
  if (!items.some((order) => order.id === selectedOrderId.value)) {
    selectedOrderId.value = items[0].id;
  }
}, { immediate: true });

watch(() => addressForm.city_municipality, (value) => {
  if (!currentBarangays.value.includes(addressForm.barangay)) {
    addressForm.barangay = '';
  }
  if (value === 'Bacoor' && !addressForm.postal_code) addressForm.postal_code = '4102';
  if (value === 'Imus' && !addressForm.postal_code) addressForm.postal_code = '4103';
  if (value === 'Dasmariñas' && !addressForm.postal_code) addressForm.postal_code = '4114';
});

function setFlash(text, type = 'info') { flash.text = text; flash.type = type; }
function money(value) { return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0)); }
function statusChip(status) { return (status || '').replaceAll('_', ' '); }
function errorMessage(err) {
  if (err?.response?.data?.errors) {
    const first = Object.values(err.response.data.errors)[0];
    if (Array.isArray(first) && first[0]) return first[0];
  }
  return err?.response?.data?.message || 'Something went wrong.';
}
function api(method, url, data = undefined, headers = {}) {
  applyApiToken(token.value);
  return window.axios({ method, url, data, headers });
}
function redirectForRole(role) {
  if (role === 'owner') window.location.href = '/owner-dashboard';
  else if (role === 'client') window.location.href = '/client-dashboard';
  else window.location.href = '/dashboard';
}
function normalizeDesignType(value) {
  const map = { logo_embroidery: 'logo', name_embroidery: 'other', patch_embroidery: 'patch', uniform_embroidery: 'uniform', cap_embroidery: 'cap', custom_design_embroidery: 'custom_art' };
  return map[value] || value || 'custom_art';
}

async function bootstrap() {
  try {
    const { data: me } = await api('get', '/api/auth/me');
    user.value = me;
    if (me?.role !== 'client') return redirectForRole(me?.role);
    await Promise.all([loadWorkspace(), loadProfile()]);
  } catch {
    window.localStorage.removeItem('embro_token');
    window.location.href = '/';
  } finally { loading.value = false; }
}

async function loadWorkspace() {
  const { data } = await api('get', '/api/client/workspace');
  workspace.value = data;
  if (!selectedOrderId.value) selectedOrderId.value = data.track_orders?.orders?.[0]?.id || null;
  if (!selectedProjectId.value) selectedProjectId.value = data.marketplace?.projects?.[0]?.id || null;
  if (!selectedPostId.value) selectedPostId.value = data.marketplace?.my_design_requests?.[0]?.id || null;
  if (!selectedThreadId.value) selectedThreadId.value = data.messages?.threads?.[0]?.id || null;
  if (!proofingForm.shop_id) proofingForm.shop_id = data.shops?.[0]?.id || '';
  if (!messageForm.shop_id) messageForm.shop_id = data.messages?.shops?.[0]?.id || '';
  if (!supportForm.shop_id) supportForm.shop_id = data.shops?.[0]?.id || '';
}

async function loadProfile() {
  const { data } = await api('get', '/api/client-profile');
  const profile = data.profile || {};
  addressOptions.value = data.address_options || addressOptions.value;
  Object.assign(profileForm, {
    first_name: profile.first_name || '', middle_name: profile.middle_name || '', last_name: profile.last_name || '', email: profile.email || user.value?.email || '', phone_number: profile.phone_number || '', registration_date: profile.registration_date || '', billing_contact_name: profile.billing_contact_name || '', billing_phone: profile.billing_phone || '', billing_email: profile.billing_email || '', default_payment_method: profile.default_payment_method || '',
  });
}

function resetAddressForm() {
  Object.assign(addressForm, { id: null, label: '', recipient_name: '', recipient_phone: '', city_municipality: '', barangay: '', house_street: '', other_house_information: '', postal_code: '', is_default: false });
}
function editAddress(address) {
  Object.assign(addressForm, {
    id: address.id, label: address.label || '', recipient_name: address.recipient_name || '', recipient_phone: address.recipient_phone || '', city_municipality: address.city_municipality || '', barangay: address.barangay || '', house_street: address.house_street || address.address_line || '', other_house_information: address.other_house_information || address.delivery_notes || '', postal_code: address.postal_code || '', is_default: !!address.is_default,
  });
}
function onMarketplaceFile(event) { marketplaceForm.image = event.target.files?.[0] || null; }
function onProofingFile(event) { proofingForm.upload_design_file = event.target.files?.[0] || null; }

async function saveDesignStudio() {
  busy.value = true;
  try {
    const payload = {
      name: designStudioForm.name,
      garment_type: designStudioForm.garment_type,
      placement_area: designStudioForm.placement_area,
      fabric_type: designStudioForm.fabric_type,
      width_mm: designStudioForm.width_mm || null,
      height_mm: designStudioForm.height_mm || null,
      color_count: designStudioForm.color_count || null,
      stitch_count_estimate: designStudioForm.stitch_count_estimate || null,
      complexity_level: designStudioForm.complexity_level,
      quantity: designStudioForm.quantity || null,
      design_type: normalizeDesignType(designStudioForm.design_type),
      notes: designStudioForm.notes,
    };
    const { data } = await api('post', '/api/design-customizations', payload);
    proofingForm.design_id = data.id;
    await loadWorkspace();
    setFlash('Design Studio draft saved.', 'success');
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function requestProofAndQuote() {
  busy.value = true;
  try {
    let designId = proofingForm.design_id;
    if (!designId) {
      const { data: draft } = await api('post', '/api/design-customizations', {
        name: designStudioForm.name || 'Imported design', garment_type: designStudioForm.garment_type, placement_area: designStudioForm.placement_area, fabric_type: designStudioForm.fabric_type, width_mm: designStudioForm.width_mm || null, height_mm: designStudioForm.height_mm || null, color_count: designStudioForm.color_count || null, stitch_count_estimate: designStudioForm.stitch_count_estimate || null, complexity_level: designStudioForm.complexity_level, quantity: designStudioForm.quantity || null, design_type: normalizeDesignType(designStudioForm.design_type), notes: proofingForm.description || designStudioForm.notes,
      });
      designId = draft.id;
      proofingForm.design_id = draft.id;
    }

    const formData = new FormData();
    formData.append('title', `${designStudioForm.name || 'Custom design'} proofing request`);
    formData.append('description', proofingForm.description || designStudioForm.notes || 'Client requested design proofing and quotation.');
    formData.append('design_type', normalizeDesignType(designStudioForm.design_type));
    formData.append('garment_type', designStudioForm.garment_type || '');
    formData.append('quantity', designStudioForm.quantity || '1');
    formData.append('target_budget', '0');
    formData.append('notes', `Service: ${proofingForm.service_selection}. ${proofingForm.description || ''}`.trim());
    if (proofingForm.upload_design_file) formData.append('reference_file', proofingForm.upload_design_file);

    const { data: designPost } = await api('post', '/api/design-posts', formData, { 'Content-Type': 'multipart/form-data' });
    await api('post', `/api/design-posts/${designPost.id}/select-shop`, { shop_id: Number(proofingForm.shop_id), convert_to_order: false });
    await api('put', `/api/design-customizations/${designId}`, { design_post_id: designPost.id, status: 'estimated', notes: proofingForm.description || designStudioForm.notes || '' });
    await loadWorkspace();
    setFlash('Design proofing and quotation request sent.', 'success');
    activeSection.value = 'proofing';
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function postToMarketplace() {
  busy.value = true;
  try {
    const formData = new FormData();
    formData.append('title', marketplaceForm.title || designStudioForm.name);
    formData.append('description', marketplaceForm.description || designStudioForm.notes || 'Public design request');
    formData.append('design_type', marketplaceForm.design_type || normalizeDesignType(designStudioForm.design_type));
    formData.append('garment_type', marketplaceForm.garment_type || designStudioForm.garment_type || '');
    formData.append('quantity', marketplaceForm.quantity || designStudioForm.quantity || '1');
    if (marketplaceForm.target_budget) formData.append('target_budget', marketplaceForm.target_budget);
    formData.append('notes', marketplaceForm.notes || 'Client marketplace request');
    formData.append('visibility', 'public');
    if (marketplaceForm.image) formData.append('reference_file', marketplaceForm.image);
    await api('post', '/api/design-posts', formData, { 'Content-Type': 'multipart/form-data' });
    Object.assign(marketplaceForm, { title: '', description: '', design_type: 'logo', garment_type: '', quantity: '', target_budget: '', notes: '', image: null });
    await loadWorkspace();
    setFlash('Public design request posted.', 'success');
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function sendProjectInquiry(project) {
  busy.value = true;
  try {
    await api('post', '/api/client/threads', { shop_id: project.shop_id, title: messageForm.title || `Project inquiry: ${project.title}`, message: messageForm.message || `Hello, I want to ask about your posted project “${project.title}”.`, context_type: 'shop_project', context_id: project.id });
    Object.assign(messageForm, { shop_id: project.shop_id, order_id: '', title: '', message: '', context_type: '', context_id: '' });
    await loadWorkspace();
    activeSection.value = 'message';
    setFlash('Project inquiry sent.', 'success');
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function createThread() {
  busy.value = true;
  try {
    await api('post', '/api/client/threads', { shop_id: Number(messageForm.shop_id), order_id: messageForm.order_id ? Number(messageForm.order_id) : null, title: messageForm.title, message: messageForm.message, context_type: messageForm.context_type || null, context_id: messageForm.context_id ? Number(messageForm.context_id) : null });
    Object.assign(messageForm, { shop_id: messageForm.shop_id, order_id: '', title: '', message: '', context_type: '', context_id: '' });
    await loadWorkspace();
    setFlash('Message thread created.', 'success');
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function sendReply(threadId) {
  busy.value = true;
  try {
    await api('post', `/api/client/threads/${threadId}/messages`, { message: replyMessage[threadId] || '' });
    replyMessage[threadId] = '';
    await loadWorkspace();
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function saveProfile() {
  busy.value = true;
  try {
    await api('put', '/api/client-profile', profileForm);
    await loadProfile();
    await loadWorkspace();
    setFlash('Profile information saved.', 'success');
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function saveAddress() {
  busy.value = true;
  try {
    const payload = { label: addressForm.label, recipient_name: addressForm.recipient_name, recipient_phone: addressForm.recipient_phone, city_municipality: addressForm.city_municipality, barangay: addressForm.barangay, house_street: addressForm.house_street, other_house_information: addressForm.other_house_information, postal_code: addressForm.postal_code, is_default: addressForm.is_default };
    if (addressForm.id) await api('put', `/api/client-profile/addresses/${addressForm.id}`, payload);
    else await api('post', '/api/client-profile/addresses', payload);
    resetAddressForm();
    await loadWorkspace();
    await loadProfile();
    setFlash('Address saved.', 'success');
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function savePaymentPreference() {
  busy.value = true;
  try {
    await api('put', '/api/client-profile', { default_payment_method: profileForm.default_payment_method });
    await loadProfile();
    setFlash('Payment preference saved.', 'success');
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function createSupportTicket() {
  busy.value = true;
  try {
    await api('post', '/api/client/support-tickets', { shop_id: supportForm.shop_id ? Number(supportForm.shop_id) : null, order_id: supportForm.order_id ? Number(supportForm.order_id) : null, subject: supportForm.subject, category: supportForm.category, priority: supportForm.priority, message: supportForm.message });
    Object.assign(supportForm, { shop_id: supportForm.shop_id, order_id: '', subject: '', category: 'support', priority: 'medium', message: '' });
    await loadWorkspace();
    setFlash('Support ticket submitted.', 'success');
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function cancelOrder(order) {
  const reason = window.prompt('Cancellation reason');
  if (!reason) return;
  busy.value = true;
  try {
    await api('post', `/api/orders/${order.id}/cancel`, { reason });
    await loadWorkspace();
    setFlash(`Order ${order.order_number} cancelled.`, 'success');
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function markNotificationRead(id) { try { await api('post', `/api/notifications/${id}/read`); await loadWorkspace(); } catch (err) { setFlash(errorMessage(err), 'error'); } }
function openNotification(item) {
  if (item.reference_type === 'order') { activeSection.value = 'track-orders'; selectedOrderId.value = item.reference_id; }
  else if (['design_proof', 'design_customization'].includes(item.reference_type)) activeSection.value = 'proofing';
  else if (item.reference_type === 'message_thread') activeSection.value = 'message';
  else if (item.reference_type === 'support_ticket') activeSection.value = 'support';
}
async function logout() { try { await api('post', '/api/auth/logout'); } catch {} localStorage.removeItem('embro_token'); window.location.href = '/'; }

onMounted(bootstrap);
</script>

<template>
  
  <Head title="Client Workspace" />

  <div v-if="loading" class="flex min-h-screen items-center justify-center bg-stone-100 text-stone-500">Loading workspace…</div>

  <AppWorkspaceLayout v-else>
    <template #sidebar>
      <WorkspaceSidebar :items="navItems" :active-key="activeSection" :user="user" @change="activeSection = $event" />
    </template>

    <template #header>
      <WorkspaceHeader eyebrow="Client workspace" :title="pageTitle" :subtitle="sectionSubtitle" :user="user" :show-profile="false" @logout="logout">
        <template #actions>
          <div v-if="flash.text" class="rounded-2xl px-4 py-2 text-sm font-medium" :class="flash.type === 'error' ? 'border border-rose-200 bg-rose-50 text-rose-700' : 'border border-emerald-200 bg-emerald-50 text-emerald-700'">{{ flash.text }}</div>
        </template>
      </WorkspaceHeader>
    </template>

    <template #right>
      <WorkspaceRightSidebar :notifications="notifications" :selected-order="selectedOrder" :assignments="[]" :revisions="workspace?.design_proofing?.requests || []" />
    </template>

    <div class="space-y-4">
      <template v-if="activeSection === 'dashboard'">
        <SectionCard title="Dashboard" description="Order focus, summary cards, and owner posted works.">
          <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Pending Quotes" :value="workspace?.overview?.stats?.pending_quotes || 0" />
            <StatCard label="Unpaid / Partial Orders" :value="workspace?.overview?.stats?.unpaid_partial_orders || 0" />
            <StatCard label="Design Approvals Needed" :value="workspace?.overview?.stats?.design_approvals_needed || 0" />
            <StatCard label="Delivery Tracking" :value="workspace?.overview?.stats?.delivery_tracking || 0" />
          </div>
        </SectionCard>

        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
          <SectionCard title="Orders in focus" description="Quick look at the most important orders.">
            <div v-if="orders.length" class="space-y-3">
              <button v-for="order in orders.slice(0, 4)" :key="order.id" class="w-full rounded-2xl border border-stone-200 bg-stone-50 p-4 text-left" @click="activeSection = 'track-orders'; selectedOrderId = order.id">
                <div class="flex items-center justify-between gap-3"><span class="font-semibold text-stone-900">{{ order.order_number }}</span><span class="rounded-full bg-white px-3 py-1 text-xs uppercase text-stone-600">{{ statusChip(order.status) }}</span></div>
                <div class="mt-2 text-sm text-stone-600">{{ order.shop?.shop_name || 'Shop' }} · {{ money(order.total_amount) }}</div>
              </button>
            </div>
            <EmptyState v-else title="No orders yet" description="Your order summary will appear here once you start requesting work." />
          </SectionCard>

          <SectionCard title="Selected order summary" description="Current order signal and next action.">
            <div v-if="workspace?.overview?.active_journey" class="rounded-2xl border border-stone-200 bg-stone-50 p-5 text-sm text-stone-700">
              <div class="text-lg font-semibold text-stone-900">{{ workspace.overview.active_journey.current_stage || 'Awaiting update' }}</div>
              <div class="mt-2">Handled by {{ workspace.overview.active_journey.handled_by || 'Production team' }}</div>
              <div class="mt-2">Estimated completion {{ workspace.overview.active_journey.estimated_completion || 'TBD' }}</div>
              <div class="mt-2">{{ workspace.overview.active_journey.recommended_action }}</div>
            </div>
            <EmptyState v-else title="No active order yet" description="Once you have an active order, the current journey summary will show here." />
          </SectionCard>
        </div>

        <SectionCard title="Owner posted projects" description="All posted works from owners are visible below. Open a project to see details and inquire directly.">
          <div v-if="dashboardProjects.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <button v-for="project in dashboardProjects" :key="project.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4 text-left" @click="selectedProjectId = project.id; activeSection = 'projects'">
              <div class="font-semibold text-stone-900">{{ project.title }}</div>
              <div class="mt-2 text-sm text-stone-600">{{ project.shop?.shop_name }}</div>
              <div class="mt-2 text-xs text-stone-500">{{ money(project.base_price) }} · {{ project.category }}</div>
            </button>
          </div>
          <EmptyState v-else title="No owner projects yet" description="Posted shop work will appear here when available." />
        </SectionCard>
      </template>

      <template v-else-if="activeSection === 'track-orders'">
        <SectionCard title="Orders" description="The order list now lives inside the tabbed order details area, with each status shown in one clean control center.">
          <div class="flex flex-wrap gap-2">
            <button v-for="tab in orderTabs" :key="tab.key" class="rounded-2xl px-4 py-2.5 text-sm font-medium transition" :class="activeOrderTab === tab.key ? 'bg-stone-900 text-white' : 'border border-stone-300 bg-white text-stone-700 hover:border-stone-500'" @click="activeOrderTab = tab.key">
              {{ tab.label }} <span class="ml-1 text-xs opacity-75">({{ workspace?.track_orders?.tabs?.[tab.key] || 0 }})</span>
            </button>
          </div>

          <div class="mt-4 space-y-4">
            <div v-if="filteredOrders.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              <button v-for="order in filteredOrders" :key="order.id" type="button" class="rounded-[1.75rem] border p-4 text-left transition" :class="selectedOrder?.id === order.id ? 'border-stone-900 bg-stone-900 text-white shadow-lg shadow-stone-300/40' : 'border-stone-200 bg-stone-50 text-stone-900 hover:border-stone-400 hover:bg-white'" @click="selectedOrderId = order.id">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm font-semibold">{{ order.order_number }}</div>
                    <div class="mt-1 text-xs uppercase tracking-[0.18em]" :class="selectedOrder?.id === order.id ? 'text-stone-300' : 'text-stone-500'">{{ statusChip(order.status) }}</div>
                  </div>
                  <span class="rounded-full px-3 py-1 text-[11px] font-medium" :class="selectedOrder?.id === order.id ? 'bg-white/10 text-white' : 'bg-white text-stone-600'">{{ money(order.total_amount) }}</span>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                  <div class="rounded-2xl border px-3 py-3" :class="selectedOrder?.id === order.id ? 'border-white/10 bg-white/5' : 'border-stone-200 bg-white'">
                    <div class="text-[11px] uppercase tracking-[0.18em]" :class="selectedOrder?.id === order.id ? 'text-stone-300' : 'text-stone-500'">Shop</div>
                    <div class="mt-1 text-sm font-medium">{{ order.shop?.shop_name || '—' }}</div>
                  </div>
                  <div class="rounded-2xl border px-3 py-3" :class="selectedOrder?.id === order.id ? 'border-white/10 bg-white/5' : 'border-stone-200 bg-white'">
                    <div class="text-[11px] uppercase tracking-[0.18em]" :class="selectedOrder?.id === order.id ? 'text-stone-300' : 'text-stone-500'">Payment</div>
                    <div class="mt-1 text-sm font-medium">{{ statusChip(order.payment_status) }}</div>
                  </div>
                  <div class="rounded-2xl border px-3 py-3 sm:col-span-2" :class="selectedOrder?.id === order.id ? 'border-white/10 bg-white/5' : 'border-stone-200 bg-white'">
                    <div class="text-[11px] uppercase tracking-[0.18em]" :class="selectedOrder?.id === order.id ? 'text-stone-300' : 'text-stone-500'">Fulfillment</div>
                    <div class="mt-1 text-sm font-medium">{{ statusChip(order.fulfillment?.status) }}</div>
                  </div>
                </div>
              </button>
            </div>
            <EmptyState v-else title="No orders in this tab" description="Switch tabs to view other order states." />

            <SectionCard :title="selectedOrder ? `Order details · ${selectedOrder.order_number}` : 'Order details'" description="Selected order summary, timeline, and direct actions stay below the tabbed order cards.">
              <div v-if="selectedOrder" class="space-y-4 text-sm text-stone-700">
                <div class="grid gap-3 md:grid-cols-4">
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4 md:col-span-2">
                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Shop</div>
                    <div class="mt-1 text-base font-semibold text-stone-900">{{ selectedOrder.shop?.shop_name || '—' }}</div>
                  </div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Order amount</div>
                    <div class="mt-1 text-base font-semibold text-stone-900">{{ money(selectedOrder.total_amount) }}</div>
                  </div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Current stage</div>
                    <div class="mt-1 text-base font-semibold text-stone-900">{{ statusChip(selectedOrder.current_stage || selectedOrder.status) }}</div>
                  </div>
                </div>

                <div class="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                    <div class="font-semibold text-stone-900">Order timeline</div>
                    <div class="mt-3 space-y-3">
                      <div v-for="log in selectedOrder.timeline || []" :key="log.id" class="rounded-2xl border border-stone-200 bg-white p-3">
                        <div class="flex items-center justify-between gap-3">
                          <div class="font-medium text-stone-900">{{ log.title }}</div>
                          <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">{{ statusChip(log.status) }}</div>
                        </div>
                        <div class="mt-1 text-stone-600">{{ log.description }}</div>
                        <div class="mt-2 text-xs text-stone-400">{{ log.created_at }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="space-y-4">
                    <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                      <div class="font-semibold text-stone-900">Order signals</div>
                      <div class="mt-3 space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3 rounded-2xl bg-white px-3 py-3"><span class="text-stone-500">Payment status</span><span class="font-medium text-stone-900">{{ statusChip(selectedOrder.payment_status) }}</span></div>
                        <div class="flex items-center justify-between gap-3 rounded-2xl bg-white px-3 py-3"><span class="text-stone-500">Fulfillment</span><span class="font-medium text-stone-900">{{ statusChip(selectedOrder.fulfillment?.status) }}</span></div>
                        <div class="flex items-center justify-between gap-3 rounded-2xl bg-white px-3 py-3"><span class="text-stone-500">Assigned staff</span><span class="font-medium text-stone-900">{{ selectedOrder.assignments?.length || 0 }}</span></div>
                      </div>
                    </div>

                    <div class="rounded-2xl border border-stone-200 bg-white p-4">
                      <div class="font-semibold text-stone-900">Actions</div>
                      <div class="mt-3 flex flex-wrap gap-2">
                        <button v-if="selectedOrder.self_service?.can_cancel" class="rounded-2xl border border-rose-300 px-4 py-2 text-sm text-rose-700" @click="cancelOrder(selectedOrder)">Cancel order</button>
                        <button v-if="selectedOrder.self_service?.can_message_shop" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm text-stone-700" @click="activeSection = 'message'; messageForm.shop_id = selectedOrder.shop_id; messageForm.order_id = selectedOrder.id; messageForm.title = `Order ${selectedOrder.order_number}`">Message shop</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <EmptyState v-else title="Select an order" description="Choose an order card from the selected tab to view details." />
            </SectionCard>
          </div>
        </SectionCard>
      </template>

      <template v-else-if="activeSection === 'design-studio'">
        <div class="grid gap-4 xl:grid-cols-[1fr_1fr]">
          <SectionCard title="Design Studio" description="Imported design details that can be reused for proofing or public posting.">
            <div class="grid gap-3 md:grid-cols-2">
              <input v-model="designStudioForm.name" class="rounded-2xl border-stone-300" placeholder="Design name">
              <input v-model="designStudioForm.garment_type" class="rounded-2xl border-stone-300" placeholder="Garment type">
              <input v-model="designStudioForm.placement_area" class="rounded-2xl border-stone-300" placeholder="Placement area">
              <input v-model="designStudioForm.fabric_type" class="rounded-2xl border-stone-300" placeholder="Fabric type">
              <input v-model="designStudioForm.width_mm" type="number" class="rounded-2xl border-stone-300" placeholder="Width mm">
              <input v-model="designStudioForm.height_mm" type="number" class="rounded-2xl border-stone-300" placeholder="Height mm">
              <input v-model="designStudioForm.color_count" type="number" class="rounded-2xl border-stone-300" placeholder="Color count">
              <input v-model="designStudioForm.stitch_count_estimate" type="number" class="rounded-2xl border-stone-300" placeholder="Stitch estimate">
              <select v-model="designStudioForm.complexity_level" class="rounded-2xl border-stone-300"><option value="simple">Simple</option><option value="standard">Standard</option><option value="complex">Complex</option><option value="premium">Premium</option></select>
              <input v-model="designStudioForm.quantity" type="number" class="rounded-2xl border-stone-300" placeholder="Quantity">
            </div>
            <textarea v-model="designStudioForm.notes" class="mt-3 w-full rounded-2xl border-stone-300" rows="5" placeholder="Design description / notes"></textarea>
            <button class="mt-4 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="saveDesignStudio">Save design studio draft</button>
          </SectionCard>

          <SectionCard title="Quick actions" description="Use the current design draft in the next flow.">
            <div class="space-y-3 text-sm text-stone-700">
              <button class="w-full rounded-2xl border border-stone-300 px-4 py-3 text-left" @click="activeSection = 'proofing'">Go to Design Proofing & Price Quotation</button>
              <button class="w-full rounded-2xl border border-stone-300 px-4 py-3 text-left" @click="activeSection = 'marketplace'">Go to Marketplace request posting</button>
              <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">Latest draft count: {{ workspace?.design_studio?.drafts?.length || 0 }}</div>
            </div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'proofing'">
        <div class="grid gap-4 xl:grid-cols-[1.02fr_0.98fr]">
          <SectionCard title="Design Proofing & Price Quotation" description="Imported design view, service selection, optional file upload, and direct shop-based proofing request connected to the owner side.">
            <div class="rounded-[1.75rem] border border-stone-200 bg-stone-50 p-4">
              <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                  <div class="text-sm font-semibold text-stone-900">Imported design</div>
                  <div class="text-xs text-stone-500">Use your current Design Studio draft or pick another saved draft.</div>
                </div>
                <button type="button" class="rounded-2xl border border-stone-300 px-3 py-2 text-xs font-medium text-stone-700" @click="activeSection = 'design-studio'">Open Design Studio</button>
              </div>
              <div class="grid gap-3 md:grid-cols-2">
                <select v-model="proofingForm.design_id" class="rounded-2xl border-stone-300 md:col-span-2">
                  <option value="">Use current Design Studio draft</option>
                  <option v-for="draft in workspace?.design_studio?.drafts || []" :key="draft.id" :value="draft.id">{{ draft.name }}</option>
                </select>
                <div class="rounded-2xl border border-stone-200 bg-white p-4">
                  <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Draft name</div>
                  <div class="mt-1 text-sm font-semibold text-stone-900">{{ workspace?.design_studio?.latest_design?.name || 'No imported design yet' }}</div>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-white p-4">
                  <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Service basis</div>
                  <div class="mt-1 text-sm font-semibold text-stone-900">{{ workspace?.design_studio?.latest_design?.design_type || 'Select a design first' }}</div>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-white p-4">
                  <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Estimated size</div>
                  <div class="mt-1 text-sm font-semibold text-stone-900">{{ workspace?.design_studio?.latest_design?.width_mm || '—' }} × {{ workspace?.design_studio?.latest_design?.height_mm || '—' }} mm</div>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-white p-4">
                  <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Colors / quantity</div>
                  <div class="mt-1 text-sm font-semibold text-stone-900">{{ workspace?.design_studio?.latest_design?.color_count || '—' }} color(s) · {{ workspace?.design_studio?.latest_design?.quantity || '—' }} pc(s)</div>
                </div>
              </div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
              <select v-model="proofingForm.shop_id" class="rounded-2xl border-stone-300">
                <option value="">Select shop</option>
                <option v-for="shop in workspace?.shops || []" :key="shop.id" :value="shop.id">{{ shop.shop_name }}</option>
              </select>
              <select v-model="proofingForm.service_selection" class="rounded-2xl border-stone-300">
                <option value="logo_embroidery">Logo embroidery</option>
                <option value="name_embroidery">Name embroidery</option>
                <option value="patch_embroidery">Patch embroidery</option>
                <option value="uniform_embroidery">Uniform embroidery</option>
                <option value="cap_embroidery">Cap embroidery</option>
                <option value="custom_design_embroidery">Custom design embroidery</option>
              </select>
            </div>
            <textarea v-model="proofingForm.description" class="mt-3 w-full rounded-2xl border-stone-300" rows="5" placeholder="Design description"></textarea>
            <label class="mt-3 block rounded-2xl border border-dashed border-stone-300 p-4 text-sm text-stone-600">Upload Design File (optional)
              <input type="file" class="mt-2 block w-full text-sm" @change="onProofingFile">
            </label>
            <button class="mt-4 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="requestProofAndQuote">Request Design Proofing and Price Quotation</button>
          </SectionCard>

          <SectionCard title="Proofing request history" description="Track requests already sent to owners, including shop connection and current estimate signal.">
            <div v-if="workspace?.design_proofing?.requests?.length" class="space-y-3">
              <div v-for="request in workspace.design_proofing.requests" :key="request.id" class="rounded-[1.75rem] border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="font-semibold text-stone-900">{{ request.name }}</div>
                    <div class="mt-1 text-xs uppercase tracking-[0.18em] text-stone-500">{{ statusChip(request.status) }}</div>
                  </div>
                  <div class="rounded-full bg-white px-3 py-1 text-xs font-medium text-stone-600">{{ money(request.estimated_total_price) }}</div>
                </div>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                  <div class="rounded-2xl border border-stone-200 bg-white p-3">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Selected shop</div>
                    <div class="mt-1 text-sm font-medium text-stone-900">{{ request.design_post?.selected_shop?.shop_name || request.order?.shop?.shop_name || 'Pending shop confirmation' }}</div>
                  </div>
                  <div class="rounded-2xl border border-stone-200 bg-white p-3">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Latest quote count</div>
                    <div class="mt-1 text-sm font-medium text-stone-900">{{ request.quote_history?.length || 0 }}</div>
                  </div>
                </div>
              </div>
            </div>
            <EmptyState v-else title="No proofing requests yet" description="Submit a proofing request to connect your design to a shop owner." />
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'marketplace'">
        <div class="grid gap-4 xl:grid-cols-[0.98fr_1.02fr]">
          <SectionCard title="Community posts request" description="Post a public client design request with an uploaded reference image so shops can respond.">
            <div class="grid gap-3 md:grid-cols-2">
              <input v-model="marketplaceForm.title" class="rounded-2xl border-stone-300" placeholder="Request title">
              <input v-model="marketplaceForm.garment_type" class="rounded-2xl border-stone-300" placeholder="Garment type">
              <select v-model="marketplaceForm.design_type" class="rounded-2xl border-stone-300"><option value="logo">Logo</option><option value="uniform">Uniform</option><option value="cap">Cap</option><option value="patch">Patch</option><option value="custom_art">Custom art</option><option value="other">Other</option></select>
              <input v-model="marketplaceForm.quantity" type="number" class="rounded-2xl border-stone-300" placeholder="Quantity">
              <input v-model="marketplaceForm.target_budget" type="number" class="rounded-2xl border-stone-300 md:col-span-2" placeholder="Target budget">
            </div>
            <textarea v-model="marketplaceForm.description" class="mt-3 w-full rounded-2xl border-stone-300" rows="4" placeholder="Describe the request"></textarea>
            <textarea v-model="marketplaceForm.notes" class="mt-3 w-full rounded-2xl border-stone-300" rows="3" placeholder="Other notes"></textarea>
            <label class="mt-3 block rounded-[1.75rem] border border-dashed border-stone-300 bg-stone-50 p-4 text-sm text-stone-600">Upload design image or file
              <input type="file" class="mt-2 block w-full text-sm" @change="onMarketplaceFile">
            </label>
            <button class="mt-4 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="postToMarketplace">Post request publicly</button>
          </SectionCard>

          <SectionCard title="Your posted requests" description="Your own request details are separated here, where proposals and bargaining can be reviewed inside each post.">
            <div class="space-y-4">
              <div v-if="myPosts.length" class="grid gap-4 lg:grid-cols-2">
                <button v-for="post in myPosts" :key="post.id" class="rounded-[1.75rem] border px-4 py-4 text-left transition" :class="selectedPost?.id === post.id ? 'border-stone-900 bg-stone-900 text-white shadow-lg shadow-stone-300/40' : 'border-stone-200 bg-stone-50 text-stone-900 hover:border-stone-400 hover:bg-white'" @click="selectedPostId = post.id">
                  <div class="flex items-center justify-between gap-3">
                    <div class="font-semibold">{{ post.title }}</div>
                    <span class="rounded-full px-3 py-1 text-[11px] font-medium" :class="selectedPost?.id === post.id ? 'bg-white/10 text-white' : 'bg-white text-stone-600'">{{ post.applications?.length || 0 }} proposal(s)</span>
                  </div>
                  <div class="mt-2 text-sm opacity-80 line-clamp-2">{{ post.description }}</div>
                  <div class="mt-3 text-xs uppercase tracking-[0.18em] opacity-75">{{ statusChip(post.status) }}</div>
                </button>
              </div>
              <EmptyState v-else title="No posted requests yet" description="Your posted marketplace requests will appear here after publishing." />

              <div v-if="selectedPost" class="rounded-[1.75rem] border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-lg font-semibold text-stone-900">{{ selectedPost.title }}</div>
                    <div class="mt-1 text-sm text-stone-600">{{ selectedPost.description }}</div>
                  </div>
                  <div class="rounded-full bg-white px-3 py-1 text-xs font-medium text-stone-600">{{ money(selectedPost.target_budget) }}</div>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                  <div class="rounded-2xl border border-stone-200 bg-white p-3">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Selected shop</div>
                    <div class="mt-1 text-sm font-medium text-stone-900">{{ selectedPost.selected_shop?.shop_name || 'None yet' }}</div>
                  </div>
                  <div class="rounded-2xl border border-stone-200 bg-white p-3">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Status</div>
                    <div class="mt-1 text-sm font-medium text-stone-900">{{ statusChip(selectedPost.status) }}</div>
                  </div>
                </div>
                <div class="mt-4">
                  <div class="mb-2 text-sm font-semibold text-stone-900">Proposals & bargaining</div>
                  <div v-if="selectedPost.applications?.length" class="space-y-2">
                    <div v-for="application in selectedPost.applications" :key="application.id" class="rounded-2xl border border-stone-200 bg-white p-3">
                      <div class="flex items-center justify-between gap-3"><div class="font-medium text-stone-900">{{ application.shop?.shop_name }}</div><div class="text-xs uppercase text-stone-500">{{ statusChip(application.status) }}</div></div>
                      <div class="mt-1 text-sm text-stone-600">{{ application.message || 'No proposal message.' }}</div>
                      <div class="mt-1 text-xs text-stone-500">Proposed {{ money(application.proposed_price) }} · {{ application.estimated_days || '—' }} day(s)</div>
                    </div>
                  </div>
                  <EmptyState v-else title="No proposals yet" description="Shops will appear here once they submit a proposal or bargaining offer." />
                </div>
              </div>
            </div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'projects'">
        <div class="grid gap-4 xl:grid-cols-[0.92fr_1.08fr]">
          <SectionCard title="Posted works from owners" description="Browse all public owner projects, then open one to view details and message the shop owner directly.">
            <div class="space-y-3">
              <button v-for="project in projects" :key="project.id" class="w-full rounded-[1.75rem] border px-4 py-4 text-left transition" :class="selectedProject?.id === project.id ? 'border-stone-900 bg-stone-900 text-white shadow-lg shadow-stone-300/40' : 'border-stone-200 bg-stone-50 text-stone-900 hover:border-stone-400 hover:bg-white'" @click="selectedProjectId = project.id">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="font-semibold">{{ project.title }}</div>
                    <div class="mt-1 text-sm opacity-80">{{ project.shop?.shop_name }}</div>
                  </div>
                  <span class="rounded-full px-3 py-1 text-[11px] font-medium" :class="selectedProject?.id === project.id ? 'bg-white/10 text-white' : 'bg-white text-stone-600'">{{ money(project.base_price) }}</span>
                </div>
                <div class="mt-3 grid gap-2 text-xs uppercase tracking-[0.18em] opacity-75 sm:grid-cols-2">
                  <div>{{ project.category || 'Project' }}</div>
                  <div>Min {{ project.min_order_qty || '—' }}</div>
                </div>
              </button>
            </div>
          </SectionCard>

          <SectionCard :title="selectedProject?.title || 'Project detail'" description="Detailed project information, owner details, and direct inquiry to the posting shop owner.">
            <div v-if="selectedProject" class="space-y-4">
              <div class="rounded-[1.75rem] border border-stone-200 bg-stone-50 p-4 text-sm text-stone-700">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-lg font-semibold text-stone-900">{{ selectedProject.title }}</div>
                    <div class="mt-1 text-sm text-stone-600">{{ selectedProject.shop?.shop_name }}</div>
                  </div>
                  <div class="rounded-full bg-white px-3 py-1 text-xs font-medium text-stone-600">{{ money(selectedProject.base_price) }}</div>
                </div>
                <div class="mt-4">{{ selectedProject.description }}</div>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                  <div class="rounded-2xl border border-stone-200 bg-white p-3"><div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Category</div><div class="mt-1 font-medium text-stone-900">{{ selectedProject.category || '—' }}</div></div>
                  <div class="rounded-2xl border border-stone-200 bg-white p-3"><div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Minimum order</div><div class="mt-1 font-medium text-stone-900">{{ selectedProject.min_order_qty || '—' }}</div></div>
                  <div class="rounded-2xl border border-stone-200 bg-white p-3"><div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Turnaround</div><div class="mt-1 font-medium text-stone-900">{{ selectedProject.turnaround_days || '—' }} day(s)</div></div>
                  <div class="rounded-2xl border border-stone-200 bg-white p-3"><div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Embroidery size / canvas</div><div class="mt-1 font-medium text-stone-900">{{ selectedProject.embroidery_size || '—' }} · {{ selectedProject.canvas_used || '—' }}</div></div>
                </div>
              </div>
              <div class="rounded-2xl border border-stone-200 bg-white p-4">
                <div class="mb-2 text-sm font-semibold text-stone-900">Chat with shop owner</div>
                <input v-model="messageForm.title" class="w-full rounded-2xl border-stone-300" :placeholder="`Inquiry about ${selectedProject.title}`">
                <textarea v-model="messageForm.message" class="mt-3 w-full rounded-2xl border-stone-300" rows="4" placeholder="Write your question about this project"></textarea>
                <button class="mt-3 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="sendProjectInquiry(selectedProject)">Send inquiry to owner</button>
              </div>
            </div>
            <EmptyState v-else title="Select a project" description="Choose a posted work from the left to see details." />
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'message'">
        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
          <SectionCard title="Start a message" description="Create a new order or project inquiry message.">
            <div class="grid gap-3 md:grid-cols-2">
              <select v-model="messageForm.shop_id" class="rounded-2xl border-stone-300"><option value="">Select shop</option><option v-for="shop in workspace?.messages?.shops || []" :key="shop.id" :value="shop.id">{{ shop.shop_name }}</option></select>
              <select v-model="messageForm.order_id" class="rounded-2xl border-stone-300"><option value="">General / project inquiry</option><option v-for="order in orders.filter((item) => String(item.shop_id) === String(messageForm.shop_id))" :key="order.id" :value="order.id">{{ order.order_number }}</option></select>
              <input v-model="messageForm.title" class="rounded-2xl border-stone-300 md:col-span-2" placeholder="Conversation title">
            </div>
            <textarea v-model="messageForm.message" class="mt-3 w-full rounded-2xl border-stone-300" rows="5" placeholder="Write your message"></textarea>
            <button class="mt-4 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="createThread">Send message</button>
          </SectionCard>

          <SectionCard :title="selectedThread?.title || 'Message threads'" description="Order-linked and inquiry conversations.">
            <div class="mb-4 flex gap-2 overflow-x-auto pb-2">
              <button v-for="thread in threads" :key="thread.id" class="rounded-2xl px-4 py-2 text-sm" :class="selectedThread?.id === thread.id ? 'bg-stone-900 text-white' : 'border border-stone-300 bg-white text-stone-700'" @click="selectedThreadId = thread.id">{{ thread.title }}</button>
            </div>
            <div v-if="selectedThread" class="space-y-3">
              <div v-for="message in selectedThread.messages" :key="message.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="text-sm font-semibold text-stone-900">{{ message.sender?.name }}</div>
                <div class="mt-1 text-sm text-stone-600">{{ message.message }}</div>
              </div>
              <div class="flex gap-2">
                <input v-model="replyMessage[selectedThread.id]" class="flex-1 rounded-2xl border-stone-300" placeholder="Reply">
                <button class="rounded-2xl bg-stone-900 px-4 py-2.5 text-sm font-semibold text-white" :disabled="busy || !replyMessage[selectedThread.id]" @click="sendReply(selectedThread.id)">Reply</button>
              </div>
            </div>
            <EmptyState v-else title="No messages yet" description="Start a new message to see it here." />
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'profile'">
        <div class="grid gap-4 xl:grid-cols-[1fr_1fr]">
          <SectionCard title="Personal & billing information" description="Save personal details and default payment preference.">
            <div class="grid gap-3 md:grid-cols-2">
              <input v-model="profileForm.first_name" class="rounded-2xl border-stone-300" placeholder="First name">
              <input v-model="profileForm.middle_name" class="rounded-2xl border-stone-300" placeholder="Middle name">
              <input v-model="profileForm.last_name" class="rounded-2xl border-stone-300" placeholder="Last name">
              <input v-model="profileForm.email" type="email" class="rounded-2xl border-stone-300" placeholder="Email">
              <input v-model="profileForm.phone_number" class="rounded-2xl border-stone-300" placeholder="09XXXXXXXXX or 0XX XXXXXXX">
              <input v-model="profileForm.registration_date" type="date" class="rounded-2xl border-stone-300" disabled>
              <input v-model="profileForm.billing_contact_name" class="rounded-2xl border-stone-300" placeholder="Billing contact name">
              <input v-model="profileForm.billing_phone" class="rounded-2xl border-stone-300" placeholder="Billing phone">
              <input v-model="profileForm.billing_email" type="email" class="rounded-2xl border-stone-300 md:col-span-2" placeholder="Billing email">
            </div>
            <button class="mt-4 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="saveProfile">Save profile information</button>

            <div class="mt-6 rounded-2xl border border-stone-200 bg-stone-50 p-4">
              <div class="mb-2 text-sm font-semibold text-stone-900">Payment references</div>
              <select v-model="profileForm.default_payment_method" class="w-full rounded-2xl border-stone-300">
                <option value="">Choose default payment method</option>
                <option v-for="payment in payments" :key="payment.id" :value="payment.method_type">{{ payment.label }} - {{ payment.method_type }}</option>
              </select>
              <button class="mt-3 rounded-2xl border border-stone-300 px-4 py-3 text-sm font-medium text-stone-700" :disabled="busy" @click="savePaymentPreference">Save payment preferences</button>
            </div>
          </SectionCard>

          <SectionCard title="Delivery addresses" description="Default Cavite delivery addresses with city and barangay filtering.">
            <div class="grid gap-3 md:grid-cols-2">
              <input v-model="addressForm.label" class="rounded-2xl border-stone-300" placeholder="Label e.g. Home / Office">
              <input v-model="addressForm.recipient_name" class="rounded-2xl border-stone-300" placeholder="Recipient">
              <input v-model="addressForm.recipient_phone" class="rounded-2xl border-stone-300" placeholder="Phone">
              <input class="rounded-2xl border-stone-300 bg-stone-100" :value="addressOptions.country" disabled>
              <input class="rounded-2xl border-stone-300 bg-stone-100" :value="addressOptions.province" disabled>
              <select v-model="addressForm.city_municipality" class="rounded-2xl border-stone-300"><option value="">Select city / municipality</option><option v-for="(barangays, city) in addressOptions.cities" :key="city" :value="city">{{ city }}</option></select>
              <select v-model="addressForm.barangay" class="rounded-2xl border-stone-300 md:col-span-2"><option value="">Select barangay</option><option v-for="barangay in currentBarangays" :key="barangay" :value="barangay">{{ barangay }}</option></select>
              <input v-model="addressForm.house_street" class="rounded-2xl border-stone-300 md:col-span-2" placeholder="House number / street">
              <input v-model="addressForm.other_house_information" class="rounded-2xl border-stone-300 md:col-span-2" placeholder="Other house information">
              <input v-model="addressForm.postal_code" class="rounded-2xl border-stone-300" placeholder="Postal code">
              <label class="flex items-center rounded-2xl border border-stone-300 bg-stone-50 px-4 py-3 text-sm"><input v-model="addressForm.is_default" type="checkbox" class="mr-2">Set as default address</label>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
              <button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="saveAddress">Save address</button>
              <button class="rounded-2xl border border-stone-300 px-4 py-3 text-sm font-medium text-stone-700" @click="resetAddressForm">Clear</button>
            </div>
            <div class="mt-6 space-y-3">
              <div v-for="address in workspace?.client_profile?.addresses || []" :key="address.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-center justify-between gap-3"><div class="font-semibold text-stone-900">{{ address.label }} <span v-if="address.is_default" class="ml-2 text-xs text-emerald-700">Default</span></div><button class="text-xs text-stone-600" @click="editAddress(address)">Edit</button></div>
                <div class="mt-2 text-sm text-stone-600">{{ address.recipient_name }} · {{ address.recipient_phone }}</div>
                <div class="mt-1 text-sm text-stone-600">{{ address.house_street }}, {{ address.barangay }}, {{ address.city_municipality }}, Cavite {{ address.postal_code }}</div>
              </div>
            </div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'support'">
        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
          <SectionCard title="Support" description="Submit a support request tied to orders, delivery, payments, or design issues.">
            <div class="grid gap-3 md:grid-cols-2">
              <select v-model="supportForm.shop_id" class="rounded-2xl border-stone-300"><option value="">Select shop</option><option v-for="shop in workspace?.shops || []" :key="shop.id" :value="shop.id">{{ shop.shop_name }}</option></select>
              <select v-model="supportForm.order_id" class="rounded-2xl border-stone-300"><option value="">Order optional</option><option v-for="order in orders" :key="order.id" :value="order.id">{{ order.order_number }}</option></select>
              <input v-model="supportForm.subject" class="rounded-2xl border-stone-300 md:col-span-2" placeholder="Subject">
              <select v-model="supportForm.category" class="rounded-2xl border-stone-300"><option value="orders">Orders</option><option value="quotes">Quotes</option><option value="payments">Payments</option><option value="production">Production</option><option value="delivery">Delivery</option><option value="support">Support</option></select>
              <select v-model="supportForm.priority" class="rounded-2xl border-stone-300"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select>
            </div>
            <textarea v-model="supportForm.message" class="mt-3 w-full rounded-2xl border-stone-300" rows="5" placeholder="Describe the issue"></textarea>
            <button class="mt-4 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="createSupportTicket">Submit support ticket</button>
          </SectionCard>

          <SectionCard title="Support history" description="Track your submitted support tickets and status.">
            <div v-if="workspace?.support?.length" class="space-y-3">
              <article v-for="ticket in workspace.support" :key="ticket.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-start justify-between gap-3"><div><div class="text-sm font-semibold text-stone-900">{{ ticket.subject }}</div><div class="mt-1 text-xs text-stone-500">{{ ticket.shop?.shop_name || 'Platform support' }} · {{ ticket.order?.order_number || 'No order linked' }}</div></div><span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-stone-700">{{ ticket.status }}</span></div>
                <div class="mt-2 text-sm text-stone-600">{{ ticket.message }}</div>
              </article>
            </div>
            <EmptyState v-else title="No support tickets yet" description="Open a support case when you need help." />
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'notification'">
        <div class="grid gap-4 xl:grid-cols-[0.85fr_1.15fr]">
          <SectionCard title="Notification summary" description="Unread, approvals, and urgent counts.">
            <div class="grid gap-4 md:grid-cols-2">
              <StatCard label="Urgent alerts" :value="workspace?.notifications?.summary?.urgent_alerts || 0" />
              <StatCard label="Pending approvals" :value="workspace?.notifications?.summary?.pending_approvals || 0" />
              <StatCard label="Unread" :value="workspace?.notifications?.summary?.unread_notifications || 0" />
              <StatCard label="Deduplicated" :value="workspace?.notifications?.summary?.deduplicated || 0" />
            </div>
          </SectionCard>
          <SectionCard title="Notification feed" description="Open linked records from one list.">
            <div class="space-y-3">
              <div v-for="item in notifications" :key="item.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-center justify-between gap-3"><div class="font-semibold text-stone-900">{{ item.title }}</div><div class="text-xs uppercase tracking-[0.2em] text-stone-500">{{ item.priority }}</div></div>
                <div class="mt-2 text-sm text-stone-600">{{ item.message }}</div>
                <div class="mt-3 flex gap-2"><button class="rounded-2xl border border-stone-900 px-3 py-2 text-xs text-stone-900" @click="openNotification(item)">{{ item.action_label || 'Open' }}</button><button v-if="!item.is_read" class="rounded-2xl border border-stone-300 px-3 py-2 text-xs text-stone-700" @click="markNotificationRead(item.id)">Mark read</button></div>
              </div>
            </div>
          </SectionCard>
        </div>
      </template>
    </div>
  </AppWorkspaceLayout>
</template>
