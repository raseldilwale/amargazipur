document.addEventListener('DOMContentLoaded', () => {
  // Handle visitor auth prompt for protected actions
  document.querySelectorAll('[data-require-auth="true"]').forEach((el) => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      const modalEl = document.getElementById('agAuthPromptModal');
      if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
      } else {
        window.location.href = el.getAttribute('data-login-url') || '/login';
      }
    });
  });

  // AJAX Like toggle when authenticated
  document.querySelectorAll('[data-like-form]').forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = form.querySelector('button');
      const countSpan = form.querySelector('[data-like-count]');
      try {
        const res = await fetch(form.action, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: new FormData(form),
        });
        if (res.status === 401) {
          const modalEl = document.getElementById('agAuthPromptModal');
          if (modalEl && typeof bootstrap !== 'undefined') {
            new bootstrap.Modal(modalEl).show();
          }
          return;
        }
        const data = await res.json();
        if (typeof data.count !== 'undefined' && countSpan) {
          countSpan.textContent = data.count;
        }
        if (btn) {
          btn.classList.toggle('btn-success', !!data.liked);
          btn.classList.toggle('btn-outline-secondary', !data.liked);
        }
      } catch (err) {
        form.submit();
      }
    });
  });
});
