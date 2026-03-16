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
import EmptyState from '@/Components/Workspace/EmptyState.vue';

const loading = ref(true);
const busy = ref(false);
const user = ref(null);
const workspace = ref(null);
const activeSection = ref('overview');
const flash = reactive({ type: 'info', text: '' });
const token = ref(window.localStorage.getItem('embro_token') || '');
const selectedOrderId = ref(null);
const selectedDesignId = ref(null);
const notificationFilter = reactive({ category: '', priority: '', unreadOnly: false });

const paymentMethodForm = reactive({ label: '', method_type: 'gcash', account_name: '', account_number: '', provider: '', instructions: '', is_default: false });
const designStudioForm = reactive({
  name: '',
  garment_type: 'Polo Shirt',
  placement_area: 'left_chest',
  fabric_type: 'cotton',
  width_mm: 80,
  height_mm: 80,
  color_count: 3,
  stitch_count_estimate: 5000,
  complexity_level: 'standard',
  quantity: 10,
  design_type: 'logo_embroidery',
  canvas_type: 'rectangle',
  canvas_color: '#ffffff',
  canvas_size: 'medium',
  thread_color: '#111827',
  text: '',
  font_family: 'Inter',
  font_size: 28,
  notes: '',
});
const quoteForm = reactive({ design_id: '', shop_id: '', service_selection: 'logo_embroidery', description: '', upload_design_file: '', request_type: 'proof_and_quote' });
const designPostForm = reactive({ title: '', description: '', design_type: 'logo', garment_type: 'Polo Shirt', quantity: 10, target_budget: 2000, notes: '' });
const messageForm = reactive({ shop_id: '', order_id: '', title: '', message: '' });
const replyMessage = reactive({});
const supportForm = reactive({ shop_id: '', order_id: '', subject: '', category: 'support', priority: 'medium', message: '' });

const navItems = computed(() => [
  { key: 'overview', label: 'Overview' },
  { key: 'track-orders', label: 'Track Orders', badge: workspace.value?.track_orders?.orders?.length || null },
  { key: 'payment-methods', label: 'Payment Methods' },
  { key: 'design-studio', label: 'Design Studio' },
  { key: 'proofing', label: 'Design Proofing & Price Quotation', badge: workspace.value?.design_proofing?.requests?.length || null },
  { key: 'marketplace', label: 'Marketplace', badge: workspace.value?.marketplace?.projects?.length || null },
  { key: 'message', label: 'Message', badge: workspace.value?.messages?.threads?.length || null },
  { key: 'support', label: 'Support', badge: workspace.value?.support?.filter((ticket) => ticket.status !== 'resolved' && ticket.status !== 'closed')?.length || null },
  { key: 'notification', label: 'Notification', badge: workspace.value?.notifications?.summary?.unread_notifications || null },
]);

const pageTitle = computed(() => navItems.value.find((item) => item.key === activeSection.value)?.label || 'Client Workspace');
const sectionSubtitle = computed(() => ({
  overview: 'Browse shop projects, active hiring opportunities, and your action-needed summary.',
  'track-orders': 'Monitor order payment, production, shipping, and post-delivery status in one place.',
  'payment-methods': 'Store reusable payment details and keep your preferred payment option ready.',
  'design-studio': 'Prepare design metadata before posting to the marketplace or requesting quotation.',
  proofing: 'Send your design to a selected shop for proofing and quotation, then review proof responses.',
  marketplace: 'See owner-posted projects and client design requests from the shared marketplace.',
  message: 'Message only the shops you already ordered from, with order-linked threads when needed.',
  support: 'Open and track support concerns tied to orders, delivery, payments, or design issues.',
  notification: 'Filter notifications by category, urgency, and unread state, then jump to the linked work.',
}[activeSection.value] || 'Client embroidery workspace.'));

const orders = computed(() => workspace.value?.track_orders?.orders || []);
const paymentMethods = computed(() => workspace.value?.payment_methods || []);
const shops = computed(() => workspace.value?.shops || []);
const designRequests = computed(() => workspace.value?.design_proofing?.requests || []);
const notifications = computed(() => workspace.value?.notifications?.items || []);
const selectedOrder = computed(() => orders.value.find((item) => item.id === selectedOrderId.value) || orders.value[0] || null);
const selectedDesign = computed(() => designRequests.value.find((item) => item.id === selectedDesignId.value) || designRequests.value[0] || null);
const filteredNotifications = computed(() => notifications.value.filter((item) => {
  if (notificationFilter.category && item.category !== notificationFilter.category) return false;
  if (notificationFilter.priority && item.priority !== notificationFilter.priority) return false;
  if (notificationFilter.unreadOnly && item.is_read) return false;
  return true;
}));

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
const activeOrderTab = ref('all');
const filteredOrders = computed(() => {
  const ordersList = orders.value;
  if (activeOrderTab.value === 'all') return ordersList;
  return ordersList.filter((order) => {
    const status = order.status || '';
    const paymentStatus = order.payment_status || '';
    const fulfillmentStatus = order.fulfillment?.status || '';
    switch (activeOrderTab.value) {
      case 'to_pay': return ['unpaid', 'partial'].includes(paymentStatus);
      case 'to_process': return ['pending', 'quoted', 'approved', 'in_production'].includes(status);
      case 'to_ship': return ['ready', 'scheduled'].includes(fulfillmentStatus) || ['ready_for_pickup', 'shipped'].includes(status);
      case 'to_receive': return ['shipped', 'out_for_delivery'].includes(fulfillmentStatus);
      case 'to_review': return status === 'completed';
      case 'returns': return ['return_requested', 'returned'].includes(status);
      case 'cancellation': return status === 'cancelled';
      default: return true;
    }
  });
});


function normalizeDesignPostType(value) {
  const mapping = {
    logo_embroidery: 'logo',
    name_embroidery: 'other',
    patch_embroidery: 'patch',
    uniform_embroidery: 'uniform',
    cap_embroidery: 'cap',
    custom_design_embroidery: 'custom_art',
  };
  return mapping[value] || 'custom_art';
}

function money(value) {
  const amount = Number(value || 0);
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

function setFlash(text, type = 'info') {
  flash.text = text;
  flash.type = type;
}

function getErrorMessage(err) {
  if (err?.response?.data?.errors) {
    const first = Object.values(err.response.data.errors)[0];
    if (Array.isArray(first) && first[0]) return first[0];
  }
  return err?.response?.data?.message || 'Something went wrong.';
}

async function api(method, url, data = undefined) {
  applyApiToken(token.value);
  return window.axios({ method, url, data });
}

function redirectForRole(role) {
  if (role === 'owner') window.location.href = '/owner-dashboard';
  else if (role === 'client') window.location.href = '/client-dashboard';
  else window.location.href = '/dashboard';
}

async function bootstrap() {
  try {
    applyApiToken(token.value);
    const { data: me } = await api('get', '/api/auth/me');
    user.value = me;
    if (user.value?.role !== 'client') {
      redirectForRole(user.value?.role);
      return;
    }
    await loadWorkspace();
  } catch (err) {
    window.localStorage.removeItem('embro_token');
    setFlash('Your session expired. Please log in again.', 'error');
    window.location.href = '/';
  } finally {
    loading.value = false;
  }
}

async function loadWorkspace() {
  const { data } = await api('get', '/api/client/workspace');
  workspace.value = data;
  if (!selectedOrderId.value && data.track_orders?.orders?.length) selectedOrderId.value = data.track_orders.orders[0].id;
  if (!selectedDesignId.value && data.design_proofing?.requests?.length) selectedDesignId.value = data.design_proofing.requests[0].id;
  if (!quoteForm.shop_id && data.shops?.length) quoteForm.shop_id = data.shops[0].id;
  if (!messageForm.shop_id && data.messages?.shops?.length) messageForm.shop_id = data.messages.shops[0].id;
  if (!supportForm.shop_id && data.shops?.length) supportForm.shop_id = data.shops[0].id;
}

async function logout() {
  try { await api('post', '/api/auth/logout'); } catch {}
  localStorage.removeItem('embro_token');
  applyApiToken('');
  window.location.href = '/';
}

async function addPaymentMethod() {
  busy.value = true;
  try {
    await api('post', '/api/client/payment-methods', paymentMethodForm);
    Object.assign(paymentMethodForm, { label: '', method_type: 'gcash', account_name: '', account_number: '', provider: '', instructions: '', is_default: false });
    await loadWorkspace();
    setFlash('Payment method added.', 'success');
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally { busy.value = false; }
}

async function saveDesign() {
  busy.value = true;
  try {
    const { data: pricing } = await api('post', '/api/design-customizations/suggest-price', {
      quantity: designStudioForm.quantity,
      stitch_count_estimate: designStudioForm.stitch_count_estimate,
      color_count: designStudioForm.color_count,
      complexity_level: designStudioForm.complexity_level,
      width_mm: designStudioForm.width_mm,
      height_mm: designStudioForm.height_mm,
      design_type: designStudioForm.design_type,
    });
    const payload = {
      name: designStudioForm.name,
      garment_type: designStudioForm.garment_type,
      placement_area: designStudioForm.placement_area,
      fabric_type: designStudioForm.fabric_type,
      width_mm: designStudioForm.width_mm,
      height_mm: designStudioForm.height_mm,
      color_count: designStudioForm.color_count,
      stitch_count_estimate: designStudioForm.stitch_count_estimate,
      complexity_level: designStudioForm.complexity_level,
      quantity: designStudioForm.quantity,
      design_type: designStudioForm.design_type,
      notes: designStudioForm.notes,
      status: 'draft',
      design_session_json: {
        canvas_type: designStudioForm.canvas_type,
        canvas_color: designStudioForm.canvas_color,
        canvas_size: designStudioForm.canvas_size,
        thread_color: designStudioForm.thread_color,
        text: designStudioForm.text,
        font_family: designStudioForm.font_family,
        font_size: designStudioForm.font_size,
      },
      preview_meta_json: { suggested_total: pricing.suggested_total },
    };
    const { data } = await api('post', '/api/design-customizations', payload);
    selectedDesignId.value = data.id;
    quoteForm.design_id = data.id;
    await loadWorkspace();
    setFlash('Design saved to your design studio.', 'success');
    activeSection.value = 'proofing';
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally { busy.value = false; }
}

async function postDesignToMarketplace() {
  busy.value = true;
  try {
    await api('post', '/api/design-posts', {
      title: designPostForm.title || designStudioForm.name,
      description: designPostForm.description || designStudioForm.notes,
      design_type: normalizeDesignPostType(designPostForm.design_type),
      garment_type: designPostForm.garment_type,
      quantity: designPostForm.quantity,
      target_budget: designPostForm.target_budget,
      notes: designPostForm.notes || `From Design Studio: ${designStudioForm.text || designStudioForm.name}`,
    });
    await loadWorkspace();
    setFlash('Design request posted to marketplace.', 'success');
    activeSection.value = 'marketplace';
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally { busy.value = false; }
}

async function requestProofAndQuote() {
  busy.value = true;
  try {
    let designId = quoteForm.design_id || selectedDesignId.value;
    if (!designId) {
      await saveDesign();
      designId = selectedDesignId.value;
    }
    const title = `${designStudioForm.name || 'Custom design'} quotation request`;
    const { data: designPost } = await api('post', '/api/design-posts', {
      title,
      description: quoteForm.description || designStudioForm.notes || 'Client requested proofing and price quotation.',
      design_type: normalizeDesignPostType(designStudioForm.design_type),
      garment_type: designStudioForm.garment_type,
      quantity: designStudioForm.quantity,
      target_budget: 0,
      notes: quoteForm.upload_design_file || null,
    });
    await api('post', `/api/design-posts/${designPost.id}/select-shop`, { shop_id: Number(quoteForm.shop_id) });
    await api('put', `/api/design-customizations/${designId}`, {
      design_post_id: designPost.id,
      name: designStudioForm.name,
      notes: quoteForm.description || designStudioForm.notes,
      status: 'estimated',
    });
    await loadWorkspace();
    setFlash('Proofing and quotation request sent to the selected shop.', 'success');
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally { busy.value = false; }
}

async function respondToProof(customizationId, proofId, status) {
  busy.value = true;
  try {
    await api('post', `/api/design-customizations/${customizationId}/proofs/${proofId}/respond`, { status });
    await loadWorkspace();
    setFlash(`Proof ${status}.`, 'success');
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally { busy.value = false; }
}

async function createThread() {
  busy.value = true;
  try {
    await api('post', '/api/client/threads', {
      shop_id: Number(messageForm.shop_id),
      order_id: messageForm.order_id ? Number(messageForm.order_id) : null,
      title: messageForm.title,
      message: messageForm.message,
    });
    Object.assign(messageForm, { shop_id: messageForm.shop_id, order_id: '', title: '', message: '' });
    await loadWorkspace();
    setFlash('Message thread created.', 'success');
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally { busy.value = false; }
}

async function sendReply(threadId) {
  busy.value = true;
  try {
    await api('post', `/api/client/threads/${threadId}/messages`, { message: replyMessage[threadId] || '' });
    replyMessage[threadId] = '';
    await loadWorkspace();
    setFlash('Reply sent.', 'success');
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally { busy.value = false; }
}

async function createSupportTicket() {
  busy.value = true;
  try {
    await api('post', '/api/client/support-tickets', {
      shop_id: supportForm.shop_id ? Number(supportForm.shop_id) : null,
      order_id: supportForm.order_id ? Number(supportForm.order_id) : null,
      subject: supportForm.subject,
      category: supportForm.category,
      priority: supportForm.priority,
      message: supportForm.message,
    });
    Object.assign(supportForm, { shop_id: supportForm.shop_id, order_id: '', subject: '', category: 'support', priority: 'medium', message: '' });
    await loadWorkspace();
    setFlash('Support ticket submitted.', 'success');
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally { busy.value = false; }
}

async function markNotificationRead(notificationId) {
  try {
    await api('post', `/api/notifications/${notificationId}/read`);
    await loadWorkspace();
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  }
}

function openNotification(item) {
  if (item.reference_type === 'order') {
    activeSection.value = 'track-orders';
    selectedOrderId.value = item.reference_id;
  } else if (['design_proof', 'design_customization'].includes(item.reference_type)) {
    activeSection.value = 'proofing';
    selectedDesignId.value = item.reference_id;
  } else if (item.reference_type === 'support_ticket') {
    activeSection.value = 'support';
  } else if (item.reference_type === 'message_thread') {
    activeSection.value = 'message';
  } else {
    activeSection.value = 'notification';
  }
}

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
      <WorkspaceHeader eyebrow="Client workspace" :title="pageTitle" :subtitle="sectionSubtitle" :user="user" @logout="logout">
        <template #actions>
          <div v-if="flash.text" class="rounded-2xl px-4 py-2 text-sm font-medium" :class="flash.type === 'error' ? 'border border-red-200 bg-red-50 text-red-700' : 'border border-emerald-200 bg-emerald-50 text-emerald-700'">{{ flash.text }}</div>
        </template>
      </WorkspaceHeader>
    </template>

    <template #right>
      <WorkspaceRightSidebar :notifications="notifications" :selected-order="selectedOrder" :assignments="[]" :revisions="selectedDesign?.proofs || []" />
    </template>

    <div class="space-y-4">
      <template v-if="activeSection === 'overview'">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <StatCard label="Pending Quotes" :value="workspace.overview.stats.pending_quotes" />
          <StatCard label="Unpaid / Partial Orders" :value="workspace.overview.stats.unpaid_partial_orders" />
          <StatCard label="Design Approvals Needed" :value="workspace.overview.stats.design_approvals_needed" />
          <StatCard label="Delivery Tracking" :value="workspace.overview.stats.delivery_tracking" />
        </section>

        <div class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
          <SectionCard title="Shops posted projects" description="Published shop projects that clients can browse or order from.">
            <div v-if="workspace.marketplace.projects.length" class="grid gap-3 md:grid-cols-2">
              <article v-for="project in workspace.marketplace.projects.slice(0, 6)" :key="project.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="text-sm font-semibold text-stone-900">{{ project.title }}</div>
                <div class="mt-1 text-xs text-stone-500">{{ project.shop?.shop_name || 'Shop project' }}</div>
                <div class="mt-3 text-sm text-stone-600">{{ project.description || 'No project description provided.' }}</div>
                <div class="mt-3 text-sm font-medium text-stone-800">{{ money(project.base_price) }}</div>
              </article>
            </div>
            <EmptyState v-else title="No posted projects yet." description="Once shops publish marketplace-ready projects, they will show here." />
          </SectionCard>

          <SectionCard title="Hiring openings" description="Active hiring opportunities from embroidery shops.">
            <div v-if="workspace.overview.hiring_openings.length" class="space-y-3">
              <article v-for="opening in workspace.overview.hiring_openings" :key="opening.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="text-sm font-semibold text-stone-900">{{ opening.title }}</div>
                <div class="mt-1 text-xs text-stone-500">{{ opening.shop?.shop_name }} · {{ opening.department || 'General' }}</div>
                <div class="mt-2 text-sm text-stone-600">{{ opening.description || 'No description yet.' }}</div>
              </article>
            </div>
            <EmptyState v-else title="No hiring openings posted yet." description="The database support is ready. Openings will appear here when shops publish them." />
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'track-orders'">
        <SectionCard title="Track Orders" description="Tabs reflect payment, process, shipping, receiving, review, return, and cancellation flows.">
          <div class="flex flex-wrap gap-2">
            <button v-for="tab in orderTabs" :key="tab.key" type="button" class="rounded-full px-4 py-2 text-sm font-medium" :class="activeOrderTab === tab.key ? 'bg-stone-900 text-white' : 'border border-stone-300 bg-white text-stone-700'" @click="activeOrderTab = tab.key">
              {{ tab.label }}
            </button>
          </div>
          <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="text-left text-stone-500">
                <tr>
                  <th class="pb-3 pr-4">Order</th><th class="pb-3 pr-4">Shop</th><th class="pb-3 pr-4">Status</th><th class="pb-3 pr-4">Payment</th><th class="pb-3 pr-4">Delivery</th><th class="pb-3 pr-4">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in filteredOrders" :key="order.id" class="border-t border-stone-100 cursor-pointer" @click="selectedOrderId = order.id">
                  <td class="py-3 pr-4 font-medium text-stone-900">{{ order.order_number }}</td>
                  <td class="py-3 pr-4">{{ order.shop?.shop_name }}</td>
                  <td class="py-3 pr-4 capitalize">{{ order.status }}</td>
                  <td class="py-3 pr-4 capitalize">{{ order.payment_status }}</td>
                  <td class="py-3 pr-4 capitalize">{{ order.fulfillment?.status || order.fulfillment_type }}</td>
                  <td class="py-3 pr-4">{{ money(order.total_amount) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <EmptyState v-if="!filteredOrders.length" title="No orders in this tab." description="Once an order reaches this stage, it will appear here." />
        </SectionCard>
      </template>

      <template v-else-if="activeSection === 'payment-methods'">
        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
          <SectionCard title="Add payment method" description="Store your payment details for faster checkout.">
            <div class="grid gap-3 md:grid-cols-2">
              <input v-model="paymentMethodForm.label" class="rounded-2xl border-stone-300" placeholder="Label">
              <select v-model="paymentMethodForm.method_type" class="rounded-2xl border-stone-300">
                <option value="gcash">GCash</option><option value="paymaya">Maya</option><option value="bank">Bank</option><option value="card">Card</option><option value="cod">Cash on delivery</option><option value="other">Other</option>
              </select>
              <input v-model="paymentMethodForm.account_name" class="rounded-2xl border-stone-300" placeholder="Account name">
              <input v-model="paymentMethodForm.account_number" class="rounded-2xl border-stone-300" placeholder="Account number">
              <input v-model="paymentMethodForm.provider" class="rounded-2xl border-stone-300 md:col-span-2" placeholder="Provider">
              <textarea v-model="paymentMethodForm.instructions" class="rounded-2xl border-stone-300 md:col-span-2" rows="3" placeholder="Instructions"></textarea>
            </div>
            <label class="mt-3 flex items-center gap-2 text-sm text-stone-600"><input v-model="paymentMethodForm.is_default" type="checkbox"> Make this my default</label>
            <button type="button" class="mt-4 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="addPaymentMethod">Save payment method</button>
          </SectionCard>
          <SectionCard title="Saved payment methods" description="Your stored payment method information.">
            <div v-if="paymentMethods.length" class="space-y-3">
              <article v-for="method in paymentMethods" :key="method.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <div class="text-sm font-semibold text-stone-900">{{ method.label }}</div>
                    <div class="text-xs uppercase tracking-wide text-stone-500">{{ method.method_type }} · {{ method.provider || 'Provider not set' }}</div>
                  </div>
                  <span v-if="method.is_default" class="rounded-full bg-stone-900 px-3 py-1 text-xs font-medium text-white">Default</span>
                </div>
                <div class="mt-2 text-sm text-stone-600">{{ method.account_name || '—' }} · {{ method.account_number || '—' }}</div>
                <div class="mt-2 text-sm text-stone-500">{{ method.instructions || 'No extra instructions.' }}</div>
              </article>
            </div>
            <EmptyState v-else title="No saved payment methods yet." description="Add GCash, Maya, bank, card, or COD details here." />
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'design-studio'">
        <div class="grid gap-4 xl:grid-cols-[1fr_0.9fr]">
          <SectionCard title="Design Studio" description="Set your design canvas, thread color, text styling, and save for quotation or marketplace posting.">
            <div class="grid gap-3 md:grid-cols-2">
              <input v-model="designStudioForm.name" class="rounded-2xl border-stone-300" placeholder="Design name">
              <select v-model="designStudioForm.design_type" class="rounded-2xl border-stone-300">
                <option value="logo_embroidery">Logo embroidery</option><option value="name_embroidery">Name embroidery</option><option value="patch_embroidery">Patch embroidery</option><option value="uniform_embroidery">Uniform embroidery</option><option value="cap_embroidery">Cap embroidery</option><option value="custom_design_embroidery">Custom design embroidery</option>
              </select>
              <select v-model="designStudioForm.canvas_type" class="rounded-2xl border-stone-300"><option>rectangle</option><option>square</option><option>circle</option><option>cap-front</option></select>
              <input v-model="designStudioForm.canvas_color" type="color" class="h-12 rounded-2xl border-stone-300">
              <select v-model="designStudioForm.canvas_size" class="rounded-2xl border-stone-300"><option>small</option><option>medium</option><option>large</option></select>
              <input v-model="designStudioForm.thread_color" type="color" class="h-12 rounded-2xl border-stone-300">
              <input v-model="designStudioForm.text" class="rounded-2xl border-stone-300" placeholder="Add text">
              <input v-model="designStudioForm.font_family" class="rounded-2xl border-stone-300" placeholder="Font family">
              <input v-model="designStudioForm.font_size" type="number" class="rounded-2xl border-stone-300" placeholder="Font size">
              <input v-model="designStudioForm.quantity" type="number" class="rounded-2xl border-stone-300" placeholder="Quantity">
              <input v-model="designStudioForm.width_mm" type="number" class="rounded-2xl border-stone-300" placeholder="Width mm">
              <input v-model="designStudioForm.height_mm" type="number" class="rounded-2xl border-stone-300" placeholder="Height mm">
              <input v-model="designStudioForm.color_count" type="number" class="rounded-2xl border-stone-300" placeholder="Color count">
              <input v-model="designStudioForm.stitch_count_estimate" type="number" class="rounded-2xl border-stone-300" placeholder="Stitch estimate">
            </div>
            <textarea v-model="designStudioForm.notes" class="mt-3 w-full rounded-2xl border-stone-300" rows="4" placeholder="Design notes / description"></textarea>
            <div class="mt-4 flex flex-wrap gap-2">
              <button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="saveDesign">Save design</button>
              <button class="rounded-2xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-700" :disabled="busy" @click="postDesignToMarketplace">Post in marketplace</button>
              <button class="rounded-2xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-700" :disabled="busy" @click="activeSection = 'proofing'">Send to proofing & quotation</button>
            </div>
          </SectionCard>
          <SectionCard title="Saved studio designs" description="Designs already saved into your account.">
            <div v-if="workspace.design_studio.saved_designs.length" class="space-y-3">
              <article v-for="design in workspace.design_studio.saved_designs" :key="design.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4 cursor-pointer" @click="selectedDesignId = design.id; quoteForm.design_id = design.id; activeSection = 'proofing'">
                <div class="text-sm font-semibold text-stone-900">{{ design.name }}</div>
                <div class="mt-1 text-xs text-stone-500">{{ design.garment_type }} · {{ design.placement_area }}</div>
                <div class="mt-2 text-sm text-stone-600">{{ design.notes || 'No notes added.' }}</div>
                <div class="mt-3 text-sm font-medium text-stone-800">Suggested {{ money(design.estimated_total_price) }}</div>
              </article>
            </div>
            <EmptyState v-else title="No saved designs yet." description="Use the studio form to create your first saved design." />
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'proofing'">
        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
          <SectionCard title="Request Design Proofing & Price Quotation" description="Import from Design Studio, select a shop, and send a quotation request.">
            <div class="grid gap-3 md:grid-cols-2">
              <select v-model="quoteForm.design_id" class="rounded-2xl border-stone-300">
                <option value="">Select saved design</option>
                <option v-for="design in workspace.design_studio.saved_designs" :key="design.id" :value="design.id">{{ design.name }}</option>
              </select>
              <select v-model="quoteForm.shop_id" class="rounded-2xl border-stone-300">
                <option v-for="shop in shops" :key="shop.id" :value="shop.id">{{ shop.shop_name }}</option>
              </select>
              <select v-model="quoteForm.service_selection" class="rounded-2xl border-stone-300">
                <option value="logo_embroidery">Logo embroidery</option><option value="name_embroidery">Name embroidery</option><option value="patch_embroidery">Patch embroidery</option><option value="uniform_embroidery">Uniform embroidery</option><option value="cap_embroidery">Cap embroidery</option><option value="custom_design_embroidery">Custom design embroidery</option>
              </select>
              <input v-model="quoteForm.upload_design_file" class="rounded-2xl border-stone-300" placeholder="Upload design file path (optional)">
            </div>
            <textarea v-model="quoteForm.description" class="mt-3 w-full rounded-2xl border-stone-300" rows="5" placeholder="Design description"></textarea>
            <button class="mt-4 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="requestProofAndQuote">Request Design Proofing and Price Quotation</button>
          </SectionCard>
          <SectionCard title="Proofing requests and approvals" description="Review imported design requests, system pricing, and pending proof approvals.">
            <div v-if="designRequests.length" class="space-y-4">
              <article v-for="request in designRequests" :key="request.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <div class="text-sm font-semibold text-stone-900">{{ request.name }}</div>
                    <div class="mt-1 text-xs text-stone-500">{{ request.designPost?.selected_shop?.shop_name || 'Shop not selected yet' }} · {{ request.status }}</div>
                  </div>
                  <div class="text-sm font-semibold text-stone-800">{{ money(request.estimated_total_price) }}</div>
                </div>
                <div class="mt-2 text-sm text-stone-600">{{ request.notes || 'No description.' }}</div>
                <div v-if="request.proofs?.length" class="mt-3 space-y-2">
                  <div v-for="proof in request.proofs" :key="proof.id" class="rounded-2xl border border-stone-200 bg-white p-3">
                    <div class="text-sm font-medium text-stone-900">Proof #{{ proof.proof_no }}</div>
                    <div class="mt-1 text-xs text-stone-500">{{ proof.status }} · {{ proof.preview_file_path || 'Preview path not attached' }}</div>
                    <div class="mt-2 text-sm text-stone-600">{{ proof.annotated_notes || 'No notes.' }}</div>
                    <div v-if="proof.status === 'pending_client'" class="mt-3 flex flex-wrap gap-2">
                      <button class="rounded-xl bg-stone-900 px-3 py-2 text-xs font-semibold text-white" @click="respondToProof(request.id, proof.id, 'approved')">Approve</button>
                      <button class="rounded-xl border border-stone-300 px-3 py-2 text-xs font-semibold text-stone-700" @click="respondToProof(request.id, proof.id, 'rejected')">Reject</button>
                    </div>
                  </div>
                </div>
              </article>
            </div>
            <EmptyState v-else title="No proofing requests yet." description="Save a design from the studio and request quotation from a shop." />
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'marketplace'">
        <div class="grid gap-4 xl:grid-cols-2">
          <SectionCard title="Shop posted projects" description="Published projects from embroidery shops.">
            <div v-if="workspace.marketplace.projects.length" class="space-y-3">
              <article v-for="project in workspace.marketplace.projects" :key="project.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="text-sm font-semibold text-stone-900">{{ project.title }}</div>
                <div class="mt-1 text-xs text-stone-500">{{ project.shop?.shop_name }} · Minimum {{ project.min_order_qty }}</div>
                <div class="mt-2 text-sm text-stone-600">{{ project.description || 'No project description.' }}</div>
              </article>
            </div>
            <EmptyState v-else title="No marketplace projects yet." description="Published shop projects will appear here." />
          </SectionCard>
          <SectionCard title="Posted requests for making a design" description="Shared client-side requests visible in the marketplace.">
            <div v-if="workspace.marketplace.design_requests.length" class="space-y-3">
              <article v-for="post in workspace.marketplace.design_requests" :key="post.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="text-sm font-semibold text-stone-900">{{ post.title }}</div>
                <div class="mt-1 text-xs text-stone-500">{{ post.client?.name || 'Client' }} · {{ post.selected_shop?.shop_name || 'Open marketplace' }}</div>
                <div class="mt-2 text-sm text-stone-600">{{ post.description || 'No description.' }}</div>
              </article>
            </div>
            <EmptyState v-else title="No marketplace design requests yet." description="Use Design Studio to post a custom design request." />
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'message'">
        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
          <SectionCard title="Start a direct shop message" description="Clients can directly message shops they ordered from before.">
            <div class="grid gap-3 md:grid-cols-2">
              <select v-model="messageForm.shop_id" class="rounded-2xl border-stone-300">
                <option value="">Select shop</option>
                <option v-for="shop in workspace.messages.shops" :key="shop.id" :value="shop.id">{{ shop.shop_name }}</option>
              </select>
              <select v-model="messageForm.order_id" class="rounded-2xl border-stone-300">
                <option value="">General message</option>
                <option v-for="order in orders.filter((item) => String(item.shop_id) === String(messageForm.shop_id))" :key="order.id" :value="order.id">{{ order.order_number }}</option>
              </select>
              <input v-model="messageForm.title" class="rounded-2xl border-stone-300 md:col-span-2" placeholder="Conversation title">
            </div>
            <textarea v-model="messageForm.message" class="mt-3 w-full rounded-2xl border-stone-300" rows="5" placeholder="Write your message"></textarea>
            <button class="mt-4 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="createThread">Send message</button>
          </SectionCard>
          <SectionCard title="Message threads" description="Order-linked and direct conversations with shops.">
            <div v-if="workspace.messages.threads.length" class="space-y-4">
              <article v-for="thread in workspace.messages.threads" :key="thread.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="text-sm font-semibold text-stone-900">{{ thread.title }}</div>
                <div class="mt-1 text-xs text-stone-500">{{ thread.type }} · {{ thread.last_message_at }}</div>
                <div class="mt-3 space-y-2">
                  <div v-for="message in thread.messages" :key="message.id" class="rounded-2xl border border-stone-200 bg-white p-3">
                    <div class="text-xs font-semibold text-stone-700">{{ message.sender?.name }}</div>
                    <div class="mt-1 text-sm text-stone-600">{{ message.message }}</div>
                  </div>
                </div>
                <div class="mt-3 flex gap-2">
                  <input v-model="replyMessage[thread.id]" class="flex-1 rounded-2xl border-stone-300" placeholder="Reply">
                  <button class="rounded-2xl bg-stone-900 px-4 py-2.5 text-sm font-semibold text-white" :disabled="busy || !replyMessage[thread.id]" @click="sendReply(thread.id)">Reply</button>
                </div>
              </article>
            </div>
            <EmptyState v-else title="No message threads yet." description="Once you message a shop you already ordered from, the conversation appears here." />
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'support'">
        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
          <SectionCard title="Support" description="Submit a support request tied to orders, delivery, payments, or design issues.">
            <div class="grid gap-3 md:grid-cols-2">
              <select v-model="supportForm.shop_id" class="rounded-2xl border-stone-300"><option value="">Select shop</option><option v-for="shop in shops" :key="shop.id" :value="shop.id">{{ shop.shop_name }}</option></select>
              <select v-model="supportForm.order_id" class="rounded-2xl border-stone-300"><option value="">Order optional</option><option v-for="order in orders" :key="order.id" :value="order.id">{{ order.order_number }}</option></select>
              <input v-model="supportForm.subject" class="rounded-2xl border-stone-300 md:col-span-2" placeholder="Subject">
              <select v-model="supportForm.category" class="rounded-2xl border-stone-300"><option value="orders">Orders</option><option value="quotes">Quotes</option><option value="payments">Payments</option><option value="production">Production</option><option value="delivery">Delivery</option><option value="support">Support</option><option value="disputes">Disputes</option><option value="exceptions">Exceptions</option></select>
              <select v-model="supportForm.priority" class="rounded-2xl border-stone-300"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select>
            </div>
            <textarea v-model="supportForm.message" class="mt-3 w-full rounded-2xl border-stone-300" rows="5" placeholder="Describe the issue"></textarea>
            <button class="mt-4 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="createSupportTicket">Submit support ticket</button>
          </SectionCard>
          <SectionCard title="Support history" description="Track your submitted support tickets and current handling status.">
            <div v-if="workspace.support.length" class="space-y-3">
              <article v-for="ticket in workspace.support" :key="ticket.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm font-semibold text-stone-900">{{ ticket.subject }}</div>
                    <div class="mt-1 text-xs text-stone-500">{{ ticket.shop?.shop_name || 'Platform support' }} · {{ ticket.order?.order_number || 'No order linked' }}</div>
                  </div>
                  <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-stone-700">{{ ticket.status }}</span>
                </div>
                <div class="mt-2 text-sm text-stone-600">{{ ticket.message }}</div>
              </article>
            </div>
            <EmptyState v-else title="No support tickets yet." description="Open a support case when you need order, payment, delivery, or proofing help." />
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'notification'">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <StatCard label="Urgent Alerts" :value="workspace.notifications.summary.urgent_alerts" />
          <StatCard label="Pending Approvals" :value="workspace.notifications.summary.pending_approvals" />
          <StatCard label="Unread Notifications" :value="workspace.notifications.summary.unread_notifications" />
          <StatCard label="Total Feed" :value="notifications.length" />
        </section>
        <SectionCard title="Notification feed" description="Filter notifications by category, priority, unread state, and linked references.">
          <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-4">
            <select v-model="notificationFilter.category" class="rounded-2xl border-stone-300"><option value="">All categories</option><option value="orders">Orders</option><option value="quotes">Quotes</option><option value="payments">Payments</option><option value="production">Production</option><option value="inventory">Inventory</option><option value="delivery">Delivery</option><option value="support">Support</option><option value="disputes">Disputes</option><option value="exceptions">Exceptions</option></select>
            <select v-model="notificationFilter.priority" class="rounded-2xl border-stone-300"><option value="">All priorities</option><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select>
            <label class="flex items-center gap-2 rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-600"><input v-model="notificationFilter.unreadOnly" type="checkbox"> Unread only</label>
          </div>
          <div class="mt-5 space-y-3">
            <article v-for="item in filteredNotifications" :key="item.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <div class="text-sm font-semibold text-stone-900">{{ item.title }}</div>
                  <div class="mt-1 text-xs text-stone-500">{{ item.category || item.type }} · {{ item.priority || 'normal' }} · {{ item.created_at }}</div>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-stone-700">{{ item.is_read ? 'Read' : 'Unread' }}</span>
              </div>
              <div class="mt-2 text-sm text-stone-600">{{ item.message }}</div>
              <div class="mt-2 text-xs text-stone-500">Ref: {{ item.reference_type || '—' }} #{{ item.reference_id || '—' }}</div>
              <div class="mt-3 flex flex-wrap gap-2">
                <button class="rounded-xl border border-stone-300 px-3 py-2 text-xs font-semibold text-stone-700" @click="openNotification(item)">{{ item.action_label || 'View' }}</button>
                <button class="rounded-xl bg-stone-900 px-3 py-2 text-xs font-semibold text-white" @click="markNotificationRead(item.id)">{{ item.is_read ? 'Open' : 'Mark read' }}</button>
              </div>
            </article>
          </div>
          <EmptyState v-if="!filteredNotifications.length" title="No notifications match your filters." description="Adjust category, priority, or unread filters to see more activity." />
        </SectionCard>
      </template>
    </div>
  </AppWorkspaceLayout>
</template>
