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
const flash = reactive({ type: 'info', text: '' });

const token = ref(window.localStorage.getItem('embro_token') || '');
const user = ref(null);
const shops = ref([]);
const orders = ref([]);
const notifications = ref([]);
const payments = ref([]);
const recommendations = ref([]);
const metrics = ref(null);
const risk = ref(null);
const assignments = ref([]);
const revisions = ref([]);
const fulfillment = ref(null);
const clientProfile = ref(null);
const designCustomizations = ref([]);
const designProofs = ref([]);
const designPosts = ref([]);
const bargainingOffers = ref([]);
const shopProjects = ref([]);
const selectedCustomizationId = ref(null);
const selectedOrderId = ref(null);
const activeSection = ref('overview');

const createOrderForm = reactive({
  shop_id: '',
  fulfillment_type: 'pickup',
  customer_notes: '',
  delivery_address: '',
  item_name: 'Polo Shirt Logo',
  quantity: 10,
  unit_price: 150,
});

const paymentForm = reactive({ amount: 500, payment_type: 'downpayment', transaction_reference: '', payer_name: '', notes: '' });
const revisionForm = reactive({ revision_type: 'color_change', request_notes: '' });
const assignmentForm = reactive({ assignment_role: 'staff', assignment_type: 'digitizing', assigned_to: '', notes: '' });
const fulfillmentForm = reactive({ receiver_name: '', receiver_contact: '', delivery_address: '', courier_name: '', tracking_number: '', shipping_fee: 0, notes: '' });
const previewForm = reactive({ preview_file_path: 'revisions/order-preview.png', response_notes: '' });
const profileForm = reactive({ organization_name: '', default_address: '', postal_code: '', preferred_contact_method: 'email', preferred_fulfillment_type: 'pickup', notes: '', saved_measurements_json: { chest: '', sleeve: '' }, default_garment_preferences_json: { garment_type: '', fabric_type: '' } });
const customizationForm = reactive({ design_post_id: '', order_id: '', name: '', garment_type: 'Polo Shirt', placement_area: 'left_chest', fabric_type: 'cotton', width_mm: 80, height_mm: 80, color_count: 3, stitch_count_estimate: 5000, complexity_level: 'standard', quantity: 10, design_type: 'logo', is_rush: false, notes: '' });
const projectForm = reactive({ title: '', description: '', category: 'logo_embroidery', base_price: 150, min_order_qty: 1, turnaround_days: 3, is_customizable: true, default_fulfillment_type: 'pickup' });
const designPostForm = reactive({ title: '', description: '', design_type: 'logo', garment_type: 'Polo Shirt', quantity: 10, target_budget: 2000, notes: '' });
const bargainingForm = reactive({ design_post_id: '', amount: 1500, estimated_days: 5, message: '' });
const projectOrderForm = reactive({ quantity: 1, fulfillment_type: 'pickup', customer_notes: '', customization_notes: '' });

const selectedOrder = computed(() => orders.value.find((order) => order.id === selectedOrderId.value) || null);
const selectedCustomization = computed(() => designCustomizations.value.find((item) => item.id === selectedCustomizationId.value) || null);
const isAdmin = computed(() => user.value?.role === 'admin');
const isOwner = computed(() => user.value?.role === 'owner');
const isHr = computed(() => user.value?.role === 'hr');
const isStaff = computed(() => user.value?.role === 'staff');
const isClient = computed(() => user.value?.role === 'client');
const canRefreshAnalytics = computed(() => isAdmin.value || isOwner.value);
const canManageAssignments = computed(() => isOwner.value || isHr.value);
const canManageFulfillment = computed(() => isOwner.value || isHr.value);
const canCancelOrders = computed(() => isAdmin.value || isOwner.value || isHr.value);
const canSeeAnalytics = computed(() => isAdmin.value || isOwner.value || isHr.value);
const currentShopId = computed(() => user.value?.shop_id || shops.value[0]?.id || null);
const pageTitle = computed(() => sectionConfig.value.find((item) => item.key === activeSection.value)?.label || 'Workspace');
const sectionSubtitle = computed(() => {
  const copy = {
    overview: 'High-priority information first, with orders and live operational signals in one place.',
    orders: 'Review order details, submit client actions, and keep one selected order in focus.',
    operations: 'Assignments, revisions, and fulfillment controls for live shop execution.',
    analytics: 'Shop metrics, recommendations, and order risk without dashboard clutter.',
    studio: 'Customization, proofing, and automated embroidery price suggestions in one place.',
    marketplace: 'Community posting, bargaining, and shop selection for marketplace jobs.',
    projects: 'Shop-posted ready-made projects that clients can order directly.',
    profile: 'Client profile defaults and repeat-order preferences for faster ordering.',
  };
  return copy[activeSection.value] || 'Embroidery operations workspace.';
});

const sectionConfig = computed(() => {
  const items = [{ key: 'overview', label: 'Overview', badge: orders.value.length || null }];
  items.push({ key: 'orders', label: 'Orders', badge: selectedOrder.value ? 1 : null });
  if (isOwner.value || isHr.value || isStaff.value) {
    items.push({ key: 'operations', label: 'Operations', badge: assignments.value.length || null });
  }
  if (canSeeAnalytics.value || recommendations.value.length) {
    items.push({ key: 'analytics', label: 'Analytics', badge: recommendations.value.length || null });
  }
  items.push({ key: 'studio', label: 'Design Studio', badge: designCustomizations.value.length || null });
  items.push({ key: 'marketplace', label: 'Marketplace', badge: designPosts.value.length || null });
  items.push({ key: 'projects', label: 'Projects', badge: shopProjects.value.length || null });
  items.push({ key: 'profile', label: 'Profile', badge: clientProfile.value ? 1 : null });
  return items;
});

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
  if (!token.value) throw new Error('Missing API token.');
  applyApiToken(token.value);
  return window.axios({ method, url, data });
}

async function bootstrap() {
  if (!token.value) {
    window.location.href = '/';
    return;
  }

  try {
    applyApiToken(token.value);
    const { data } = await api('get', '/api/auth/me');
    user.value = data;
    if (user.value?.role === 'owner') {
      window.location.href = '/owner-dashboard';
      return;
    }
    await Promise.all([
      loadOrders(),
      loadNotifications(),
      loadShops(),
      loadPayments(),
      loadRecommendations(),
      loadClientProfile(),
      loadDesignCustomizations(),
      loadDesignPosts(),
      loadShopProjects(),
    ]);
    if (orders.value.length && !selectedOrderId.value) {
      selectedOrderId.value = orders.value[0].id;
    }
    if (canSeeAnalytics.value && currentShopId.value) {
      await loadMetrics();
    }
  } catch (err) {
    console.error(err);
    localStorage.removeItem('embro_token');
    applyApiToken('');
    setFlash('Your session is no longer valid. Please log in again.', 'error');
    setTimeout(() => {
      window.location.href = '/';
    }, 1200);
  } finally {
    loading.value = false;
  }
}

async function loadOrders() {
  const { data } = await api('get', '/api/orders');
  orders.value = data;
}

async function loadNotifications() {
  const { data } = await api('get', '/api/notifications');
  notifications.value = data;
}

async function loadShops() {
  const { data } = await api('get', '/api/shops');
  shops.value = data;
  if (isClient.value && !createOrderForm.shop_id && data.length) {
    createOrderForm.shop_id = data[0].id;
  }
}

async function loadPayments() {
  const { data } = await api('get', '/api/payments');
  payments.value = data;
}

async function loadRecommendations() {
  if (!(isClient.value || isAdmin.value)) return;
  const { data } = await api('get', '/api/analytics/recommendations');
  recommendations.value = data;
}

async function loadClientProfile() {
  if (!isClient.value) return;
  const { data } = await api('get', '/api/client-profile');
  clientProfile.value = data;
  Object.assign(profileForm, {
    organization_name: data.organization_name || '',
    default_address: data.default_address || '',
    postal_code: data.postal_code || '',
    preferred_contact_method: data.preferred_contact_method || 'email',
    preferred_fulfillment_type: data.preferred_fulfillment_type || 'pickup',
    notes: data.notes || '',
    saved_measurements_json: data.saved_measurements_json || { chest: '', sleeve: '' },
    default_garment_preferences_json: data.default_garment_preferences_json || { garment_type: '', fabric_type: '' },
  });
}

async function loadDesignCustomizations() {
  const { data } = await api('get', '/api/design-customizations');
  designCustomizations.value = data;
  if (!selectedCustomizationId.value && data.length) {
    selectedCustomizationId.value = data[0].id;
  }
}

async function loadDesignPosts() {
  const { data } = await api('get', '/api/design-posts');
  designPosts.value = data;
  if (!bargainingForm.design_post_id && data.length) {
    bargainingForm.design_post_id = data[0].id;
  }
}

async function loadShopProjects() {
  const { data } = await api('get', '/api/shop-projects');
  shopProjects.value = data;
}

async function loadDesignProofs(customizationId) {
  if (!customizationId) return;
  const { data } = await api('get', `/api/design-customizations/${customizationId}/proofs`);
  designProofs.value = data;
}

async function loadMetrics() {
  if (!currentShopId.value || !canSeeAnalytics.value) return;
  const { data } = await api('get', `/api/analytics/shops/${currentShopId.value}/metrics`);
  metrics.value = Array.isArray(data) ? data[0] || null : data;
}

async function loadOrderRelations(orderId) {
  if (!orderId) return;
  try {
    const [assignmentRes, revisionRes, fulfillmentRes, riskRes] = await Promise.allSettled([
      api('get', `/api/orders/${orderId}/assignments`),
      api('get', `/api/orders/${orderId}/revisions`),
      api('get', `/api/orders/${orderId}/fulfillment`),
      api('get', `/api/analytics/orders/${orderId}/risk`),
    ]);

    assignments.value = assignmentRes.status === 'fulfilled' ? assignmentRes.value.data : [];
    revisions.value = revisionRes.status === 'fulfilled' ? revisionRes.value.data : [];
    fulfillment.value = fulfillmentRes.status === 'fulfilled' ? fulfillmentRes.value.data : null;
    risk.value = riskRes.status === 'fulfilled' ? riskRes.value.data : null;

    if (fulfillment.value) {
      Object.assign(fulfillmentForm, {
        receiver_name: fulfillment.value.receiver_name || '',
        receiver_contact: fulfillment.value.receiver_contact || '',
        delivery_address: fulfillment.value.delivery_address || '',
        courier_name: fulfillment.value.courier_name || '',
        tracking_number: fulfillment.value.tracking_number || '',
        shipping_fee: fulfillment.value.shipping_fee || 0,
        notes: fulfillment.value.notes || '',
      });
    }
  } catch (err) {
    console.error(err);
  }
}

watch(selectedOrderId, async (orderId) => {
  if (orderId) {
    await loadOrderRelations(orderId);
  }
});

watch(selectedCustomizationId, async (customizationId) => {
  if (customizationId) {
    await loadDesignProofs(customizationId);
  }
});

async function submitClientOrder() {
  busy.value = true;
  try {
    await api('post', '/api/orders', {
      shop_id: Number(createOrderForm.shop_id),
      fulfillment_type: createOrderForm.fulfillment_type,
      customer_notes: createOrderForm.customer_notes,
      delivery_address: createOrderForm.fulfillment_type === 'delivery' ? createOrderForm.delivery_address : null,
      items: [
        {
          item_name: createOrderForm.item_name,
          quantity: Number(createOrderForm.quantity),
          unit_price: Number(createOrderForm.unit_price),
          line_total: Number(createOrderForm.quantity) * Number(createOrderForm.unit_price),
          garment_type: 'Polo Shirt',
          embroidery_type: 'flat',
          customization_notes: createOrderForm.customer_notes,
        },
      ],
    });
    setFlash('Order created successfully.', 'success');
    await loadOrders();
    selectedOrderId.value = orders.value[0]?.id || null;
    activeSection.value = 'orders';
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function submitPayment() {
  if (!selectedOrderId.value) return;
  busy.value = true;
  try {
    await api('post', '/api/payments', {
      order_id: selectedOrderId.value,
      payment_type: paymentForm.payment_type,
      amount: Number(paymentForm.amount),
      transaction_reference: paymentForm.transaction_reference,
      payer_name: paymentForm.payer_name,
      notes: paymentForm.notes,
    });
    setFlash('Payment submitted successfully.', 'success');
    await loadPayments();
    await loadOrders();
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function createRevision() {
  if (!selectedOrderId.value) return;
  busy.value = true;
  try {
    await api('post', `/api/orders/${selectedOrderId.value}/revisions`, revisionForm);
    setFlash('Revision request created.', 'success');
    revisionForm.request_notes = '';
    await loadOrderRelations(selectedOrderId.value);
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function approveRevision(revisionId, action) {
  busy.value = true;
  try {
    await api('post', `/api/orders/${selectedOrderId.value}/revisions/${revisionId}/${action}`, {
      response_notes: action === 'approve' ? 'Approved from dashboard.' : 'Rejected from dashboard.',
    });
    setFlash(`Revision ${action}d successfully.`, 'success');
    await loadOrderRelations(selectedOrderId.value);
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function createAssignment() {
  if (!selectedOrderId.value) return;
  busy.value = true;
  try {
    await api('post', `/api/orders/${selectedOrderId.value}/assignments`, {
      assignment_role: assignmentForm.assignment_role,
      assignment_type: assignmentForm.assignment_type,
      assigned_to: assignmentForm.assigned_to ? Number(assignmentForm.assigned_to) : undefined,
      notes: assignmentForm.notes,
    });
    setFlash('Assignment created.', 'success');
    assignmentForm.assigned_to = '';
    assignmentForm.notes = '';
    await loadOrderRelations(selectedOrderId.value);
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function takeAssignment(assignmentId, action) {
  busy.value = true;
  try {
    await api('post', `/api/assignments/${assignmentId}/${action}`, action === 'complete' ? { notes: 'Completed from dashboard.' } : undefined);
    setFlash(`Assignment ${action}ed successfully.`, 'success');
    await loadOrderRelations(selectedOrderId.value);
    await loadOrders();
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function saveFulfillment() {
  if (!selectedOrderId.value) return;
  busy.value = true;
  try {
    await api('post', `/api/orders/${selectedOrderId.value}/fulfillment`, fulfillmentForm);
    setFlash('Fulfillment saved.', 'success');
    await loadOrderRelations(selectedOrderId.value);
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function runFulfillmentAction(action) {
  if (!selectedOrderId.value) return;
  busy.value = true;
  try {
    const payloads = {
      ready: { notes: 'Packed and ready.' },
      shipped: { courier_name: fulfillmentForm.courier_name, tracking_number: fulfillmentForm.tracking_number, notes: 'Dispatched to courier.' },
      delivered: { notes: 'Marked delivered from dashboard.' },
      'picked-up': { notes: 'Marked picked up from dashboard.' },
    };
    await api('post', `/api/orders/${selectedOrderId.value}/fulfillment/${action}`, payloads[action]);
    setFlash(`Fulfillment ${action} action completed.`, 'success');
    await loadOrderRelations(selectedOrderId.value);
    await loadOrders();
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function cancelSelectedOrder() {
  if (!selectedOrderId.value) return;
  busy.value = true;
  try {
    await api('post', `/api/orders/${selectedOrderId.value}/cancel`, { reason: 'Cancelled from frontend dashboard.' });
    setFlash('Order cancelled.', 'success');
    await loadOrders();
    await loadOrderRelations(selectedOrderId.value);
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function refreshAnalytics() {
  if (!currentShopId.value) return;
  busy.value = true;
  try {
    await api('post', `/api/analytics/shops/${currentShopId.value}/refresh`);
    setFlash('Analytics refreshed.', 'success');
    await loadMetrics();
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function claimRevision(revisionId) {
  busy.value = true;
  try {
    await api('post', `/api/orders/${selectedOrderId.value}/revisions/${revisionId}/claim`);
    setFlash('Revision claimed.', 'success');
    await loadOrderRelations(selectedOrderId.value);
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function uploadPreview(revisionId) {
  busy.value = true;
  try {
    await api('post', `/api/orders/${selectedOrderId.value}/revisions/${revisionId}/upload-preview`, previewForm);
    setFlash('Preview uploaded.', 'success');
    await loadOrderRelations(selectedOrderId.value);
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function implementRevision(revisionId) {
  busy.value = true;
  try {
    await api('post', `/api/orders/${selectedOrderId.value}/revisions/${revisionId}/implement`, { response_notes: 'Implemented from dashboard.' });
    setFlash('Revision implemented.', 'success');
    await loadOrderRelations(selectedOrderId.value);
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}


async function saveClientProfile() {
  busy.value = true;
  try {
    await api('put', '/api/client-profile', profileForm);
    setFlash('Profile saved.', 'success');
    await loadClientProfile();
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function createCustomization() {
  busy.value = true;
  try {
    await api('post', '/api/design-customizations', {
      ...customizationForm,
      design_post_id: customizationForm.design_post_id || null,
      order_id: customizationForm.order_id || selectedOrderId.value || null,
    });
    setFlash('Design customization saved with automated estimate.', 'success');
    await loadDesignCustomizations();
    activeSection.value = 'studio';
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function generateProof() {
  if (!selectedCustomizationId.value) return;
  busy.value = true;
  try {
    await api('post', `/api/design-customizations/${selectedCustomizationId.value}/proofs`, {
      preview_file_path: previewForm.preview_file_path,
      annotated_notes: previewForm.response_notes,
    });
    setFlash('Proof generated.', 'success');
    await loadDesignProofs(selectedCustomizationId.value);
    await loadDesignCustomizations();
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function respondProof(proofId, status) {
  if (!selectedCustomizationId.value) return;
  busy.value = true;
  try {
    await api('post', `/api/design-customizations/${selectedCustomizationId.value}/proofs/${proofId}/respond`, { status, annotated_notes: status === 'approved' ? 'Approved from dashboard.' : 'Rejected from dashboard.' });
    setFlash(`Proof ${status}.`, 'success');
    await loadDesignProofs(selectedCustomizationId.value);
    await loadDesignCustomizations();
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function createDesignPost() {
  busy.value = true;
  try {
    await api('post', '/api/design-posts', designPostForm);
    setFlash('Community post created.', 'success');
    await loadDesignPosts();
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function createBargainingOffer() {
  busy.value = true;
  try {
    await api('post', `/api/design-posts/${bargainingForm.design_post_id}/bargaining-offers`, {
      amount: bargainingForm.amount,
      estimated_days: bargainingForm.estimated_days,
      message: bargainingForm.message,
    });
    setFlash('Bargaining offer sent.', 'success');
    const { data } = await api('get', `/api/design-posts/${bargainingForm.design_post_id}/bargaining-offers`);
    bargainingOffers.value = data;
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function loadBargainingOffers(postId) {
  if (!postId) return;
  try {
    const { data } = await api('get', `/api/design-posts/${postId}/bargaining-offers`);
    bargainingOffers.value = data;
  } catch (err) {
    bargainingOffers.value = [];
  }
}

async function createShopProject() {
  busy.value = true;
  try {
    await api('post', '/api/shop-projects', projectForm);
    setFlash('Shop project created.', 'success');
    await loadShopProjects();
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

async function orderShopProject(projectId) {
  busy.value = true;
  try {
    await api('post', `/api/shop-projects/${projectId}/order`, projectOrderForm);
    setFlash('Shop project ordered successfully.', 'success');
    await loadOrders();
    activeSection.value = 'orders';
  } catch (err) {
    setFlash(getErrorMessage(err), 'error');
  } finally {
    busy.value = false;
  }
}

function goToProfile() {
  window.location.href = '/profile';
}

async function logout() {
  try {
    await api('post', '/api/auth/logout');
  } catch (err) {
    console.warn(err);
  }
  window.localStorage.removeItem('embro_token');
  applyApiToken('');
  window.location.href = '/';
}

onMounted(bootstrap);
</script>

<template>
  <Head title="Operations Workspace" />

  <div v-if="loading" class="flex min-h-screen items-center justify-center bg-stone-100 text-sm text-stone-500">Loading workspace…</div>

  <AppWorkspaceLayout v-else>
    <template #sidebar>
      <WorkspaceSidebar :items="sectionConfig" :active-key="activeSection" :user="user" @change="activeSection = $event" />
    </template>

    <template #header>
      <WorkspaceHeader :title="pageTitle" :subtitle="sectionSubtitle" :user="user" @logout="logout" @profile="goToProfile">
        <template #actions>
          <button
            v-if="canRefreshAnalytics"
            type="button"
            class="rounded-2xl border border-stone-300 px-4 py-2.5 text-sm font-medium text-stone-700 transition hover:bg-stone-50"
            @click="refreshAnalytics"
          >
            Refresh analytics
          </button>
        </template>
      </WorkspaceHeader>

      <div class="rounded-2xl border border-stone-200 bg-white px-3 py-2 shadow-sm xl:hidden">
        <div class="flex gap-2 overflow-x-auto pb-1">
          <button
            v-for="item in sectionConfig"
            :key="item.key"
            type="button"
            class="whitespace-nowrap rounded-2xl px-4 py-2 text-sm font-medium transition"
            :class="activeSection === item.key ? 'bg-stone-900 text-white' : 'bg-stone-100 text-stone-700'"
            @click="activeSection = item.key"
          >
            {{ item.label }}
          </button>
        </div>
      </div>

      <div
        v-if="flash.text"
        class="rounded-2xl border px-4 py-3 text-sm shadow-sm"
        :class="flash.type === 'error' ? 'border-red-200 bg-red-50 text-red-700' : flash.type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-stone-200 bg-white text-stone-700'"
      >
        {{ flash.text }}
      </div>
    </template>

    <div class="space-y-4">
      <template v-if="activeSection === 'overview'">
        <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
          <StatCard label="Orders" :value="orders.length" hint="Live records available in the workspace." />
          <StatCard label="Notifications" :value="notifications.length" hint="Unread and recent operational updates." />
          <StatCard label="Assignments" :value="assignments.length" hint="Current tasks tied to the selected order." />
          <StatCard label="Current shop" :value="currentShopId || '—'" hint="Context for owner and shop staff workflows." />
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.3fr_0.9fr]">
          <SectionCard title="Orders in focus" description="Keep one order selected, then use the middle workspace for actions and follow-up.">
            <div class="grid gap-3 lg:grid-cols-2 2xl:grid-cols-3">
              <button
                v-for="order in orders.slice(0, 6)"
                :key="order.id"
                type="button"
                class="rounded-2xl border px-4 py-4 text-left transition"
                :class="selectedOrderId === order.id ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-200 bg-stone-50 hover:border-stone-300'"
                @click="selectedOrderId = order.id; activeSection = 'orders'"
              >
                <div class="text-sm font-semibold">{{ order.order_number }}</div>
                <div class="mt-2 text-xs capitalize" :class="selectedOrderId === order.id ? 'text-stone-300' : 'text-stone-500'">{{ order.status }} · {{ order.current_stage }}</div>
                <div class="mt-4 text-sm font-medium">₱{{ Number(order.total_amount || 0).toLocaleString() }}</div>
              </button>
            </div>
            <EmptyState v-if="!orders.length" title="No orders yet" description="Create the first order from the client workspace to start the flow." />
          </SectionCard>

          <SectionCard title="Selected order summary" description="A quick operational snapshot before you switch to detailed panels.">
            <div v-if="selectedOrder" class="space-y-3 text-sm text-stone-600">
              <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="text-xs uppercase tracking-[0.2em] text-stone-500">Status</div>
                <div class="mt-2 text-base font-semibold capitalize text-stone-900">{{ selectedOrder.status }}</div>
                <div class="mt-2">Stage: <span class="capitalize text-stone-900">{{ selectedOrder.current_stage }}</span></div>
                <div class="mt-1">Payment: <span class="capitalize text-stone-900">{{ selectedOrder.payment_status }}</span></div>
                <div class="mt-1">Fulfillment: <span class="capitalize text-stone-900">{{ selectedOrder.fulfillment_type }}</span></div>
              </div>
              <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="text-xs uppercase tracking-[0.2em] text-stone-500">Notes</div>
                <div class="mt-2 leading-6">{{ selectedOrder.customer_notes || 'No customer notes available.' }}</div>
              </div>
            </div>
            <EmptyState v-else title="No order selected" description="Choose an order from the left sidebar or the overview cards to start working." />
          </SectionCard>
        </div>
      </template>

      <template v-if="activeSection === 'orders'">
        <div class="grid gap-4 2xl:grid-cols-[340px_minmax(0,1fr)]">
          <SectionCard title="Order list" description="Select one order at a time to keep the workspace organized.">
            <div class="max-h-[40rem] space-y-3 overflow-auto pr-1">
              <button
                v-for="order in orders"
                :key="order.id"
                type="button"
                class="w-full rounded-2xl border px-4 py-3 text-left transition"
                :class="selectedOrderId === order.id ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-200 bg-stone-50 hover:border-stone-300'"
                @click="selectedOrderId = order.id"
              >
                <div class="text-sm font-semibold">{{ order.order_number }}</div>
                <div class="mt-1 text-xs capitalize" :class="selectedOrderId === order.id ? 'text-stone-300' : 'text-stone-500'">{{ order.status }} · {{ order.current_stage }}</div>
                <div class="mt-3 text-sm font-medium">₱{{ Number(order.total_amount || 0).toLocaleString() }}</div>
              </button>
              <EmptyState v-if="!orders.length" title="No orders available" description="Orders will appear here once created through the client flow." />
            </div>
          </SectionCard>

          <div class="space-y-4">
            <SectionCard title="Order detail" description="Focused information and actions for the currently selected order.">
              <template #actions>
                <button
                  v-if="canCancelOrders"
                  type="button"
                  class="rounded-2xl border border-red-300 px-4 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-50"
                  @click="cancelSelectedOrder"
                >
                  Cancel order
                </button>
              </template>

              <div v-if="selectedOrder" class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-600">
                  <div class="text-xs uppercase tracking-[0.2em] text-stone-500">Order status</div>
                  <div class="mt-2 text-lg font-semibold capitalize text-stone-900">{{ selectedOrder.status }}</div>
                  <div class="mt-3">Stage: <span class="capitalize text-stone-900">{{ selectedOrder.current_stage }}</span></div>
                  <div class="mt-1">Payment: <span class="capitalize text-stone-900">{{ selectedOrder.payment_status }}</span></div>
                  <div class="mt-1">Fulfillment: <span class="capitalize text-stone-900">{{ selectedOrder.fulfillment_type }}</span></div>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-600">
                  <div class="text-xs uppercase tracking-[0.2em] text-stone-500">Customer notes</div>
                  <div class="mt-2 leading-6">{{ selectedOrder.customer_notes || 'No notes from the customer.' }}</div>
                </div>
                <div v-if="risk" class="lg:col-span-2 rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-600">
                  <div class="text-xs uppercase tracking-[0.2em] text-stone-500">Risk insight</div>
                  <pre class="mt-3 whitespace-pre-wrap text-xs leading-6 text-stone-700">{{ JSON.stringify(risk, null, 2) }}</pre>
                </div>
              </div>
              <EmptyState v-else title="Select an order" description="Use the order list on the left to load detailed data into the center workspace." />
            </SectionCard>

            <div class="grid gap-4 xl:grid-cols-2">
              <SectionCard v-if="isClient" title="Create order" description="Start a new request using live backend data.">
                <div class="space-y-3">
                  <select v-model="createOrderForm.shop_id" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                    <option disabled value="">Select shop</option>
                    <option v-for="shop in shops" :key="shop.id" :value="shop.id">{{ shop.shop_name || `Shop #${shop.id}` }}</option>
                  </select>
                  <div class="grid gap-3 sm:grid-cols-2">
                    <input v-model="createOrderForm.item_name" type="text" placeholder="Item name" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                    <select v-model="createOrderForm.fulfillment_type" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                      <option value="pickup">Pickup</option>
                      <option value="delivery">Delivery</option>
                    </select>
                  </div>
                  <div class="grid gap-3 sm:grid-cols-2">
                    <input v-model="createOrderForm.quantity" type="number" min="1" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900" placeholder="Quantity">
                    <input v-model="createOrderForm.unit_price" type="number" min="0" step="0.01" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900" placeholder="Unit price">
                  </div>
                  <input v-if="createOrderForm.fulfillment_type === 'delivery'" v-model="createOrderForm.delivery_address" type="text" placeholder="Delivery address" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                  <textarea v-model="createOrderForm.customer_notes" rows="4" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900" placeholder="Order notes"></textarea>
                  <button type="button" class="w-full rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-stone-800 disabled:opacity-60" :disabled="busy" @click="submitClientOrder">
                    Submit order
                  </button>
                </div>
              </SectionCard>

              <SectionCard v-if="isClient && selectedOrderId" title="Submit payment" description="Attach a payment to the selected order using the live API.">
                <div class="space-y-3">
                  <div class="grid gap-3 sm:grid-cols-2">
                    <select v-model="paymentForm.payment_type" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                      <option value="downpayment">Downpayment</option>
                      <option value="partial">Partial</option>
                      <option value="full">Full</option>
                    </select>
                    <input v-model="paymentForm.amount" type="number" min="1" step="0.01" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900" placeholder="Amount">
                  </div>
                  <input v-model="paymentForm.transaction_reference" type="text" placeholder="Transaction reference" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                  <input v-model="paymentForm.payer_name" type="text" placeholder="Payer name" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                  <textarea v-model="paymentForm.notes" rows="3" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900" placeholder="Payment notes"></textarea>
                  <button type="button" class="w-full rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-stone-800 disabled:opacity-60" :disabled="busy" @click="submitPayment">
                    Submit payment
                  </button>
                </div>
              </SectionCard>
            </div>
          </div>
        </div>
      </template>

      <template v-if="activeSection === 'operations'">
        <div class="grid gap-4 2xl:grid-cols-2">
          <SectionCard title="Assignments" description="Live task queue for the selected order.">
            <div class="space-y-3">
              <div v-for="assignment in assignments" :key="assignment.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <div class="text-sm font-semibold capitalize text-stone-900">{{ assignment.assignment_type }}</div>
                    <div class="mt-1 text-xs capitalize text-stone-500">{{ assignment.status }} · {{ assignment.assignment_role }}</div>
                    <div class="mt-3 text-sm text-stone-600">Assigned to #{{ assignment.assigned_to }} · Assigned by #{{ assignment.assigned_by }}</div>
                    <div class="mt-1 text-sm leading-6 text-stone-700">{{ assignment.notes || 'No notes.' }}</div>
                  </div>
                  <div class="flex flex-wrap gap-2" v-if="isStaff || canManageAssignments || isAdmin">
                    <button v-if="assignment.status === 'assigned'" type="button" class="rounded-2xl border border-stone-300 px-3 py-2 text-xs font-medium text-stone-700 hover:bg-white" @click="takeAssignment(assignment.id, 'accept')">Accept</button>
                    <button v-if="['assigned', 'in_progress'].includes(assignment.status)" type="button" class="rounded-2xl bg-stone-900 px-3 py-2 text-xs font-medium text-white hover:bg-stone-800" @click="takeAssignment(assignment.id, 'complete')">Complete</button>
                  </div>
                </div>
              </div>
              <EmptyState v-if="!assignments.length" title="No assignments yet" description="Assignments will appear here once the shop starts work on the selected order." />
            </div>
          </SectionCard>

          <SectionCard v-if="canManageAssignments" title="Create assignment" description="Manual assignment is still available when automatic assignment needs an override.">
            <div class="space-y-3">
              <select v-model="assignmentForm.assignment_role" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <option value="staff">Staff</option>
                <option value="hr">HR</option>
              </select>
              <select v-model="assignmentForm.assignment_type" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <option value="digitizing">Digitizing</option>
                <option value="embroidery">Embroidery</option>
                <option value="quality_check">Quality check</option>
                <option value="packing">Packing</option>
                <option value="delivery">Delivery</option>
              </select>
              <input v-model="assignmentForm.assigned_to" type="number" min="1" placeholder="Assigned user ID (optional)" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              <textarea v-model="assignmentForm.notes" rows="4" placeholder="Assignment notes" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900"></textarea>
              <button type="button" class="w-full rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-stone-800 disabled:opacity-60" :disabled="busy || !selectedOrderId" @click="createAssignment">
                Create assignment
              </button>
            </div>
          </SectionCard>

          <SectionCard title="Revisions" description="Request, review, approve, and implement revision changes without crowding the screen.">
            <div class="space-y-3">
              <div v-for="revision in revisions" :key="revision.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <div class="text-sm font-semibold capitalize text-stone-900">{{ revision.revision_type }}</div>
                    <div class="mt-1 text-xs capitalize text-stone-500">{{ revision.status }}</div>
                    <div class="mt-3 text-sm leading-6 text-stone-700">{{ revision.request_notes }}</div>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <button v-if="(isStaff || canManageAssignments) && revision.status === 'requested'" type="button" class="rounded-2xl border border-stone-300 px-3 py-2 text-xs font-medium text-stone-700 hover:bg-white" @click="claimRevision(revision.id)">Claim</button>
                    <button v-if="(isClient || isAdmin) && revision.status === 'preview_uploaded'" type="button" class="rounded-2xl border border-emerald-300 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50" @click="approveRevision(revision.id, 'approve')">Approve</button>
                    <button v-if="(isClient || isAdmin) && revision.status === 'preview_uploaded'" type="button" class="rounded-2xl border border-amber-300 px-3 py-2 text-xs font-medium text-amber-700 hover:bg-amber-50" @click="approveRevision(revision.id, 'reject')">Reject</button>
                    <button v-if="(isStaff || canManageAssignments) && revision.status === 'approved'" type="button" class="rounded-2xl bg-stone-900 px-3 py-2 text-xs font-medium text-white hover:bg-stone-800" @click="implementRevision(revision.id)">Implement</button>
                  </div>
                </div>
              </div>
              <EmptyState v-if="!revisions.length" title="No revisions for this order" description="Use the revision form to request changes only when needed." />
            </div>
          </SectionCard>

          <SectionCard v-if="isClient || canManageAssignments || isStaff" title="Revision actions" description="Use a small dedicated form instead of mixing revision controls everywhere.">
            <div class="space-y-3">
              <template v-if="isClient">
                <select v-model="revisionForm.revision_type" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                  <option value="color_change">Color change</option>
                  <option value="design_change">Design change</option>
                  <option value="placement_change">Placement change</option>
                </select>
                <textarea v-model="revisionForm.request_notes" rows="4" placeholder="Describe the revision needed" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900"></textarea>
                <button type="button" class="w-full rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-stone-800 disabled:opacity-60" :disabled="busy || !selectedOrderId" @click="createRevision">
                  Request revision
                </button>
              </template>

              <template v-if="isStaff || canManageAssignments">
                <input v-model="previewForm.preview_file_path" type="text" placeholder="Preview file path" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <textarea v-model="previewForm.response_notes" rows="4" placeholder="Preview notes" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900"></textarea>
                <button type="button" class="w-full rounded-2xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-800 transition hover:bg-stone-50 disabled:opacity-60" :disabled="busy || !selectedOrderId || !revisions.length" @click="uploadPreview(revisions[0].id)">
                  Upload preview for first listed revision
                </button>
              </template>
            </div>
          </SectionCard>

          <SectionCard v-if="canManageFulfillment || isClient" title="Fulfillment" description="A separate fulfillment panel keeps shipping and pickup tasks out of the main order card.">
            <div class="grid gap-4 xl:grid-cols-2">
              <div>
                <div v-if="fulfillment" class="rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-700">
                  <div><span class="font-medium text-stone-900">Type:</span> {{ fulfillment.fulfillment_type }}</div>
                  <div class="mt-1"><span class="font-medium text-stone-900">Status:</span> {{ fulfillment.status }}</div>
                  <div class="mt-1"><span class="font-medium text-stone-900">Receiver:</span> {{ fulfillment.receiver_name || '—' }}</div>
                  <div class="mt-1"><span class="font-medium text-stone-900">Tracking:</span> {{ fulfillment.tracking_number || '—' }}</div>
                  <div class="mt-1"><span class="font-medium text-stone-900">Notes:</span> {{ fulfillment.notes || '—' }}</div>
                </div>
                <EmptyState v-else title="No fulfillment loaded" description="Select an order with a fulfillment record to view its status here." />
              </div>
              <div v-if="canManageFulfillment" class="space-y-3">
                <input v-model="fulfillmentForm.receiver_name" type="text" placeholder="Receiver name" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <input v-model="fulfillmentForm.receiver_contact" type="text" placeholder="Receiver contact" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <input v-model="fulfillmentForm.delivery_address" type="text" placeholder="Delivery address" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <div class="grid gap-3 sm:grid-cols-2">
                  <input v-model="fulfillmentForm.courier_name" type="text" placeholder="Courier" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                  <input v-model="fulfillmentForm.tracking_number" type="text" placeholder="Tracking number" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                </div>
                <input v-model="fulfillmentForm.shipping_fee" type="number" min="0" step="0.01" placeholder="Shipping fee" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <textarea v-model="fulfillmentForm.notes" rows="3" placeholder="Fulfillment notes" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900"></textarea>
                <div class="grid gap-3 sm:grid-cols-2">
                  <button type="button" class="rounded-2xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-800 transition hover:bg-stone-50" :disabled="busy || !selectedOrderId" @click="saveFulfillment">Save</button>
                  <button type="button" class="rounded-2xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-800 transition hover:bg-stone-50" :disabled="busy || !selectedOrderId" @click="runFulfillmentAction('ready')">Ready</button>
                  <button type="button" class="rounded-2xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-800 transition hover:bg-stone-50" :disabled="busy || !selectedOrderId" @click="runFulfillmentAction('shipped')">Shipped</button>
                  <button type="button" class="rounded-2xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-800 transition hover:bg-stone-50" :disabled="busy || !selectedOrderId" @click="runFulfillmentAction('delivered')">Delivered</button>
                  <button type="button" class="sm:col-span-2 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-stone-800 disabled:opacity-60" :disabled="busy || !selectedOrderId" @click="runFulfillmentAction('picked-up')">Picked up</button>
                </div>
              </div>
            </div>
          </SectionCard>
        </div>
      </template>

      <template v-if="activeSection === 'analytics'">
        <div class="grid gap-4 2xl:grid-cols-[1fr_1fr]">
          <SectionCard v-if="canSeeAnalytics" title="Shop metrics" description="Operational metrics stay in a dedicated page instead of cluttering the main workspace.">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-4">
              <StatCard label="Total orders" :value="metrics?.total_orders ?? 0" />
              <StatCard label="Completed" :value="metrics?.completed_orders ?? 0" />
              <StatCard label="Completion rate" :value="metrics?.completion_rate ?? 0" />
              <StatCard label="Delay risk" :value="metrics?.delay_risk_score ?? 0" />
            </div>
            <div class="mt-4 rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-600">
              <div>Review count: <span class="font-medium text-stone-900">{{ metrics?.review_count ?? 0 }}</span></div>
              <div class="mt-1">Revenue total: <span class="font-medium text-stone-900">{{ metrics?.revenue_total ?? 0 }}</span></div>
              <div class="mt-1">Recommendation score: <span class="font-medium text-stone-900">{{ metrics?.recommendation_score ?? 0 }}</span></div>
            </div>
          </SectionCard>

          <SectionCard v-if="recommendations.length" title="Recommendations" description="Client-facing and admin-audit recommendation results in one clean list.">
            <div class="space-y-3">
              <div v-for="recommendation in recommendations" :key="recommendation.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm font-semibold text-stone-900">Shop #{{ recommendation.shop_id }}</div>
                    <div class="mt-1 text-xs text-stone-500">{{ recommendation.basis }} · Rank {{ recommendation.rank_position }}</div>
                  </div>
                  <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-stone-700">Score {{ recommendation.score }}</div>
                </div>
              </div>
            </div>
          </SectionCard>

          <SectionCard v-if="risk" title="Selected order risk" description="Keep order risk in analytics where it belongs, not mixed into every card.">
            <pre class="whitespace-pre-wrap text-xs leading-6 text-stone-700">{{ JSON.stringify(risk, null, 2) }}</pre>
          </SectionCard>
        </div>
      </template>
    </div>


      <template v-if="activeSection === 'studio'">
        <div class="grid gap-4 2xl:grid-cols-[1.1fr_0.9fr]">
          <SectionCard title="Design customization studio" description="Capture embroidery-specific details and let the system suggest pricing automatically.">
            <div class="grid gap-3 md:grid-cols-2">
              <input v-model="customizationForm.name" type="text" placeholder="Customization name" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 md:col-span-2">
              <select v-model="customizationForm.design_post_id" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <option value="">Link to community post (optional)</option>
                <option v-for="post in designPosts" :key="post.id" :value="post.id">{{ post.title }}</option>
              </select>
              <input v-model="customizationForm.order_id" type="number" min="1" placeholder="Order ID (optional)" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              <input v-model="customizationForm.garment_type" type="text" placeholder="Garment type" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              <input v-model="customizationForm.placement_area" type="text" placeholder="Placement area" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              <input v-model="customizationForm.width_mm" type="number" min="1" placeholder="Width (mm)" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              <input v-model="customizationForm.height_mm" type="number" min="1" placeholder="Height (mm)" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              <input v-model="customizationForm.color_count" type="number" min="1" placeholder="Color count" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              <input v-model="customizationForm.stitch_count_estimate" type="number" min="1" placeholder="Estimated stitches" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              <select v-model="customizationForm.complexity_level" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <option value="simple">Simple</option><option value="standard">Standard</option><option value="complex">Complex</option><option value="premium">Premium</option>
              </select>
              <input v-model="customizationForm.quantity" type="number" min="1" placeholder="Quantity" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              <textarea v-model="customizationForm.notes" rows="4" placeholder="Customization notes" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900 md:col-span-2"></textarea>
              <button type="button" class="md:col-span-2 w-full rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="createCustomization">Save customization + auto estimate</button>
            </div>
          </SectionCard>
          <SectionCard title="Proofs and estimates" description="Generate proofs and approve or reject them with price snapshots attached.">
            <div class="space-y-3">
              <div v-for="item in designCustomizations" :key="item.id" class="rounded-2xl border p-4" :class="selectedCustomizationId===item.id ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-200 bg-stone-50'" @click="selectedCustomizationId=item.id">
                <div class="flex items-center justify-between gap-3"><div><div class="text-sm font-semibold">{{ item.name }}</div><div class="mt-1 text-xs capitalize" :class="selectedCustomizationId===item.id ? 'text-stone-300' : 'text-stone-500'">{{ item.status }}</div></div><div class="text-sm font-semibold">₱{{ Number(item.estimated_total_price || 0).toLocaleString() }}</div></div>
              </div>
              <div v-if="selectedCustomization" class="rounded-2xl border border-stone-200 bg-white p-4">
                <div class="text-xs uppercase tracking-[0.2em] text-stone-500">Selected estimate</div>
                <pre class="mt-3 whitespace-pre-wrap text-xs leading-6 text-stone-700">{{ JSON.stringify(selectedCustomization.pricing_breakdown_json, null, 2) }}</pre>
                <div class="mt-4 space-y-2">
                  <input v-model="previewForm.preview_file_path" type="text" placeholder="Proof preview path" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                  <textarea v-model="previewForm.response_notes" rows="3" placeholder="Proof notes" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900"></textarea>
                  <button type="button" class="w-full rounded-2xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-800" :disabled="busy" @click="generateProof">Generate proof</button>
                </div>
                <div class="mt-4 space-y-2" v-if="designProofs.length">
                  <div v-for="proof in designProofs" :key="proof.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-3 text-sm">
                    <div class="flex items-center justify-between"><span>Proof #{{ proof.proof_no }}</span><span class="capitalize">{{ proof.status }}</span></div>
                    <div class="mt-2 text-xs text-stone-500">{{ proof.preview_file_path }}</div>
                    <div class="mt-3 flex gap-2" v-if="isClient || isAdmin">
                      <button type="button" class="rounded-2xl border border-emerald-300 px-3 py-2 text-xs text-emerald-700" @click="respondProof(proof.id, 'approved')">Approve</button>
                      <button type="button" class="rounded-2xl border border-amber-300 px-3 py-2 text-xs text-amber-700" @click="respondProof(proof.id, 'rejected')">Reject</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </SectionCard>
        </div>
      </template>

      <template v-if="activeSection === 'marketplace'">
        <div class="grid gap-4 2xl:grid-cols-[1.1fr_0.9fr]">
          <SectionCard title="Community posts" description="Post work requests for shops to take, review, or bargain on.">
            <div class="space-y-3" v-if="isClient">
              <input v-model="designPostForm.title" type="text" placeholder="Post title" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              <textarea v-model="designPostForm.description" rows="4" placeholder="Describe the embroidery job" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900"></textarea>
              <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="designPostForm.quantity" type="number" min="1" placeholder="Quantity" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <input v-model="designPostForm.target_budget" type="number" min="0" step="0.01" placeholder="Target budget" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              </div>
              <button type="button" class="w-full rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="createDesignPost">Create community post</button>
            </div>
            <div class="mt-4 space-y-3">
              <div v-for="post in designPosts" :key="post.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4" @click="bargainingForm.design_post_id = post.id; loadBargainingOffers(post.id)">
                <div class="flex items-center justify-between gap-3"><div><div class="text-sm font-semibold text-stone-900">{{ post.title }}</div><div class="mt-1 text-xs capitalize text-stone-500">{{ post.status }} · qty {{ post.quantity }}</div></div><div class="text-sm font-semibold text-stone-900">₱{{ Number(post.target_budget || 0).toLocaleString() }}</div></div>
              </div>
            </div>
          </SectionCard>
          <SectionCard title="Bargaining and proposals" description="Counter-offer on community jobs and keep pricing negotiation recorded.">
            <div class="space-y-3">
              <select v-model="bargainingForm.design_post_id" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <option disabled value="">Select design post</option>
                <option v-for="post in designPosts" :key="post.id" :value="post.id">{{ post.title }}</option>
              </select>
              <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="bargainingForm.amount" type="number" min="0" step="0.01" placeholder="Offer amount" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <input v-model="bargainingForm.estimated_days" type="number" min="1" placeholder="Estimated days" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              </div>
              <textarea v-model="bargainingForm.message" rows="4" placeholder="Offer message" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900"></textarea>
              <button type="button" class="w-full rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy || !bargainingForm.design_post_id" @click="createBargainingOffer">Send bargaining offer</button>
              <div class="space-y-2" v-if="bargainingOffers.length">
                <div v-for="offer in bargainingOffers" :key="offer.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-3 text-sm">
                  <div class="flex items-center justify-between"><span>₱{{ Number(offer.amount).toLocaleString() }}</span><span class="capitalize">{{ offer.status }}</span></div>
                  <div class="mt-1 text-xs text-stone-500">{{ offer.message || 'No message' }}</div>
                </div>
              </div>
            </div>
          </SectionCard>
        </div>
      </template>

      <template v-if="activeSection === 'projects'">
        <div class="grid gap-4 2xl:grid-cols-[1.1fr_0.9fr]">
          <SectionCard title="Shop projects catalog" description="Owners can post ready-made projects while clients can order them instantly.">
            <div class="grid gap-3 lg:grid-cols-2">
              <div v-for="project in shopProjects" :key="project.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="text-sm font-semibold text-stone-900">{{ project.title }}</div>
                <div class="mt-1 text-xs text-stone-500">{{ project.category }} · min {{ project.min_order_qty }}</div>
                <div class="mt-3 text-sm leading-6 text-stone-700">{{ project.description }}</div>
                <div class="mt-4 flex items-center justify-between"><span class="text-sm font-semibold text-stone-900">₱{{ Number(project.base_price || 0).toLocaleString() }}</span><button v-if="isClient" type="button" class="rounded-2xl bg-stone-900 px-3 py-2 text-xs font-semibold text-white" @click="orderShopProject(project.id)">Order project</button></div>
              </div>
            </div>
          </SectionCard>
          <SectionCard v-if="isOwner || isHr" title="Create shop project" description="Publish repeatable shop offerings that clients can order directly.">
            <div class="space-y-3">
              <input v-model="projectForm.title" type="text" placeholder="Project title" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              <textarea v-model="projectForm.description" rows="4" placeholder="Describe the project" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900"></textarea>
              <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="projectForm.base_price" type="number" min="0" step="0.01" placeholder="Base price" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <input v-model="projectForm.min_order_qty" type="number" min="1" placeholder="Minimum quantity" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              </div>
              <button type="button" class="w-full rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="createShopProject">Publish project</button>
            </div>
          </SectionCard>
        </div>
      </template>

      <template v-if="activeSection === 'profile'">
        <div class="grid gap-4 2xl:grid-cols-[0.95fr_1.05fr]">
          <SectionCard title="Client profile defaults" description="Saved preferences help automate repeat orders and embroidery details.">
            <div class="space-y-3">
              <input v-model="profileForm.organization_name" type="text" placeholder="Organization name" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              <input v-model="profileForm.default_address" type="text" placeholder="Default address" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="profileForm.postal_code" type="text" placeholder="Postal code" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <select v-model="profileForm.preferred_fulfillment_type" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900"><option value="pickup">Pickup</option><option value="delivery">Delivery</option></select>
              </div>
              <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="profileForm.saved_measurements_json.chest" type="text" placeholder="Chest size" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <input v-model="profileForm.saved_measurements_json.sleeve" type="text" placeholder="Sleeve size" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              </div>
              <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="profileForm.default_garment_preferences_json.garment_type" type="text" placeholder="Preferred garment type" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
                <input v-model="profileForm.default_garment_preferences_json.fabric_type" type="text" placeholder="Preferred fabric" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900">
              </div>
              <textarea v-model="profileForm.notes" rows="4" placeholder="Profile notes" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-stone-900"></textarea>
              <button type="button" class="w-full rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="saveClientProfile">Save profile</button>
            </div>
          </SectionCard>
          <SectionCard title="Automation benefits" description="These profile defaults now feed the system with faster fulfillment and repeat-order setup.">
            <div class="space-y-3 text-sm text-stone-700">
              <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">Default delivery and address information can be reused for new orders.</div>
              <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">Garment and measurement preferences are preserved for recurring embroidery jobs.</div>
              <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">Organization information helps shops quote community and catalog work faster.</div>
            </div>
          </SectionCard>
        </div>
      </template>

    <template #right>
      <WorkspaceRightSidebar :notifications="notifications" :selected-order="selectedOrder" :assignments="assignments" :revisions="revisions" />
    </template>
  </AppWorkspaceLayout>
</template>
