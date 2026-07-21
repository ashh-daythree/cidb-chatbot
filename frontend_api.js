//const API_BASE_URL = 'http://localhost:8000';
const API_BASE_URL = window.location.origin;
const WORKFLOW_CODE = 'CIDB_EMAIL_ID_CANCELLATION';
const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;
const FILE_POLICY = {
  allowedMimeTypes: ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'],
  allowedExtensions: ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
};
const MY_STATES = ['Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Perak', 'Perlis',
  'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu',
  'W.P. Kuala Lumpur', 'W.P. Labuan', 'W.P. Putrajaya'];

const messagesEl = document.getElementById('chatMessages');
const inputEl = document.getElementById('userInput');
const sendBtn = document.getElementById('sendBtn');
const uploadArea = document.getElementById('uploadArea');
const uploadBtn = document.getElementById('uploadBtn');
const uploadTitle = document.getElementById('uploadTitle');
const quickRepliesEl = document.getElementById('quickReplies');
const sigOverlay = document.getElementById('sigOverlay');
const sigCanvas = document.getElementById('sigCanvas');

const FILE_SLOTS = ['front', 'back'];
const SLOT_DOC_TYPES = { front: 'IC_FRONT', back: 'IC_BACK' };

const state = {
  step: 'booting',
  sessionId: null,
  languageCode: null,
  en: true,
  stateName: '',
  stateCode: '',
  name: '',
  identityType: '',
  identityNumber: '',
  sigDataUrl: null,
  uploads: { front: null, back: null, signature: null },
  files: { front: null, back: null },
  submission: null,
  requestNumber: null,
};

let sigCtx = null;
let sigDrawing = false;
let sigHasStrokes = false;
let sigCanvasReady = false;

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function normalizeApiBaseUrl(baseUrl) {
  return String(baseUrl || '').replace(/\/+$/, '');
}

function apiUrl(path) {
  const normalizedPath = path.startsWith('/') ? path : '/' + path;
  return normalizeApiBaseUrl(API_BASE_URL) + normalizedPath;
}

function isPlainObject(value) {
  return value !== null && typeof value === 'object' && !Array.isArray(value);
}

function firstNonEmpty(...values) {
  for (const value of values) {
    if (value === null || value === undefined) continue;
    const text = String(value).trim();
    if (text !== '') return text;
  }
  return '';
}

function extractData(payload) {
  if (!isPlainObject(payload)) return {};
  if (isPlainObject(payload.data)) return payload.data;
  if (isPlainObject(payload.payload?.data)) return payload.payload.data;
  return {};
}

function extractEntityId(entity) {
  if (!isPlainObject(entity)) return '';
  return firstNonEmpty(
    entity.id,
    entity.session_id,
    entity.sessionId,
    entity.request_id,
    entity.requestId,
    entity.request_number,
    entity.requestNumber,
    entity.uuid
  );
}

function extractRequestNumber(entity) {
  if (!isPlainObject(entity)) return '';
  return firstNonEmpty(entity.request_number, entity.requestNumber, entity.reference_no, entity.referenceNo);
}

function normalizeBotReplies(value) {
  if (!Array.isArray(value)) return [];
  return value
    .map(item => {
      if (typeof item === 'string') return item.trim();
      if (!isPlainObject(item)) return '';
      return firstNonEmpty(item.label, item.text, item.message, item.value);
    })
    .filter(Boolean);
}

function renderBackendMessage(message) {
  return escapeHtml(String(message || '')).replace(/\r?\n/g, '<br>');
}

function formatValidationErrors(errors) {
    if (!isPlainObject(errors)) return '';
    const parts = [];
    const appendValue = (prefix, value) => {
      if (Array.isArray(value)) {
        value.forEach((item, index) => appendValue(`${prefix}[${index}]`, item));
        return;
      }
      if (isPlainObject(value)) {
        const entries = Object.entries(value);
        if (entries.length === 0) {
          return;
        }
        entries.forEach(([childKey, childValue]) => appendValue(prefix ? `${prefix}.${childKey}` : childKey, childValue));
        return;
      }
      if (value !== null && value !== undefined && String(value).trim() !== '') {
        parts.push(`${escapeHtml(prefix)}: ${escapeHtml(value)}`);
      }
    };

    for (const [key, value] of Object.entries(errors)) {
      appendValue(key, value);
    }

    return parts.join('<br>');
  }

function addMsg(html, type = 'bot') {
  return new Promise(resolve => {
    const div = document.createElement('div');
    div.className = 'msg ' + type;
    div.innerHTML = html;
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
    setTimeout(resolve, 0);
  });
}

function showTyping(ms = 900) {
  return new Promise(resolve => {
    const t = document.createElement('div');
    t.className = 'typing';
    t.innerHTML = '<span></span><span></span><span></span>';
    messagesEl.appendChild(t);
    messagesEl.scrollTop = messagesEl.scrollHeight;
    setTimeout(() => {
      t.remove();
      resolve();
    }, ms);
  });
}

function setInput(on) {
  inputEl.disabled = !on;
  sendBtn.disabled = !on;
}

function setQR(opts) {
  quickRepliesEl.innerHTML = '';
  opts.forEach(option => {
    const button = document.createElement('button');
    button.className = 'qr-btn';
    button.textContent = option;
    button.onclick = () => {
      quickRepliesEl.innerHTML = '';
      inputEl.value = option;
      sendMessage();
    };
    quickRepliesEl.appendChild(button);
  });
}

function setUploadLabels(en) {
  uploadTitle.textContent = en ? '📎 Upload your documents (all 3 required):' : '📎 Muat naik dokumen anda (kesemua 3 diperlukan):';
  document.getElementById('lbl-front').innerHTML = en ? 'IC Front' : 'IC Depan';
  document.getElementById('lbl-back').innerHTML = en ? 'IC Back' : 'IC Belakang';
  document.getElementById('lbl-sig').innerHTML = en ? 'Signature' : 'Tandatangan';
  document.getElementById('sub-front').textContent = en ? 'Tap to upload' : 'Ketik untuk muat naik';
  document.getElementById('sub-back').textContent = en ? 'Tap to upload' : 'Ketik untuk muat naik';
  document.getElementById('sub-sig').textContent = en ? 'Tap to sign' : 'Ketik untuk tandatangan';
  uploadBtn.textContent = 'Submit / Hantar';
}

function resetUploadSlot(slotId) {
  const fileInput = document.getElementById(`file-${slotId}`);
  const slot = document.getElementById(`slot-${slotId}`);
  const thumb = document.getElementById(`thumb-${slotId}`);
  const sub = document.getElementById(`sub-${slotId}`);
  fileInput.value = '';
  slot.classList.remove('has-file');
  thumb.removeAttribute('src');
  sub.textContent = state.en ? 'Tap to upload' : 'Ketik untuk muat naik';
}

function setSlotSuccess(slotId, file, previewUrl) {
  const slot = document.getElementById(`slot-${slotId}`);
  const thumb = document.getElementById(`thumb-${slotId}`);
  const sub = document.getElementById(`sub-${slotId}`);
  slot.classList.add('has-file');
  if (previewUrl && file.type.startsWith('image/')) {
    thumb.src = previewUrl;
  } else {
    thumb.removeAttribute('src');
  }
  sub.textContent = file.name.length > 20 ? `${file.name.slice(0, 19)}...` : file.name;
}

function setSignatureSlotSuccess(previewUrl) {
  const slot = document.getElementById('slot-sig');
  const thumb = document.getElementById('thumb-sig');
  const sub = document.getElementById('sub-sig');
  slot.classList.add('has-file');
  thumb.src = previewUrl;
  sub.textContent = state.en ? 'Signed' : 'Ditandatangani';
}

function allUploadsComplete() {
  return Boolean(state.uploads.front && state.uploads.back && state.uploads.signature);
}

function checkAllFilled() {
  uploadBtn.disabled = !allUploadsComplete();
}

function validateLocalFile(file) {
  if (!file) return { ok: false, message: state.en ? 'Please choose a file.' : 'Sila pilih fail.' };
  const name = String(file.name || '');
  const lowerName = name.toLowerCase();
  const ext = lowerName.includes('.') ? lowerName.split('.').pop() : '';
  if (!name.trim()) return { ok: false, message: state.en ? 'File name is missing.' : 'Nama fail tiada.' };
  if (/[\\/\x00]/.test(name)) return { ok: false, message: state.en ? 'File name contains invalid characters.' : 'Nama fail mengandungi aksara tidak sah.' };
  if (!FILE_POLICY.allowedExtensions.includes(ext)) return { ok: false, message: state.en ? 'Unsupported file extension.' : 'Sambungan fail tidak disokong.' };
  if (!FILE_POLICY.allowedMimeTypes.includes(file.type)) return { ok: false, message: state.en ? 'Unsupported file type.' : 'Jenis fail tidak disokong.' };
  if (file.size <= 0) return { ok: false, message: state.en ? 'Uploaded file is empty.' : 'Fail yang dimuat naik kosong.' };
  if (file.size > MAX_UPLOAD_BYTES) return { ok: false, message: state.en ? 'Uploaded file exceeds the size limit.' : 'Fail melebihi had saiz.' };
  if (lowerName.split('.').length > 2) {
    const dangerous = ['php', 'phtml', 'phar', 'cgi', 'pl', 'asp', 'aspx', 'js', 'exe', 'bat', 'cmd', 'sh', 'com', 'scr', 'jar'];
    const prefixSegments = lowerName.split('.').slice(0, -1);
    if (prefixSegments.some(segment => dangerous.includes(segment))) {
      return { ok: false, message: state.en ? 'Double extensions are not allowed.' : 'Sambungan berganda tidak dibenarkan.' };
    }
  }
  return { ok: true };
}

async function apiRequest(path, options = {}) {
  const method = options.method || 'GET';
  const headers = { Accept: 'application/json', ...(options.headers || {}) };
  const fetchOptions = { method, headers, credentials: 'omit' };

  if (options.body instanceof FormData) {
    fetchOptions.body = options.body;
    delete fetchOptions.headers['Content-Type'];
  } else if (options.body !== undefined && options.body !== null) {
    fetchOptions.body = typeof options.body === 'string' ? options.body : JSON.stringify(options.body);
    fetchOptions.headers['Content-Type'] = 'application/json';
  }

  let response;
  try {
    response = await fetch(apiUrl(path), fetchOptions);
  } catch (error) {
    const networkError = new Error('Unable to reach the backend service.');
    networkError.cause = error;
    networkError.status = 0;
    networkError.payload = {};
    throw networkError;
  }

  const raw = await response.text();
  let parsed = {};
  if (raw.trim() !== '') {
    try {
      parsed = JSON.parse(raw);
    } catch (error) {
      const parseError = new Error('Backend returned an invalid response.');
      parseError.status = response.status;
      parseError.payload = { raw };
      throw parseError;
    }
  }

  if (!response.ok || parsed.success === false) {
    const message = parsed.message || `Request failed with status ${response.status}.`;
    const error = new Error(message);
    error.status = response.status;
    error.payload = parsed;
    error.errors = parsed.errors || {};
    error.errorCode = parsed.error_code || parsed.errorCode || null;
    throw error;
  }

  return parsed;
}

async function showApiError(error, fallbackMessage) {
  const message = error?.message || fallbackMessage || 'An unexpected error occurred.';
  const details = formatValidationErrors(error?.errors);
  await addMsg(`<strong>${escapeHtml(message)}</strong>${details ? `<br>${details}` : ''}`, 'error');
}

async function refreshSession() {
  if (!state.sessionId) return null;
  try {
    const response = await apiRequest(`/session/${encodeURIComponent(state.sessionId)}`);
    return extractData(response);
  } catch (error) {
    return null;
  }
}

async function refreshSubmission(identifier) {
  if (!identifier) return null;
  try {
    const response = await apiRequest(`/submission/${encodeURIComponent(identifier)}`);
    return extractData(response);
  } catch (error) {
    return null;
  }
}

async function startBackendSession() {
  const response = await apiRequest('/session/start', {
    method: 'POST',
    body: { workflow_code: WORKFLOW_CODE },
  });
  const data = extractData(response);
  const session = isPlainObject(data.session) ? data.session : data;
  const sessionId = extractEntityId(session);
  if (!sessionId) {
    throw new Error('Backend session did not return a session identifier.');
  }
  state.sessionId = sessionId;
  return { response, data, session };
}

function buildLanguagePayload(text) {
  const value = String(text || '').trim().toLowerCase();
  if (value === 'english' || value === 'en') return { language: 'en' };
  if (value === 'bahasa malaysia' || value === 'bahasa melayu' || value === 'malay' || value === 'bm' || value === 'ms') return { language: 'ms' };
  return null;
}

function buildStatePayload(text) {
  const value = String(text || '').trim().toLowerCase();
  const match = MY_STATES.find(item => item.toLowerCase() === value);
  return match ? { state: match } : null;
}

function buildIdentityPayload(text) {
  const clean = String(text || '').replace(/[\s-]/g, '');
  const myKadMatch = /^\d{12}$/.test(clean);
  const passportMatch = /^[A-Za-z0-9]{6,20}$/.test(clean);
  if (myKadMatch) {
    const normalized = clean.replace(/(\d{6})(\d{2})(\d{4})/, '$1-$2-$3');
    return { identity_type: 'MYKAD', identity_number: normalized, display: normalized };
  }
  if (passportMatch) {
    const normalized = clean.toUpperCase();
    return { identity_type: 'PASSPORT', identity_number: normalized, display: normalized };
  }
  return null;
}

async function uploadDocument(slotId, file) {
  const form = new FormData();
  form.append('session_id', state.sessionId);
  form.append('document_type_code', SLOT_DOC_TYPES[slotId]);
  form.append('file_field', 'file');
  form.append('file', file, file.name || `${slotId}.bin`);
  form.append('upload_source', 'user_upload');
  const response = await apiRequest('/documents/upload', { method: 'POST', body: form });
  return extractData(response);
}

async function uploadSignature(dataUrl) {
  const form = new FormData();
  form.append('session_id', state.sessionId);
  form.append('signature_data_url', dataUrl);
  form.append('upload_source', 'signature_pad');
  const response = await apiRequest('/signature/upload', { method: 'POST', body: form });
  return extractData(response);
}

function renderSummaryBox() {
  const front = state.uploads.front?.document || state.uploads.front;
  const back = state.uploads.back?.document || state.uploads.back;
  const signaturePreview = state.sigDataUrl
    ? `<img src="${state.sigDataUrl}" style="max-height:36px;border-radius:4px;border:1px solid #aaa;vertical-align:middle;margin-left:4px;">`
    : '';

  return `<strong>${state.en ? 'Summary of your submission:' : 'Ringkasan penghantaran anda:'}</strong>`
    + '<div class="info-box">'
    + `👤 <strong>${state.en ? 'Name' : 'Nama'}:</strong> ${escapeHtml(state.name)}<br>`
    + `🪪 <strong>${state.en ? 'IC / Passport No.' : 'No. IC / Pasport'}:</strong> ${escapeHtml(state.identityNumber)}<br>`
    + `📍 <strong>${state.en ? 'State' : 'Negeri'}:</strong> ${escapeHtml(state.stateName)}<br>`
    + `📎 <strong>${state.en ? 'IC Front' : 'IC Depan'}:</strong> ${escapeHtml(front?.original_filename || front?.file_name || front?.stored_filename || 'Uploaded')}<br>`
    + `📎 <strong>${state.en ? 'IC Back' : 'IC Belakang'}:</strong> ${escapeHtml(back?.original_filename || back?.file_name || back?.stored_filename || 'Uploaded')}<br>`
    + `✍️ <strong>${state.en ? 'Signature' : 'Tandatangan'}:</strong> ${signaturePreview}`
    + '</div>';
}

function renderFinalOutcome(outcome) {
  const ic = escapeHtml(state.identityNumber || '');
  const en = state.en;
  if (outcome === 'deleted') {
    return en
      ? '✅ <strong>Good news!</strong> We have successfully verified your details and your CIMS Individual Email ID has been <strong>cancelled</strong>.<br><br>'
        + 'You may register a new Email ID at any time via <a href="https://cims.cidb.gov.my" target="_blank" rel="noreferrer">cims.cidb.gov.my</a>.<br><br>'
        + 'Thank you for contacting <strong>CIDB Livechat</strong>. Have a wonderful day!'
      : '✅ <strong>Berita baik!</strong> Kami telah berjaya mengesahkan maklumat anda dan Email ID CIMS Individu anda telah berjaya <strong>dibatalkan</strong>.<br><br>'
        + 'Anda boleh mendaftar Email ID baharu pada bila-bila masa melalui <a href="https://cims.cidb.gov.my" target="_blank" rel="noreferrer">cims.cidb.gov.my</a>.<br><br>'
        + 'Terima kasih kerana menghubungi <strong>CIDB Livechat</strong>. Selamat sejahtera!';
  }
  if (outcome === 'linked') {
    return en
      ? '⚠️ We are unable to cancel your Email ID at this time as your <strong>User ID is currently linked to a CIMS module</strong>.<br><br>'
        + 'Please ensure all active module links are removed before requesting cancellation, or contact our support team for further assistance.<br><br>'
        + 'Thank you for your patience.'
      : '⚠️ Kami tidak dapat membatalkan Email ID anda buat masa ini kerana <strong>ID Pengguna anda masih dikaitkan dengan modul CIMS</strong>.<br><br>'
        + 'Sila pastikan semua pautan modul aktif telah dibuang sebelum membuat permintaan pembatalan, atau hubungi pasukan sokongan kami untuk bantuan lanjut.<br><br>'
        + 'Terima kasih atas kesabaran anda.';
  }
  if (outcome === 'norecord') {
    return en
      ? `⚠️ We were unable to locate an Email ID record under the IC / Passport number provided (<strong>${ic}</strong>).<br><br>`
        + 'If you wish to create a new Email ID, please visit <a href="https://cims.cidb.gov.my" target="_blank" rel="noreferrer">cims.cidb.gov.my</a> and register directly.<br><br>'
        + 'Thank you for contacting <strong>CIDB Livechat</strong>. Have a wonderful day!'
      : `⚠️ Kami tidak dapat menjumpai rekod Email ID di bawah nombor IC / Pasport yang diberikan (<strong>${ic}</strong>).<br><br>`
        + 'Sekiranya anda ingin mencipta Email ID baharu, sila layari <a href="https://cims.cidb.gov.my" target="_blank" rel="noreferrer">cims.cidb.gov.my</a> dan daftar secara terus.<br><br>'
        + 'Terima kasih kerana menghubungi <strong>CIDB Livechat</strong>. Selamat sejahtera!';
  }
  return en
    ? '⚠️ We could not complete the verification right now. Please try again later or contact our support team.'
    : '⚠️ Kami tidak dapat melengkapkan pengesahan buat masa ini. Sila cuba lagi kemudian atau hubungi pasukan sokongan kami.';
}

function updateSessionStateFromSession(session) {
  if (!isPlainObject(session)) return;
  state.languageCode = firstNonEmpty(session.language_code, session.languageCode, state.languageCode);
  state.stateName = firstNonEmpty(session.state_name, session.stateName, state.stateName);
  state.name = firstNonEmpty(session.full_name, session.name, state.name);
  state.identityNumber = firstNonEmpty(session.identity_number, session.identityNumber, state.identityNumber);
  state.identityType = firstNonEmpty(session.identity_type, state.identityType);
}

async function bootstrapConversation() {
  setInput(false);
  quickRepliesEl.innerHTML = '';
  uploadArea.style.display = 'none';
  await showTyping(700);
  try {
    const { session } = await startBackendSession();
    updateSessionStateFromSession(session);
    await showTyping(500);
    await addMsg('Welcome to <strong>CIDB Livechat</strong>!<br>Selamat datang ke <strong>CIDB Livechat</strong>!');
    await showTyping(500);
    await addMsg('Please select your preferred language:<br>Sila pilih bahasa pilihan anda:');
    setQR(['English', 'Bahasa Malaysia']);
    state.step = 'ask_lang';
    setInput(true);
    await refreshSession();
  } catch (error) {
    await addMsg(`<strong>${escapeHtml(error?.message || 'Failed to start session.')}</strong>`, 'error');
    await addMsg('Please refresh the page to try again.', 'error');
    state.step = 'booting';
    setInput(false);
  }
}

async function sendMessage() {
  const text = inputEl.value.trim();
  if (!text) return;
  inputEl.value = '';
  quickRepliesEl.innerHTML = '';
  setInput(false);
  await addMsg(escapeHtml(text), 'user');
  await handleStep(text);
}

async function handleStep(text) {
  if (!state.sessionId) {
    setInput(true);
    return;
  }

  if (state.step === 'ask_lang') {
    const payload = buildLanguagePayload(text);
    if (!payload) {
      await showApiError({ message: 'Unsupported language.', errors: { language: 'Language must be English or Bahasa Malaysia.' } }, 'Unsupported language.');
      await addMsg('Please select your preferred language:<br>Sila pilih bahasa pilihan anda:');
      setQR(['English', 'Bahasa Malaysia']);
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/language', { method: 'POST', body: { session_id: state.sessionId, language: payload.language } });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.en = payload.language === 'en';
      state.languageCode = payload.language;
      state.step = 'ask_state';
      await showTyping(450);
      await addMsg(state.en ? 'Thank you! My name is <strong>Bena</strong> and I am here to help you today.' : 'Terima kasih! Nama saya <strong>Bena</strong> dan saya di sini untuk membantu anda hari ini.');
      await showTyping(450);
      await addMsg(state.en ? 'Before we begin, may I know which <strong>state</strong> you are contacting us from?' : 'Sebelum kita mulakan, bolehkah saya tahu dari <strong>negeri</strong> mana anda menghubungi kami?');
      setQR(MY_STATES);
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save language.');
      await addMsg('Please select your preferred language:<br>Sila pilih bahasa pilihan anda:');
      setQR(['English', 'Bahasa Malaysia']);
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_state') {
    const payload = buildStatePayload(text);
    if (!payload) {
      await showApiError({ message: 'Invalid Malaysian state selected.', errors: { state: 'Please choose a valid Malaysian state.' } }, 'Invalid Malaysian state selected.');
      await addMsg(state.en ? 'Before we continue, may I know which <strong>state</strong> you are contacting us from?' : 'Sebelum kita teruskan, bolehkah saya tahu dari <strong>negeri</strong> mana anda menghubungi kami?');
      setQR(MY_STATES);
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/state', { method: 'POST', body: { session_id: state.sessionId, state: payload.state } });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.stateName = payload.state;
      state.step = 'ask_name';
      await showTyping(450);
      await addMsg(state.en ? `Thank you! You are contacting us from <strong>${escapeHtml(payload.state)}</strong>.` : `Terima kasih! Anda menghubungi kami dari <strong>${escapeHtml(payload.state)}</strong>.`);
      await showTyping(650);
      await addMsg(state.en
        ? 'To proceed with your <strong>Email ID Cancellation</strong> request, please provide the following:<div class="info-box">1️⃣ Full name (as per IC / Passport)<br>2️⃣ IC / Passport number<br>3️⃣ IC copy (front & back) - clear and readable<br><em>Note: The crossout marking "Untuk Kegunaan CIDB" and applicant signature are encouraged but not mandatory - as long as the IC copy is clear and readable, it will be accepted.</em></div>'
        : 'Untuk meneruskan permohonan <strong>Pembatalan Email ID</strong> anda, sila sediakan maklumat berikut:<div class="info-box">1️⃣ Nama penuh (seperti dalam IC / Pasport)<br>2️⃣ Nombor IC / Pasport<br>3️⃣ Salinan IC (depan & belakang) - jelas dan boleh dibaca<br><em>Nota: Tanda palangan "Untuk Kegunaan CIDB" dan tandatangan pemohon digalakkan tetapi tidak diwajibkan - asalkan salinan IC jelas dan boleh dibaca, ia akan diterima.</em></div>');
      await showTyping(450);
      await addMsg(state.en ? 'May I have your <strong>full name</strong> please?' : 'Boleh saya dapatkan <strong>nama penuh</strong> anda?');
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save state.');
      await addMsg(state.en ? 'Before we continue, may I know which <strong>state</strong> you are contacting us from?' : 'Sebelum kita teruskan, bolehkah saya tahu dari <strong>negeri</strong> mana anda menghubungi kami?');
      setQR(MY_STATES);
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_name') {
    try {
      const response = await apiRequest('/session/name', { method: 'POST', body: { session_id: state.sessionId, full_name: text } });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.name = text;
      state.step = 'ask_ic';
      await showTyping(450);
      await addMsg(state.en ? `Thank you, <strong>${escapeHtml(text)}</strong>! May I have your <strong>IC / Passport number</strong>?` : `Terima kasih, <strong>${escapeHtml(text)}</strong>! Boleh saya dapatkan <strong>nombor IC / Pasport</strong> anda?`);
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save name.');
      await addMsg(state.en ? 'Please enter your <strong>full name</strong> again.' : 'Sila masukkan semula <strong>nama penuh</strong> anda.');
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_ic') {
    const payload = buildIdentityPayload(text);
    if (!payload) {
      await showApiError({ message: 'Invalid IC / Passport number.', errors: { identity_number: 'Use a valid MyKad or passport number.' } }, 'Invalid IC / Passport number.');
      await addMsg(state.en ? 'Please enter your <strong>IC / Passport number</strong> again.' : 'Sila masukkan semula <strong>nombor IC / Pasport</strong> anda.');
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/identity', {
        method: 'POST',
        body: { session_id: state.sessionId, identity_type: payload.identity_type, identity_number: payload.identity_number },
      });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.identityType = payload.identity_type;
      state.identityNumber = payload.display;
      state.step = 'ask_ic_copy';
      await showTyping(500);
      await addMsg(state.en
        ? 'Thank you. Please upload your <strong>IC copy (front & back)</strong>. Make sure the image is <strong>clear and fully visible</strong>.<div class="warn-box">📷 <strong>Photo tips:</strong><br>• Place IC on a flat, well-lit surface<br>• Avoid blur, shadows, or cropping<br>• Both front and back copies are required</div>'
        : 'Terima kasih. Sila muat naik <strong>salinan IC (depan & belakang)</strong>. Pastikan gambar <strong>jelas dan tidak terpotong</strong>.<div class="warn-box">📷 <strong>Petua foto:</strong><br>• Letak IC di permukaan rata yang terang<br>• Elakkan kabur, bayang, atau gambar terpotong<br>• Salinan depan dan belakang diperlukan</div>');
      setUploadLabels(state.en);
      state.sigDataUrl = null;
      state.uploads = { front: null, back: null, signature: null };
      state.files = { front: null, back: null };
      resetUploadSlot('front');
      resetUploadSlot('back');
      document.getElementById('slot-sig').classList.remove('has-file');
      document.getElementById('thumb-sig').removeAttribute('src');
      document.getElementById('sub-sig').textContent = state.en ? 'Tap to sign' : 'Ketik untuk tandatangan';
      uploadArea.style.display = 'flex';
      uploadBtn.disabled = true;
      setInput(false);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save identity.');
      await addMsg(state.en ? 'Please enter your <strong>IC / Passport number</strong> again.' : 'Sila masukkan semula <strong>nombor IC / Pasport</strong> anda.');
      setInput(true);
      return;
    }
  }

  setInput(true);
}

async function slotChanged(slotId) {
  const fileInput = document.getElementById(`file-${slotId}`);
  const file = fileInput.files[0];
  const slot = document.getElementById(`slot-${slotId}`);
  const thumb = document.getElementById(`thumb-${slotId}`);
  const sub = document.getElementById(`sub-${slotId}`);

  if (!file) {
    state.uploads[slotId] = null;
    state.files[slotId] = null;
    slot.classList.remove('has-file');
    sub.textContent = state.en ? 'Tap to upload' : 'Ketik untuk muat naik';
    checkAllFilled();
    return;
  }

  const localValidation = validateLocalFile(file);
  if (!localValidation.ok) {
    state.uploads[slotId] = null;
    state.files[slotId] = null;
    resetUploadSlot(slotId);
    await showApiError({ message: localValidation.message, errors: { file: localValidation.message } }, localValidation.message);
    checkAllFilled();
    return;
  }

  state.files[slotId] = file;
  const previewUrl = file.type.startsWith('image/') ? URL.createObjectURL(file) : '';
  setSlotSuccess(slotId, file, previewUrl);

  try {
    const data = await uploadDocument(slotId, file);
    state.uploads[slotId] = data.document || data;
    if (previewUrl) setTimeout(() => URL.revokeObjectURL(previewUrl), 0);
    await addMsg(state.en
      ? `Uploaded <strong>${escapeHtml(slotId === 'front' ? 'IC Front' : 'IC Back')}</strong>: ${escapeHtml(file.name)}`
      : `Berjaya dimuat naik <strong>${escapeHtml(slotId === 'front' ? 'IC Depan' : 'IC Belakang')}</strong>: ${escapeHtml(file.name)}`, 'bot');
    checkAllFilled();
  } catch (error) {
    state.uploads[slotId] = null;
    state.files[slotId] = null;
    if (previewUrl) setTimeout(() => URL.revokeObjectURL(previewUrl), 0);
    resetUploadSlot(slotId);
    await showApiError(error, 'Document upload failed.');
    checkAllFilled();
  }
}

function openSigPad() {
  const en = state.en;
  document.getElementById('sigTitle').textContent = en ? 'Draw your signature' : 'Lukis tandatangan anda';
  document.getElementById('sigSub').textContent = en ? 'Sign inside the box using your finger or mouse' : 'Lukis di dalam kotak menggunakan jari atau tetikus';
  document.getElementById('sigClearBtn').textContent = en ? 'Clear' : 'Padam';
  document.getElementById('sigCancelLink').textContent = en ? 'Cancel' : 'Batal';
  sigOverlay.classList.add('open');
  const rect = sigCanvas.getBoundingClientRect();
  sigCanvas.width = rect.width * (window.devicePixelRatio || 1);
  sigCanvas.height = rect.height * (window.devicePixelRatio || 1);
  sigCtx = sigCanvas.getContext('2d');
  sigCtx.scale(window.devicePixelRatio || 1, window.devicePixelRatio || 1);
  sigCtx.strokeStyle = '#1a3a5c';
  sigCtx.lineWidth = 2.2;
  sigCtx.lineCap = 'round';
  sigCtx.lineJoin = 'round';
  sigHasStrokes = false;
  sigCanvasReady = true;
  if (state.sigDataUrl) {
    const image = new Image();
    image.onload = () => sigCtx.drawImage(image, 0, 0, rect.width, rect.height);
    image.src = state.sigDataUrl;
    sigHasStrokes = true;
  }
}

function closeSigPad() {
  sigOverlay.classList.remove('open');
}

function clearSig() {
  if (!sigCanvasReady || !sigCtx) return;
  const rect = sigCanvas.getBoundingClientRect();
  sigCtx.clearRect(0, 0, rect.width, rect.height);
  sigHasStrokes = false;
}

function getPos(e) {
  const rect = sigCanvas.getBoundingClientRect();
  if (e.touches) return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
  return { x: e.clientX - rect.left, y: e.clientY - rect.top };
}

sigCanvas.addEventListener('mousedown', e => {
  if (!sigCanvasReady) return;
  sigDrawing = true;
  const p = getPos(e);
  sigCtx.beginPath();
  sigCtx.moveTo(p.x, p.y);
});
sigCanvas.addEventListener('mousemove', e => {
  if (!sigDrawing || !sigCanvasReady) return;
  const p = getPos(e);
  sigCtx.lineTo(p.x, p.y);
  sigCtx.stroke();
  sigHasStrokes = true;
});
sigCanvas.addEventListener('mouseup', () => { sigDrawing = false; });
sigCanvas.addEventListener('mouseleave', () => { sigDrawing = false; });
sigCanvas.addEventListener('touchstart', e => {
  e.preventDefault();
  if (!sigCanvasReady) return;
  sigDrawing = true;
  const p = getPos(e);
  sigCtx.beginPath();
  sigCtx.moveTo(p.x, p.y);
}, { passive: false });
sigCanvas.addEventListener('touchmove', e => {
  e.preventDefault();
  if (!sigDrawing || !sigCanvasReady) return;
  const p = getPos(e);
  sigCtx.lineTo(p.x, p.y);
  sigCtx.stroke();
  sigHasStrokes = true;
}, { passive: false });
sigCanvas.addEventListener('touchend', () => { sigDrawing = false; });
sigOverlay.addEventListener('click', e => { if (e.target === sigOverlay) closeSigPad(); });

async function confirmSig() {
  const en = state.en;
  if (!sigHasStrokes || !sigCanvasReady) {
    const sub = document.getElementById('sigSub');
    sub.style.color = '#c0392b';
    sub.textContent = en ? 'Please draw your signature first.' : 'Sila lukis tandatangan anda terlebih dahulu.';
    setTimeout(() => {
      sub.style.color = '';
      sub.textContent = en ? 'Sign inside the box using your finger or mouse' : 'Lukis di dalam kotak menggunakan jari atau tetikus';
    }, 2000);
    return;
  }
  const dataUrl = sigCanvas.toDataURL('image/png');
  try {
    const data = await uploadSignature(dataUrl);
    state.sigDataUrl = dataUrl;
    state.uploads.signature = data.signature || data;
    setSignatureSlotSuccess(dataUrl);
    checkAllFilled();
    closeSigPad();
    await addMsg(state.en ? 'Signature uploaded successfully.' : 'Tandatangan berjaya dimuat naik.', 'bot');
  } catch (error) {
    state.uploads.signature = null;
    state.sigDataUrl = null;
    const sub = document.getElementById('sigSub');
    sub.style.color = '#c0392b';
    sub.textContent = error?.message || (en ? 'Signature upload failed.' : 'Muat naik tandatangan gagal.');
    await showApiError(error, 'Signature upload failed.');
    setTimeout(() => {
      sub.style.color = '';
      sub.textContent = en ? 'Sign inside the box using your finger or mouse' : 'Lukis di dalam kotak menggunakan jari atau tetikus';
    }, 2500);
    checkAllFilled();
  }
}

async function submitIC() {
  const en = state.en;
  if (!state.sessionId) {
    await addMsg(en ? 'Session is not ready yet.' : 'Sesi belum sedia lagi.', 'error');
    return;
  }
  if (!state.name || !state.identityNumber || !state.stateName) {
    await addMsg(en ? 'Please complete the previous steps before submitting.' : 'Sila lengkapkan langkah sebelumnya sebelum menghantar.', 'error');
    return;
  }
  if (!state.uploads.front || !state.uploads.back || !state.uploads.signature) {
    await addMsg(en ? 'Please upload both IC images and your signature before submitting.' : 'Sila muat naik kedua-dua imej IC dan tandatangan anda sebelum menghantar.', 'error');
    return;
  }

  uploadArea.style.display = 'none';
  uploadBtn.disabled = true;

  const front = state.uploads.front?.document || state.uploads.front;
  const back = state.uploads.back?.document || state.uploads.back;
  const sigThumbHtml = state.sigDataUrl
    ? `<img src="${state.sigDataUrl}" style="max-height:36px;border-radius:4px;border:1px solid #aaa;vertical-align:middle;margin-left:4px;">`
    : '';
  const names = [
    `IC Front: <strong>${escapeHtml(front?.original_filename || front?.file_name || state.files.front?.name || 'Uploaded')}</strong>`,
    `IC Back: <strong>${escapeHtml(back?.original_filename || back?.file_name || state.files.back?.name || 'Uploaded')}</strong>`,
    `Signature: ${sigThumbHtml}`,
  ].join('<br>');

  await addMsg(`Documents submitted:<br>${names}`, 'user');
  await showTyping(900);
  await addMsg(en ? 'Thank you. We are reviewing your submitted documents...' : 'Terima kasih. Kami sedang menyemak dokumen yang dihantar...');
  await showTyping(1400);
  await addMsg(en ? '✅ <strong>All documents have been received.</strong>' : '✅ <strong>Semua dokumen telah diterima.</strong>');
  await showTyping(650);
  await addMsg(renderSummaryBox());
  await showTyping(700);
  await addMsg(en
    ? 'Please <strong>stay on the line</strong> while we verify your details in the <strong>CIMS portal</strong>. This will only take a moment...'
    : 'Sila <strong>tunggu sebentar</strong> sementara kami mengesahkan maklumat anda dalam <strong>portal CIMS</strong>. Ini hanya mengambil masa sebentar...');

  try {
    const response = await apiRequest('/submission', {
      method: 'POST',
      body: { session_id: state.sessionId },
    });
    const data = extractData(response);
    state.submission = isPlainObject(data.request) ? data.request : data.submission || data;
    state.requestNumber = extractRequestNumber(data.request) || extractRequestNumber(state.submission);
    const verification = isPlainObject(data.verification) ? data.verification : null;
    const botMessage = firstNonEmpty(
      verification?.response_message,
      verification?.display_message,
      verification?.message
    );
    const botReplies = normalizeBotReplies(verification?.quick_replies || verification?.options || verification?.choices);
    const outcome = firstNonEmpty(
      verification?.result_status,
      verification?.status,
      data?.result_status,
      state.submission?.verification_result,
      'error'
    );
    const identifier = state.requestNumber || extractEntityId(state.submission) || state.sessionId;
    const refreshed = await refreshSubmission(identifier);
    if (isPlainObject(refreshed?.submission)) {
      state.submission = refreshed.submission;
    }
    await showTyping(900);
    if (botMessage) {
      const botResponseLines = [renderBackendMessage(botMessage)];
      if (botReplies.length > 0) {
        botResponseLines.push('<br><strong>Options:</strong><br>' + botReplies.map(option => `• ${escapeHtml(option)}`).join('<br>'));
      }
      await addMsg(botResponseLines.join(''), 'bot');
    } else {
      await addMsg(renderFinalOutcome(outcome));
    }
    state.step = 'done';
    setInput(false);
  } catch (error) {
    await showApiError(error, 'Submission failed.');
    state.step = 'ask_ic_copy';
    uploadArea.style.display = 'flex';
    setInput(false);
    checkAllFilled();
  }
}

bootstrapConversation();
