

'use strict';


function initSidebar() {
  const sidebar  = document.querySelector('.sidebar');
  const mainContent = document.querySelector('.main-content');
  const toggleBtn   = document.querySelector('.toggle-btn');
  const overlay     = document.querySelector('.sidebar-overlay');

  if (!sidebar || !toggleBtn) return;

  const isMobile = () => window.innerWidth <= 768;

  function toggle() {
    if (isMobile()) {
      sidebar.classList.toggle('mobile-open');
      overlay && overlay.classList.toggle('show');
    } else {
      sidebar.classList.toggle('collapsed');
      mainContent && mainContent.classList.toggle('expanded');
      localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
    }
  }

  toggleBtn.addEventListener('click', toggle);
  overlay && overlay.addEventListener('click', () => {
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('show');
  });

  if (!isMobile() && localStorage.getItem('sidebar-collapsed') === 'true') {
    sidebar.classList.add('collapsed');
    mainContent && mainContent.classList.add('expanded');
  }

  window.addEventListener('resize', () => {
    if (!isMobile()) {
      sidebar.classList.remove('mobile-open');
      overlay && overlay.classList.remove('show');
    }
  });
}


function initNavHighlight() {
  const current = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-item[data-page]').forEach(item => {
    if (item.dataset.page === current) item.classList.add('active');
    item.addEventListener('click', () => {
      document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
      item.classList.add('active');
    });
  });
}

function initTabs() {
  document.querySelectorAll('.tabs').forEach(tabsEl => {
    const items  = tabsEl.querySelectorAll('.tab-item');
    const panels = tabsEl.nextElementSibling
      ? tabsEl.closest('.card-body, .page-content, .tab-wrapper')?.querySelectorAll('.tab-panel') || []
      : [];

    items.forEach((item, i) => {
      item.addEventListener('click', () => {
        items.forEach(t => t.classList.remove('active'));
        item.classList.add('active');
        
        const targetId = item.dataset.tab;
        if (targetId) {
          document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
          const target = document.getElementById(targetId);
          if (target) target.classList.add('active');
        }
      });
    });
  });
}


function openModal(id) {
  const overlay = document.getElementById(id);
  if (!overlay) return;
  overlay.classList.add('show');
  document.body.style.overflow = 'hidden';
  
  const firstInput = overlay.querySelector('input, select, textarea');
  if (firstInput) setTimeout(() => firstInput.focus(), 100);
}

function closeModal(id) {
  const overlay = document.getElementById(id);
  if (!overlay) return;
  overlay.classList.remove('show');
  document.body.style.overflow = '';
}

function initModals() {
  
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) closeModal(overlay.id);
    });
  });

  
  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = btn.closest('.modal-overlay');
      if (modal) closeModal(modal.id);
    });
  });

  
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => openModal(btn.dataset.modalOpen));
  });

  
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.show').forEach(m => closeModal(m.id));
    }
  });
}


function initDropdowns() {
  document.querySelectorAll('[data-dropdown]').forEach(trigger => {
    const menu = document.getElementById(trigger.dataset.dropdown);
    if (!menu) return;
    trigger.addEventListener('click', e => {
      e.stopPropagation();
      menu.classList.toggle('show');
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
  });
}


function showToast(message, type = 'info', duration = 3500) {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const icons = { info: 'bi-info-circle-fill', success: 'bi-check-circle-fill',
                  warning: 'bi-exclamation-triangle-fill', danger: 'bi-x-circle-fill' };
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<i class="bi ${icons[type] || icons.info}"></i><span>${message}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = 'slideInRight .3s ease reverse forwards';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}


function animateCounter(el, target, duration = 1200) {
  const start = 0;
  const step  = (target / duration) * 16;
  let current = start;

  const timer = setInterval(() => {
    current += step;
    if (current >= target) { current = target; clearInterval(timer); }
    el.textContent = Math.floor(current).toLocaleString();
  }, 16);
}

function initCounters() {
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const target = parseFloat(el.dataset.count || el.textContent.replace(/[^0-9.]/g, ''));
        if (!isNaN(target)) animateCounter(el, target);
        observer.unobserve(el);
      }
    });
  }, { threshold: 0.2 });

  document.querySelectorAll('[data-count]').forEach(el => observer.observe(el));
}


  
function initAvatarUpload() {
  document.querySelectorAll('.avatar-file-input').forEach(input => {
    input.addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;
      if (!file.type.startsWith('image/')) {
        showToast('Please select a valid image file.', 'danger');
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        showToast('Image must be under 5 MB.', 'danger');
        return;
      }
      const reader = new FileReader();
      reader.onload = e => {
        const preview = input.closest('.avatar-upload-area')?.querySelector('.avatar-preview img') ||
                        input.closest('.avatar-upload-area')?.querySelector('.avatar-preview');
        if (preview && preview.tagName === 'IMG') {
          preview.src = e.target.result;
        } else if (preview) {
          preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
        }
      };
      reader.readAsDataURL(file);
    });
  });
}


function initTableSearch() {
  document.querySelectorAll('[data-search-table]').forEach(input => {
    const tableId = input.dataset.searchTable;
    const table   = document.getElementById(tableId);
    if (!table) return;
    const rows = table.querySelectorAll('tbody tr');

    input.addEventListener('input', () => {
      const q = input.value.toLowerCase().trim();
      rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  });
}


function validateForm(formEl) {
  let valid = true;
  formEl.querySelectorAll('[required]').forEach(field => {
    const group = field.closest('.form-group');
    const errEl = group?.querySelector('.form-error');
    if (!field.value.trim()) {
      valid = false;
      field.style.borderColor = 'var(--danger)';
      if (errEl) errEl.style.display = 'flex';
    } else {
      field.style.borderColor = '';
      if (errEl) errEl.style.display = 'none';
    }
  });
  return valid;
}

function initFormValidation() {
  document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', e => {
      if (!validateForm(form)) {
        e.preventDefault();
        showToast('Please fill all required fields.', 'danger');
      }
    });
  });
}


function confirmAction(message, callback) {
  if (window.confirm(message || 'Are you sure?')) {
    callback && callback();
  }
}


function getStatusBadge(status) {
  const map = {
    'Paid':       'badge-paid',
    'Pending':    'badge-pending',
    'Overdue':    'badge-overdue',
    'Vacant':     'badge-vacant',
    'Occupied':   'badge-occupied',
    'Active':     'badge-active',
    'Inactive':   'badge-inactive',
    'In Progress':'badge-progress',
    'Resolved':   'badge-resolved',
    'New':        'badge-new',
  };
  return map[status] || 'badge-info';
}


document.addEventListener('DOMContentLoaded', () => {
  if (window.rmsCsrfToken) {
    document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(form => {
      if (!form.querySelector('input[name="csrf_token"]')) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = window.rmsCsrfToken;
        form.appendChild(input);
      }
    });
  }
  initSidebar();
  initNavHighlight();
  initTabs();
  initModals();
  initDropdowns();
  initCounters();
  initAvatarUpload();
  initTableSearch();
  initFormValidation();
});
