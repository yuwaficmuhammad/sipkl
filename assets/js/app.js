/**
 * SIPKL Global App Script
 * Handles: notification polling, chat badge polling, notification panel UI
 */

(function () {
  'use strict';

  const BASE = (() => {
    const base = document.querySelector('meta[name="base-url"]');
    return base ? base.content : '/';
  })();

  // ─── NOTIFICATION POLLING ──────────────────────────────────────────────────
  function updateNotifBadge(count) {
    const badge = document.getElementById('notif-badge');
    if (!badge) return;
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  }

  function updateChatBadge(count) {
    const badge = document.getElementById('chat-nav-badge');
    if (!badge) return;
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  }

  async function pollNotifCount() {
    try {
      const res = await fetch(BASE + 'api/notif.php?action=count', { credentials: 'same-origin' });
      const data = await res.json();
      updateNotifBadge(data.count || 0);
    } catch (_) {}
  }

  async function pollChatCount() {
    try {
      const res = await fetch(BASE + 'api/chat.php?action=unread_count', { credentials: 'same-origin' });
      const data = await res.json();
      updateChatBadge(data.count || 0);
    } catch (_) {}
  }

  function startPolling() {
    pollNotifCount();
    pollChatCount();
    setInterval(pollNotifCount, 10000); // tiap 10 detik
    setInterval(pollChatCount, 8000);   // tiap 8 detik
  }

  // ─── NOTIFICATION PANEL ───────────────────────────────────────────────────
  function timeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60) return diff + 'd lalu';
    if (diff < 3600) return Math.floor(diff / 60) + 'm lalu';
    if (diff < 86400) return Math.floor(diff / 3600) + 'j lalu';
    return Math.floor(diff / 86400) + 'hr lalu';
  }

  function roleLabel(role) {
    const map = {
      admin: 'Admin',
      siswa: 'Siswa',
      pembimbing_sekolah: 'Guru',
      pembimbing_dudika: 'DUDI',
      all: 'Semua',
    };
    return map[role] || role;
  }

  async function openNotifPanel() {
    const panel = document.getElementById('notif-panel');
    const list  = document.getElementById('notif-list');
    if (!panel || !list) return;

    panel.classList.add('active');
    list.innerHTML = '<div class="notif-loading"><span class="notif-spinner"></span></div>';

    try {
      const res  = await fetch(BASE + 'api/notif.php?action=list', { credentials: 'same-origin' });
      const data = await res.json();

      if (!data.length) {
        list.innerHTML = '<div class="notif-empty"><i data-lucide="bell-off"></i><p>Belum ada notifikasi</p></div>';
        lucide.createIcons();
        return;
      }

      list.innerHTML = data.map(n => `
        <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}" data-id="${n.id}">
          <div class="notif-dot" ${n.is_read == 1 ? 'style="display:none"' : ''}></div>
          <div class="notif-body">
            <div class="notif-title">${escHtml(n.judul)}</div>
            <div class="notif-msg">${escHtml(n.pesan)}</div>
            <div class="notif-time">${timeAgo(n.created_at)}</div>
          </div>
        </div>
      `).join('');

      // Mark as read on click
      list.querySelectorAll('.notif-item.unread').forEach(el => {
        el.addEventListener('click', async () => {
          const id = el.dataset.id;
          el.classList.remove('unread');
          el.querySelector('.notif-dot').style.display = 'none';
          await fetch(BASE + 'api/notif.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=read&id=${id}`,
          });
          pollNotifCount();
        });
      });
    } catch (_) {
      list.innerHTML = '<div class="notif-empty"><p>Gagal memuat notifikasi</p></div>';
    }
  }

  async function markAllRead() {
    await fetch(BASE + 'api/notif.php', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=read_all',
    });
    document.querySelectorAll('.notif-item').forEach(el => {
      el.classList.remove('unread');
      const dot = el.querySelector('.notif-dot');
      if (dot) dot.style.display = 'none';
    });
    updateNotifBadge(0);
  }

  function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  // ─── INIT ─────────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    // Bell icon click
    const bellBtn = document.getElementById('btn-notif');
    if (bellBtn) {
      bellBtn.addEventListener('click', openNotifPanel);
    }

    // Panel close
    const panelOverlay = document.getElementById('notif-panel');
    if (panelOverlay) {
      panelOverlay.addEventListener('click', e => {
        if (e.target === panelOverlay) panelOverlay.classList.remove('active');
      });
    }

    const closeBtn = document.getElementById('notif-panel-close');
    if (closeBtn) closeBtn.addEventListener('click', () => {
      document.getElementById('notif-panel')?.classList.remove('active');
    });

    const readAllBtn = document.getElementById('notif-read-all');
    if (readAllBtn) readAllBtn.addEventListener('click', markAllRead);

    startPolling();
  });

  // Expose for chat page
  window.sipkl = { pollNotifCount, pollChatCount, updateChatBadge };
})();
