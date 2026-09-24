/* ======================================================
   K'Educ — Module Témoignages (site public)
   Réactions + "Voir plus" (AJAX)
   ====================================================== */

(function () {
    'use strict';

    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    var token = tokenMeta ? tokenMeta.getAttribute('content') : '';

    function post(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });
    }

    /* ---------- Réactions ---------- */

    var reactionsBlock = document.getElementById('temo-reactions');
    var reactionButtons = reactionsBlock ? reactionsBlock.querySelectorAll('.temo-reaction-btn') : [];

    reactionButtons.forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var url = reactionsBlock.dataset.url;
            var reaction = this.dataset.reaction;

            try {
                var response = await post(url, { reaction: reaction });
                if (!response.ok) return;
                var data = await response.json();

                reactionButtons.forEach(function (b) {
                    b.classList.remove('is-active');
                });

                if (data.current) {
                    var activeBtn = reactionsBlock.querySelector('.temo-reaction-btn[data-reaction="' + data.current + '"]');
                    if (activeBtn) activeBtn.classList.add('is-active');
                }

                reactionButtons.forEach(function (b) {
                    var key = b.dataset.reaction;
                    var el = reactionsBlock.querySelector('.temo-reaction-count[data-count-for="' + key + '"]');
                    if (el) {
                        var count = (data.counts && data.counts[key]) ? data.counts[key] : 0;
                        el.textContent = count > 0 ? count : '';
                    }
                });

                var totalEl = document.getElementById('temo-total-reactions');
                if (totalEl) {
                    totalEl.textContent = data.total + ' réaction' + (data.total > 1 ? 's' : '');
                }

                var scoreEl = document.querySelector('[data-score]');
                if (scoreEl && typeof data.score !== 'undefined') {
                    scoreEl.textContent = data.score;
                }
            } catch (err) {
                console.error('Erreur réaction :', err);
            }
        });
    });

    /* ---------- Voir plus ---------- */

    var loadMoreBtn = document.getElementById('temoignage-voir-plus');

    if (loadMoreBtn) {
        var listEl = document.getElementById('temoignage-list');
        var baseUrl = loadMoreBtn.dataset.url;
        var page = parseInt(loadMoreBtn.dataset.page, 10) || 2;

        loadMoreBtn.addEventListener('click', async function () {
            var original = loadMoreBtn.innerHTML;
            loadMoreBtn.classList.add('is-loading');
            loadMoreBtn.disabled = true;
            loadMoreBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Chargement...';

            try {
                var separator = baseUrl.indexOf('?') === -1 ? '?' : '&';
                var response = await fetch(baseUrl + separator + 'page=' + page + '&fragment=1', {
                    headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }
                });

                var html = await response.text();

                if (!html || html.trim() === '') {
                    loadMoreBtn.remove();
                    return;
                }

                var wrap = document.createElement('div');
                wrap.innerHTML = html;

                var row = wrap.querySelector('.row');
                if (row) {
                    row.classList.add('mt-4');
                    listEl.appendChild(row);
                } else {
                    loadMoreBtn.remove();
                    return;
                }

                page++;
            } catch (err) {
                console.error('Erreur chargement :', err);
            } finally {
                if (loadMoreBtn.isConnected) {
                    loadMoreBtn.classList.remove('is-loading');
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.innerHTML = original;
                }
            }
        });
    }
})();
