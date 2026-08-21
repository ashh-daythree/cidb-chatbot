//const API_BASE_URL = window.location.origin;
const API_BASE_URL = 'http://localhost:8000';
const WORKFLOW_CODE = 'CIDB_EMAIL_ID_CANCELLATION';
const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;
const FILE_POLICY = {
  allowedMimeTypes: ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
  allowedExtensions: ['jpg', 'jpeg', 'png', 'webp'],
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
const sigUploadBtn = document.getElementById('sigUploadBtn');
const sigFileInput = document.getElementById('sigFileInput');
const sigSub = document.getElementById('sigSub');

const FILE_SLOTS = ['front', 'back', 'certificate'];
const SLOT_DOC_TYPES = { front: 'IC_FRONT', back: 'IC_BACK', certificate: 'SSM_PPK_CERTIFICATE' };
const WAIT_MESSAGE_INTERVAL_MS = 2400;
const FINAL_VERIFICATION_TIMEOUT_MS = 10 * 60 * 1000;
const DEBUG_RPA_FLOW = true;
const SUBMISSION_CONTEXT_STORAGE_KEY = 'cidb_submission_context';
const WAIT_MESSAGES = {
  en: [
    'Thank you for waiting. We are checking your details now...',
    'We are still verifying your information. Please stay with us...',
    'Almost there. Your request is still being processed...',
  ],
  ms: [
    'Terima kasih kerana menunggu. Kami sedang menyemak maklumat anda sekarang...',
    'Kami masih mengesahkan maklumat anda. Sila terus bersama kami...',
    'Hampir selesai. Permohonan anda masih diproses...',
  ],
};

const DISPLAY_WAIT_MESSAGES = {
  en: [
    'Thanks for your patience. We are preparing the final update...',
    'Almost done. The final message is being loaded now...',
    'Just a moment longer while we fetch the latest result...',
  ],
  ms: [
    'Terima kasih atas kesabaran anda. Kami sedang menyediakan kemas kini akhir...',
    'Hampir siap. Mesej akhir sedang dimuatkan sekarang...',
    'Sila tunggu sebentar lagi sementara kami mendapatkan keputusan terkini...',
  ],
};

const SERVICE_OPTIONS = {
  en: [
    { value: 'individual', label: '1. Individual Email ID Cancellation' },
    { value: 'company', label: '2. Company Email ID Cancellation' },
  ],
  ms: [
    { value: 'individual', label: '1. Pembatalan Email ID Individu' },
    { value: 'company', label: '2. Pembatalan Email ID Syarikat' },
  ],
};

const state = {
  step: 'booting',
  sessionId: null,
  languageCode: null,
  en: true,
  serviceType: 'individual',
  stateName: '',
  stateCode: '',
  name: '',
  identityType: '',
  identityNumber: '',
  companyPpkNumber: '',
  companyName: '',
  companyEmail: '',
  companyContactNumber: '',
  companyCategory: '',
  companyDirectorName: '',
  companyDirectorIdentityType: '',
  companyDirectorIdentityNumber: '',
  companyReason: '',
  faqTopics: [],
  faqTopicCode: '',
  faqSubtopics: [],
  faqSubtopicCode: '',
  faqQuestionOffset: 0,
  sigDataUrl: null,
  uploads: { front: null, back: null, certificate: null, signature: null },
  files: { front: null, back: null, certificate: null, signature: null },
  submission: null,
  requestNumber: null,
  retryRequestIdentifier: null,
  cancellationRetryInFlight: false,
};

function isCompanyService() {
  return String(state.serviceType || '').toLowerCase() === 'company';
}

function getActiveIdentityType() {
  return isCompanyService() ? state.companyDirectorIdentityType : state.identityType;
}

function isPassportIdentityType(identityType = getActiveIdentityType()) {
  return String(identityType || '').trim().toUpperCase() === 'PASSPORT';
}

function requiresBackDocument(identityType = getActiveIdentityType()) {
  return !isPassportIdentityType(identityType);
}

function getFrontDocumentLabel(en = state.en) {
  return isPassportIdentityType() ? (en ? 'Passport Page' : 'Muka Surat Pasport') : (en ? 'IC Front' : 'IC Depan');
}

function getBackDocumentLabel(en = state.en) {
  return en ? 'IC Back' : 'IC Belakang';
}

function getSignatureInstructionLabel(en = state.en) {
  return en ? 'Tap to sign / upload' : 'Ketik untuk tandatangan / muat naik';
}

function getCertificateDocumentLabel(en = state.en) {
  return en ? 'SSM / PPK Certificate' : 'Sijil SSM / PPK';
}

function syncUploadPanelBranch() {
  const passport = isPassportIdentityType();
  const backSlot = document.getElementById('slot-back');
  const backInput = document.getElementById('file-back');
  const backThumb = document.getElementById('thumb-back');
  const backSub = document.getElementById('sub-back');
  const sigSlot = document.getElementById('slot-sig');
  const certSlot = document.getElementById('slot-certificate');
  const certInput = document.getElementById('file-certificate');
  const certThumb = document.getElementById('thumb-certificate');
  const certSub = document.getElementById('sub-certificate');

  if (backSlot) {
    backSlot.style.display = passport ? 'none' : 'flex';
  }

  if (sigSlot) {
    sigSlot.style.display = isCompanyService() ? 'none' : 'flex';
  }

  if (certSlot) {
    certSlot.style.display = isCompanyService() ? 'flex' : 'none';
  }

  if (passport) {
    state.uploads.back = null;
    state.files.back = null;
    if (backInput) backInput.value = '';
    if (backThumb) backThumb.removeAttribute('src');
    if (backSub) backSub.textContent = state.en ? 'Not required for passport' : 'Tidak diperlukan untuk pasport';
  } else if (backSub) {
    backSub.textContent = state.en ? 'Tap to upload' : 'Ketik untuk muat naik';
  }

  if (isCompanyService()) {
    state.uploads.signature = null;
    state.files.signature = null;
    state.sigDataUrl = null;
    if (sigSlot) sigSlot.classList.remove('has-file');
    if (document.getElementById('thumb-sig')) {
      document.getElementById('thumb-sig').removeAttribute('src');
    }
    if (document.getElementById('sub-sig')) {
      document.getElementById('sub-sig').textContent = state.en ? 'Not required for company' : 'Tidak diperlukan untuk syarikat';
    }
    if (certSub && !state.uploads.certificate) {
      certSub.textContent = state.en ? 'Tap to upload' : 'Ketik untuk muat naik';
    }
    if (certInput && state.uploads.certificate === null) {
      certInput.value = '';
    }
    if (certThumb && state.uploads.certificate === null) {
      certThumb.removeAttribute('src');
    }
  } else {
    state.uploads.certificate = null;
    state.files.certificate = null;
    if (certInput) certInput.value = '';
    if (certThumb) certThumb.removeAttribute('src');
    if (certSub) certSub.textContent = state.en ? 'Tap to upload' : 'Ketik untuk muat naik';
  }
}

let sigCtx = null;
let sigDrawing = false;
let sigHasStrokes = false;
let sigCanvasReady = false;
let submissionPollSequence = 0;
let pendingQuickReplyValue = null;
let pendingQuickReplyDisplay = null;

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

function traceRpaFlow(event, details = {}) {
  if (!DEBUG_RPA_FLOW || typeof console === 'undefined' || typeof console.debug !== 'function') {
    return;
  }

  console.debug('[RPA FLOW]', event, {
    clientTimestamp: new Date().toISOString(),
    ...details,
  });
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

function resolveSubmissionIdentifier(requestNumber, submission) {
  const requestNo = firstNonEmpty(requestNumber, extractRequestNumber(submission));
  return requestNo;
}

function stripWrappingQuotes(text) {
  let value = String(text || '').trim();
  while (value.length >= 2) {
    const first = value[0];
    const last = value[value.length - 1];
    if ((first === '"' && last === '"') || (first === '\'' && last === '\'') || (first === '“' && last === '”')) {
      value = value.slice(1, -1).trim();
      continue;
    }
    break;
  }
  return value;
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

function sanitizeBotHtml(html) {
  const allowedTags = new Set(['A', 'B', 'BR', 'EM', 'LI', 'OL', 'P', 'STRONG', 'UL']);
  const allowedProtocols = new Set(['http:', 'https:']);
  const template = document.createElement('template');
  template.innerHTML = String(html || '');

  const sanitizeNode = (node) => {
    if (node.nodeType === Node.TEXT_NODE) {
      const text = String(node.nodeValue || '');
      if (text.trim() === '') {
        return '';
      }
      return escapeHtml(text).replace(/\r?\n/g, '<br>');
    }

    if (node.nodeType !== Node.ELEMENT_NODE) {
      return '';
    }

    const tagName = node.tagName.toUpperCase();
    const children = Array.from(node.childNodes).map(sanitizeNode).join('');

    if (!allowedTags.has(tagName)) {
      return children;
    }

    if (tagName === 'BR') {
      return '<br>';
    }

    if (tagName === 'A') {
      const href = String(node.getAttribute('href') || '').trim();
      if (!href) {
        return children;
      }

      try {
        const parsed = new URL(href, window.location.href);
        if (!allowedProtocols.has(parsed.protocol)) {
          return children;
        }

        const safeHref = escapeHtml(parsed.toString());
        const safeChildren = children || safeHref;
        return `<a href="${safeHref}" target="_blank" rel="noreferrer noopener">${safeChildren}</a>`;
      } catch (error) {
        return children;
      }
    }

    return `<${tagName.toLowerCase()}>${children}</${tagName.toLowerCase()}>`;
  };

  return Array.from(template.content.childNodes).map(sanitizeNode).join('');
}

function renderBotRichMessage(message) {
  return sanitizeBotHtml(String(message || ''));
}

function startWaitMessageSequence(en) {
  const messages = en ? WAIT_MESSAGES.en : WAIT_MESSAGES.ms;
  const bubble = document.createElement('div');
  bubble.className = 'msg bot wait-message';
  let index = 0;
  let stopped = false;

  const renderCurrent = () => {
    bubble.innerHTML = renderBackendMessage(messages[index]);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  };

  renderCurrent();
  messagesEl.appendChild(bubble);
  messagesEl.scrollTop = messagesEl.scrollHeight;

  const timer = setInterval(() => {
    if (stopped) return;
    index = (index + 1) % messages.length;
    renderCurrent();
  }, WAIT_MESSAGE_INTERVAL_MS);

  return {
    stop() {
      if (stopped) {
        return;
      }
      stopped = true;
      clearInterval(timer);
      bubble.remove();
    },
  };
}

function startDisplayMessageWaitSequence(en) {
  const messages = en ? DISPLAY_WAIT_MESSAGES.en : DISPLAY_WAIT_MESSAGES.ms;
  const bubble = document.createElement('div');
  bubble.className = 'msg bot wait-message';
  let index = 0;
  let stopped = false;

  const renderCurrent = () => {
    bubble.innerHTML = renderBackendMessage(messages[index]);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  };

  renderCurrent();
  messagesEl.appendChild(bubble);
  messagesEl.scrollTop = messagesEl.scrollHeight;

  const timer = setInterval(() => {
    if (stopped) return;
    index = (index + 1) % messages.length;
    renderCurrent();
  }, WAIT_MESSAGE_INTERVAL_MS);

  return {
    stop() {
      if (stopped) {
        return;
      }
      stopped = true;
      clearInterval(timer);
      bubble.remove();
    },
  };
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
    if (isPlainObject(option)) {
      button.textContent = option.label ?? option.text ?? option.value ?? '';
      button.dataset.value = String(option.value ?? option.label ?? '');
    } else {
      button.textContent = option;
      button.dataset.value = String(option);
    }
    button.onclick = () => {
      quickRepliesEl.innerHTML = '';
      pendingQuickReplyValue = button.dataset.value || null;
      pendingQuickReplyDisplay = button.textContent || '';
      inputEl.value = pendingQuickReplyDisplay;
      sendMessage();
    };
    quickRepliesEl.appendChild(button);
  });
}

function setQuickReplyAction(label, handler) {
  quickRepliesEl.innerHTML = '';
  const button = document.createElement('button');
  button.className = 'qr-btn';
  button.textContent = label;
  button.onclick = async () => {
    if (button.disabled) {
      return;
    }

    button.disabled = true;
    await handler(button);
  };
  quickRepliesEl.appendChild(button);
  return button;
}

function persistSubmissionContext(identifier) {
  if (!identifier) {
    return;
  }

  try {
    localStorage.setItem(SUBMISSION_CONTEXT_STORAGE_KEY, JSON.stringify({
      requestNumber: identifier,
      sessionId: state.sessionId || null,
    }));
  } catch (error) {
    traceRpaFlow('frontend persistSubmissionContext failed', {
      identifier,
      message: error?.message || 'Unknown error',
    });
  }
}

function loadSubmissionContext() {
  try {
    const raw = localStorage.getItem(SUBMISSION_CONTEXT_STORAGE_KEY);
    if (!raw) {
      return null;
    }

    const parsed = JSON.parse(raw);
    if (!isPlainObject(parsed)) {
      return null;
    }

    const requestNumber = firstNonEmpty(parsed.requestNumber, parsed.request_number);
    return requestNumber ? { requestNumber } : null;
  } catch (error) {
    return null;
  }
}

function clearSubmissionContext() {
  state.retryRequestIdentifier = null;
  state.cancellationRetryInFlight = false;

  try {
    localStorage.removeItem(SUBMISSION_CONTEXT_STORAGE_KEY);
  } catch (error) {
    traceRpaFlow('frontend clearSubmissionContext failed', {
      message: error?.message || 'Unknown error',
    });
  }
}

function setUploadLabels(en) {
  const passport = isPassportIdentityType();
  uploadTitle.textContent = isCompanyService()
    ? (en ? 'Upload company documents:' : 'Muat naik dokumen syarikat:')
    : passport
      ? (en ? 'Upload your documents (passport branch required):' : 'Muat naik dokumen anda (cawangan pasport diperlukan):')
      : (en ? 'Upload your documents (all required):' : 'Muat naik dokumen anda (semua diperlukan):');
  document.getElementById('lbl-front').innerHTML = getFrontDocumentLabel(en);
  document.getElementById('lbl-back').innerHTML = getBackDocumentLabel(en);
  document.getElementById('lbl-sig').innerHTML = en ? 'Signature' : 'Tandatangan';
  document.getElementById('lbl-certificate').innerHTML = getCertificateDocumentLabel(en);
  document.getElementById('sub-front').textContent = en ? 'Tap to upload' : 'Ketik untuk muat naik';
  document.getElementById('sub-back').textContent = en ? 'Tap to upload' : 'Ketik untuk muat naik';
  document.getElementById('sub-sig').textContent = getSignatureInstructionLabel(en);
  document.getElementById('sub-certificate').textContent = en ? 'Tap to upload' : 'Ketik untuk muat naik';
  if (sigUploadBtn) {
    sigUploadBtn.textContent = en
      ? 'Upload signature image instead (JPG / PNG)'
      : 'Muat naik imej tandatangan (JPG / PNG)';
  }
  document.getElementById('uploadBtn').textContent = en ? 'Submit' : 'Hantar';
  syncUploadPanelBranch();
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
  if (isCompanyService()) {
    return Boolean(
      state.uploads.front
        && (!requiresBackDocument(state.companyDirectorIdentityType) || state.uploads.back)
        && state.uploads.certificate
        && state.companyPpkNumber
        && state.companyName
        && state.companyEmail
        && state.companyCategory
        && state.stateName
        && state.companyDirectorName
        && state.companyDirectorIdentityNumber
        && state.companyReason
    );
  }

  return Boolean(
    state.uploads.front
      && state.uploads.signature
      && (!requiresBackDocument() || state.uploads.back)
      && state.mobile
      && state.email
  );
}

function checkAllFilled() {
  uploadBtn.disabled = !allUploadsComplete();
}

function validateLocalFile(file, slotId = 'front') {
  if (!file) return { ok: false, message: state.en ? 'Please choose a file.' : 'Sila pilih fail.' };
  const name = String(file.name || '');
  const lowerName = name.toLowerCase();
  const ext = lowerName.includes('.') ? lowerName.split('.').pop() : '';
  const allowedExtensions = slotId === 'certificate'
    ? [...FILE_POLICY.allowedExtensions, 'pdf']
    : FILE_POLICY.allowedExtensions;
  const allowedMimeTypes = slotId === 'certificate'
    ? [...FILE_POLICY.allowedMimeTypes, 'application/pdf']
    : FILE_POLICY.allowedMimeTypes;
  if (!name.trim()) return { ok: false, message: state.en ? 'File name is missing.' : 'Nama fail tiada.' };
  if (/[\\/\x00]/.test(name)) return { ok: false, message: state.en ? 'File name contains invalid characters.' : 'Nama fail mengandungi aksara tidak sah.' };
  if (!allowedExtensions.includes(ext)) return { ok: false, message: state.en ? 'Unsupported file extension.' : 'Sambungan fail tidak disokong.' };
  if (!allowedMimeTypes.includes(file.type)) return { ok: false, message: state.en ? 'Unsupported file type.' : 'Jenis fail tidak disokong.' };
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
  const fetchOptions = { method, headers, credentials: 'omit', cache: options.cache || 'default' };

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
    const response = await apiRequest(`/session/${encodeURIComponent(state.sessionId)}`, { cache: 'no-store' });
    return extractData(response);
  } catch (error) {
    return null;
  }
}

async function refreshSubmission(identifier) {
  if (!identifier) return null;
  try {
    const pollId = ++submissionPollSequence;
    const requestStartedAt = new Date().toISOString();
    const requestStartedMs = Date.now();
    traceRpaFlow('frontend refreshSubmission request start', {
      pollId,
      identifier,
      requestStartedAt,
    });

    const response = await apiRequest(`/submission/${encodeURIComponent(identifier)}`, { cache: 'no-store' });
    const requestFinishedAt = new Date().toISOString();
    const requestFinishedMs = Date.now();
    const data = extractData(response);
    const verification = isPlainObject(data.verification) ? data.verification : null;
    traceRpaFlow('frontend refreshSubmission request complete', {
      pollId,
      identifier,
      requestStartedAt,
      requestFinishedAt,
      durationMs: requestFinishedMs - requestStartedMs,
      verificationId: verification?.id ?? null,
      displayMessageLength: String(verification?.display_message ?? '').length,
      displayMessageIsEmpty: !String(verification?.display_message ?? '').trim(),
      resultStatus: verification?.result_status ?? null,
    });
    return data;
  } catch (error) {
    traceRpaFlow('frontend refreshSubmission request failed', {
      identifier,
      message: error?.message || 'Unknown error',
      status: error?.status ?? null,
    });
    return null;
  }
}

async function renderCancellationSubmissionState(data, { fromRetry = false, allowTerminal = false } = {}) {
  const en = state.en;
  const request = isPlainObject(data?.request) ? data.request : null;
  const verification = isPlainObject(data?.verification) ? data.verification : null;
  const session = isPlainObject(data?.session) ? data.session : null;
  const requestNumber = resolveSubmissionIdentifier(
    firstNonEmpty(data?.request_number, data?.requestNumber, extractRequestNumber(request), state.requestNumber),
    request
  );
  const nextAction = firstNonEmpty(data?.next_action, data?.nextAction, 'done').toLowerCase();
  const finalFailureType = firstNonEmpty(data?.final_failure_type, data?.finalFailureType, null);
  const message = firstNonEmpty(
    data?.message,
    verification?.display_message,
    verification?.response_message,
    ''
  );

  if (requestNumber) {
    state.requestNumber = requestNumber;
  }

  if (session) {
    state.sessionId = firstNonEmpty(session.id, state.sessionId);
    state.en = String(session.language_code || '').toLowerCase() !== 'ms';
    updateSessionStateFromSession(session);
  }

  if (isPlainObject(data?.submission)) {
    state.submission = data.submission;
  } else if (request) {
    state.submission = request;
  }

  if (data?.retry_available === true || nextAction === 'retry_available') {
    state.retryRequestIdentifier = requestNumber || state.requestNumber;
    state.step = 'awaiting_retry';
    uploadArea.style.display = 'none';
    setInput(false);
    if (requestNumber) {
      persistSubmissionContext(requestNumber);
    }

    await addMsg(renderBotRichMessage(message || (en
      ? 'Cancellation attempt 1 was unsuccessful. Click Retry to try again.'
      : 'Percubaan pertama pembatalan tidak berjaya. Klik Retry untuk mencuba semula.')), 'error');
    setQuickReplyAction(en ? 'Retry' : 'Retry', async () => {
      await handleCancellationRetry();
    });
    return { handled: true, retry_available: true };
  }

  if (data?.retry_in_progress === true) {
    state.step = 'done';
    uploadArea.style.display = 'none';
    setInput(false);
    quickRepliesEl.innerHTML = '';
    await addMsg(renderBotRichMessage(message || (en
      ? 'Cancellation retry is already running. Please wait.'
      : 'Percubaan semula pembatalan sedang berjalan. Sila tunggu.')), 'bot');
    return { handled: true, retry_in_progress: true };
  }

  if (nextAction === 'poll') {
    state.step = 'done';
    uploadArea.style.display = 'none';
    quickRepliesEl.innerHTML = '';
    setInput(false);
    if (requestNumber) {
      persistSubmissionContext(requestNumber);
    }

    const identifier = requestNumber || state.requestNumber;
    if (!identifier) {
      return { handled: false, polling: true };
    }

    const displayWaitSequence = startDisplayMessageWaitSequence(en);
    try {
      const resolved = await waitForFinalVerificationMessage(identifier, verification);
      const resolvedMessage = firstNonEmpty(resolved.message, message, '');
      if (resolvedMessage) {
        await addMsg(renderBotRichMessage(resolvedMessage), 'bot');
      } else {
        await addMsg(
          en
            ? 'Thank you for waiting. We are still processing your request. Please check back shortly.'
            : 'Terima kasih kerana menunggu. Permintaan anda masih diproses. Sila semak semula sebentar lagi.'
        );
      }
      return { handled: true, polling: true };
    } finally {
      displayWaitSequence.stop();
    }
  }

  if (allowTerminal && (finalFailureType === 'cancellation' || finalFailureType === 'company_rejected')) {
    state.step = 'done';
    uploadArea.style.display = 'none';
    quickRepliesEl.innerHTML = '';
    setInput(false);
    clearSubmissionContext();
    await addMsg(renderBotRichMessage(message || (en
      ? 'We are unable to complete the cancellation at this time.'
      : 'Kami tidak dapat melengkapkan pembatalan pada masa ini.')), 'bot');
    return { handled: true, terminal: true };
  }

  if (allowTerminal && nextAction === 'done') {
    state.step = 'done';
    uploadArea.style.display = 'none';
    quickRepliesEl.innerHTML = '';
    setInput(false);
    clearSubmissionContext();
    if (message) {
      await addMsg(renderBotRichMessage(message), 'bot');
    }
    return { handled: true, terminal: true };
  }

  if (fromRetry && allowTerminal && message) {
    state.step = 'done';
    uploadArea.style.display = 'none';
    quickRepliesEl.innerHTML = '';
    setInput(false);
    clearSubmissionContext();
    await addMsg(renderBotRichMessage(message), 'bot');
    return { handled: true, terminal: true };
  }

  return { handled: false };
}

async function handleCancellationRetry() {
  if (!state.retryRequestIdentifier || state.cancellationRetryInFlight) {
    return;
  }

  state.cancellationRetryInFlight = true;
  state.step = 'done';
  setInput(false);
  quickRepliesEl.innerHTML = '';
  await addMsg(state.en
    ? 'Retrying cancellation now...'
    : 'Sedang cuba semula pembatalan...', 'bot');

  try {
    const response = await apiRequest(`/submission/${encodeURIComponent(state.retryRequestIdentifier)}/retry`, {
      method: 'POST',
      body: { session_id: state.sessionId },
    });
    const data = extractData(response);
    if (await renderCancellationSubmissionState(data, { fromRetry: true, allowTerminal: true })) {
      return;
    }

    await addMsg(renderBotRichMessage(firstNonEmpty(data?.message, state.en ? 'Cancellation retry completed.' : 'Percubaan semula pembatalan selesai.')), 'bot');
  } catch (error) {
    await showApiError(error, state.en ? 'Cancellation retry failed.' : 'Percubaan semula pembatalan gagal.');
    state.step = 'awaiting_retry';
    if (state.retryRequestIdentifier) {
      setQuickReplyAction(state.en ? 'Retry' : 'Retry', async () => {
        await handleCancellationRetry();
      });
    }
    return;
  } finally {
    state.cancellationRetryInFlight = false;
  }

  state.step = 'done';
  setInput(false);
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

function buildServicePayload(text) {
  const value = String(text || '').trim().toLowerCase();
  if (value === '1' || value === 'individual' || value.includes('individual') || value.includes('individu')) {
    return { service_type: 'individual' };
  }
  if (value === '2' || value === 'company' || value.includes('company') || value.includes('syarikat')) {
    return { service_type: 'company' };
  }
  if (value === '3' || value.includes('faq') || value.includes('soalan lazim')) {
    return { service_type: 'faq' };
  }
  return null;
}

function getServiceQuickReplies() {
  return state.en ? SERVICE_OPTIONS.en : SERVICE_OPTIONS.ms;
}

function faqTopicLabel(topic) {
  return state.en ? (topic.label_en || topic.topic_code) : (topic.label_ms || topic.topic_code);
}

function faqTopicOptions() {
  return (state.faqTopics || []).map(faqTopicLabel);
}

function buildFaqTopicPayload(text) {
  const value = String(text || '').trim().toLowerCase();
  const match = (state.faqTopics || []).find(topic =>
    String(topic.label_en || '').toLowerCase() === value
    || String(topic.label_ms || '').toLowerCase() === value
    || String(topic.topic_code || '').toLowerCase() === value);
  return match ? { topic_code: match.topic_code, topic: match } : null;
}

function faqSubtopicLabel(subtopic) {
  return state.en ? (subtopic.label_en || subtopic.subtopic_code) : (subtopic.label_ms || subtopic.subtopic_code);
}

function faqSubtopicOptions() {
  return (state.faqSubtopics || []).map(faqSubtopicLabel);
}

function buildFaqSubtopicPayload(text) {
  const value = String(text || '').trim().toLowerCase();
  const match = (state.faqSubtopics || []).find(subtopic =>
    String(subtopic.label_en || '').toLowerCase() === value
    || String(subtopic.label_ms || '').toLowerCase() === value
    || String(subtopic.subtopic_code || '').toLowerCase() === value);
  return match ? { subtopic_code: match.subtopic_code, subtopic: match } : null;
}

let faqAnswerIdCounter = 0;

function renderFaqQuestionList(questions) {
  const itemsHtml = questions.map(question => {
    faqAnswerIdCounter += 1;
    const answerId = `faq-answer-${faqAnswerIdCounter}`;
    const questionText = state.en ? (question.question_en || '') : (question.question_ms || '');
    const answerText = state.en ? (question.answer_en || '') : (question.answer_ms || '');
    return `<div class="faq-item">
      <button type="button" class="faq-question-btn" onclick="toggleFaqAnswer('${answerId}')">${escapeHtml(questionText)}</button>
      <div class="faq-answer" id="${answerId}">${escapeHtml(answerText)}</div>
    </div>`;
  }).join('');
  return `<div class="faq-list">${itemsHtml}</div>`;
}

function toggleFaqAnswer(answerId) {
  const el = document.getElementById(answerId);
  if (!el) return;
  el.classList.toggle('open');
}

function buildStatePayload(text) {
  const value = String(text || '').trim().toLowerCase();
  const match = MY_STATES.find(item => item.toLowerCase() === value);
  return match ? { state: match } : null;
}

function buildIdentityPayload(text) {
  const clean = String(text || '').replace(/[\s-]/g, '');
  const myKadMatch = /^\d{12}$/.test(clean);
  const passportMatch = /^[A-Za-z0-9]{6,20}$/.test(clean) && /\d/.test(clean);
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

function buildMobilePayload(text) {
  const normalized = String(text || '').trim().replace(/[\s()-]/g, '');
  if (!/^\+?\d{8,15}$/.test(normalized)) {
    return null;
  }
  return { mobile: normalized };
}

function buildEmailPayload(text) {
  const normalized = String(text || '').trim().toLowerCase();
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(normalized)) {
    return null;
  }
  return { email: normalized };
}

function buildCompanyTextPayload(text) {
  const normalized = String(text || '').trim();
  return normalized ? { value: normalized } : null;
}

function buildCompanyPpkPayload(text) {
  const value = String(text || '').trim().toUpperCase();
  if (!value) return null;

  const modernMatch = /^(\d{4})(0[1-6])(\d{6})$/.exec(value);
  if (modernMatch) {
    return { value };
  }

  if (/^(?:[A-Z]{0,3})\d{7}-[A-Z]$/.test(value)) {
    return { value };
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

function validateSignatureFile(file) {
  if (!file) {
    return { ok: false, message: state.en ? 'Please choose a signature image.' : 'Sila pilih imej tandatangan.' };
  }
  const allowedMimeTypes = new Set(['image/jpeg', 'image/png', 'image/jpg', 'image/webp']);
  if (!allowedMimeTypes.has(file.type)) {
    return { ok: false, message: state.en ? 'Unsupported signature image format.' : 'Format imej tandatangan tidak disokong.' };
  }
  if (file.size <= 0) {
    return { ok: false, message: state.en ? 'Uploaded file is empty.' : 'Fail yang dimuat naik kosong.' };
  }
  if (file.size > MAX_UPLOAD_BYTES) {
    return { ok: false, message: state.en ? 'Uploaded file exceeds the size limit.' : 'Fail melebihi had saiz.' };
  }
  return { ok: true };
}

function readFileAsDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result || ''));
    reader.onerror = () => reject(new Error('Unable to read file.'));
    reader.readAsDataURL(file);
  });
}

function renderSummaryBox() {
  if (isCompanyService()) {
    const front = state.uploads.front?.document || state.uploads.front;
    const back = state.uploads.back?.document || state.uploads.back;
    const cert = state.uploads.certificate?.document || state.uploads.certificate;
    const showBackDocument = requiresBackDocument(state.companyDirectorIdentityType);
    return `<strong>${state.en ? 'Summary of your company submission:' : 'Ringkasan penghantaran syarikat anda:'}</strong>`
      + '<div class="info-box">'
      + `<strong>${state.en ? 'PPK / SSM Number' : 'No. PPK / SSM'}:</strong> ${escapeHtml(state.companyPpkNumber)}<br>`
      + `<strong>${state.en ? 'Company Name' : 'Nama Syarikat'}:</strong> ${escapeHtml(state.companyName)}<br>`
      + `<strong>${state.en ? 'Company Email' : 'Emel Syarikat'}:</strong> ${escapeHtml(state.companyEmail)}<br>`
      + `<strong>${state.en ? 'Company Contact Number' : 'Nombor Telefon Syarikat'}:</strong> ${escapeHtml(state.companyContactNumber)}<br>`
      + `<strong>${state.en ? 'Company State' : 'Negeri Syarikat'}:</strong> ${escapeHtml(state.stateName)}<br>`
      + `<strong>${state.en ? 'Category' : 'Kategori'}:</strong> ${escapeHtml(state.companyCategory)}<br>`
      + `<strong>${state.en ? 'Director Name' : 'Nama Pengarah'}:</strong> ${escapeHtml(state.companyDirectorName)}<br>`
      + `<strong>${state.en ? 'Director IC' : 'IC Pengarah'}:</strong> ${escapeHtml(state.companyDirectorIdentityNumber)}<br>`
      + `<strong>${state.en ? 'Reason' : 'Sebab'}:</strong> ${escapeHtml(state.companyReason)}<br>`
      + `<strong>${escapeHtml(getFrontDocumentLabel(state.en))}:</strong> ${escapeHtml(front?.original_filename || front?.file_name || front?.stored_filename || 'Uploaded')}<br>`
      + (showBackDocument ? `<strong>${escapeHtml(getBackDocumentLabel(state.en))}:</strong> ${escapeHtml(back?.original_filename || back?.file_name || back?.stored_filename || 'Uploaded')}<br>` : '')
      + `<strong>${escapeHtml(getCertificateDocumentLabel(state.en))}:</strong> ${escapeHtml(cert?.original_filename || cert?.file_name || cert?.stored_filename || 'Uploaded')}`
      + '</div>';
  }

  const front = state.uploads.front?.document || state.uploads.front;
  const back = state.uploads.back?.document || state.uploads.back;
  const signaturePreview = state.sigDataUrl
    ? `<img src="${state.sigDataUrl}" style="max-height:36px;border-radius:4px;border:1px solid #b7d7d6;vertical-align:middle;margin-left:4px;">`
    : '';
  const showBackDocument = requiresBackDocument();

  return `<strong>${state.en ? 'Summary of your submission:' : 'Ringkasan penghantaran anda:'}</strong>`
    + '<div class="info-box">'
    + `<strong>${state.en ? 'Name' : 'Nama'}:</strong> ${escapeHtml(state.name)}<br>`
    + `<strong>${state.en ? 'IC / Passport No.' : 'No. IC / Pasport'}:</strong> ${escapeHtml(state.identityNumber)}<br>`
    + `<strong>${state.en ? 'Mobile' : 'Telefon'}:</strong> ${escapeHtml(state.mobile)}<br>`
    + `<strong>${state.en ? 'Email' : 'Emel'}:</strong> ${escapeHtml(state.email)}<br>`
    + `<strong>${state.en ? 'State' : 'Negeri'}:</strong> ${escapeHtml(state.stateName)}<br>`
    + `<strong>${escapeHtml(getFrontDocumentLabel(state.en))}:</strong> ${escapeHtml(front?.original_filename || front?.file_name || front?.stored_filename || 'Uploaded')}<br>`
    + (showBackDocument ? `<strong>${escapeHtml(getBackDocumentLabel(state.en))}:</strong> ${escapeHtml(back?.original_filename || back?.file_name || back?.stored_filename || 'Uploaded')}<br>` : '')
    + `<strong>${state.en ? 'Signature' : 'Tandatangan'}:</strong> ${signaturePreview}`
    + '</div>';
}

function renderOcrVerificationBubble(ocrVerification) {
  if (!isPlainObject(ocrVerification)) return '';

  const message = firstNonEmpty(ocrVerification.message, ocrVerification.status, '');
  if (!message) return '';

  const status = String(ocrVerification.status || '').trim().toUpperCase();
  const heading = status === 'VERIFIED'
    ? (state.en ? 'ID verification completed successfully.' : 'Pengesahan ID berjaya diselesaikan.')
    : (state.en ? 'ID verification requires attention.' : 'Pengesahan ID memerlukan perhatian.');

  return `<strong>${escapeHtml(heading)}</strong><br>${escapeHtml(message)}`;
}

function renderFinalOutcome(outcome) {
  if (isCompanyService()) {
    const en = state.en;
    if (outcome === 'approved' || outcome === 'deleted') {
      return en
        ? '<strong>Good news!</strong> Your company email ID cancellation request has been processed successfully.<br><br>'
          + 'Thank you for contacting <strong>CIDB Livechat</strong>.'
        : '<strong>Berita baik!</strong> Permohonan pembatalan Email ID syarikat anda telah berjaya diproses.<br><br>'
          + 'Terima kasih kerana menghubungi <strong>CIDB Livechat</strong>.';
    }
    if (outcome === 'pending' || outcome === 'under_review') {
      return en
        ? 'Your company cancellation request is still being processed. Please check back shortly.'
        : 'Permohonan pembatalan syarikat anda masih diproses. Sila semak semula sebentar lagi.';
    }
    if (outcome === 'manual_review') {
      return en
        ? 'Your company cancellation request requires manual review. Our team will follow up if further action is needed.'
        : 'Permohonan pembatalan syarikat anda memerlukan semakan manual. Pasukan kami akan menghubungi anda jika tindakan lanjut diperlukan.';
    }
    if (outcome === 'rejected' || outcome === 'norecord') {
      return en
        ? 'We are unable to complete your company cancellation request at this time. Please review the submitted details and try again.'
        : 'Kami tidak dapat melengkapkan permohonan pembatalan syarikat anda buat masa ini. Sila semak semula butiran yang dihantar dan cuba lagi.';
    }
    return en
      ? 'We are unable to complete the company cancellation at this time. Please try again later or contact our support team.'
      : 'Kami tidak dapat melengkapkan pembatalan syarikat buat masa ini. Sila cuba lagi kemudian atau hubungi pasukan sokongan kami.';
  }

  const ic = escapeHtml(state.identityNumber || '');
  const en = state.en;
  if (outcome === 'deleted') {
    return en
      ? '<strong>Good news!</strong> We have successfully verified your details and your CIMS Individual Email ID has been <strong>cancelled</strong>.<br><br>'
        + 'You may register a new Email ID at any time via <a href="https://cims.cidb.gov.my" target="_blank" rel="noreferrer">cims.cidb.gov.my</a>.<br><br>'
        + 'Thank you for contacting <strong>CIDB Livechat</strong>. Have a wonderful day!'
      : '<strong>Berita baik!</strong> Kami telah berjaya mengesahkan maklumat anda dan Email ID CIMS Individu anda telah berjaya <strong>dibatalkan</strong>.<br><br>'
        + 'Anda boleh mendaftar Email ID baharu pada bila-bila masa melalui <a href="https://cims.cidb.gov.my" target="_blank" rel="noreferrer">cims.cidb.gov.my</a>.<br><br>'
        + 'Terima kasih kerana menghubungi <strong>CIDB Livechat</strong>. Selamat sejahtera!';
  }
  if (outcome === 'linked') {
    return en
      ? 'We are unable to cancel your Email ID at this time as your <strong>User ID is currently linked to a CIMS module</strong>.<br><br>'
        + 'Please ensure all active module links are removed before requesting cancellation, or contact our support team for further assistance.<br><br>'
        + 'Thank you for your patience.'
      : 'Kami tidak dapat membatalkan Email ID anda buat masa ini kerana <strong>ID Pengguna anda masih dikaitkan dengan modul CIMS</strong>.<br><br>'
        + 'Sila pastikan semua pautan modul aktif telah dibuang sebelum membuat permintaan pembatalan, atau hubungi pasukan sokongan kami untuk bantuan lanjut.<br><br>'
        + 'Terima kasih atas kesabaran anda.';
  }
  if (outcome === 'norecord') {
    return en
      ? `We were unable to locate an Email ID record under the IC / Passport number provided (<strong>${ic}</strong>).<br><br>`
        + 'If you wish to create a new Email ID, please visit <a href="https://cims.cidb.gov.my" target="_blank" rel="noreferrer">cims.cidb.gov.my</a> and register directly.<br><br>'
        + 'Thank you for contacting <strong>CIDB Livechat</strong>. Have a wonderful day!'
      : `Kami tidak dapat menjumpai rekod Email ID di bawah nombor IC / Pasport yang diberikan (<strong>${ic}</strong>).<br><br>`
        + 'Sekiranya anda ingin mencipta Email ID baharu, sila layari <a href="https://cims.cidb.gov.my" target="_blank" rel="noreferrer">cims.cidb.gov.my</a> dan daftar secara terus.<br><br>'
        + 'Terima kasih kerana menghubungi <strong>CIDB Livechat</strong>. Selamat sejahtera!';
  }
  return en
    ? 'We are unable to complete the verification at this time. Please try again later or contact our support team.'
    : 'Kami tidak dapat melengkapkan pengesahan buat masa ini. Sila cuba lagi kemudian atau hubungi pasukan sokongan kami.';
}

function looksLikeBotAcknowledgementText(text) {
  const trimmed = String(text || '').trim();
  if (!trimmed.startsWith('{')) {
    return false;
  }

  try {
    const parsed = JSON.parse(trimmed);
    return isPlainObject(parsed)
      && String(parsed.status || '').toLowerCase() === 'inserted'
      && firstNonEmpty(parsed.schedule_id) !== '';
  } catch (error) {
    return false;
  }
}

function extractVerificationCustomerMessage(verification) {
  if (!isPlainObject(verification)) return '';
  const message = stripWrappingQuotes(verification.display_message);
  if (!message) {
    return '';
  }

  if (looksLikeBotAcknowledgementText(message)) {
    return '';
  }

  return message;
}

function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

async function waitForFinalVerificationMessage(identifier, initialVerification, timeoutMs = FINAL_VERIFICATION_TIMEOUT_MS, intervalMs = 1000) {
  if (!firstNonEmpty(identifier)) {
    return {
      verification: isPlainObject(initialVerification) ? initialVerification : null,
      message: extractVerificationCustomerMessage(initialVerification),
    };
  }

  let verification = isPlainObject(initialVerification) ? initialVerification : null;
  const deadline = Date.now() + timeoutMs;
  let pollCount = 0;

  traceRpaFlow('frontend wait loop start', {
    identifier,
    timeoutMs,
    intervalMs,
    initialVerificationId: verification?.id ?? null,
    initialDisplayMessageLength: String(verification?.display_message ?? '').length,
    initialDisplayMessageIsEmpty: !String(verification?.display_message ?? '').trim(),
  });

  while (Date.now() <= deadline) {
    pollCount++;
    const message = extractVerificationCustomerMessage(verification);
    if (message) {
      traceRpaFlow('frontend wait loop resolved before timeout', {
        identifier,
        pollCount,
        verificationId: verification?.id ?? null,
        messageLength: message.length,
      });
      return { verification, message };
    }

    traceRpaFlow('frontend wait loop polling', {
      identifier,
      pollCount,
      verificationId: verification?.id ?? null,
      displayMessageLength: String(verification?.display_message ?? '').length,
      displayMessageIsEmpty: !String(verification?.display_message ?? '').trim(),
    });

    await delay(intervalMs);
    const refreshed = await refreshSubmission(identifier);
    if (isPlainObject(refreshed?.verification)) {
      verification = refreshed.verification;
      traceRpaFlow('frontend wait loop refreshed verification', {
        identifier,
        pollCount,
        verificationId: verification?.id ?? null,
        displayMessageLength: String(verification?.display_message ?? '').length,
        displayMessageIsEmpty: !String(verification?.display_message ?? '').trim(),
      });
    }
  }

  traceRpaFlow('frontend wait loop timed out', {
    identifier,
    pollCount,
    verificationId: verification?.id ?? null,
    finalDisplayMessageLength: String(verification?.display_message ?? '').length,
    finalDisplayMessageIsEmpty: !String(verification?.display_message ?? '').trim(),
  });

  return {
    verification,
    message: extractVerificationCustomerMessage(verification),
  };
}

function updateSessionStateFromSession(session) {
  if (!isPlainObject(session)) return;
  const draft = isPlainObject(session.draft_payload)
    ? session.draft_payload
    : (() => {
        try {
          return JSON.parse(String(session.draft_payload || '{}'));
        } catch (error) {
          return {};
        }
      })();

  state.languageCode = firstNonEmpty(session.language_code, session.languageCode, state.languageCode);
  state.stateName = firstNonEmpty(session.state_name, draft.state_name, session.stateName, state.stateName);
  state.stateCode = firstNonEmpty(session.state_code, draft.state_code, state.stateCode);
  state.name = firstNonEmpty(session.full_name, session.name, state.name);
  state.identityNumber = firstNonEmpty(session.identity_number, session.identityNumber, state.identityNumber);
  state.identityType = firstNonEmpty(session.identity_type, state.identityType);
  state.mobile = firstNonEmpty(session.mobile, session.mobileNumber, session.mobile_number, state.mobile);
  state.email = firstNonEmpty(session.email, session.email_address, session.emailAddress, state.email);
  state.serviceType = firstNonEmpty(draft.service_type, 'individual');
  state.companyPpkNumber = firstNonEmpty(draft.company_ppk_number, state.companyPpkNumber);
  state.companyName = firstNonEmpty(draft.company_name, state.companyName);
  state.companyEmail = firstNonEmpty(draft.company_email, state.companyEmail);
  state.companyContactNumber = firstNonEmpty(draft.company_contact_number, state.companyContactNumber);
  state.companyCategory = firstNonEmpty(draft.category, draft.company_category, state.companyCategory);
  state.companyDirectorName = firstNonEmpty(draft.director_full_name, state.companyDirectorName);
  state.companyDirectorIdentityType = firstNonEmpty(draft.director_identity_type, state.companyDirectorIdentityType);
  state.companyDirectorIdentityNumber = firstNonEmpty(draft.director_identity_number, state.companyDirectorIdentityNumber);
  state.companyReason = firstNonEmpty(draft.company_cancellation_reason, state.companyReason);

  if (!isCompanyService()) {
    state.companyPpkNumber = '';
    state.companyName = '';
    state.companyEmail = '';
    state.companyContactNumber = '';
    state.companyCategory = '';
    state.companyDirectorName = '';
    state.companyDirectorIdentityType = '';
    state.companyDirectorIdentityNumber = '';
    state.companyReason = '';
  }

  syncUploadPanelBranch();
}

async function bootstrapConversation() {
  state.serviceType = 'individual';
  state.stateName = '';
  state.stateCode = '';
  state.name = '';
  state.identityType = '';
  state.identityNumber = '';
  state.companyPpkNumber = '';
  state.companyName = '';
  state.companyEmail = '';
  state.companyContactNumber = '';
  state.companyCategory = '';
  state.companyDirectorName = '';
  state.companyDirectorIdentityType = '';
  state.companyDirectorIdentityNumber = '';
  state.companyReason = '';
  state.faqTopics = [];
  state.faqTopicCode = '';
  state.faqSubtopics = [];
  state.faqSubtopicCode = '';
  state.faqQuestionOffset = 0;
  state.sigDataUrl = null;
  state.uploads = { front: null, back: null, certificate: null, signature: null };
  state.files = { front: null, back: null, certificate: null };
  state.submission = null;
  state.requestNumber = null;
  state.retryRequestIdentifier = null;
  state.cancellationRetryInFlight = false;
  setInput(false);
  quickRepliesEl.innerHTML = '';
  uploadArea.style.display = 'none';
  await showTyping(700);

  const savedContext = loadSubmissionContext();
  if (savedContext?.requestNumber) {
    const restored = await refreshSubmission(savedContext.requestNumber);
    if (restored) {
      const handled = await renderCancellationSubmissionState(restored, { fromRetry: false, allowTerminal: true });
      if (handled.handled) {
        return;
      }
    }

    clearSubmissionContext();
  }

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
  const displayText = inputEl.value.trim();
  if (!displayText) return;
  const text = pendingQuickReplyValue || displayText;
  pendingQuickReplyValue = null;
  pendingQuickReplyDisplay = null;
  inputEl.value = '';
  quickRepliesEl.innerHTML = '';
  setInput(false);
  await addMsg(escapeHtml(displayText), 'user');
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
      state.step = 'ask_service';
      await showTyping(450);
      await addMsg(state.en ? 'Thank you! My name is <strong>Bena</strong> and I am here to help you today.' : 'Terima kasih! Nama saya <strong>Bena</strong> dan saya di sini untuk membantu anda hari ini.');
      await showTyping(450);
      await addMsg(state.en ? 'What do you need help with today?' : 'Apakah bantuan yang anda perlukan hari ini?');
      setQR(getServiceQuickReplies());
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

  if (state.step === 'ask_service') {
    const payload = buildServicePayload(text);
    if (!payload) {
      await showApiError({ message: 'Invalid service selected.', errors: { service: 'Please choose a supported service.' } }, 'Invalid service selected.');
      await addMsg(state.en ? 'What do you need help with today?' : 'Apakah bantuan yang anda perlukan hari ini?');
      setQR(getServiceQuickReplies());
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/service', { method: 'POST', body: { session_id: state.sessionId, service_type: payload.service_type } });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.serviceType = payload.service_type;
      if (payload.service_type === 'faq') {
        const topicsResponse = await apiRequest('/faq/topics', { method: 'GET' });
        const topicsData = extractData(topicsResponse);
        state.faqTopics = Array.isArray(topicsData.topics) ? topicsData.topics : [];
        state.step = 'ask_faq_topic';
        await showTyping(450);
        await addMsg(state.en ? 'Please choose an FAQ topic:' : 'Sila pilih topik Soalan Lazim:');
        setQR(faqTopicOptions());
        setInput(true);
        await refreshSession();
        return;
      }

      if (payload.service_type === 'company') {
        state.step = 'ask_company_ppk';
        await showTyping(450);
        await addMsg(state.en
          ? 'Please provide your <strong>PPK / SSM number</strong> to begin the company cancellation request.'
          : 'Sila berikan <strong>nombor PPK / SSM</strong> anda untuk memulakan permohonan pembatalan syarikat.');
        setInput(true);
        await refreshSession();
        return;
      }

      state.step = 'ask_state';
      await showTyping(450);
      await addMsg(state.en
        ? 'Before we begin, may I know which <strong>state</strong> you are contacting us from?'
        : 'Sebelum kita mulakan, bolehkah saya tahu dari <strong>negeri</strong> mana anda menghubungi kami?');
      setQR(MY_STATES);
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save service selection.');
      await addMsg(state.en ? 'What do you need help with today?' : 'Apakah bantuan yang anda perlukan hari ini?');
      setQR(getServiceQuickReplies());
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_faq_topic') {
    const backMenuLabel = state.en ? 'Back to menu' : 'Kembali ke menu';
    if (text.trim() === backMenuLabel) {
      state.step = 'ask_service';
      await showTyping(350);
      await addMsg(state.en ? 'What do you need help with today?' : 'Apakah bantuan yang anda perlukan hari ini?');
      setQR([
        state.en ? '1. Individual Email ID Cancellation' : '1. Pembatalan Email ID Individu',
        state.en ? '2. Company Email ID Cancellation' : '2. Pembatalan Email ID Syarikat',
        state.en ? '3. FAQ' : '3. Soalan Lazim',
      ]);
      setInput(true);
      return;
    }

    const payload = buildFaqTopicPayload(text);
    if (!payload) {
      await showApiError({ message: 'Invalid FAQ topic selected.', errors: { topic: 'Please choose a listed FAQ topic.' } }, 'Invalid FAQ topic selected.');
      await addMsg(state.en ? 'Please choose an FAQ topic:' : 'Sila pilih topik Soalan Lazim:');
      setQR(faqTopicOptions());
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/faq-topic', { method: 'POST', body: { session_id: state.sessionId, topic_code: payload.topic_code } });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.faqTopicCode = payload.topic_code;
      state.faqSubtopics = Array.isArray(data.subtopics) ? data.subtopics : [];
      state.step = 'ask_faq_subtopic';
      await showTyping(400);
      await addMsg(state.en
        ? `Here are the subtopics under <strong>${escapeHtml(faqTopicLabel(payload.topic))}</strong>:`
        : `Berikut ialah subtopik di bawah <strong>${escapeHtml(faqTopicLabel(payload.topic))}</strong>:`);
      setQR(faqSubtopicOptions());
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save FAQ topic selection.');
      await addMsg(state.en ? 'Please choose an FAQ topic:' : 'Sila pilih topik Soalan Lazim:');
      setQR(faqTopicOptions());
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_faq_subtopic') {
    const backTopicsLabel = state.en ? 'Back to topics' : 'Kembali ke topik';
    const backMenuLabel = state.en ? 'Back to menu' : 'Kembali ke menu';
    const nextLabel = state.en ? 'Next' : 'Seterusnya';
    const trimmed = text.trim();

    if (trimmed === backMenuLabel) {
      state.step = 'ask_service';
      await showTyping(350);
      await addMsg(state.en ? 'What do you need help with today?' : 'Apakah bantuan yang anda perlukan hari ini?');
      setQR([
        state.en ? '1. Individual Email ID Cancellation' : '1. Pembatalan Email ID Individu',
        state.en ? '2. Company Email ID Cancellation' : '2. Pembatalan Email ID Syarikat',
        state.en ? '3. FAQ' : '3. Soalan Lazim',
      ]);
      setInput(true);
      return;
    }

    if (trimmed === backTopicsLabel) {
      state.step = 'ask_faq_topic';
      await showTyping(350);
      await addMsg(state.en ? 'Please choose an FAQ topic:' : 'Sila pilih topik Soalan Lazim:');
      setQR(faqTopicOptions());
      setInput(true);
      return;
    }

    if (trimmed === nextLabel && state.faqSubtopicCode) {
      try {
        const nextOffset = state.faqQuestionOffset + 10;
        const response = await apiRequest(`/faq/subtopics/${encodeURIComponent(state.faqSubtopicCode)}/questions?offset=${nextOffset}`, { method: 'GET' });
        const data = extractData(response);
        const questions = Array.isArray(data.questions) ? data.questions : [];
        state.faqQuestionOffset = nextOffset;
        await showTyping(300);
        await addMsg(renderFaqQuestionList(questions));
        setQR(data.has_more ? [nextLabel, backTopicsLabel, backMenuLabel] : [backTopicsLabel, backMenuLabel]);
        setInput(true);
        return;
      } catch (error) {
        await showApiError(error, 'Unable to load more FAQ questions.');
        setQR([nextLabel, backTopicsLabel, backMenuLabel]);
        setInput(true);
        return;
      }
    }

    const payload = buildFaqSubtopicPayload(text);
    if (!payload) {
      await showApiError({ message: 'Invalid FAQ subtopic selected.', errors: { subtopic: 'Please choose a listed FAQ subtopic.' } }, 'Invalid FAQ subtopic selected.');
      await addMsg(state.en ? 'Please choose a subtopic:' : 'Sila pilih subtopik:');
      setQR(faqSubtopicOptions());
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/faq-subtopic', { method: 'POST', body: { session_id: state.sessionId, subtopic_code: payload.subtopic_code } });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.faqSubtopicCode = payload.subtopic_code;
      state.faqQuestionOffset = 0;
      const questions = Array.isArray(data.questions) ? data.questions : [];
      await showTyping(400);
      if (questions.length === 0) {
        await addMsg(state.en ? 'No FAQs are available for this subtopic yet.' : 'Tiada Soalan Lazim tersedia untuk subtopik ini buat masa ini.');
      } else {
        await addMsg(renderFaqQuestionList(questions));
      }
      setQR(data.has_more ? [nextLabel, backTopicsLabel, backMenuLabel] : [backTopicsLabel, backMenuLabel]);
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to load FAQ questions.');
      await addMsg(state.en ? 'Please choose a subtopic:' : 'Sila pilih subtopik:');
      setQR(faqSubtopicOptions());
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
      await addMsg(state.en ? `You are contacting us from <strong>${escapeHtml(payload.state)}</strong>.` : `Terima kasih! Anda menghubungi kami dari <strong>${escapeHtml(payload.state)}</strong>.`);
      await showTyping(650);
      await addMsg(state.en
        ? 'To proceed with your <strong>Email ID Cancellation</strong> request, please provide the following:<div class="info-box">1. Full name (as per IC / Passport)<br>2. IC / Passport number<br>3. Mobile number<br>4. Email address<br>5. Identity document and signature after we collect your contact details</div>'
        : 'Untuk meneruskan permohonan <strong>Pembatalan Email ID</strong> anda, sila sediakan maklumat berikut:<div class="info-box">1. Nama penuh (seperti dalam IC / Pasport)<br>2. Nombor IC / Pasport<br>3. Nombor telefon bimbit<br>4. Alamat emel<br>5. Dokumen pengenalan dan tandatangan selepas kami mengumpul maklumat hubungan anda</div>');
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

  if (state.step === 'ask_company_ppk') {
    const payload = buildCompanyPpkPayload(text);
    if (!payload) {
      await showApiError({ message: 'Please enter a valid PPK / SSM number.', errors: { ppk_number: 'Please enter a valid PPK / SSM number.' } }, 'Please enter a valid PPK / SSM number.');
      await addMsg(state.en ? 'Please enter a valid <strong>PPK / SSM number</strong>.' : 'Sila masukkan <strong>nombor PPK / SSM</strong> yang sah.');
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/company-ppk', { method: 'POST', body: { session_id: state.sessionId, ppk_number: payload.value } });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.companyPpkNumber = payload.value;
      state.step = 'ask_company_name';
      await showTyping(450);
      await addMsg(state.en ? 'Thank you. What is your <strong>company name</strong>?' : 'Terima kasih. Apakah <strong>nama syarikat</strong> anda?');
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save company number.');
      await addMsg(state.en ? 'Please enter a valid <strong>PPK / SSM number</strong>.' : 'Sila masukkan <strong>nombor PPK / SSM</strong> yang sah.');
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_company_name') {
    const payload = buildCompanyTextPayload(text);
    if (!payload) {
      await showApiError({ message: 'Invalid company name.', errors: { company_name: 'Company name is required.' } }, 'Invalid company name.');
      await addMsg(state.en ? 'Please provide your <strong>company name</strong>.' : 'Sila berikan <strong>nama syarikat</strong> anda.');
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/company-name', { method: 'POST', body: { session_id: state.sessionId, company_name: payload.value } });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.companyName = payload.value;
      state.step = 'ask_company_email';
      await showTyping(450);
      await addMsg(state.en ? 'Please provide the <strong>company email address</strong> to cancel.' : 'Sila berikan <strong>alamat emel syarikat</strong> yang ingin dibatalkan.');
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save company name.');
      await addMsg(state.en ? 'Please provide your <strong>company name</strong>.' : 'Sila berikan <strong>nama syarikat</strong> anda.');
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_company_email') {
    const payload = buildEmailPayload(text);
    if (!payload) {
      await showApiError({ message: 'Invalid company email address.', errors: { company_email: 'Enter a valid email address.' } }, 'Invalid company email address.');
      await addMsg(state.en ? 'Please provide the <strong>company email address</strong>.' : 'Sila berikan <strong>alamat emel syarikat</strong>.');
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/company-email', { method: 'POST', body: { session_id: state.sessionId, company_email: payload.email } });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.companyEmail = payload.email;
      state.companyCategory = state.companyCategory || 'company';
      state.step = 'ask_company_contact';
      await showTyping(450);
      await addMsg(state.en
        ? 'Please provide the <strong>company contact number</strong> so we can continue.'
        : 'Sila berikan <strong>nombor telefon syarikat</strong> untuk meneruskan.');
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save company email.');
      await addMsg(state.en ? 'Please provide the <strong>company email address</strong>.' : 'Sila berikan <strong>alamat emel syarikat</strong>.');
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_company_contact') {
    const payload = buildMobilePayload(text);
    if (!payload) {
      await showApiError({ message: 'Invalid company contact number.', errors: { company_contact_number: 'Enter a valid contact number.' } }, 'Invalid company contact number.');
      await addMsg(state.en ? 'Please provide the <strong>company contact number</strong>.' : 'Sila berikan <strong>nombor telefon syarikat</strong>.');
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/company-contact', { method: 'POST', body: { session_id: state.sessionId, company_contact_number: payload.mobile } });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.companyContactNumber = payload.mobile;
      state.step = 'ask_company_state';
      await showTyping(450);
      await addMsg(state.en
        ? 'Please provide the <strong>company state</strong> for RPA verification.'
        : 'Sila berikan <strong>negeri syarikat</strong> untuk pengesahan RPA.');
      setQR(MY_STATES);
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save company contact number.');
      await addMsg(state.en ? 'Please provide the <strong>company contact number</strong>.' : 'Sila berikan <strong>nombor telefon syarikat</strong>.');
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_company_state') {
    const payload = buildStatePayload(text);
    if (!payload) {
      await showApiError({ message: 'Invalid Malaysian state selected.', errors: { state: 'Please choose a valid Malaysian state.' } }, 'Invalid Malaysian state selected.');
      await addMsg(state.en
        ? 'Please provide the <strong>company state</strong> for RPA verification.'
        : 'Sila berikan <strong>negeri syarikat</strong> untuk pengesahan RPA.');
      setQR(MY_STATES);
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/company-state', { method: 'POST', body: { session_id: state.sessionId, state: payload.state } });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.stateName = payload.state;
      state.step = 'ask_company_director_name';
      await showTyping(450);
      await addMsg(state.en ? 'What is the <strong>director full name</strong>?' : 'Apakah <strong>nama penuh pengarah</strong>?');
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save company state.');
      await addMsg(state.en
        ? 'Please provide the <strong>company state</strong> for RPA verification.'
        : 'Sila berikan <strong>negeri syarikat</strong> untuk pengesahan RPA.');
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_company_director_name') {
    const payload = buildCompanyTextPayload(text);
    if (!payload) {
      await showApiError({ message: 'Invalid director name.', errors: { director_full_name: 'Director full name is required.' } }, 'Invalid director name.');
      await addMsg(state.en ? 'Please provide the <strong>director full name</strong>.' : 'Sila berikan <strong>nama penuh pengarah</strong>.');
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/company-director-name', { method: 'POST', body: { session_id: state.sessionId, director_full_name: payload.value } });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.companyDirectorName = payload.value;
      state.step = 'ask_company_director_ic';
      await showTyping(450);
      await addMsg(state.en ? 'Please provide the <strong>director IC number</strong>.' : 'Sila berikan <strong>nombor IC pengarah</strong>.');
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save director name.');
      await addMsg(state.en ? 'Please provide the <strong>director full name</strong>.' : 'Sila berikan <strong>nama penuh pengarah</strong>.');
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_company_director_ic') {
    const payload = buildIdentityPayload(text);
    if (!payload) {
      await showApiError({ message: 'Invalid director IC number.', errors: { director_identity_number: 'Use a valid MyKad or passport number.' } }, 'Invalid director IC number.');
      await addMsg(state.en ? 'Please provide the <strong>director IC number</strong>.' : 'Sila berikan <strong>nombor IC pengarah</strong>.');
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/company-director-identity', {
        method: 'POST',
        body: { session_id: state.sessionId, identity_type: payload.identity_type, director_identity_number: payload.identity_number },
      });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.companyDirectorIdentityType = payload.identity_type;
      state.companyDirectorIdentityNumber = payload.display;
      state.step = 'ask_company_reason';
      await showTyping(450);
      await addMsg(state.en ? 'Please tell us the <strong>reason for company email ID cancellation</strong>.' : 'Sila nyatakan <strong>sebab pembatalan Email ID syarikat</strong>.');
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save director IC.');
      await addMsg(state.en ? 'Please provide the <strong>director IC number</strong>.' : 'Sila berikan <strong>nombor IC pengarah</strong>.');
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_company_reason') {
    const payload = buildCompanyTextPayload(text);
    if (!payload) {
      await showApiError({ message: 'Cancellation reason is required.', errors: { reason: 'Reason for company cancellation is required.' } }, 'Cancellation reason is required.');
      await addMsg(state.en ? 'Please tell us the <strong>reason for company email ID cancellation</strong>.' : 'Sila nyatakan <strong>sebab pembatalan Email ID syarikat</strong>.');
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/company-reason', { method: 'POST', body: { session_id: state.sessionId, reason: payload.value } });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.companyReason = payload.value;
      state.step = 'ask_ic_copy';
      await showTyping(450);
      await addMsg(state.en
        ? 'Thank you. Please upload the director <strong>IC front</strong>, <strong>IC back</strong>, and the <strong>SSM / PPK certificate</strong>.'
        : 'Terima kasih. Sila muat naik <strong>IC depan</strong>, <strong>IC belakang</strong>, dan <strong>sijil SSM / PPK</strong> pengarah.');
      setUploadLabels(state.en);
      state.sigDataUrl = null;
      state.uploads = { front: null, back: null, certificate: null, signature: null };
      state.files = { front: null, back: null, certificate: null };
      resetUploadSlot('front');
      resetUploadSlot('back');
      resetUploadSlot('certificate');
      const sigSlot = document.getElementById('slot-sig');
      if (sigSlot) sigSlot.classList.remove('has-file');
      const sigThumb = document.getElementById('thumb-sig');
      if (sigThumb) sigThumb.removeAttribute('src');
      const sigSub = document.getElementById('sub-sig');
      if (sigSub) sigSub.textContent = getSignatureInstructionLabel(state.en);
      uploadArea.style.display = 'flex';
      uploadBtn.disabled = true;
      syncUploadPanelBranch();
      setInput(false);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save cancellation reason.');
      await addMsg(state.en ? 'Please tell us the <strong>reason for company email ID cancellation</strong>.' : 'Sila nyatakan <strong>sebab pembatalan Email ID syarikat</strong>.');
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
      await addMsg(state.en ? `Now, <strong>${escapeHtml(text)}</strong>! May I have your <strong>IC / Passport number</strong>?` : `Terima kasih, <strong>${escapeHtml(text)}</strong>! Boleh saya dapatkan <strong>nombor IC / Pasport</strong> anda?`);
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
      state.step = 'ask_mobile';
      await showTyping(500);
      await addMsg(state.en
        ? 'Before we continue, may I have your <strong>mobile number</strong> for verification purposes?'
        : 'Sebelum kita teruskan, boleh saya dapatkan <strong>nombor telefon bimbit</strong> anda untuk tujuan pengesahan?');
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save identity.');
      await addMsg(state.en ? 'Please enter your <strong>IC / Passport number</strong> again.' : 'Sila masukkan semula <strong>nombor IC / Pasport</strong> anda.');
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_mobile') {
    const payload = buildMobilePayload(text);
    if (!payload) {
      await showApiError({ message: 'Invalid mobile number.', errors: { mobile: 'Enter a valid mobile number.' } }, 'Invalid mobile number.');
      await addMsg(state.en
        ? 'Please enter your <strong>mobile number</strong> again.'
        : 'Sila masukkan semula <strong>nombor telefon bimbit</strong> anda.');
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/mobile', {
        method: 'POST',
        body: { session_id: state.sessionId, mobile: payload.mobile },
      });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.mobile = payload.mobile;
      state.step = 'ask_email';
      await showTyping(450);
      await addMsg(state.en
        ? 'Now, kindly provide your <strong>email address</strong>.'
        : 'Seterusnya, sila berikan <strong>alamat emel</strong> anda.');
      setInput(true);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save mobile number.');
      await addMsg(state.en
        ? 'Please enter your <strong>mobile number</strong> again.'
        : 'Sila masukkan semula <strong>nombor telefon bimbit</strong> anda.');
      setInput(true);
      return;
    }
  }

  if (state.step === 'ask_email') {
    const payload = buildEmailPayload(text);
    if (!payload) {
      await showApiError({ message: 'Invalid email address.', errors: { email: 'Enter a valid email address.' } }, 'Invalid email address.');
      await addMsg(state.en
        ? 'Please enter your <strong>email address</strong> again.'
        : 'Sila masukkan semula <strong>alamat emel</strong> anda.');
      setInput(true);
      return;
    }
    try {
      const response = await apiRequest('/session/email', {
        method: 'POST',
        body: { session_id: state.sessionId, email: payload.email },
      });
      const data = extractData(response);
      updateSessionStateFromSession(isPlainObject(data.session) ? data.session : data);
      state.email = payload.email;
      state.step = 'ask_ic_copy';
      await showTyping(500);
      if (isPassportIdentityType()) {
        await addMsg(state.en
          ? 'Thank you. Please upload a clear copy of your <strong>Passport Information Page</strong>.<div class="warn-box">Photo tips:<br>- Keep the full page visible<br>- Avoid blur, shadows, or cropping<br>- Make sure all details are readable</div>'
          : 'Terima kasih. Sila muat naik salinan <strong>Muka Surat Maklumat Pasport</strong> yang jelas.<div class="warn-box">Petua foto:<br>- Pastikan keseluruhan muka surat kelihatan<br>- Elakkan kabur, bayang, atau gambar terpotong<br>- Pastikan semua butiran boleh dibaca</div>');
      } else {
        await addMsg(state.en
          ? 'Thank you. Please upload your <strong>IC copy (front & back)</strong>. Make sure the image is <strong>clear and fully visible</strong>.<div class="warn-box">Photo tips:<br>- Place IC on a flat, well-lit surface<br>- Avoid blur, shadows, or cropping<br>- Both front and back copies are required</div>'
          : 'Terima kasih. Sila muat naik <strong>salinan IC (depan & belakang)</strong>. Pastikan gambar <strong>jelas dan tidak terpotong</strong>.<div class="warn-box">Petua foto:<br>- Letak IC di permukaan rata yang terang<br>- Elakkan kabur, bayang, atau gambar terpotong<br>- Salinan depan dan belakang diperlukan</div>');
      }
      setUploadLabels(state.en);
      state.sigDataUrl = null;
      state.uploads = { front: null, back: null, certificate: null, signature: null };
      state.files = { front: null, back: null, certificate: null };
      resetUploadSlot('front');
      resetUploadSlot('back');
      document.getElementById('slot-sig').classList.remove('has-file');
      document.getElementById('thumb-sig').removeAttribute('src');
      document.getElementById('sub-sig').textContent = getSignatureInstructionLabel(state.en);
      uploadArea.style.display = 'flex';
      uploadBtn.disabled = true;
      syncUploadPanelBranch();
      setInput(false);
      await refreshSession();
      return;
    } catch (error) {
      await showApiError(error, 'Unable to save email address.');
      await addMsg(state.en
        ? 'Please enter your <strong>email address</strong> again.'
        : 'Sila masukkan semula <strong>alamat emel</strong> anda.');
      setInput(true);
      return;
    }
  }

  setInput(true);
}

async function slotChanged(slotId) {
  if (slotId === 'back' && !requiresBackDocument()) {
    return;
  }
  if (slotId === 'certificate' && !isCompanyService()) {
    return;
  }
  if (slotId === 'signature' && isCompanyService()) {
    return;
  }
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

  const localValidation = validateLocalFile(file, slotId);
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
  if (sigUploadBtn) {
    sigUploadBtn.textContent = en
      ? 'Upload signature image instead (JPG / PNG)'
      : 'Muat naik imej tandatangan (JPG / PNG)';
  }
  sigOverlay.classList.add('open');
  const rect = sigCanvas.getBoundingClientRect();
  sigCanvas.width = rect.width * (window.devicePixelRatio || 1);
  sigCanvas.height = rect.height * (window.devicePixelRatio || 1);
  sigCtx = sigCanvas.getContext('2d');
  sigCtx.scale(window.devicePixelRatio || 1, window.devicePixelRatio || 1);
  sigCtx.strokeStyle = '#165f66';
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

async function sigFileUploaded() {
  const file = sigFileInput?.files?.[0];
  const en = state.en;
  if (!file) {
    return;
  }

  const validation = validateSignatureFile(file);
  if (!validation.ok) {
    if (sigFileInput) {
      sigFileInput.value = '';
    }
    await showApiError({ message: validation.message, errors: { signature: validation.message } }, validation.message);
    return;
  }

  try {
    const dataUrl = await readFileAsDataUrl(file);
    const data = await uploadSignature(dataUrl);
    state.sigDataUrl = dataUrl;
    state.uploads.signature = data.signature || data;
    setSignatureSlotSuccess(dataUrl);
    checkAllFilled();
    closeSigPad();
    if (sigFileInput) {
      sigFileInput.value = '';
    }
    await addMsg(state.en ? 'Signature uploaded successfully.' : 'Tandatangan berjaya dimuat naik.', 'bot');
  } catch (error) {
    state.uploads.signature = null;
    state.sigDataUrl = null;
    if (sigFileInput) {
      sigFileInput.value = '';
    }
    const sub = document.getElementById('sigSub');
    if (sub) {
      sub.style.color = '#c85a57';
      sub.textContent = error?.message || (en ? 'Signature upload failed.' : 'Muat naik tandatangan gagal.');
    }
    await showApiError(error, 'Signature upload failed.');
    setTimeout(() => {
      if (!sub) return;
      sub.style.color = '';
      sub.textContent = en ? 'Sign inside the box using your finger or mouse' : 'Lukis di dalam kotak menggunakan jari atau tetikus';
    }, 2500);
    checkAllFilled();
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
    sub.style.color = '#c85a57';
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
    sub.style.color = '#c85a57';
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

  if (isCompanyService()) {
    if (!state.companyPpkNumber || !state.companyName || !state.companyEmail || !state.companyContactNumber || !state.stateName || !state.companyCategory || !state.companyDirectorName || !state.companyDirectorIdentityNumber || !state.companyReason) {
      await addMsg(en ? 'Please complete the company details before submitting.' : 'Sila lengkapkan butiran syarikat sebelum menghantar.', 'error');
      return;
    }
    if (!state.uploads.front || (requiresBackDocument(state.companyDirectorIdentityType) && !state.uploads.back) || !state.uploads.certificate) {
      await addMsg(en ? 'Please upload the required company documents before submitting.' : 'Sila muat naik dokumen syarikat yang diperlukan sebelum menghantar.', 'error');
      return;
    }
  } else {
    if (!state.name || !state.identityNumber || !state.stateName) {
      await addMsg(en ? 'Please complete the previous steps before submitting.' : 'Sila lengkapkan langkah sebelumnya sebelum menghantar.', 'error');
      return;
    }
    if (!state.mobile || !state.email) {
      await addMsg(en ? 'Please complete your mobile number and email address before submitting.' : 'Sila lengkapkan nombor telefon bimbit dan alamat emel anda sebelum menghantar.', 'error');
      return;
    }
    if (!state.uploads.front || (requiresBackDocument() && !state.uploads.back) || !state.uploads.signature) {
      await addMsg(
        en
          ? (requiresBackDocument() ? 'Please upload the required documents and your signature before submitting.' : 'Please upload the passport page and your signature before submitting.')
          : (requiresBackDocument() ? 'Sila muat naik dokumen yang diperlukan dan tandatangan anda sebelum menghantar.' : 'Sila muat naik muka surat pasport dan tandatangan anda sebelum menghantar.'),
        'error'
      );
      return;
    }
  }

  uploadArea.style.display = 'none';
  uploadBtn.disabled = true;

  const front = state.uploads.front?.document || state.uploads.front;
  const back = state.uploads.back?.document || state.uploads.back;
  const cert = state.uploads.certificate?.document || state.uploads.certificate;
  const names = [];

  if (isCompanyService()) {
    names.push(`${escapeHtml(getFrontDocumentLabel(en))}: <strong>${escapeHtml(front?.original_filename || front?.file_name || state.files.front?.name || 'Uploaded')}</strong>`);
    names.push(`${escapeHtml(getBackDocumentLabel(en))}: <strong>${escapeHtml(back?.original_filename || back?.file_name || state.files.back?.name || 'Uploaded')}</strong>`);
    names.push(`${escapeHtml(getCertificateDocumentLabel(en))}: <strong>${escapeHtml(cert?.original_filename || cert?.file_name || state.files.certificate?.name || 'Uploaded')}</strong>`);
  } else {
    const sigThumbHtml = state.sigDataUrl
      ? `<img src="${state.sigDataUrl}" style="max-height:36px;border-radius:4px;border:1px solid #b7d7d6;vertical-align:middle;margin-left:4px;">`
      : '';
    names.push(`${escapeHtml(getFrontDocumentLabel(en))}: <strong>${escapeHtml(front?.original_filename || front?.file_name || state.files.front?.name || 'Uploaded')}</strong>`);
    if (requiresBackDocument()) {
      names.push(`${escapeHtml(getBackDocumentLabel(en))}: <strong>${escapeHtml(back?.original_filename || back?.file_name || state.files.back?.name || 'Uploaded')}</strong>`);
    }
    names.push(`Signature: ${sigThumbHtml}`);
  }

  await addMsg(`Documents submitted:<br>${names.join('<br>')}`, 'user');
  await showTyping(900);
  await addMsg(isCompanyService()
    ? (en ? 'Thank you. We\'ve received your company documents and are reviewing them.' : 'Terima kasih. Kami telah menerima dokumen syarikat anda dan sedang menyemaknya.')
    : (en ? 'Thank you. We\'ve received your documents and are reviewing them.' : 'Terima kasih. Kami telah menerima dokumen anda dan sedang menyemaknya.'));
  await showTyping(650);
  await addMsg(isCompanyService()
    ? (en
      ? 'Please <strong>stay on the line</strong> while we verify your company details. This will only take a moment...'
      : 'Sila <strong>tunggu sebentar</strong> sementara kami mengesahkan maklumat syarikat anda. Ini hanya mengambil masa sebentar...')
    : (en
      ? 'Please <strong>stay on the line</strong> while we verify your details. This will only take a moment...'
      : 'Sila <strong>tunggu sebentar</strong> sementara kami mengesahkan maklumat anda dalam <strong>portal CIMS</strong>. Ini hanya mengambil masa sebentar...'));

  const ocrWaitSequence = startWaitMessageSequence(en);
  let displayWaitSequence = null;
  try {
    const response = await apiRequest('/submission', {
      method: 'POST',
      body: { session_id: state.sessionId },
    });
    const data = extractData(response);
    const nextAction = firstNonEmpty(data.next_action, data.nextAction, 'done').toLowerCase();
    const request = isPlainObject(data.request) ? data.request : null;
    const requestNumber = resolveSubmissionIdentifier(firstNonEmpty(data.request_number, data.requestNumber, extractRequestNumber(request)), request);
    state.submission = request || data.submission || data;
    const ocrVerification = isPlainObject(data.ocr_verification) ? data.ocr_verification : null;
    const ocrShouldContinue = ocrVerification ? ocrVerification.should_continue !== false : true;
    state.requestNumber = requestNumber;
    if (requestNumber) {
      persistSubmissionContext(requestNumber);
    }
    const verification = isPlainObject(data.verification) ? data.verification : null;
    const finalFailureType = firstNonEmpty(data.final_failure_type, data.finalFailureType, state.submission?.final_failure_type);
    traceRpaFlow('frontend submit response received', {
      identifier: requestNumber,
      submissionRequestNumber: extractRequestNumber(request),
      nextAction,
      verificationId: verification?.id ?? null,
      displayMessageLength: String(verification?.display_message ?? '').length,
      displayMessageIsEmpty: !String(verification?.display_message ?? '').trim(),
      resultStatus: verification?.result_status ?? null,
    });

    if (finalFailureType === 'ocr') {
      ocrWaitSequence.stop();
      displayWaitSequence?.stop?.();
      const finalMessage = firstNonEmpty(data.message, ocrVerification?.message, ocrVerification?.display_message, en
        ? 'We are unable to complete the ID verification after two attempts. Your request has been forwarded for further processing. Please wait for further updates.'
        : 'Kami tidak dapat melengkapkan pengesahan ID selepas dua percubaan. Permohonan anda telah dihantar untuk tindakan lanjut. Sila tunggu maklum balas seterusnya.');
      await addMsg(finalMessage, 'bot');
      uploadArea.style.display = 'none';
      uploadBtn.disabled = true;
      state.step = 'done';
      setInput(false);
      clearSubmissionContext();
      return;
    }

    if (nextAction === 'reupload' || (ocrVerification && ocrShouldContinue === false)) {
      ocrWaitSequence.stop();
      const ocrMessage = firstNonEmpty(
        ocrVerification?.message,
        data.message,
        en
          ? 'ID verification failed. Please re-upload the documents.'
          : 'Pengesahan ID gagal. Sila muat naik semula dokumen.'
      );
      await addMsg(renderOcrVerificationBubble({ ...ocrVerification, message: ocrMessage }), 'error');
      state.step = 'ask_ic_copy';
      uploadArea.style.display = 'flex';
      setInput(false);
      checkAllFilled();
      return;
    }

    if (!requestNumber) {
      ocrWaitSequence.stop();
      await showApiError({
        message: 'Submission request is missing.',
        errors: { submission: 'The backend did not return a request number.' },
      }, 'Submission request is missing.');
      state.step = 'ask_ic_copy';
      uploadArea.style.display = 'flex';
      setInput(false);
      checkAllFilled();
      return;
    }

    ocrWaitSequence.stop();

    if (ocrVerification) {
      await addMsg(renderOcrVerificationBubble(ocrVerification), 'bot');
    }

    await addMsg(renderSummaryBox());

    if (data?.retry_available === true || data?.retry_in_progress === true || nextAction === 'retry_available') {
      const cancellationState = await renderCancellationSubmissionState(data, { fromRetry: false });
      if (cancellationState.handled) {
        displayWaitSequence?.stop?.();
        return;
      }
    }

    const outcome = firstNonEmpty(
      verification?.result_status,
      verification?.status,
      data?.result_status,
      state.submission?.verification_result,
      'error'
    );
    const identifier = requestNumber;
    let resolvedVerification = verification;
    let botMessage = extractVerificationCustomerMessage(verification);

    if (!botMessage) {
      displayWaitSequence = startDisplayMessageWaitSequence(en);
      const resolved = await waitForFinalVerificationMessage(identifier, verification);
      resolvedVerification = resolved.verification;
      botMessage = resolved.message;
      displayWaitSequence.stop();
      displayWaitSequence = null;
    }

    traceRpaFlow('frontend bot message resolution', {
      identifier,
      nextAction,
      resolvedVerificationId: resolvedVerification?.id ?? null,
      botMessageLength: String(botMessage || '').length,
      botMessageIsEmpty: !String(botMessage || '').trim(),
      resolvedDisplayMessageLength: String(resolvedVerification?.display_message ?? '').length,
      resolvedDisplayMessageIsEmpty: !String(resolvedVerification?.display_message ?? '').trim(),
    });

    if (botMessage) {
      const refreshed = await refreshSubmission(identifier);
      if (isPlainObject(refreshed?.submission)) {
        state.submission = refreshed.submission;
      }
    }

    if (botMessage) {
      const botReplies = normalizeBotReplies(resolvedVerification?.quick_replies || resolvedVerification?.options || resolvedVerification?.choices);
      const botResponseLines = [renderBotRichMessage(botMessage)];
      if (botReplies.length > 0) {
        botResponseLines.push('<br><strong>Options:</strong><br>' + botReplies.map(option => '- ' + escapeHtml(option)).join('<br>'));
      }
      traceRpaFlow('frontend rendering backend message', {
        identifier,
        botMessageLength: botMessage.length,
        botRepliesCount: botReplies.length,
      });
      await addMsg(botResponseLines.join(''), 'bot');
    } else {
      traceRpaFlow('frontend rendering pending message', {
        identifier,
        nextAction,
        outcome,
        resolvedVerificationId: resolvedVerification?.id ?? null,
        resolvedDisplayMessageLength: String(resolvedVerification?.display_message ?? '').length,
        resolvedDisplayMessageIsEmpty: !String(resolvedVerification?.display_message ?? '').trim(),
      });
      await addMsg(
        en
          ? 'Thank you for waiting. We are still processing your request. Please check back shortly.'
          : 'Terima kasih kerana menunggu. Permintaan anda masih diproses. Sila semak semula sebentar lagi.'
      );
    }
    state.step = 'done';
    setInput(false);
    clearSubmissionContext();
  } catch (error) {
    ocrWaitSequence.stop();
    if (displayWaitSequence) {
      displayWaitSequence.stop();
    }
    await showApiError(error, 'Submission failed.');
    state.step = 'ask_ic_copy';
    uploadArea.style.display = 'flex';
    setInput(false);
    checkAllFilled();
  }
}

bootstrapConversation();
