<?php
require_once '../includes/config.php';
checkLogin();
$role = getRole();
$name = $_SESSION['name'];
$me   = (int)$_SESSION['user_id'];
$active_page = 'chat';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Chat - SIPKL</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<div id="page-loader"><div class="loader-spinner"></div></div>
<div class="app-container">

    <!-- Header -->
    <header class="app-header">
        <div>
            <h1 style="font-size:18px; font-weight:700;">💬 Live Chat</h1>
            <div style="font-size:11px; opacity:.7; text-transform:uppercase; letter-spacing:.5px;">Semua Percakapan</div>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <button id="btn-notif" class="icon-btn" type="button" title="Notifikasi" style="position:relative; background:none; border:none; cursor:pointer; padding:4px;">
                <i data-lucide="bell"></i>
                <span id="notif-badge" class="badge-dot" style="display:none;">0</span>
            </button>
        </div>
    </header>

    <!-- Contact Search -->
    <div style="padding:12px 16px; border-bottom:1px solid var(--border); background:var(--surface);">
        <div style="position:relative;">
            <i data-lucide="search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); width:16px; height:16px; color:var(--text-muted);"></i>
            <input type="text" id="contact-search" placeholder="Cari nama pengguna..."
                style="width:100%; padding:10px 16px 10px 36px; background:var(--bg-color); border:1px solid var(--border); font-size:13px; color:var(--primary); font-family:'Maven Pro',sans-serif;">
        </div>
    </div>

    <!-- Contact List -->
    <main class="main-content" style="padding:0; padding-bottom:80px;" id="contact-list-area">
        <div id="contact-list">
            <div class="notif-loading"><span class="notif-spinner"></span></div>
        </div>
    </main>

    <?php $active_page = 'chat'; include '../includes/bottom_nav.php'; ?>
</div>

<!-- CHAT WINDOW (slide in from right) -->
<div class="chat-window" id="chat-window" aria-hidden="true">
    <div class="chat-header">
        <button class="chat-back-btn" id="chat-back" type="button" aria-label="Kembali">
            <i data-lucide="arrow-left" style="width:22px;height:22px;"></i>
        </button>
        <div id="chat-contact-avatar" class="contact-avatar" style="width:36px;height:36px;font-size:14px;">?</div>
        <div style="flex:1; overflow:hidden; margin-left:4px;">
            <div id="chat-contact-name" style="font-size:15px; font-weight:700; color:var(--primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></div>
            <div id="chat-contact-role" style="font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.4px;"></div>
        </div>
        <div id="chat-online-dot" style="width:8px;height:8px;background:var(--success);border-radius:50%;margin-right:4px;display:none;"></div>
    </div>

    <div class="chat-messages" id="chat-messages">
        <div class="notif-loading" id="chat-loader"><span class="notif-spinner"></span></div>
    </div>

    <div class="chat-input-bar">
        <textarea id="chat-input" placeholder="Ketik pesan..." rows="1" maxlength="2000"></textarea>
        <button id="chat-send-btn" type="button" aria-label="Kirim">
            <i data-lucide="send" style="width:18px;height:18px;"></i>
        </button>
    </div>
</div>

<script>
const ME       = <?= $me ?>;
const MY_NAME  = <?= json_encode($name) ?>;
const BASE_URL = <?= json_encode(BASE_URL) ?>;

// ─── HELPERS ──────────────────────────────────────────────────────────────────
function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
function formatTime(dt) {
    const d = new Date(dt.replace(' ', 'T'));
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}
function formatDate(dt) {
    const d = new Date(dt.replace(' ', 'T'));
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' });
}
function roleLabel(r) {
    return { admin:'Admin', siswa:'Siswa', pembimbing_sekolah:'Guru', pembimbing_dudika:'DUDI' }[r] || r;
}
function avatarLetter(name) {
    return (name || '?').trim().charAt(0).toUpperCase();
}

// ─── CONTACTS ─────────────────────────────────────────────────────────────────
let allContacts = [];

async function loadContacts() {
    const list = document.getElementById('contact-list');
    try {
        const res  = await fetch(BASE_URL + 'api/chat.php?action=contacts', { credentials: 'same-origin' });
        allContacts = await res.json();
        renderContacts(allContacts);
    } catch(_) {
        list.innerHTML = '<div class="notif-empty"><p>Gagal memuat kontak</p></div>';
    }
}

function renderContacts(contacts) {
    const list = document.getElementById('contact-list');
    if (!contacts.length) {
        list.innerHTML = '<div class="notif-empty"><i data-lucide="users"></i><p>Belum ada pengguna lain</p></div>';
        lucide.createIcons();
        return;
    }
    list.innerHTML = contacts.map(c => {
        const initial = avatarLetter(c.name);
        const avatar  = c.foto
            ? `<img src="${BASE_URL}uploads/profil/${escHtml(c.foto)}" alt="${escHtml(c.name)}">`
            : initial;
        const unread  = parseInt(c.unread_count) > 0
            ? `<span class="badge-dot" style="position:static; border:none; min-width:18px; height:18px; font-size:10px;">${c.unread_count}</span>`
            : '';
        const preview = c.last_message ? escHtml(c.last_message.substring(0, 40)) + (c.last_message.length > 40 ? '…' : '') : '<span style="color:var(--text-muted)">Mulai percakapan</span>';
        return `
        <div class="contact-item" data-id="${c.id}" data-name="${escHtml(c.name)}" data-role="${c.role}" data-foto="${c.foto || ''}">
            <div class="contact-avatar">${avatar}</div>
            <div class="contact-info">
                <div class="contact-name">${escHtml(c.name)}</div>
                <div class="contact-preview">${preview}</div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0;">
                <span class="contact-role-badge">${roleLabel(c.role)}</span>
                ${unread}
            </div>
        </div>`;
    }).join('');

    list.querySelectorAll('.contact-item').forEach(el => {
        el.addEventListener('click', () => openChat(
            parseInt(el.dataset.id),
            el.dataset.name,
            el.dataset.role,
            el.dataset.foto
        ));
    });

    lucide.createIcons();
}

// ─── SEARCH FILTER ────────────────────────────────────────────────────────────
document.getElementById('contact-search').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    renderContacts(q ? allContacts.filter(c => c.name.toLowerCase().includes(q)) : allContacts);
});

// ─── CHAT WINDOW ──────────────────────────────────────────────────────────────
let currentWith   = null;
let lastMsgId     = 0;
let pollInterval  = null;
const chatWindow  = document.getElementById('chat-window');
const chatMsgs    = document.getElementById('chat-messages');
const chatInput   = document.getElementById('chat-input');

function openChat(userId, userName, userRole, userFoto) {
    currentWith = userId;
    lastMsgId   = 0;

    // Update header
    document.getElementById('chat-contact-name').textContent = userName;
    document.getElementById('chat-contact-role').textContent = roleLabel(userRole);
    const avatarEl = document.getElementById('chat-contact-avatar');
    if (userFoto) {
        avatarEl.innerHTML = `<img src="${BASE_URL}uploads/profil/${escHtml(userFoto)}" alt="${escHtml(userName)}">`;
    } else {
        avatarEl.innerHTML = avatarLetter(userName);
    }

    // Show window
    chatWindow.classList.add('open');
    chatWindow.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    chatMsgs.innerHTML = '<div class="notif-loading"><span class="notif-spinner"></span></div>';
    chatInput.value = '';
    adjustTextarea();

    // Load messages & start polling
    fetchMessages(false);
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(() => fetchMessages(true), 4000);
}

function closeChat() {
    chatWindow.classList.remove('open');
    chatWindow.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    currentWith = null;
    loadContacts(); // refresh unread badges
    if (window.sipkl) window.sipkl.pollChatCount();
}

document.getElementById('chat-back').addEventListener('click', closeChat);

// ESC key to close
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && chatWindow.classList.contains('open')) closeChat();
});

// ─── FETCH MESSAGES ───────────────────────────────────────────────────────────
let lastDateShown = '';

async function fetchMessages(isPolling) {
    if (!currentWith) return;
    const url = `${BASE_URL}api/chat.php?action=messages&with=${currentWith}&after=${isPolling ? lastMsgId : 0}`;
    try {
        const res  = await fetch(url, { credentials: 'same-origin' });
        const msgs = await res.json();

        if (!msgs.length && !isPolling) {
            chatMsgs.innerHTML = '<div class="notif-empty" style="padding:60px 20px;"><i data-lucide="message-circle" style="width:40px;height:40px;opacity:.3;"></i><p>Belum ada pesan. Mulai percakapan!</p></div>';
            lucide.createIcons();
            return;
        }

        if (msgs.length) {
            if (!isPolling) {
                chatMsgs.innerHTML = '';
                lastDateShown = '';
            }
            const wasAtBottom = chatMsgs.scrollHeight - chatMsgs.scrollTop - chatMsgs.clientHeight < 60;

            msgs.forEach(m => {
                const msgDate = m.created_at.substring(0, 10);
                if (msgDate !== lastDateShown) {
                    chatMsgs.insertAdjacentHTML('beforeend', `<div class="chat-date-divider">${formatDate(m.created_at)}</div>`);
                    lastDateShown = msgDate;
                }
                appendBubble(m);
                lastMsgId = Math.max(lastMsgId, parseInt(m.id));
            });

            lucide.createIcons();
            if (!isPolling || wasAtBottom) {
                chatMsgs.scrollTop = chatMsgs.scrollHeight;
            }
        }
    } catch(_) {}
}

function appendBubble(m) {
    const isMe = parseInt(m.from_user_id) === ME;
    const wrap = document.createElement('div');
    wrap.className = `chat-bubble-wrap ${isMe ? 'me' : 'other'}`;
    wrap.innerHTML = `
        <div class="chat-bubble">
            ${escHtml(m.message)}
            <div class="bubble-time">${formatTime(m.created_at)}${isMe ? (m.is_read == 1 ? ' ✓✓' : ' ✓') : ''}</div>
        </div>`;
    chatMsgs.appendChild(wrap);
}

// ─── SEND MESSAGE ─────────────────────────────────────────────────────────────
async function sendMessage() {
    const msg = chatInput.value.trim();
    if (!msg || !currentWith) return;

    const btn = document.getElementById('chat-send-btn');
    btn.disabled = true;
    chatInput.value = '';
    adjustTextarea();

    try {
        const body = new URLSearchParams({ action: 'send', to_user_id: currentWith, message: msg });
        const res  = await fetch(BASE_URL + 'api/chat.php', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        });
        const data = await res.json();
        if (data.ok) {
            // Optimistic UI
            const fake = { id: data.id, from_user_id: ME, to_user_id: currentWith, message: msg, is_read: 0, created_at: data.created_at };
            const msgDate = fake.created_at.substring(0, 10);
            if (msgDate !== lastDateShown) {
                chatMsgs.insertAdjacentHTML('beforeend', `<div class="chat-date-divider">${formatDate(fake.created_at)}</div>`);
                lastDateShown = msgDate;
            }
            appendBubble(fake);
            lastMsgId = Math.max(lastMsgId, data.id);
            chatMsgs.scrollTop = chatMsgs.scrollHeight;
        }
    } catch(_) {}

    btn.disabled = false;
    chatInput.focus();
}

document.getElementById('chat-send-btn').addEventListener('click', sendMessage);

// Send on Enter (Shift+Enter = newline)
chatInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Auto-resize textarea
function adjustTextarea() {
    chatInput.style.height = 'auto';
    chatInput.style.height = Math.min(chatInput.scrollHeight, 120) + 'px';
}
chatInput.addEventListener('input', adjustTextarea);

// ─── INIT ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadContacts();
    lucide.createIcons();
});
</script>
</body>
</html>
