<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
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
const DESIGNER_AUTOSAVE_KEY = 'embro_client_designer_autosave_v2';
const designerAutosaveStatus = ref('');
const lastSavedFingerprint = ref('');
const garmentView = ref('front');
const designerProcessing = ref(false);

const currentDesignDraftId = ref(null);
const designerCanvasRef = ref(null);
const designerFileInputRef = ref(null);
const designerReplaceInputRef = ref(null);
const dragState = reactive({ mode: null, layerId: null, pointerId: null, startX: 0, startY: 0, startLayer: null });
const designFonts = ['Inter', 'Arial', 'Helvetica', 'Georgia', 'Times New Roman', 'Courier New', 'Montserrat', 'Poppins', 'Bebas Neue'];
const garmentColorSwatches = ['#f5f5f4', '#e7e5e4', '#d6d3d1', '#111827', '#1f2937', '#1d4ed8', '#14532d', '#7f1d1d', '#f8fafc'];
const embroideryThreadPalette = [
  { name: 'Jet Black', hex: '#1c1917' },
  { name: 'Bone White', hex: '#fafaf9' },
  { name: 'Steel Gray', hex: '#6b7280' },
  { name: 'Royal Blue', hex: '#1d4ed8' },
  { name: 'Navy', hex: '#1e3a8a' },
  { name: 'Forest Green', hex: '#166534' },
  { name: 'Sun Gold', hex: '#ca8a04' },
  { name: 'Crimson', hex: '#b91c1c' },
  { name: 'Rose', hex: '#e11d48' },
  { name: 'Orange', hex: '#ea580c' },
  { name: 'Violet', hex: '#7c3aed' },
  { name: 'Teal', hex: '#0f766e' },
];
const garmentOptions = [
  { value: 'tshirt', label: 'T-shirt', base: 'Classic front body' },
  { value: 'polo', label: 'Polo', base: 'Structured chest embroidery' },
  { value: 'hoodie', label: 'Hoodie', base: 'Heavyweight front panel' },
  { value: 'cap', label: 'Cap', base: 'Curved cap crown' },
  { value: 'patch', label: 'Patch', base: 'Standalone patch base' },
  { value: 'jacket', label: 'Jacket', base: 'Outerwear placement' },
];
const placementOptions = [
  { value: 'left_chest', label: 'Left chest' },
  { value: 'center_chest', label: 'Center chest' },
  { value: 'full_front', label: 'Full front' },
  { value: 'sleeve', label: 'Sleeve' },
  { value: 'cap_front', label: 'Cap front' },
  { value: 'cap_side', label: 'Cap side' },
  { value: 'back', label: 'Back' },
  { value: 'patch_center', label: 'Patch center' },
];
const designerState = reactive({
  garment: 'polo',
  placement: 'left_chest',
  fabric: 'cotton pique',
  designType: 'logo_embroidery',
  quantity: 12,
  canvasName: '',
  notes: '',
  garmentColor: '#f5f5f4',
  layers: [],
  selectedLayerId: null,
  activeDraftLabel: '',
});


function parseHexColor(hex) {
  const normalized = String(hex || '').replace('#', '').trim();
  if (normalized.length !== 6) return null;
  const value = Number.parseInt(normalized, 16);
  if (Number.isNaN(value)) return null;
  return { r: (value >> 16) & 255, g: (value >> 8) & 255, b: value & 255 };
}
function luminance(hex) {
  const rgb = parseHexColor(hex);
  if (!rgb) return 1;
  const values = [rgb.r, rgb.g, rgb.b].map((channel) => {
    const s = channel / 255;
    return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
  });
  return 0.2126 * values[0] + 0.7152 * values[1] + 0.0722 * values[2];
}
function contrastRatio(fg, bg) {
  const l1 = luminance(fg) + 0.05;
  const l2 = luminance(bg) + 0.05;
  return l1 > l2 ? l1 / l2 : l2 / l1;
}
function stitchDensityScore(area, objectCount, colors) {
  return ((objectCount * 150) + (colors * 120)) / Math.max(700, area);
}
function friendlyComplexityLabel(key) {
  return ({ low: 'Low', medium: 'Medium', high: 'High', very_high: 'Very High' })[key] || 'Medium';
}
function designerFingerprint() {
  return JSON.stringify({
    garment: designerState.garment,
    placement: designerState.placement,
    fabric: designerState.fabric,
    garmentColor: designerState.garmentColor,
    quantity: designerState.quantity,
    notes: designerState.notes,
    width_mm: designStudioForm.width_mm,
    height_mm: designStudioForm.height_mm,
    layers: designerState.layers,
  });
}
function persistDesignerAutosave() {
  try {
    const payload = {
      saved_at: new Date().toISOString(),
      currentDesignDraftId: currentDesignDraftId.value,
      state: serializeDesignerState(),
      form: {
        name: designStudioForm.name,
        width_mm: designStudioForm.width_mm,
        height_mm: designStudioForm.height_mm,
        notes: designStudioForm.notes,
      },
    };
    window.localStorage.setItem(DESIGNER_AUTOSAVE_KEY, JSON.stringify(payload));
    designerAutosaveStatus.value = `Autosaved ${new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`;
  } catch {}
}
function restoreDesignerAutosave() {
  try {
    const raw = window.localStorage.getItem(DESIGNER_AUTOSAVE_KEY);
    if (!raw) return false;
    const payload = JSON.parse(raw);
    if (!payload?.state) return false;
    applyDraftToDesigner({
      id: payload.currentDesignDraftId || null,
      name: payload.form?.name || payload.state.canvasName || 'Embroidery design',
      garment_type: payload.state.garment,
      placement_area: payload.state.placement,
      fabric_type: payload.state.fabric,
      quantity: payload.state.quantity,
      notes: payload.form?.notes || payload.state.notes,
      width_mm: payload.form?.width_mm,
      height_mm: payload.form?.height_mm,
      design_session_json: payload.state,
    });
    designerAutosaveStatus.value = payload.saved_at ? `Restored autosave from ${new Date(payload.saved_at).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })}` : 'Restored autosaved design';
    return true;
  } catch {
    return false;
  }
}
function clearDesignerAutosave() {
  try { window.localStorage.removeItem(DESIGNER_AUTOSAVE_KEY); } catch {}
}
function handleBeforeUnload(event) {
  if (activeSection.value === 'design-studio' && designerFingerprint() !== lastSavedFingerprint.value) {
    event.preventDefault();
    event.returnValue = '';
  }
}
function rgbToHex(r, g, b) {
  return `#${[r, g, b].map((value) => Number(value || 0).toString(16).padStart(2, '0')).join('')}`;
}
function colorDistance(a, b) {
  const c1 = parseHexColor(a) || { r: 0, g: 0, b: 0 };
  const c2 = parseHexColor(b) || { r: 0, g: 0, b: 0 };
  return Math.sqrt(((c1.r - c2.r) ** 2) + ((c1.g - c2.g) ** 2) + ((c1.b - c2.b) ** 2));
}
function closestThreadColor(hex) {
  return embroideryThreadPalette.reduce((best, thread) => {
    const distance = colorDistance(hex, thread.hex);
    return distance < best.distance ? { ...thread, distance } : best;
  }, { ...embroideryThreadPalette[0], distance: Number.POSITIVE_INFINITY });
}
async function analyzeRasterDataUrl(dataUrl) {
  return await new Promise((resolve) => {
    const image = new Image();
    image.onload = () => {
      const canvas = document.createElement('canvas');
      const maxSide = 160;
      const scale = Math.min(1, maxSide / Math.max(image.width || 1, image.height || 1));
      canvas.width = Math.max(1, Math.round(image.width * scale));
      canvas.height = Math.max(1, Math.round(image.height * scale));
      const ctx = canvas.getContext('2d', { willReadFrequently: true });
      if (!ctx) {
        resolve({ palette: ['#1c1917', '#d6d3d1'], hasTransparency: false, detailScore: 0, busyBackground: false, resolutionWarning: true, edgeBackgroundHex: '#ffffff', imageDataUrl: dataUrl });
        return;
      }
      ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
      const { data } = ctx.getImageData(0, 0, canvas.width, canvas.height);
      const paletteMap = new Map();
      const borderSamples = [];
      let alphaPixels = 0;
      let detailScore = 0;
      for (let y = 0; y < canvas.height; y += 1) {
        for (let x = 0; x < canvas.width; x += 1) {
          const index = (y * canvas.width + x) * 4;
          const r = data[index];
          const g = data[index + 1];
          const b = data[index + 2];
          const a = data[index + 3];
          if (a < 245) alphaPixels += 1;
          const key = rgbToHex(Math.round(r / 32) * 32, Math.round(g / 32) * 32, Math.round(b / 32) * 32);
          paletteMap.set(key, (paletteMap.get(key) || 0) + 1);
          if (x === 0 || y === 0 || x === canvas.width - 1 || y === canvas.height - 1) borderSamples.push([r, g, b, a]);
          if (x < canvas.width - 1) {
            const ni = index + 4;
            detailScore += Math.abs(r - data[ni]) + Math.abs(g - data[ni + 1]) + Math.abs(b - data[ni + 2]);
          }
        }
      }
      const palette = [...paletteMap.entries()].sort((a, b) => b[1] - a[1]).slice(0, 8).map(([hex]) => hex);
      const edgeBackground = borderSamples.length
        ? borderSamples.reduce((acc, [r, g, b]) => ({ r: acc.r + r, g: acc.g + g, b: acc.b + b }), { r: 0, g: 0, b: 0 })
        : { r: 255, g: 255, b: 255 };
      const edgeBackgroundHex = rgbToHex(Math.round(edgeBackground.r / Math.max(1, borderSamples.length)), Math.round(edgeBackground.g / Math.max(1, borderSamples.length)), Math.round(edgeBackground.b / Math.max(1, borderSamples.length)));
      const busyBackground = palette.length >= 6 || detailScore / Math.max(1, canvas.width * canvas.height) > 58;
      resolve({
        palette: palette.length ? palette : ['#1c1917', '#d6d3d1'],
        hasTransparency: alphaPixels > 0,
        detailScore: Math.round(detailScore / Math.max(1, canvas.width * canvas.height)),
        busyBackground,
        resolutionWarning: image.width < 900 || image.height < 900,
        edgeBackgroundHex,
        imageDataUrl: canvas.toDataURL('image/png'),
      });
    };
    image.onerror = () => resolve({ palette: ['#1c1917', '#d6d3d1'], hasTransparency: false, detailScore: 0, busyBackground: false, resolutionWarning: true, edgeBackgroundHex: '#ffffff', imageDataUrl: dataUrl });
    image.src = dataUrl;
  });
}
async function collectImageMeta(file, dataUrl) {
  const raster = file.type === 'image/svg+xml' ? null : await analyzeRasterDataUrl(dataUrl);
  return await new Promise((resolve) => {
    const image = new Image();
    image.onload = () => {
      const palette = file.type === 'image/svg+xml'
        ? ['#1c1917', '#a8a29e']
        : (raster?.palette || ['#1c1917', '#57534e', '#d6d3d1']);
      const cleanupHints = [];
      if (raster?.resolutionWarning || image.width < 900 || image.height < 900 || file.size < 40000) cleanupHints.push('Low resolution source');
      if (raster?.busyBackground) cleanupHints.push('Busy background detected');
      if ((raster?.detailScore || 0) > 62) cleanupHints.push('Very detailed artwork');
      if (palette.length > 6) cleanupHints.push('Many colors detected');
      resolve({
        fileName: file.name,
        fileSize: file.size,
        mimeType: file.type,
        pixelWidth: image.width,
        pixelHeight: image.height,
        aspectRatio: image.width && image.height ? Number((image.width / image.height).toFixed(3)) : 1,
        hasTransparency: file.type === 'image/svg+xml' ? true : !!raster?.hasTransparency,
        resolutionWarning: file.type === 'image/svg+xml' ? false : !!raster?.resolutionWarning || file.size < 40000,
        palette,
        busyBackground: !!raster?.busyBackground,
        detailScore: raster?.detailScore || 0,
        cleanupHints,
        edgeBackgroundHex: raster?.edgeBackgroundHex || '#ffffff',
        originalSrc: dataUrl,
        simplifiedSrc: null,
        backgroundRemovedSrc: null,
      });
    };
    image.onerror = () => resolve({ fileName: file.name, fileSize: file.size, mimeType: file.type, pixelWidth: 0, pixelHeight: 0, hasTransparency: false, resolutionWarning: true, palette: ['#1c1917', '#a8a29e'], busyBackground: false, detailScore: 0, cleanupHints: ['Unreadable file'], edgeBackgroundHex: '#ffffff', originalSrc: dataUrl, simplifiedSrc: null, backgroundRemovedSrc: null });
    image.src = dataUrl;
  });
}
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
const revisionRequestForm = reactive({ design_id: '', reason: '' });
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

const designDrafts = computed(() => workspace.value?.design_studio?.drafts || []);
const designRequests = computed(() => workspace.value?.design_proofing?.requests || []);
const activeProofingDesign = computed(() => designRequests.value.find((item) => String(item.id) === String(proofingForm.design_id || currentDesignDraftId.value)) || designRequests.value[0] || null);
const selectedLayer = computed(() => designerState.layers.find((layer) => layer.id === designerState.selectedLayerId) || null);
const visibleLayers = computed(() => designerState.layers.filter((layer) => !layer.hidden));
const safeZone = computed(() => {
  const map = {
    left_chest: { left: 16, top: 24, width: 30, height: 30, label: 'Left chest safe zone', maxWidth: 110, maxHeight: 110 },
    center_chest: { left: 28, top: 22, width: 44, height: 34, label: 'Center chest safe zone', maxWidth: 180, maxHeight: 160 },
    full_front: { left: 20, top: 20, width: 60, height: 52, label: 'Full front safe zone', maxWidth: 280, maxHeight: 320 },
    sleeve: { left: 68, top: 28, width: 18, height: 30, label: 'Sleeve safe zone', maxWidth: 90, maxHeight: 140 },
    cap_front: { left: 27, top: 24, width: 46, height: 30, label: 'Cap front safe zone', maxWidth: 120, maxHeight: 60 },
    cap_side: { left: 61, top: 32, width: 18, height: 22, label: 'Cap side safe zone', maxWidth: 70, maxHeight: 45 },
    back: { left: 18, top: 18, width: 64, height: 56, label: 'Back safe zone', maxWidth: 300, maxHeight: 360 },
    patch_center: { left: 24, top: 24, width: 52, height: 52, label: 'Patch safe zone', maxWidth: 140, maxHeight: 140 },
  };
  return map[designerState.placement] || map.left_chest;
});
const designerValidation = computed(() => {
  const width = Number(designStudioForm.width_mm || 0);
  const height = Number(designStudioForm.height_mm || 0);
  const area = Math.max(1, width * height);
  const objectCount = designerState.layers.length;
  const textLayers = designerState.layers.filter((layer) => layer.type === 'text');
  const artworkLayers = designerState.layers.filter((layer) => layer.type === 'artwork');
  const shapeLayers = designerState.layers.filter((layer) => ['rectangle', 'circle', 'line'].includes(layer.type));
  const colorCount = estimateColorCount();
  const messages = [];
  const tinyTextLayers = textLayers.filter((layer) => Number(layer.fontSize || 0) < 16 || (layer.text || '').length > 12 && Number(layer.width || 0) < 16);
  const thinShapes = shapeLayers.filter((layer) => Number(layer.strokeWidth || 0) < 1.5 || (layer.type === 'line' && Number(layer.height || 0) < 1.8));
  const lowResArtwork = artworkLayers.filter((layer) => layer.meta?.resolutionWarning || Number(layer.meta?.pixelWidth || 0) < 900 || Number(layer.meta?.pixelHeight || 0) < 900);
  const photoLikeArtwork = artworkLayers.filter((layer) => (layer.meta?.palette?.length || 0) >= 8 || String(layer.meta?.mimeType || '').includes('jpeg'));
  const nonTransparentArtwork = artworkLayers.filter((layer) => layer.meta?.hasTransparency === false && layer.meta?.mimeType !== 'image/svg+xml');
  const busyArtwork = artworkLayers.filter((layer) => layer.meta?.busyBackground);
  const outsideZone = designerState.layers.filter((layer) => Number(layer.x || 0) < 0 || Number(layer.y || 0) < 0 || Number(layer.x || 0) + Number(layer.width || 0) > 100 || Number(layer.y || 0) + Number(layer.height || 0) > 100);
  const lowContrast = textLayers.filter((layer) => contrastRatio(layer.fill || '#1c1917', designerState.garmentColor || '#f5f5f4') < 2.4);
  if (!objectCount) messages.push({ level: 'critical', text: 'Add at least one design element before saving or submitting.' });
  if (!designerState.placement) messages.push({ level: 'critical', text: 'Choose a placement area before moving to proofing or quotation.' });
  if (!width || !height || width < 20 || height < 20) messages.push({ level: 'critical', text: 'Set a valid embroidery size of at least 20 × 20 mm.' });
  if (tinyTextLayers.length) messages.push({ level: 'high', text: 'Some text is too small for reliable embroidery and should be enlarged or simplified.' });
  if (thinShapes.length) messages.push({ level: 'high', text: 'Some line or shape strokes are too thin for embroidery and should be thickened.' });
  if (lowResArtwork.length) messages.push({ level: 'high', text: 'One or more uploaded artworks are too low in resolution for clean stitch planning.' });
  if (colorCount > 9) messages.push({ level: 'high', text: 'The design uses many colors and may need simplification before quotation or proofing.' });
  if (photoLikeArtwork.length) messages.push({ level: 'medium', text: 'Some uploaded artwork looks photo-like or heavily shaded and may need manual redesign for embroidery.' });
  if (busyArtwork.length) messages.push({ level: 'medium', text: 'Some uploaded artwork has a busy background. Use cleanup tools before requesting proofing.' });
  if (outsideZone.length) messages.push({ level: 'critical', text: 'At least one object extends outside the embroidery-safe area.' });
  if (width > safeZone.value.maxWidth || height > safeZone.value.maxHeight) messages.push({ level: 'high', text: `Current size exceeds the recommended ${safeZone.value.label.toLowerCase()} dimensions.` });
  if (stitchDensityScore(area, objectCount, colorCount) > 1.35) messages.push({ level: 'high', text: 'The design is dense for its selected size and may require simplification.' });
  if (lowContrast.length) messages.push({ level: 'medium', text: 'Some text colors have weak contrast against the selected garment color.' });
  if (nonTransparentArtwork.length) messages.push({ level: 'medium', text: 'Some uploaded artwork may contain a background that should be cleaned before proofing.' });
  if (objectCount > 10) messages.push({ level: 'medium', text: 'The design has many objects in one area and may need cleanup before review.' });

  const detailScore = Math.round(
    area / 1000 +
    objectCount * 6 +
    textLayers.length * 8 +
    colorCount * 7 +
    tinyTextLayers.length * 8 +
    thinShapes.length * 8 +
    lowResArtwork.length * 8 +
    photoLikeArtwork.length * 10 +
    (designerState.garment === 'cap' ? 10 : 0) +
    (['sleeve', 'cap_side'].includes(designerState.placement) ? 8 : 0)
  );
  let complexityKey = 'low';
  if (detailScore >= 78) complexityKey = 'very_high';
  else if (detailScore >= 56) complexityKey = 'high';
  else if (detailScore >= 30) complexityKey = 'medium';
  const complexityReasons = [];
  if (tinyTextLayers.length) complexityReasons.push('small text');
  if (colorCount >= 6) complexityReasons.push(`${colorCount} color regions`);
  if (objectCount >= 6) complexityReasons.push('many elements');
  if (photoLikeArtwork.length) complexityReasons.push('photo-like artwork');
  if (busyArtwork.length) complexityReasons.push('busy background cleanup');
  if (!complexityReasons.length) complexityReasons.push('a manageable layer count and clean placement');

  const stitchEstimate = Math.max(900, Math.round(
    area * 0.14 +
    objectCount * 420 +
    textLayers.reduce((sum, layer) => sum + ((layer.text?.length || 0) * Math.max(16, Number(layer.fontSize || 16)) * 0.7), 0) +
    colorCount * 240 +
    thinShapes.length * 80 +
    (designerState.placement === 'full_front' ? 680 : 0) +
    (designerState.garment === 'cap' ? 340 : 0)
  ));

  const readiness = !objectCount
    ? { label: 'Not Ready', tone: 'bg-rose-50 text-rose-700 border-rose-200' }
    : messages.some((item) => item.level === 'critical')
      ? { label: 'Not Ready', tone: 'bg-rose-50 text-rose-700 border-rose-200' }
      : messages.some((item) => item.level === 'high')
        ? { label: 'Needs Cleanup', tone: 'bg-amber-50 text-amber-700 border-amber-200' }
        : messages.some((item) => item.level === 'medium')
          ? { label: 'Ready for Quotation', tone: 'bg-sky-50 text-sky-700 border-sky-200' }
          : stitchEstimate > 9000
            ? { label: 'Ready for Production Review', tone: 'bg-violet-50 text-violet-700 border-violet-200' }
            : { label: 'Ready for Proofing', tone: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
  return {
    messages,
    readiness,
    stitchEstimate,
    complexity: { label: friendlyComplexityLabel(complexityKey), reason: `${friendlyComplexityLabel(complexityKey)} complexity due to ${complexityReasons.join(', ')}.` },
    productionDifficulty: stitchEstimate >= 10000 ? 'Extended run' : stitchEstimate >= 6000 ? 'Moderate run' : 'Quick run',
    digitizingDifficulty: ['High', 'Very High'].includes(friendlyComplexityLabel(complexityKey)) ? 'Designer review recommended' : friendlyComplexityLabel(complexityKey) === 'Medium' ? 'Standard digitizing review' : 'Straightforward setup',
    runtimeCategory: stitchEstimate >= 10000 ? 'Long machine time' : stitchEstimate >= 6000 ? 'Medium machine time' : 'Short machine time',
    checklist: [
      { label: 'Garment and placement selected', passed: !!designerState.garment && !!designerState.placement },
      { label: 'At least one design element added', passed: objectCount > 0 },
      { label: 'Valid embroidery dimensions set', passed: width >= 20 && height >= 20 },
      { label: 'No blocking embroidery issues', passed: !messages.some((item) => item.level === 'critical') },
    ],
  };
});
const designerWarnings = computed(() => designerValidation.value.messages);
const designerBlockingIssues = computed(() => designerWarnings.value.filter((item) => item.level === 'critical'));
const readinessState = computed(() => designerValidation.value.readiness);
const stitchEstimate = computed(() => designerValidation.value.stitchEstimate);
const complexityLevel = computed(() => designerValidation.value.complexity.label);
const complexityReason = computed(() => designerValidation.value.complexity.reason);
const productionDifficulty = computed(() => designerValidation.value.productionDifficulty);
const digitizingDifficulty = computed(() => designerValidation.value.digitizingDifficulty);
const runtimeCategory = computed(() => designerValidation.value.runtimeCategory);
const readinessChecklist = computed(() => designerValidation.value.checklist);
const threadPaletteSummary = computed(() => threadMappingSummary());
const selectedArtworkCleanupHints = computed(() => (selectedLayer.value?.type === 'artwork' ? (selectedLayer.value.meta?.cleanupHints || []) : []));
const canvasSummary = computed(() => ({
  layers: designerState.layers.length,
  visibleLayers: visibleLayers.value.length,
  colors: estimateColorCount(),
  stitchEstimate: stitchEstimate.value,
  complexity: complexityLevel.value,
  readiness: readinessState.value.label,
}));
const hasUnsavedChanges = computed(() => designerFingerprint() !== lastSavedFingerprint.value);


watch(activeSection, (value) => {
  if (value === 'design-studio') initializeDesignerFromWorkspace();
});
watch([() => designerState.garment, () => designerState.placement], () => {
  ensureDesignerDefaults();
  syncDesignerFormFromState();
});
watch([stitchEstimate, complexityLevel], () => {
  designStudioForm.stitch_count_estimate = stitchEstimate.value;
  designStudioForm.complexity_level = complexityLevel.value;
  designStudioForm.color_count = estimateColorCount();
});
watch(() => designerState.notes, (value) => { designStudioForm.notes = value; });
watch(() => addressForm.city_municipality, (value) => {
  if (!currentBarangays.value.includes(addressForm.barangay)) {
    addressForm.barangay = '';
  }
  if (value === 'Bacoor' && !addressForm.postal_code) addressForm.postal_code = '4102';
  if (value === 'Imus' && !addressForm.postal_code) addressForm.postal_code = '4103';
  if (value === 'Dasmariñas' && !addressForm.postal_code) addressForm.postal_code = '4114';
});
watch(() => [designerState.layers, designStudioForm.width_mm, designStudioForm.height_mm, designerState.garment, designerState.placement, designerState.fabric, designerState.garmentColor, designerState.notes, designerState.quantity], () => {
  if (activeSection.value === 'design-studio') persistDesignerAutosave();
}, { deep: true });
watch(() => designerState.garment, () => {
  garmentView.value = 'front';
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
function designProgressLabel(item) {
  if (!item) return 'Draft in progress';
  if (item.digitizing_status === 'approved_for_machine' || item.machine_file_status === 'approved') return 'Production preparation';
  if (['pending_digitizing', 'in_digitizing', 'digitized', 'revision_required'].includes(item.digitizing_status)) return 'Digitizing in progress';
  if (item.approved_proof_id || item.workflow_status === 'approved' || item.status === 'approved') return 'Proof approved';
  return 'Draft in progress';
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
  } catch (err) {
    if (err?.response?.status === 401 && token.value) {
      try {
        applyApiToken(token.value);
        const { data: retryMe } = await window.axios.get('/api/auth/me');
        user.value = retryMe;
        if (retryMe?.role !== 'client') return redirectForRole(retryMe?.role);
        await Promise.all([loadWorkspace(), loadProfile()]);
        return;
      } catch {}
    }
    window.localStorage.removeItem('embro_token');
    applyApiToken('');
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
  if (activeSection.value === 'design-studio' || !designerState.layers.length) initializeDesignerFromWorkspace();
}

async function loadProfile() {
  const { data } = await api('get', '/api/client-profile');
  const profile = data.profile || {};
  addressOptions.value = data.address_options || addressOptions.value;
  Object.assign(profileForm, {
    first_name: profile.first_name || '', middle_name: profile.middle_name || '', last_name: profile.last_name || '', email: profile.email || user.value?.email || '', phone_number: profile.phone_number || '', registration_date: profile.registration_date || '', billing_contact_name: profile.billing_contact_name || '', billing_phone: profile.billing_phone || '', billing_email: profile.billing_email || '', default_payment_method: profile.default_payment_method || '',
  });
}


function createId(prefix = 'layer') { return `${prefix}_${Math.random().toString(36).slice(2, 10)}`; }
function defaultLayerBase(type) {
  return { id: createId(type), type, name: type === 'text' ? 'Text layer' : type === 'artwork' ? 'Artwork layer' : `${type[0].toUpperCase()}${type.slice(1)} layer`, x: 18, y: 18, width: type === 'line' ? 38 : 28, height: type === 'text' ? 12 : 24, rotation: 0, opacity: 1, hidden: false, locked: false, fill: '#1c1917' };
}
function syncDesignerFormFromState() {
  designStudioForm.name = designerState.canvasName || designStudioForm.name || 'Embroidery design';
  designStudioForm.garment_type = designerState.garment;
  designStudioForm.placement_area = designerState.placement;
  designStudioForm.fabric_type = designerState.fabric;
  designStudioForm.quantity = designerState.quantity || 1;
  designStudioForm.color_count = estimateColorCount();
  designStudioForm.stitch_count_estimate = stitchEstimate.value;
  designStudioForm.complexity_level = complexityLevel.value;
  designStudioForm.notes = designerState.notes;
}
function estimateColorCount() {
  const colors = new Set();
  designerState.layers.forEach((layer) => {
    if (layer.type === 'artwork') {
      (layer.meta?.palette || []).forEach((color) => colors.add(color));
    } else {
      colors.add(layer.fill || '#1c1917');
    }
  });
  return Math.max(colors.size, designerState.layers.length ? 1 : 0);
}
function ensureDesignerDefaults() {
  if (!designStudioForm.width_mm) designStudioForm.width_mm = String(safeZone.value.maxWidth);
  if (!designStudioForm.height_mm) designStudioForm.height_mm = String(safeZone.value.maxHeight);
  if (!designStudioForm.name) designStudioForm.name = 'Embroidery design';
  syncDesignerFormFromState();
}
function selectLayer(id) { designerState.selectedLayerId = id; }
function addTextLayer() {
  const layer = { ...defaultLayerBase('text'), name: `Text ${designerState.layers.filter((item) => item.type === 'text').length + 1}`, text: 'Embroidery', fontFamily: 'Inter', fontSize: 28, fontWeight: 700, letterSpacing: 0, lineHeight: 1.1, textAlign: 'center', textCase: 'normal', fill: '#1c1917', width: 34, height: 14, curveStrength: 0, outlineWidth: 0, outlineColor: '#fafaf9' };
  designerState.layers.push(layer);
  selectLayer(layer.id);
}
function addShapeLayer(shape) {
  const layer = { ...defaultLayerBase(shape), name: `${shape[0].toUpperCase()}${shape.slice(1)} ${designerState.layers.filter((item) => item.type === shape).length + 1}`, width: shape === 'line' ? 44 : 20, height: shape === 'line' ? 2 : 20, stroke: '#1c1917', strokeWidth: shape === 'line' ? 2 : 1.5, fill: shape === 'line' ? '#1c1917' : '#d6d3d1' };
  designerState.layers.push(layer);
  selectLayer(layer.id);
}
function triggerArtworkUpload(replace = false) {
  (replace ? designerReplaceInputRef.value : designerFileInputRef.value)?.click();
}
function readFileAsDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

async function processRasterArtworkSource(src, processor) {
  return await new Promise((resolve, reject) => {
    const image = new Image();
    image.onload = () => {
      const canvas = document.createElement('canvas');
      canvas.width = image.width;
      canvas.height = image.height;
      const ctx = canvas.getContext('2d', { willReadFrequently: true });
      if (!ctx) {
        reject(new Error('Canvas tools are unavailable in this browser.'));
        return;
      }
      ctx.drawImage(image, 0, 0);
      const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
      processor(imageData.data, canvas.width, canvas.height);
      ctx.putImageData(imageData, 0, 0);
      resolve(canvas.toDataURL('image/png'));
    };
    image.onerror = () => reject(new Error('Unable to process the artwork file.'));
    image.src = src;
  });
}
async function refreshArtworkMeta(layer, src, patch = {}) {
  const baseMeta = layer.meta || {};
  const file = {
    name: baseMeta.fileName || `${layer.name || 'artwork'}.png`,
    size: baseMeta.fileSize || 0,
    type: baseMeta.mimeType === 'image/svg+xml' ? 'image/png' : (baseMeta.mimeType || 'image/png'),
  };
  const meta = await collectImageMeta(file, src);
  layer.src = src;
  layer.meta = { ...baseMeta, ...meta, ...patch, originalSrc: baseMeta.originalSrc || meta.originalSrc || src };
}
async function removeArtworkBackground() {
  if (!selectedLayer.value || selectedLayer.value.type !== 'artwork') return;
  if (selectedLayer.value.meta?.mimeType === 'image/svg+xml') {
    setFlash('SVG artwork already preserves vector edges and usually does not need background removal.', 'success');
    return;
  }
  designerProcessing.value = true;
  try {
    const source = selectedLayer.value.meta?.originalSrc || selectedLayer.value.src;
    const backgroundHex = selectedLayer.value.meta?.edgeBackgroundHex || '#ffffff';
    const bg = parseHexColor(backgroundHex) || { r: 255, g: 255, b: 255 };
    const processed = await processRasterArtworkSource(source, (data) => {
      for (let i = 0; i < data.length; i += 4) {
        const r = data[i];
        const g = data[i + 1];
        const b = data[i + 2];
        const distance = Math.sqrt(((r - bg.r) ** 2) + ((g - bg.g) ** 2) + ((b - bg.b) ** 2));
        const brightness = (r + g + b) / 3;
        if (distance < 42 || brightness > 246) data[i + 3] = 0;
      }
    });
    await refreshArtworkMeta(selectedLayer.value, processed, { backgroundRemovedSrc: processed, hasTransparency: true });
    setFlash('Background cleanup applied to the selected artwork.', 'success');
  } catch (error) {
    setFlash(error?.message || 'Unable to remove the background for this artwork.', 'error');
  } finally {
    designerProcessing.value = false;
  }
}
async function simplifyArtworkColors() {
  if (!selectedLayer.value || selectedLayer.value.type !== 'artwork') return;
  if (selectedLayer.value.meta?.mimeType === 'image/svg+xml') {
    setFlash('SVG artwork is already vector-friendly. Use proofing review if you still want manual simplification.', 'success');
    return;
  }
  designerProcessing.value = true;
  try {
    const source = selectedLayer.value.src;
    const processed = await processRasterArtworkSource(source, (data) => {
      for (let i = 0; i < data.length; i += 4) {
        data[i] = Math.round(data[i] / 64) * 64;
        data[i + 1] = Math.round(data[i + 1] / 64) * 64;
        data[i + 2] = Math.round(data[i + 2] / 64) * 64;
      }
    });
    await refreshArtworkMeta(selectedLayer.value, processed, { simplifiedSrc: processed });
    setFlash('Color simplification applied to the selected artwork.', 'success');
  } catch (error) {
    setFlash(error?.message || 'Unable to simplify artwork colors right now.', 'error');
  } finally {
    designerProcessing.value = false;
  }
}
async function restoreOriginalArtwork() {
  if (!selectedLayer.value || selectedLayer.value.type !== 'artwork') return;
  const original = selectedLayer.value.meta?.originalSrc;
  if (!original) return;
  designerProcessing.value = true;
  try {
    await refreshArtworkMeta(selectedLayer.value, original, { simplifiedSrc: null, backgroundRemovedSrc: null });
    setFlash('Original artwork restored.', 'success');
  } catch (error) {
    setFlash(error?.message || 'Unable to restore the original artwork.', 'error');
  } finally {
    designerProcessing.value = false;
  }
}
function curvedTextPath(layer) {
  const curve = Number(layer?.curveStrength || 0);
  const sweep = curve >= 0 ? 1 : 0;
  const arcHeight = Math.max(8, Math.min(42, Math.abs(curve) * 0.9 + 8));
  const startY = curve >= 0 ? 68 : 32;
  const endY = startY;
  const controlY = curve >= 0 ? startY - arcHeight : startY + arcHeight;
  return `M 10 ${startY} Q 50 ${controlY} 90 ${endY}`;
}
function threadMappingSummary() {
  const used = [];
  designerState.layers.forEach((layer) => {
    if (layer.type === 'artwork') {
      (layer.meta?.palette || []).slice(0, 6).forEach((hex) => used.push(closestThreadColor(hex)));
    } else {
      used.push(closestThreadColor(layer.fill || '#1c1917'));
      if (layer.stroke) used.push(closestThreadColor(layer.stroke));
      if (layer.outlineColor && Number(layer.outlineWidth || 0) > 0) used.push(closestThreadColor(layer.outlineColor));
    }
  });
  const unique = new Map();
  used.forEach((thread) => {
    if (!unique.has(thread.hex)) unique.set(thread.hex, thread);
  });
  return [...unique.values()].slice(0, 8);
}
async function handleArtworkInput(event, replace = false) {
  const file = event.target.files?.[0];
  if (!file) return;
  try {
    const acceptedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'];
    if (!acceptedTypes.includes(file.type)) {
      setFlash('Only PNG, JPG, JPEG, and SVG files are supported in the designer.', 'error');
      return;
    }
    if (file.size > 8 * 1024 * 1024) {
      setFlash('Upload an artwork file smaller than 8 MB.', 'error');
      return;
    }
    const dataUrl = await readFileAsDataUrl(file);
    const meta = await collectImageMeta(file, dataUrl);
    const layerBase = { ...defaultLayerBase('artwork'), name: file.name.replace(/\.[^.]+$/, ''), src: dataUrl, width: 30, height: 30, fill: '#ffffff', meta };
    if (replace && selectedLayer.value?.type === 'artwork') {
      Object.assign(selectedLayer.value, layerBase, { id: selectedLayer.value.id, x: selectedLayer.value.x, y: selectedLayer.value.y, rotation: selectedLayer.value.rotation });
      selectLayer(selectedLayer.value.id);
    } else {
      designerState.layers.push(layerBase);
      selectLayer(layerBase.id);
    }
    syncDesignerFormFromState();
    if (meta.resolutionWarning) setFlash('Artwork uploaded, but its source resolution looks low for embroidery.', 'error');
  } finally {
    event.target.value = '';
  }
}
function duplicateLayer(id) {
  const source = designerState.layers.find((layer) => layer.id === id);
  if (!source) return;
  const copy = JSON.parse(JSON.stringify(source));
  copy.id = createId(source.type);
  copy.name = `${source.name} copy`;
  copy.x = Math.min(100 - (copy.width || 20), (copy.x || 0) + 4);
  copy.y = Math.min(100 - (copy.height || 20), (copy.y || 0) + 4);
  designerState.layers.push(copy);
  selectLayer(copy.id);
}
function removeLayer(id) {
  designerState.layers = designerState.layers.filter((layer) => layer.id !== id);
  if (designerState.selectedLayerId === id) designerState.selectedLayerId = designerState.layers[designerState.layers.length - 1]?.id || null;
  syncDesignerFormFromState();
}
function moveLayer(id, direction) {
  const index = designerState.layers.findIndex((layer) => layer.id === id);
  if (index === -1) return;
  const target = direction === 'up' ? index + 1 : index - 1;
  if (target < 0 || target >= designerState.layers.length) return;
  const [layer] = designerState.layers.splice(index, 1);
  designerState.layers.splice(target, 0, layer);
}
function updateSelectedLayer(patch) {
  if (!selectedLayer.value) return;
  Object.assign(selectedLayer.value, patch);
  syncDesignerFormFromState();
}
function clampLayer(layer) {
  layer.width = Math.min(100, Math.max(4, Number(layer.width || 10)));
  layer.height = Math.min(100, Math.max(layer.type === 'line' ? 1 : 4, Number(layer.height || 10)));
  layer.x = Math.min(100 - layer.width, Math.max(0, Number(layer.x || 0)));
  layer.y = Math.min(100 - layer.height, Math.max(0, Number(layer.y || 0)));
  if (Math.abs(layer.x + layer.width / 2 - 50) <= 2) layer.x = 50 - layer.width / 2;
  if (Math.abs(layer.y + layer.height / 2 - 50) <= 2) layer.y = 50 - layer.height / 2;
}
function beginLayerInteraction(event, layer, mode = 'move') {
  if (layer.locked) return;
  event.preventDefault();
  event.stopPropagation();
  dragState.mode = mode;
  dragState.layerId = layer.id;
  dragState.pointerId = event.pointerId;
  dragState.startX = event.clientX;
  dragState.startY = event.clientY;
  dragState.startLayer = JSON.parse(JSON.stringify(layer));
  selectLayer(layer.id);
  event.target.setPointerCapture?.(event.pointerId);
}
function trackLayerInteraction(event) {
  if (!dragState.layerId || !designerCanvasRef.value) return;
  const layer = designerState.layers.find((item) => item.id === dragState.layerId);
  if (!layer) return;
  const bounds = designerCanvasRef.value.getBoundingClientRect();
  const dx = ((event.clientX - dragState.startX) / bounds.width) * 100;
  const dy = ((event.clientY - dragState.startY) / bounds.height) * 100;
  if (dragState.mode === 'move') {
    layer.x = dragState.startLayer.x + dx;
    layer.y = dragState.startLayer.y + dy;
  } else if (dragState.mode === 'resize') {
    layer.width = dragState.startLayer.width + dx;
    layer.height = dragState.startLayer.height + dy;
  } else if (dragState.mode === 'rotate') {
    layer.rotation = Math.round(dragState.startLayer.rotation + dx * 2.2 + dy * 1.4);
  }
  clampLayer(layer);
}
function endLayerInteraction() {
  if (!dragState.layerId) return;
  const layer = designerState.layers.find((item) => item.id === dragState.layerId);
  if (layer) clampLayer(layer);
  dragState.mode = null;
  dragState.layerId = null;
  dragState.pointerId = null;
  dragState.startLayer = null;
  syncDesignerFormFromState();
}
function canvasLayerStyle(layer) {
  return {
    left: `${layer.x}%`,
    top: `${layer.y}%`,
    width: `${layer.width}%`,
    height: `${Math.max(layer.height, layer.type === 'line' ? 1.2 : 4)}%`,
    opacity: layer.opacity ?? 1,
    transform: `rotate(${layer.rotation || 0}deg)`,
    zIndex: designerState.layers.findIndex((item) => item.id === layer.id) + 10,
  };
}
function formatTextLayer(layer) {
  const text = layer.text || 'Text';
  if (layer.textCase === 'upper') return text.toUpperCase();
  if (layer.textCase === 'lower') return text.toLowerCase();
  return text;
}
function layerBadge(layer) { return layer.type === 'artwork' ? 'ART' : layer.type === 'text' ? 'TXT' : 'SHP'; }
function garmentShellClass() {
  return ({ cap: 'rounded-[42%_42%_30%_30%/38%_38%_22%_22%]', patch: 'rounded-[24%]', hoodie: 'rounded-[26%]', jacket: 'rounded-[28%]', tshirt: 'rounded-[26%]', polo: 'rounded-[26%]' })[designerState.garment] || 'rounded-[26%]';
}
function serializeDesignerState() {
  syncDesignerFormFromState();
  return {
    garment: designerState.garment,
    placement: designerState.placement,
    fabric: designerState.fabric,
    designType: designerState.designType,
    quantity: designerState.quantity,
    canvasName: designStudioForm.name,
    notes: designerState.notes,
    garmentColor: designerState.garmentColor,
    selectedLayerId: designerState.selectedLayerId,
    layers: designerState.layers,
  };
}
function designerPreviewMeta() {
  return {
    garment_label: garmentOptions.find((item) => item.value === designerState.garment)?.label || designerState.garment,
    placement_label: placementOptions.find((item) => item.value === designerState.placement)?.label || designerState.placement,
    readiness: readinessState.value.label,
    warnings: designerWarnings.value,
    summary: canvasSummary.value,
    complexity_reason: complexityReason.value,
    production_difficulty: productionDifficulty.value,
    digitizing_difficulty: digitizingDifficulty.value,
    runtime_category: runtimeCategory.value,
    safe_zone: safeZone.value,
  };
}
function applyDraftToDesigner(draft) {
  currentDesignDraftId.value = draft?.id || null;
  const session = draft?.design_session_json || {};
  designerState.garment = session.garment || draft?.garment_type || 'polo';
  designerState.placement = session.placement || draft?.placement_area || 'left_chest';
  designerState.fabric = session.fabric || draft?.fabric_type || 'cotton pique';
  designerState.designType = session.designType || designStudioForm.design_type || 'logo_embroidery';
  designerState.quantity = session.quantity || draft?.quantity || 12;
  designerState.canvasName = session.canvasName || draft?.name || 'Embroidery design';
  designerState.notes = session.notes || draft?.notes || '';
  designerState.garmentColor = session.garmentColor || '#f5f5f4';
  designerState.layers = Array.isArray(session.layers) && session.layers.length ? session.layers : [];
  designerState.selectedLayerId = session.selectedLayerId || designerState.layers[designerState.layers.length - 1]?.id || null;
  Object.assign(designStudioForm, {
    name: draft?.name || designerState.canvasName,
    garment_type: draft?.garment_type || designerState.garment,
    placement_area: draft?.placement_area || designerState.placement,
    fabric_type: draft?.fabric_type || designerState.fabric,
    width_mm: String(draft?.width_mm || safeZone.value.maxWidth),
    height_mm: String(draft?.height_mm || safeZone.value.maxHeight),
    color_count: draft?.color_count || estimateColorCount(),
    stitch_count_estimate: draft?.stitch_count_estimate || stitchEstimate.value,
    complexity_level: draft?.complexity_level || complexityLevel.value,
    quantity: draft?.quantity || designerState.quantity,
    notes: draft?.notes || designerState.notes,
  });
  ensureDesignerDefaults();
}
function initializeDesignerFromWorkspace() {
  if (!currentDesignDraftId.value && restoreDesignerAutosave()) {
    ensureDesignerDefaults();
    return;
  }
  if (designDrafts.value.length) {
    const preferred = designDrafts.value.find((item) => item.id === currentDesignDraftId.value) || workspace.value?.design_studio?.latest_design || designDrafts.value[0];
    applyDraftToDesigner(preferred);
    proofingForm.design_id = preferred?.id || '';
    return;
  }
  if (!designerState.layers.length) addTextLayer();
  ensureDesignerDefaults();
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
function workflowBadge(value) {
  return ({ draft: 'Draft', submitted_for_review: 'Submitted', proof_ready: 'Proof ready', revision_requested: 'Revision requested', approved: 'Approved', locked: 'Locked' })[value] || statusChip(value);
}
async function submitDesignForQuotation(design) {
  if (!design?.id) { setFlash('Save the draft first before submitting it for quotation.', 'error'); return; }
  busy.value = true;
  try {
    await api('post', `/api/design-customizations/${design.id}/submit-for-quotation`, { notes: proofingForm.description || design.notes || '' });
    setFlash('Design submitted for quotation.', 'success');
    await loadWorkspace();
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}
async function submitDesignForProofing(design) {
  if (!design?.id) { setFlash('Save the draft first before submitting it for proofing.', 'error'); return; }
  busy.value = true;
  try {
    await api('post', `/api/design-customizations/${design.id}/submit-for-proofing`, { notes: proofingForm.description || design.notes || '' });
    setFlash('Design submitted for proofing.', 'success');
    await loadWorkspace();
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}
async function approveProofRequest(request) {
  if (!request?.id) return;
  busy.value = true;
  try {
    const pendingProof = (request.proof_history || request.proofs || []).find((item) => item.status === 'pending_client');
    if (pendingProof?.id) {
      await api('post', `/api/design-customizations/${request.id}/proofs/${pendingProof.id}/respond`, { status: 'approved', annotated_notes: 'Approved from client workspace.' });
    } else {
      await api('post', `/api/design-customizations/${request.id}/approve`, { lock: false });
    }
    setFlash('Proof approved.', 'success');
    await loadWorkspace();
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}
async function requestDesignRevision(request) {
  if (!request?.id) return;
  const reason = revisionRequestForm.reason || request.notes || 'Please revise this design.';
  busy.value = true;
  try {
    const pendingProof = (request.proof_history || request.proofs || []).find((item) => item.status === 'pending_client');
    if (pendingProof?.id) {
      await api('post', `/api/design-customizations/${request.id}/proofs/${pendingProof.id}/respond`, { status: 'rejected', annotated_notes: reason });
    } else {
      await api('post', `/api/design-customizations/${request.id}/request-revision`, { reason });
    }
    revisionRequestForm.reason = '';
    setFlash('Revision request sent.', 'success');
    await loadWorkspace();
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}
async function restoreDesignVersion(request, version) {
  if (!request?.id || !version?.id) return;
  busy.value = true;
  try {
    await api('post', `/api/design-customizations/${request.id}/versions/${version.id}/restore`);
    setFlash(`Restored version #${version.version_no}.`, 'success');
    await loadWorkspace();
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function saveDesignStudio() {
  if (designerBlockingIssues.value.length) { setFlash(designerBlockingIssues.value[0].text, 'error'); return; }
  busy.value = true;
  try {
    syncDesignerFormFromState();
    const payload = {
      name: designStudioForm.name,
      garment_type: designerState.garment,
      placement_area: designerState.placement,
      fabric_type: designerState.fabric,
      width_mm: designStudioForm.width_mm || null,
      height_mm: designStudioForm.height_mm || null,
      color_count: estimateColorCount() || null,
      stitch_count_estimate: stitchEstimate.value,
      complexity_level: complexityLevel.value,
      quantity: designerState.quantity || null,
      design_type: normalizeDesignType(designerState.designType),
      notes: designerState.notes,
      design_session_json: serializeDesignerState(),
      preview_meta_json: designerPreviewMeta(),
      status: 'draft',
    };
    let data;
    if (currentDesignDraftId.value) ({ data } = await api('put', `/api/design-customizations/${currentDesignDraftId.value}`, payload));
    else ({ data } = await api('post', '/api/design-customizations', payload));
    currentDesignDraftId.value = data.id;
    proofingForm.design_id = data.id;
    await loadWorkspace();
    lastSavedFingerprint.value = designerFingerprint();
    clearDesignerAutosave();
    designerAutosaveStatus.value = 'Draft saved to workspace';
    setFlash('Design Studio draft saved.', 'success');
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function requestProofAndQuote() {
  if (designerBlockingIssues.value.length) { setFlash(designerBlockingIssues.value[0].text, 'error'); return; }
  busy.value = true;
  try {
    let designId = proofingForm.design_id;
    if (!designId) {
      const { data: draft } = await api('post', '/api/design-customizations', {
        name: designStudioForm.name || 'Imported design', garment_type: designerState.garment, placement_area: designerState.placement, fabric_type: designerState.fabric, width_mm: designStudioForm.width_mm || null, height_mm: designStudioForm.height_mm || null, color_count: estimateColorCount() || null, stitch_count_estimate: stitchEstimate.value || null, complexity_level: complexityLevel.value, quantity: designerState.quantity || null, design_type: normalizeDesignType(designerState.designType), notes: proofingForm.description || designerState.notes, design_session_json: serializeDesignerState(), preview_meta_json: designerPreviewMeta(),
      });
      designId = draft.id;
      proofingForm.design_id = draft.id;
    }

    const formData = new FormData();
    formData.append('title', `${designStudioForm.name || 'Custom design'} proofing request`);
    formData.append('description', proofingForm.description || designerState.notes || 'Client requested design proofing and quotation.');
    formData.append('design_type', normalizeDesignType(designerState.designType));
    formData.append('garment_type', designerState.garment || '');
    formData.append('quantity', String(designerState.quantity || 1));
    formData.append('target_budget', '0');
    formData.append('notes', `Service: ${proofingForm.service_selection}. ${proofingForm.description || ''}`.trim());
    if (proofingForm.upload_design_file) formData.append('reference_file', proofingForm.upload_design_file);

    const { data: designPost } = await api('post', '/api/design-posts', formData, { 'Content-Type': 'multipart/form-data' });
    await api('post', `/api/design-posts/${designPost.id}/select-shop`, { shop_id: Number(proofingForm.shop_id), convert_to_order: false });
    await api('put', `/api/design-customizations/${designId}`, { design_post_id: designPost.id, status: 'estimated', notes: proofingForm.description || designerState.notes || '', design_session_json: serializeDesignerState(), preview_meta_json: designerPreviewMeta() });
    await api('post', `/api/design-customizations/${designId}/submit-for-proofing`, { notes: proofingForm.description || designerState.notes || '' });
    designerAutosaveStatus.value = 'Submitted into proofing workflow';
    setFlash('Design proofing request saved and submitted to the workflow.', 'success');
    await loadWorkspace();
  } catch (err) { setFlash(errorMessage(err), 'error'); } finally { busy.value = false; }
}

async function postToMarketplace() {
  busy.value = true;
  try {
    const formData = new FormData();
    formData.append('title', marketplaceForm.title || designStudioForm.name);
    formData.append('description', marketplaceForm.description || designerState.notes || 'Public design request');
    formData.append('design_type', marketplaceForm.design_type || normalizeDesignType(designerState.designType));
    formData.append('garment_type', marketplaceForm.garment_type || designerState.garment || '');
    formData.append('quantity', marketplaceForm.quantity || String(designerState.quantity || 1));
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

onMounted(async () => {
  window.addEventListener('beforeunload', handleBeforeUnload);
  await bootstrap();
  lastSavedFingerprint.value = designerFingerprint();
});
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload);
});
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
      <template v-if="activeSection === 'design-studio'">
        <div class="space-y-4">
          <SectionCard title="Designer tools" description="Live controls for the active embroidery draft.">
            <div class="space-y-5 text-sm text-stone-700">
              <section class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                  <div class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Design Elements</div>
                  <span class="rounded-full border px-2.5 py-1 text-[11px] font-semibold" :class="readinessState.tone">{{ readinessState.label }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <button class="rounded-2xl border border-stone-300 bg-stone-50 px-3 py-2.5 font-medium" @click="addTextLayer">Add text</button>
                  <button class="rounded-2xl border border-stone-300 bg-stone-50 px-3 py-2.5 font-medium" @click="triggerArtworkUpload(false)">Upload art</button>
                  <button class="rounded-2xl border border-stone-300 bg-stone-50 px-3 py-2.5 font-medium" @click="addShapeLayer('rectangle')">Rectangle</button>
                  <button class="rounded-2xl border border-stone-300 bg-stone-50 px-3 py-2.5 font-medium" @click="addShapeLayer('circle')">Circle</button>
                </div>
                <button class="w-full rounded-2xl border border-stone-300 bg-stone-50 px-3 py-2.5 font-medium" @click="addShapeLayer('line')">Add line</button>
                <input ref="designerFileInputRef" type="file" accept="image/png,image/jpeg,image/jpg,image/svg+xml" class="hidden" @change="handleArtworkInput($event, false)">
                <input ref="designerReplaceInputRef" type="file" accept="image/png,image/jpeg,image/jpg,image/svg+xml" class="hidden" @change="handleArtworkInput($event, true)">
              </section>

              <section class="space-y-3">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Layers</div>
                <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                  <button v-for="layer in [...designerState.layers].slice().reverse()" :key="layer.id" class="w-full rounded-2xl border px-3 py-2.5 text-left" :class="selectedLayer?.id === layer.id ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-200 bg-stone-50 text-stone-900'" @click="selectLayer(layer.id)">
                    <div class="flex items-center justify-between gap-2">
                      <div class="flex items-center gap-2"><span class="rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="selectedLayer?.id === layer.id ? 'border-white/30 text-white' : 'border-stone-300 text-stone-500'">{{ layerBadge(layer) }}</span><span class="truncate font-medium">{{ layer.name }}</span></div>
                      <span class="text-[11px] uppercase opacity-70">{{ layer.hidden ? 'Hidden' : 'Visible' }}</span>
                    </div>
                    <div class="mt-2 grid grid-cols-6 gap-1 text-[11px]" @click.stop>
                      <button class="rounded-xl border px-2 py-1" :class="selectedLayer?.id === layer.id ? 'border-white/20' : 'border-stone-300'" @click="moveLayer(layer.id, 'up')">Up</button>
                      <button class="rounded-xl border px-2 py-1" :class="selectedLayer?.id === layer.id ? 'border-white/20' : 'border-stone-300'" @click="moveLayer(layer.id, 'down')">Down</button>
                      <button class="rounded-xl border px-2 py-1" :class="selectedLayer?.id === layer.id ? 'border-white/20' : 'border-stone-300'" @click="layer.hidden = !layer.hidden">{{ layer.hidden ? 'Show' : 'Hide' }}</button>
                      <button class="rounded-xl border px-2 py-1" :class="selectedLayer?.id === layer.id ? 'border-white/20' : 'border-stone-300'" @click="layer.locked = !layer.locked">{{ layer.locked ? 'Unlock' : 'Lock' }}</button>
                      <button class="rounded-xl border px-2 py-1" :class="selectedLayer?.id === layer.id ? 'border-white/20' : 'border-stone-300'" @click="duplicateLayer(layer.id)">Copy</button>
                      <button class="rounded-xl border px-2 py-1 text-rose-600" :class="selectedLayer?.id === layer.id ? 'border-white/20 text-rose-200' : 'border-stone-300'" @click="removeLayer(layer.id)">Delete</button>
                    </div>
                  </button>
                </div>
              </section>

              <section class="space-y-3">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Guided flow</div>
                <div class="grid gap-2 text-xs text-stone-600">
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3">1. Choose garment and placement.</div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3">2. Upload artwork or add text and shapes.</div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3">3. Arrange everything inside the safe zone.</div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3">4. Review the automatic embroidery checks.</div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3">5. Save draft or continue to proofing.</div>
                </div>
              </section>

              <section class="space-y-3">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Garment</div>
                <select v-model="designerState.garment" class="w-full rounded-2xl border-stone-300">
                  <option v-for="option in garmentOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
                <select v-model="designerState.placement" class="w-full rounded-2xl border-stone-300">
                  <option v-for="option in placementOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
                <input v-model="designerState.fabric" class="w-full rounded-2xl border-stone-300" placeholder="Fabric type">
                <div class="grid grid-cols-[1fr_auto] items-center gap-3">
                  <label class="text-xs font-medium text-stone-600">Garment color</label>
                  <input v-model="designerState.garmentColor" type="color" class="h-11 w-16 rounded-2xl border-stone-300 p-1">
                </div>
                <div class="grid grid-cols-5 gap-2">
                  <button v-for="swatch in garmentColorSwatches" :key="swatch" type="button" class="h-9 rounded-2xl border transition" :class="designerState.garmentColor === swatch ? 'border-stone-900 ring-2 ring-stone-900/15' : 'border-stone-200'" :style="{ backgroundColor: swatch }" @click="designerState.garmentColor = swatch"></button>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <button type="button" class="rounded-2xl border px-3 py-2 text-xs font-semibold" :class="garmentView === 'front' ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-300 bg-stone-50 text-stone-700'" @click="garmentView = 'front'">Front</button>
                  <button type="button" class="rounded-2xl border px-3 py-2 text-xs font-semibold" :class="garmentView === 'back' ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-300 bg-stone-50 text-stone-700'" @click="garmentView = 'back'">Back</button>
                  <button type="button" class="rounded-2xl border px-3 py-2 text-xs font-semibold" :class="garmentView === 'side' ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-300 bg-stone-50 text-stone-700'" @click="garmentView = 'side'">Side</button>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <input v-model="designStudioForm.width_mm" type="number" min="20" class="rounded-2xl border-stone-300" placeholder="Width mm">
                  <input v-model="designStudioForm.height_mm" type="number" min="20" class="rounded-2xl border-stone-300" placeholder="Height mm">
                </div>
              </section>

              <section v-if="selectedLayer" class="space-y-3">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Selected Layer</div>
                <input v-model="selectedLayer.name" class="w-full rounded-2xl border-stone-300" placeholder="Layer label">
                <div class="grid grid-cols-2 gap-2">
                  <input v-model.number="selectedLayer.x" type="number" min="0" max="100" class="rounded-2xl border-stone-300" placeholder="X %">
                  <input v-model.number="selectedLayer.y" type="number" min="0" max="100" class="rounded-2xl border-stone-300" placeholder="Y %">
                  <input v-model.number="selectedLayer.width" type="number" min="1" max="100" class="rounded-2xl border-stone-300" placeholder="Width %">
                  <input v-model.number="selectedLayer.height" type="number" min="1" max="100" class="rounded-2xl border-stone-300" placeholder="Height %">
                  <input v-model.number="selectedLayer.rotation" type="number" class="rounded-2xl border-stone-300" placeholder="Rotation">
                  <input v-model.number="selectedLayer.opacity" type="number" step="0.1" min="0.1" max="1" class="rounded-2xl border-stone-300" placeholder="Opacity">
                </div>
                <template v-if="selectedLayer.type === 'text'">
                  <textarea v-model="selectedLayer.text" rows="3" class="w-full rounded-2xl border-stone-300" placeholder="Text content"></textarea>
                  <div class="grid grid-cols-2 gap-2">
                    <select v-model="selectedLayer.fontFamily" class="rounded-2xl border-stone-300"><option v-for="font in designFonts" :key="font" :value="font">{{ font }}</option></select>
                    <select v-model="selectedLayer.fontWeight" class="rounded-2xl border-stone-300"><option :value="400">Regular</option><option :value="600">Semibold</option><option :value="700">Bold</option><option :value="800">Extra bold</option></select>
                    <input v-model.number="selectedLayer.fontSize" type="number" min="12" class="rounded-2xl border-stone-300" placeholder="Font size">
                    <input v-model.number="selectedLayer.letterSpacing" type="number" class="rounded-2xl border-stone-300" placeholder="Letter spacing">
                    <select v-model="selectedLayer.textAlign" class="rounded-2xl border-stone-300"><option value="left">Left</option><option value="center">Center</option><option value="right">Right</option></select>
                    <select v-model="selectedLayer.textCase" class="rounded-2xl border-stone-300"><option value="normal">Normal</option><option value="upper">UPPER</option><option value="lower">lower</option></select>
                    <input v-model.number="selectedLayer.curveStrength" type="number" min="-40" max="40" class="rounded-2xl border-stone-300" placeholder="Curve strength">
                    <input v-model.number="selectedLayer.outlineWidth" type="number" min="0" step="0.5" class="rounded-2xl border-stone-300" placeholder="Outline width">
                  </div>
                  <div class="grid grid-cols-[1fr_auto] items-center gap-3">
                    <label class="text-xs font-medium text-stone-600">Outline color</label>
                    <input v-model="selectedLayer.outlineColor" type="color" class="h-11 w-16 rounded-2xl border-stone-300 p-1">
                  </div>
                </template>
                <template v-if="selectedLayer.type === 'artwork'">
                  <button class="w-full rounded-2xl border border-stone-300 bg-stone-50 px-3 py-2.5 font-medium" @click="triggerArtworkUpload(true)">Replace artwork</button>
                  <div class="grid grid-cols-2 gap-2">
                    <button class="rounded-2xl border border-stone-300 bg-stone-50 px-3 py-2.5 font-medium disabled:opacity-50" :disabled="designerProcessing" @click="removeArtworkBackground">Remove background</button>
                    <button class="rounded-2xl border border-stone-300 bg-stone-50 px-3 py-2.5 font-medium disabled:opacity-50" :disabled="designerProcessing" @click="simplifyArtworkColors">Simplify colors</button>
                  </div>
                  <button class="w-full rounded-2xl border border-stone-300 bg-stone-50 px-3 py-2.5 font-medium disabled:opacity-50" :disabled="designerProcessing || !selectedLayer.meta?.originalSrc" @click="restoreOriginalArtwork">Restore original</button>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3 text-xs text-stone-500">{{ selectedLayer.meta?.fileName || 'Artwork asset' }} · {{ selectedLayer.meta?.mimeType || 'image' }}</div>
                  <div v-if="selectedArtworkCleanupHints.length" class="rounded-2xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                    <div class="font-semibold uppercase tracking-[0.18em]">Cleanup hints</div>
                    <ul class="mt-2 space-y-1">
                      <li v-for="hint in selectedArtworkCleanupHints" :key="hint">• {{ hint }}</li>
                    </ul>
                  </div>
                </template>
                <div class="grid grid-cols-2 gap-2">
                  <input v-model="selectedLayer.fill" type="color" class="h-11 rounded-2xl border-stone-300 p-1" :disabled="selectedLayer.type === 'artwork'">
                  <input v-if="selectedLayer.type !== 'text' && selectedLayer.type !== 'artwork'" v-model="selectedLayer.stroke" type="color" class="h-11 rounded-2xl border-stone-300 p-1">
                </div>
              </section>

              <section class="space-y-3">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Colors</div>
                <div class="grid grid-cols-2 gap-2 text-xs text-stone-600">
                  <div v-for="thread in threadPaletteSummary" :key="thread.hex" class="flex items-center gap-2 rounded-2xl border border-stone-200 bg-stone-50 px-3 py-2">
                    <span class="h-4 w-4 rounded-full border border-black/10" :style="{ backgroundColor: thread.hex }"></span>
                    <span class="truncate font-medium text-stone-800">{{ thread.name }}</span>
                  </div>
                </div>
              </section>

              <section class="space-y-3">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Readiness Checks</div>
                <div class="rounded-2xl border p-3 text-sm" :class="readinessState.tone">{{ readinessState.label }}</div>
                <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3 text-xs text-stone-600">{{ complexityReason }}</div>
                <div class="grid gap-2">
                  <div v-for="item in readinessChecklist" :key="item.label" class="rounded-2xl border px-3 py-2 text-xs" :class="item.passed ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-stone-200 bg-white text-stone-600'">{{ item.passed ? '✓' : '•' }} {{ item.label }}</div>
                </div>
                <div v-if="designerWarnings.length" class="space-y-2">
                  <div v-for="warning in designerWarnings" :key="warning.text" class="rounded-2xl border border-stone-200 bg-stone-50 p-3 text-xs text-stone-600">{{ warning.text }}</div>
                </div>
                <div v-else class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-700">The current design is clean enough for client proofing.</div>
              </section>

              <section class="space-y-3">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Estimate</div>
                <div class="grid grid-cols-2 gap-2">
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3"><div class="text-[11px] uppercase text-stone-500">Colors</div><div class="mt-1 text-lg font-semibold text-stone-900">{{ canvasSummary.colors }}</div></div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3"><div class="text-[11px] uppercase text-stone-500">Layers</div><div class="mt-1 text-lg font-semibold text-stone-900">{{ canvasSummary.layers }}</div></div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3 col-span-2"><div class="text-[11px] uppercase text-stone-500">Estimated stitches</div><div class="mt-1 text-2xl font-semibold text-stone-900">{{ stitchEstimate.toLocaleString() }}</div></div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3 col-span-2"><div class="text-[11px] uppercase text-stone-500">Complexity</div><div class="mt-1 text-lg font-semibold text-stone-900">{{ complexityLevel }}</div></div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3"><div class="text-[11px] uppercase text-stone-500">Production</div><div class="mt-1 text-sm font-semibold text-stone-900">{{ productionDifficulty }}</div></div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3"><div class="text-[11px] uppercase text-stone-500">Digitizing</div><div class="mt-1 text-sm font-semibold text-stone-900">{{ digitizingDifficulty }}</div></div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3 col-span-2"><div class="text-[11px] uppercase text-stone-500">Machine time</div><div class="mt-1 text-sm font-semibold text-stone-900">{{ runtimeCategory }}</div></div>
                </div>
              </section>

              <section class="space-y-3">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Save / Submit</div>
                <input v-model="designStudioForm.name" class="w-full rounded-2xl border-stone-300" placeholder="Design name">
                <input v-model.number="designerState.quantity" type="number" min="1" class="w-full rounded-2xl border-stone-300" placeholder="Quantity">
                <textarea v-model="designerState.notes" rows="4" class="w-full rounded-2xl border-stone-300" placeholder="Design notes for proofing, quotation, or production review"></textarea>
                <div class="rounded-2xl border border-stone-200 bg-stone-50 p-3 text-xs text-stone-600">{{ designerAutosaveStatus || 'Autosave activates while you work in Design Studio.' }}</div>
                <button class="w-full rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50" :disabled="busy || designerBlockingIssues.length > 0" @click="saveDesignStudio">Save design studio draft</button>
                <button class="w-full rounded-2xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="designerBlockingIssues.length > 0" @click="activeSection = 'proofing'">Continue to proofing</button>
              </section>
            </div>
          </SectionCard>
        </div>
      </template>
      <WorkspaceRightSidebar v-else :notifications="notifications" :selected-order="selectedOrder" :assignments="[]" :revisions="workspace?.design_proofing?.requests || []" />
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
          <div v-if="projects.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <button v-for="project in projects" :key="project.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4 text-left" @click="selectedProjectId = project.id; activeSection = 'projects'">
              <div class="font-semibold text-stone-900">{{ project.title }}</div>
              <div class="mt-2 text-sm text-stone-600">{{ project.shop?.shop_name }}</div>
              <div class="mt-2 text-xs text-stone-500">{{ money(project.base_price) }} · {{ project.category }}</div>
            </button>
          </div>
          <EmptyState v-else title="No owner projects yet" description="Posted shop work will appear here when available." />
        </SectionCard>
      </template>

      <template v-else-if="activeSection === 'track-orders'">
        <SectionCard title="Orders" description="All order details live inside the tabbed order details panel.">
          <div class="flex flex-wrap gap-2">
            <button v-for="tab in orderTabs" :key="tab.key" class="rounded-2xl px-4 py-2.5 text-sm font-medium" :class="activeOrderTab === tab.key ? 'bg-stone-900 text-white' : 'border border-stone-300 bg-white text-stone-700'" @click="activeOrderTab = tab.key">
              {{ tab.label }} <span class="ml-1 text-xs opacity-75">({{ workspace?.track_orders?.tabs?.[tab.key] || 0 }})</span>
            </button>
          </div>

          <div class="mt-4 grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
            <div class="space-y-3">
              <div v-for="order in filteredOrders" :key="order.id" class="rounded-2xl border p-4" :class="selectedOrder?.id === order.id ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-200 bg-stone-50 text-stone-900'" @click="selectedOrderId = order.id">
                <div class="flex items-center justify-between gap-3">
                  <div class="font-semibold">{{ order.order_number }}</div>
                  <div class="text-xs uppercase">{{ statusChip(order.status) }}</div>
                </div>
                <div class="mt-2 text-sm" :class="selectedOrder?.id === order.id ? 'text-stone-200' : 'text-stone-600'">{{ order.shop?.shop_name }} · {{ money(order.total_amount) }}</div>
                <div class="mt-2 text-xs" :class="selectedOrder?.id === order.id ? 'text-stone-300' : 'text-stone-500'">Payment {{ statusChip(order.payment_status) }} · Fulfillment {{ statusChip(order.fulfillment?.status) }}</div>
              </div>
              <EmptyState v-if="!filteredOrders.length" title="No orders in this tab" description="Switch tabs to view other order statuses." />
            </div>

            <SectionCard :title="selectedOrder ? `Order details · ${selectedOrder.order_number}` : 'Order details'" description="Payment, production, shipping, and actions for the selected order.">
              <div v-if="selectedOrder" class="space-y-4 text-sm text-stone-700">
                <div class="grid gap-3 md:grid-cols-2">
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4"><div class="text-xs uppercase text-stone-500">Shop</div><div class="mt-1 font-semibold text-stone-900">{{ selectedOrder.shop?.shop_name || '—' }}</div></div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4"><div class="text-xs uppercase text-stone-500">Amount</div><div class="mt-1 font-semibold text-stone-900">{{ money(selectedOrder.total_amount) }}</div></div>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                  <div class="font-semibold text-stone-900">Timeline</div>
                  <div class="mt-3 space-y-2">
                    <div v-for="log in selectedOrder.timeline || []" :key="log.id" class="rounded-2xl border border-stone-200 bg-white p-3">
                      <div class="font-medium text-stone-900">{{ log.title }}</div>
                      <div class="mt-1 text-stone-600">{{ log.description }}</div>
                    </div>
                  </div>
                </div>
                <div class="flex flex-wrap gap-2">
                  <button v-if="selectedOrder.self_service?.can_cancel" class="rounded-2xl border border-rose-300 px-4 py-2 text-sm text-rose-700" @click="cancelOrder(selectedOrder)">Cancel order</button>
                  <button v-if="selectedOrder.self_service?.can_message_shop" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm text-stone-700" @click="activeSection = 'message'; messageForm.shop_id = selectedOrder.shop_id; messageForm.order_id = selectedOrder.id; messageForm.title = `Order ${selectedOrder.order_number}`">Message shop</button>
                </div>
              </div>
              <EmptyState v-else title="Select an order" description="Click an order card to view its details." />
            </SectionCard>
          </div>
        </SectionCard>
      </template>

      <template v-else-if="activeSection === 'design-studio'">
        <SectionCard title="Design Studio" description="Build a clean embroidery-ready layout directly in the main workspace, then save the structured draft for proofing or quotation.">
          <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_260px]">
            <div class="space-y-4">
              <div class="flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-stone-200 bg-stone-50 p-4">
                <div>
                  <div class="text-xs font-semibold uppercase tracking-[0.24em] text-stone-500">Main workspace designer</div>
                  <div class="mt-1 text-lg font-semibold text-stone-900">{{ designStudioForm.name || 'Embroidery design' }}</div>
                  <div class="mt-1 text-sm text-stone-500">{{ garmentOptions.find((item) => item.value === designerState.garment)?.label }} · {{ garmentView }} view · {{ placementOptions.find((item) => item.value === designerState.placement)?.label }} · {{ designStudioForm.width_mm }} × {{ designStudioForm.height_mm }} mm</div>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                  <span class="rounded-full border border-stone-300 bg-white px-3 py-1.5 font-semibold text-stone-700">{{ canvasSummary.layers }} layer(s)</span>
                  <span class="rounded-full border border-stone-300 bg-white px-3 py-1.5 font-semibold text-stone-700">{{ stitchEstimate.toLocaleString() }} est. stitches</span>
                  <span class="rounded-full border px-3 py-1.5 font-semibold capitalize" :class="readinessState.tone">{{ readinessState.label }}</span>
                </div>
              </div>

              <div class="rounded-[2rem] border border-stone-200 bg-gradient-to-br from-white via-stone-50 to-stone-100 p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                  <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-stone-500">Embroidery canvas</div>
                    <p class="mt-1 text-sm text-stone-500">Drag layers directly inside the safe zone. The designer checks embroidery risks automatically while you work, and artwork cleanup tools stay available in the right panel.</p>
                  </div>
                  <div class="hidden rounded-2xl border border-stone-200 bg-white px-3 py-2 text-xs text-stone-500 lg:block">Snap guides are active near the center lines.</div>
                </div>

                <div class="flex justify-center">
                  <div ref="designerCanvasRef" class="relative aspect-square w-full max-w-[860px] overflow-hidden rounded-[2.5rem] border border-stone-200 bg-[radial-gradient(circle_at_top,#fafaf9,white_58%,#f5f5f4)] p-8 shadow-inner" @pointermove="trackLayerInteraction" @pointerup="endLayerInteraction" @pointerleave="endLayerInteraction">
                    <div class="relative h-full w-full" :class="garmentShellClass()">
                      <div class="absolute inset-[6%] rounded-[2rem] border border-stone-200 shadow-sm" :style="{ background: garmentView === 'back' ? `linear-gradient(180deg, ${designerState.garmentColor}, #d6d3d1 70%, #f5f5f4)` : garmentView === 'side' ? `linear-gradient(135deg, ${designerState.garmentColor}, #fafaf9 72%)` : `linear-gradient(180deg, ${designerState.garmentColor}, white 78%)` }"></div>
                      <div class="absolute inset-[6%] rounded-[2rem] opacity-30 mix-blend-multiply" :style="{ backgroundImage: 'linear-gradient(90deg, rgba(255,255,255,0.35) 0, rgba(255,255,255,0.05) 18%, transparent 40%, rgba(0,0,0,0.05) 100%)' }"></div>
                      <div class="pointer-events-none absolute inset-[8%] rounded-[2rem] border border-white/50"></div>
                      <div v-if="designerState.garment === 'polo' && garmentView !== 'back'" class="absolute left-1/2 top-[6%] h-[15%] w-[22%] -translate-x-1/2 rounded-b-[2rem] border border-stone-200 bg-white/90"></div>
                      <div v-if="designerState.garment === 'hoodie' && garmentView !== 'back'" class="absolute left-1/2 top-[4%] h-[22%] w-[34%] -translate-x-1/2 rounded-b-[2.5rem] border border-stone-200 bg-stone-50/90"></div>
                      <div v-if="designerState.garment === 'cap'" class="absolute left-1/2 top-[8%] h-[38%] w-[58%] -translate-x-1/2 rounded-[48%_48%_28%_28%/44%_44%_18%_18%] border border-stone-200 bg-white shadow-sm"></div>
                      <div v-if="designerState.garment === 'patch'" class="absolute inset-[16%] rounded-[24%] border border-stone-200 bg-white shadow-sm"></div>

                      <div class="pointer-events-none absolute inset-x-1/2 top-0 h-full w-px -translate-x-1/2 border-l border-dashed border-stone-300/70"></div>
                      <div class="pointer-events-none absolute inset-y-1/2 left-0 w-full -translate-y-1/2 border-t border-dashed border-stone-300/70"></div>

                      <div class="absolute border-2 border-dashed border-stone-400/70 bg-stone-200/20" :style="{ left: `${safeZone.left}%`, top: `${safeZone.top}%`, width: `${safeZone.width}%`, height: `${safeZone.height}%` }">
                        <div class="absolute inset-x-0 bottom-2 text-center text-[11px] font-medium text-stone-500">Approx. {{ designStudioForm.width_mm || safeZone.maxWidth }} × {{ designStudioForm.height_mm || safeZone.maxHeight }} mm</div>
                        <div class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-600">{{ safeZone.label }}</div>
                        <div v-for="layer in visibleLayers" :key="layer.id" class="absolute select-none" :style="canvasLayerStyle(layer)" @pointerdown="beginLayerInteraction($event, layer, 'move')">
                          <template v-if="layer.type === 'artwork'">
                            <img :src="layer.src" :alt="layer.name" class="h-full w-full rounded-lg object-contain" draggable="false">
                          </template>
                          <template v-else-if="layer.type === 'text'">
                            <svg v-if="Number(layer.curveStrength || 0) !== 0" viewBox="0 0 100 100" class="h-full w-full overflow-visible">
                              <defs>
                                <path :id="`text-path-${layer.id}`" :d="curvedTextPath(layer)" />
                              </defs>
                              <text :style="{ fill: layer.fill, fontFamily: layer.fontFamily, fontSize: `${Math.max(12, layer.fontSize) * 0.85}px`, fontWeight: layer.fontWeight, letterSpacing: `${layer.letterSpacing || 0}px`, paintOrder: 'stroke fill', stroke: Number(layer.outlineWidth || 0) > 0 ? (layer.outlineColor || '#fafaf9') : 'transparent', strokeWidth: Number(layer.outlineWidth || 0) > 0 ? Math.max(0.4, Number(layer.outlineWidth || 0) * 0.35) : 0 }">
                                <textPath :href="`#text-path-${layer.id}`" startOffset="50%" text-anchor="middle">{{ formatTextLayer(layer) }}</textPath>
                              </text>
                            </svg>
                            <div v-else class="flex h-full w-full items-center justify-center overflow-hidden rounded-lg px-2 text-center" :style="{ color: layer.fill, fontFamily: layer.fontFamily, fontSize: `${Math.max(12, layer.fontSize)}px`, fontWeight: layer.fontWeight, letterSpacing: `${layer.letterSpacing || 0}px`, lineHeight: layer.lineHeight || 1.1, textAlign: layer.textAlign, WebkitTextStroke: Number(layer.outlineWidth || 0) > 0 ? `${layer.outlineWidth}px ${layer.outlineColor || '#fafaf9'}` : '0 transparent', textShadow: Number(layer.outlineWidth || 0) > 0 ? `0 0 ${Math.max(0.5, Number(layer.outlineWidth || 0))}px ${layer.outlineColor || '#fafaf9'}` : 'none' }">{{ formatTextLayer(layer) }}</div>
                          </template>
                          <template v-else-if="layer.type === 'line'">
                            <div class="flex h-full items-center"><div class="h-full w-full rounded-full" :style="{ backgroundColor: layer.fill, height: `${Math.max(2, (layer.strokeWidth || 2) * 2)}px` }"></div></div>
                          </template>
                          <template v-else>
                            <div class="h-full w-full" :class="layer.type === 'circle' ? 'rounded-full' : 'rounded-lg'" :style="{ backgroundColor: layer.fill, border: `${layer.strokeWidth || 1.5}px solid ${layer.stroke || '#1c1917'}` }"></div>
                          </template>

                          <div v-if="selectedLayer?.id === layer.id" class="absolute inset-0 rounded-lg border-2 border-stone-900 shadow-[0_0_0_9999px_rgba(255,255,255,0.06)]"></div>
                          <button v-if="selectedLayer?.id === layer.id && !layer.locked" type="button" class="absolute -right-2 -top-2 h-5 w-5 rounded-full border border-stone-900 bg-white" @pointerdown="beginLayerInteraction($event, layer, 'resize')"></button>
                          <button v-if="selectedLayer?.id === layer.id && !layer.locked" type="button" class="absolute left-1/2 -top-5 h-5 w-5 -translate-x-1/2 rounded-full border border-stone-900 bg-white" @pointerdown="beginLayerInteraction($event, layer, 'rotate')"></button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="space-y-4">
              <SectionCard title="Quick actions" description="Open an existing draft, duplicate the current layout direction, or continue to the next workflow.">
                <div class="space-y-3">
                  <select class="w-full rounded-2xl border-stone-300" :value="currentDesignDraftId || ''" @change="applyDraftToDesigner(designDrafts.find((item) => String(item.id) === String($event.target.value)))">
                    <option value="">Current unsaved layout</option>
                    <option v-for="draft in designDrafts" :key="draft.id" :value="draft.id">{{ draft.name }}</option>
                  </select>
                  <button class="w-full rounded-2xl border border-stone-300 px-4 py-3 text-left text-sm font-medium text-stone-700" @click="activeSection = 'proofing'">Go to Design Proofing & Price Quotation</button>
                  <button class="w-full rounded-2xl bg-stone-900 px-4 py-3 text-left text-sm font-medium text-white" :disabled="busy || !currentDesignDraftId" @click="submitDesignForProofing(designDrafts.find((item) => item.id === currentDesignDraftId) || activeProofingDesign)">Submit current draft for proofing</button>
                  <button class="w-full rounded-2xl border border-stone-300 px-4 py-3 text-left text-sm font-medium text-stone-700" @click="activeSection = 'marketplace'">Go to Marketplace request posting</button>
                </div>
              </SectionCard>

              <SectionCard title="Canvas summary" description="A quick view of the current setup that will be stored with the draft.">
                <div class="space-y-3 text-sm text-stone-600">
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Placement profile</div>
                    <div class="mt-2 font-semibold text-stone-900">{{ safeZone.label }}</div>
                    <div class="mt-1">Suggested max size {{ safeZone.maxWidth }} × {{ safeZone.maxHeight }} mm</div>
                  </div>
                  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Draft status</div>
                    <div class="mt-2 font-semibold text-stone-900">{{ currentDesignDraftId ? `Saved draft #${currentDesignDraftId}` : 'Unsaved draft' }}</div>
                    <div class="mt-1">Use the right tools panel to adjust layers, garment settings, and embroidery estimates.</div>
                  </div>
                </div>
              </SectionCard>
            </div>
          </div>
        </SectionCard>
      </template>

      <template v-else-if="activeSection === 'proofing'">
        <div class="grid gap-4 xl:grid-cols-[0.92fr_1.08fr]">
          <SectionCard title="Design Proofing & Price Quotation" description="Move a saved design into quotation and proofing, then manage revisions and approvals from one workflow panel.">
            <div class="grid gap-3 md:grid-cols-2">
              <select v-model="proofingForm.design_id" class="rounded-2xl border-stone-300">
                <option value="">Use current Design Studio draft</option>
                <option v-for="draft in workspace?.design_studio?.drafts || []" :key="draft.id" :value="draft.id">{{ draft.name }}</option>
              </select>
              <select v-model="proofingForm.shop_id" class="rounded-2xl border-stone-300">
                <option value="">Select shop</option>
                <option v-for="shop in workspace?.shops || []" :key="shop.id" :value="shop.id">{{ shop.shop_name }}</option>
              </select>
              <select v-model="proofingForm.service_selection" class="rounded-2xl border-stone-300 md:col-span-2">
                <option value="logo_embroidery">Logo embroidery</option>
                <option value="name_embroidery">Name embroidery</option>
                <option value="patch_embroidery">Patch embroidery</option>
                <option value="uniform_embroidery">Uniform embroidery</option>
                <option value="cap_embroidery">Cap embroidery</option>
                <option value="custom_design_embroidery">Custom design embroidery</option>
              </select>
            </div>
            <textarea v-model="proofingForm.description" class="mt-3 w-full rounded-2xl border-stone-300" rows="4" placeholder="Instructions for the owner or designer"></textarea>
            <label class="mt-3 block rounded-2xl border border-dashed border-stone-300 p-4 text-sm text-stone-600">Upload Design File (optional)
              <input type="file" class="mt-2 block w-full text-sm" @change="onProofingFile">
            </label>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
              <button class="rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="requestProofAndQuote">Save and submit for proofing</button>
              <button class="rounded-2xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-700" :disabled="busy || !activeProofingDesign?.id" @click="submitDesignForQuotation(activeProofingDesign)">Submit selected design for quotation</button>
            </div>

            <div v-if="activeProofingDesign" class="mt-5 space-y-3 rounded-3xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-600">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Selected design workflow</div>
                  <div class="mt-1 text-lg font-semibold text-stone-900">{{ activeProofingDesign.name }}</div>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                  <span class="rounded-full border border-stone-300 bg-white px-3 py-1.5 font-semibold text-stone-700">Version #{{ activeProofingDesign.current_version_no || activeProofingDesign.snapshots?.length || 1 }}</span>
                  <span class="rounded-full border border-stone-300 bg-white px-3 py-1.5 font-semibold text-stone-700">{{ workflowBadge(activeProofingDesign.workflow_status || activeProofingDesign.status) }}</span>
                </div>
              </div>
              <div class="grid gap-3 md:grid-cols-4">
                <div class="rounded-2xl border border-stone-200 bg-white p-3"><div class="text-xs uppercase tracking-[0.16em] text-stone-400">Garment</div><div class="mt-1 font-semibold text-stone-900">{{ activeProofingDesign.garment_type || '—' }}</div></div>
                <div class="rounded-2xl border border-stone-200 bg-white p-3"><div class="text-xs uppercase tracking-[0.16em] text-stone-400">Placement</div><div class="mt-1 font-semibold text-stone-900">{{ activeProofingDesign.placement_area || '—' }}</div></div>
                <div class="rounded-2xl border border-stone-200 bg-white p-3"><div class="text-xs uppercase tracking-[0.16em] text-stone-400">Estimate</div><div class="mt-1 font-semibold text-stone-900">{{ activeProofingDesign.stitch_count_estimate || '—' }} stitches</div></div>
                <div class="rounded-2xl border border-stone-200 bg-white p-3"><div class="text-xs uppercase tracking-[0.16em] text-stone-400">Production package</div><div class="mt-1 font-semibold text-stone-900">{{ activeProofingDesign.latest_production_package ? `#${activeProofingDesign.latest_production_package.package_no}` : 'Pending' }}</div></div>
              </div>
              <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-2xl border border-stone-200 bg-white p-3 text-sm text-stone-600">
                  <div class="text-xs uppercase tracking-[0.16em] text-stone-400">Quote basis</div>
                  <div class="mt-1 font-semibold text-stone-900">₱ {{ money(activeProofingDesign.suggested_quote_basis_json?.suggested_total || activeProofingDesign.estimated_total_price) }}</div>
                  <div class="mt-1">Digitizing fee: ₱ {{ money(activeProofingDesign.suggested_quote_basis_json?.estimated_digitizing_fee) }}</div>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-white p-3 text-sm text-stone-600">
                  <div class="text-xs uppercase tracking-[0.16em] text-stone-400">Thread planning</div>
                  <div class="mt-1 font-semibold text-stone-900">{{ activeProofingDesign.color_mapping_json?.length || activeProofingDesign.color_count || 0 }} mapped thread colors</div>
                  <div class="mt-1">{{ (activeProofingDesign.color_mapping_json || []).map((thread) => thread.thread_name).join(', ') || 'Awaiting owner refinement' }}</div>
                </div>
              </div>
              <div class="grid gap-3 md:grid-cols-2">
                <button class="rounded-2xl border border-stone-300 px-4 py-3 text-sm font-medium text-stone-700" :disabled="busy" @click="approveProofRequest(activeProofingDesign)">Approve current proof / version</button>
                <textarea v-model="revisionRequestForm.reason" class="rounded-2xl border-stone-300" rows="3" placeholder="Revision reason for the owner"></textarea>
              </div>
              <button class="w-full rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900" :disabled="busy" @click="requestDesignRevision(activeProofingDesign)">Request revision</button>
            </div>
          </SectionCard>

          <SectionCard title="Proofing request history" description="Review versions, proofs, approval state, and revision history per design.">
            <div v-if="workspace?.design_proofing?.requests?.length" class="space-y-3">
              <div v-for="request in workspace.design_proofing.requests" :key="request.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                  <div>
                    <div class="flex flex-wrap items-center gap-2"><div class="font-semibold text-stone-900">{{ request.name }}</div><span class="rounded-full border border-stone-300 bg-white px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-600">{{ workflowBadge(request.workflow_status || request.status) }}</span></div>
                    <div class="mt-2 text-sm text-stone-600">{{ request.design_post?.selected_shop?.shop_name || request.order?.shop?.shop_name || 'Pending shop confirmation' }}</div>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-4 text-sm text-stone-600">
                      <div>Quote basis: <span class="font-medium text-stone-900">₱ {{ money(request.estimated_total_price) }}</span></div>
                      <div>Version: <span class="font-medium text-stone-900">#{{ request.current_version_no || request.snapshots?.length || 1 }}</span></div>
                      <div>Approved: <span class="font-medium text-stone-900">{{ request.approved_version_no ? `#${request.approved_version_no}` : '—' }}</span></div>
                      <div>Proofs: <span class="font-medium text-stone-900">{{ request.proof_history?.length || request.proofs?.length || 0 }}</span></div>
                      <div>Packages: <span class="font-medium text-stone-900">{{ request.production_package_history?.length || request.production_packages?.length || 0 }}</span></div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-stone-600">
                      <span class="rounded-full border border-stone-300 bg-white px-2.5 py-1">{{ designProgressLabel(request) }}</span>
                      <span class="rounded-full border border-stone-300 bg-white px-2.5 py-1">Digitizing: {{ request.digitizing_status || 'Not started' }}</span>
                      <span class="rounded-full border border-stone-300 bg-white px-2.5 py-1">Machine files: {{ request.machine_file_status || 'Awaiting upload' }}</span>
                      <span class="rounded-full border border-stone-300 bg-white px-2.5 py-1">Approved machine files: {{ request.approved_machine_file_count || 0 }}</span>
                    </div>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <button class="rounded-2xl border border-stone-300 px-3 py-2 text-sm text-stone-700" :disabled="busy" @click="proofingForm.design_id = request.id">Open</button>
                    <button class="rounded-2xl border border-stone-300 px-3 py-2 text-sm text-stone-700" :disabled="busy" @click="submitDesignForProofing(request)">Resubmit</button>
                    <button class="rounded-2xl border border-stone-300 px-3 py-2 text-sm text-stone-700" :disabled="busy || !(request.version_history?.length || request.snapshots?.length)" @click="restoreDesignVersion(request, (request.version_history || request.snapshots)[0])">Restore latest saved version</button>
                  </div>
                </div>

                <div v-if="request.proof_history?.length || request.proofs?.length" class="mt-3 space-y-2">
                  <div class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">Proof history</div>
                  <div v-for="proof in (request.proof_history || request.proofs || [])" :key="proof.id" class="rounded-2xl border border-stone-200 bg-white p-3 text-sm text-stone-600">
                    <div class="flex flex-wrap items-center justify-between gap-2"><div class="font-medium text-stone-900">Proof #{{ proof.proof_no }} · Version #{{ proof.version_no || request.current_version_no || 1 }}</div><div class="text-xs uppercase text-stone-500">{{ statusChip(proof.status) }}</div></div>
                    <div class="mt-1">{{ proof.proof_summary_json?.dimensions || `${request.width_mm || '—'} × ${request.height_mm || '—'} mm` }} · {{ proof.proof_summary_json?.estimated_stitch_count || request.stitch_count_estimate || '—' }} stitches</div>
                    <div v-if="proof.annotated_notes" class="mt-1 text-stone-500">{{ proof.annotated_notes }}</div>
                  </div>
                </div>

                <div v-if="request.production_package_history?.length || request.production_packages?.length" class="mt-3 space-y-2">
                  <div class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">Production handoff</div>
                  <div v-for="pkg in (request.production_package_history || request.production_packages || [])" :key="pkg.id" class="rounded-2xl border border-stone-200 bg-white p-3 text-sm text-stone-600">
                    <div class="flex flex-wrap items-center justify-between gap-2"><div class="font-medium text-stone-900">Package #{{ pkg.package_no }} · Version #{{ pkg.version_no }}</div><div class="text-xs uppercase text-stone-500">{{ statusChip(pkg.status) }}</div></div>
                    <div class="mt-1">{{ pkg.production_summary_json?.placement_area || request.placement_area }} · {{ pkg.production_summary_json?.stitch_count_estimate || request.stitch_count_estimate || '—' }} stitches</div>
                    <div v-if="pkg.thread_mapping_json?.length" class="mt-1 text-stone-500">Threads: {{ pkg.thread_mapping_json.map((thread) => thread.thread_name).join(', ') }}</div>
                  </div>
                </div>

                <div v-if="request.activity_trail?.length || request.workflow_events?.length" class="mt-3 space-y-2">
                  <div class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">Activity trail</div>
                  <div class="space-y-2">
                    <div v-for="event in (request.activity_trail || request.workflow_events || []).slice(0, 4)" :key="event.id" class="rounded-2xl border border-stone-200 bg-white p-3 text-sm text-stone-600">
                      <div class="font-medium text-stone-900">{{ event.summary }}</div>
                      <div class="mt-1">{{ event.actor?.name || 'System' }} · {{ new Date(event.created_at).toLocaleString() }}</div>
                      <div v-if="event.details" class="mt-1 text-stone-500">{{ event.details }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <EmptyState v-else title="No proofing requests yet" description="Submit a proofing request to connect a design to a shop owner." />
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'marketplace'">
        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
          <SectionCard title="Community posts request" description="Post a public design request to all shops, with design image upload.">
            <div class="grid gap-3 md:grid-cols-2">
              <input v-model="marketplaceForm.title" class="rounded-2xl border-stone-300" placeholder="Request title">
              <input v-model="marketplaceForm.garment_type" class="rounded-2xl border-stone-300" placeholder="Garment type">
              <select v-model="marketplaceForm.design_type" class="rounded-2xl border-stone-300"><option value="logo">Logo</option><option value="uniform">Uniform</option><option value="cap">Cap</option><option value="patch">Patch</option><option value="custom_art">Custom art</option><option value="other">Other</option></select>
              <input v-model="marketplaceForm.quantity" type="number" class="rounded-2xl border-stone-300" placeholder="Quantity">
              <input v-model="marketplaceForm.target_budget" type="number" class="rounded-2xl border-stone-300 md:col-span-2" placeholder="Target budget">
            </div>
            <textarea v-model="marketplaceForm.description" class="mt-3 w-full rounded-2xl border-stone-300" rows="4" placeholder="Describe the request"></textarea>
            <textarea v-model="marketplaceForm.notes" class="mt-3 w-full rounded-2xl border-stone-300" rows="3" placeholder="Other notes"></textarea>
            <label class="mt-3 block rounded-2xl border border-dashed border-stone-300 p-4 text-sm text-stone-600">Upload design image or file
              <input type="file" class="mt-2 block w-full text-sm" @change="onMarketplaceFile">
            </label>
            <button class="mt-4 rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white" :disabled="busy" @click="postToMarketplace">Post request publicly</button>
          </SectionCard>

          <SectionCard title="Your posted requests" description="Open your request details to review proposals and bargaining entries from shops.">
            <div class="grid gap-4 lg:grid-cols-[0.95fr_1.05fr]">
              <div class="space-y-3">
                <button v-for="post in myPosts" :key="post.id" class="w-full rounded-2xl border px-4 py-3 text-left" :class="selectedPost?.id === post.id ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-200 bg-stone-50 text-stone-900'" @click="selectedPostId = post.id">
                  <div class="font-semibold">{{ post.title }}</div>
                  <div class="mt-1 text-xs opacity-75">{{ statusChip(post.status) }} · {{ post.applications?.length || 0 }} proposal(s)</div>
                </button>
                <EmptyState v-if="!myPosts.length" title="No posted requests yet" description="Your posted marketplace requests will appear here." />
              </div>
              <div>
                <div v-if="selectedPost" class="space-y-3 rounded-2xl border border-stone-200 bg-stone-50 p-4">
                  <div class="font-semibold text-stone-900">{{ selectedPost.title }}</div>
                  <div class="text-sm text-stone-600">{{ selectedPost.description }}</div>
                  <div class="text-xs text-stone-500">Budget {{ money(selectedPost.target_budget) }} · Selected shop {{ selectedPost.selected_shop?.shop_name || 'None yet' }}</div>
                  <div>
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
                <EmptyState v-else title="Select a posted request" description="Click one of your posted requests to view proposals." />
              </div>
            </div>
          </SectionCard>
        </div>
      </template>

      <template v-else-if="activeSection === 'projects'">
        <div class="grid gap-4 xl:grid-cols-[0.92fr_1.08fr]">
          <SectionCard title="Owner posted works" description="Open any posted work to view details and contact the owner.">
            <div class="space-y-3">
              <button v-for="project in projects" :key="project.id" class="w-full rounded-2xl border px-4 py-3 text-left" :class="selectedProject?.id === project.id ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-200 bg-stone-50 text-stone-900'" @click="selectedProjectId = project.id">
                <div class="font-semibold">{{ project.title }}</div>
                <div class="mt-1 text-sm opacity-80">{{ project.shop?.shop_name }}</div>
                <div class="mt-1 text-xs opacity-70">{{ project.category }} · {{ money(project.base_price) }}</div>
              </button>
            </div>
          </SectionCard>

          <SectionCard :title="selectedProject?.title || 'Project detail'" description="Detailed project information and direct chat to the posting owner.">
            <div v-if="selectedProject" class="space-y-4">
              <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-700">
                <div class="font-semibold text-stone-900">{{ selectedProject.shop?.shop_name }}</div>
                <div class="mt-2">{{ selectedProject.description }}</div>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                  <div>Category: {{ selectedProject.category }}</div>
                  <div>Base price: {{ money(selectedProject.base_price) }}</div>
                  <div>Minimum order: {{ selectedProject.min_order_qty }}</div>
                  <div>Turnaround: {{ selectedProject.turnaround_days }} day(s)</div>
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
