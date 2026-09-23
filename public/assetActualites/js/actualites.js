/* ======================================================
   K'Educ — Module Actualités (site public)
   Réactions + Partage
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

    var reactionButtons = document.querySelectorAll('.actu-reaction-btn');

    reactionButtons.forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var url = this.dataset.url;
            var reaction = this.dataset.reaction;

            try {
                var response = await post(url, { reaction: reaction });
                if (!response.ok) return;
                var data = await response.json();

                reactionButtons.forEach(function (b) {
                    b.classList.remove('active');
                });

                if (data.current) {
                    var activeBtn = document.querySelector('.actu-reaction-btn[data-reaction="' + data.current + '"]');
                    if (activeBtn) activeBtn.classList.add('active');
                }

                reactionButtons.forEach(function (b) {
                    var key = b.dataset.reaction;
                    var el = document.querySelector('.actu-reaction-count[data-count-for="' + key + '"]');
                    if (el) {
                        var count = (data.counts && data.counts[key]) ? data.counts[key] : 0;
                        el.textContent = count > 0 ? count : '';
                    }
                });

                var totalEl = document.getElementById('actu-total-reactions');
                if (totalEl) {
                    totalEl.textContent = data.total + ' réaction' + (data.total > 1 ? 's' : '');
                }
            } catch (err) {
                console.error('Erreur réaction :', err);
            }
        });
    });

    /* ---------- Partage ---------- */

    document.querySelectorAll('.actu-share-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var canal = this.dataset.canal;
            var recordUrl = this.dataset.url;

            try {
                await post(recordUrl, { canal: canal });
            } catch (err) {
                // Échec d'enregistrement du compteur : le partage fonctionne quand même.
            }

            if (canal === 'copier') {
                var copyUrl = this.dataset.copyUrl;
                var original = this.innerHTML;

                try {
                    await navigator.clipboard.writeText(copyUrl);
                    this.innerHTML = '<i class="bi bi-check-lg me-1"></i>Lien copié';
                    setTimeout(function () { btn.innerHTML = original; }, 2000);
                } catch (err) {
                    window.prompt('Copiez le lien :', copyUrl);
                }
                return;
            }

            var shareUrl = this.dataset.shareUrl;
            window.open(shareUrl, '_blank', 'width=600,height=500,noopener');
        });
    });
})();
