/**
 * Bibliothèque Numérique — K'Educ
 * JavaScript module for library interactions
 */

document.addEventListener('DOMContentLoaded', function() {

    // === Star Rating ===
    initStarRating();

    // === File Preview ===
    initFilePreview();

    // === Tags Input ===
    initTagsInput();

    // === Confirm Actions ===
    initConfirmActions();
});

function initStarRating() {
    document.querySelectorAll('.doc-card-rating, .star-rating').forEach(function(container) {
        var currentRating = parseInt(container.dataset.current || 0);
        var documentId = container.dataset.documentId;
        var stars = container.querySelectorAll('.star');

        if (!stars.length) return;

        stars.forEach(function(star) {
            star.addEventListener('click', function() {
                var note = parseInt(this.dataset.note);
                submitRating(documentId, note);
            });

            star.addEventListener('mouseenter', function() {
                var rating = parseInt(this.dataset.note);
                highlightStars(stars, rating);
            });
        });

        container.addEventListener('mouseleave', function() {
            highlightStars(stars, currentRating);
        });

        // Initial highlight
        highlightStars(stars, currentRating);
    });
}

function highlightStars(stars, rating) {
    stars.forEach(function(s) {
        var note = parseInt(s.dataset.note);
        if (note <= rating) {
            s.classList.add('text-warning');
            s.classList.remove('text-muted');
        } else {
            s.classList.remove('text-warning');
            s.classList.add('text-muted');
        }
    });
}

function submitRating(documentId, note) {
    var form = document.createElement('form');
    form.method = 'POST';

    var noteUrl = window.BIBLIO_NOTE_URL;
    if (!noteUrl) {
        console.error('Note URL not configured');
        return;
    }
    form.action = noteUrl.replace('__ID__', documentId);

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.content : '';

    if (!csrfToken) {
        var csrfInput = document.querySelector('input[name="_token"]');
        csrfToken = csrfInput ? csrfInput.value : '';
    }

    if (!csrfToken) {
        console.error('CSRF token not found');
        return;
    }

    var csrfField = document.createElement('input');
    csrfField.type = 'hidden';
    csrfField.name = '_token';
    csrfField.value = csrfToken;
    form.appendChild(csrfField);

    var noteField = document.createElement('input');
    noteField.type = 'hidden';
    noteField.name = 'note';
    noteField.value = note;
    form.appendChild(noteField);

    document.body.appendChild(form);
    form.submit();
}

function initFilePreview() {
    var fileInput = document.getElementById('fichierInput');
    if (!fileInput) return;

    fileInput.addEventListener('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;

        var preview = document.getElementById('fichierPreview');
        var nameEl = document.getElementById('fichierName');
        var sizeEl = document.getElementById('fichierSize');

        if (preview) {
            preview.classList.remove('d-none');
        }

        if (nameEl) {
            nameEl.textContent = file.name;
        }

        if (sizeEl) {
            var size = (file.size / 1024 / 1024).toFixed(2);
            sizeEl.textContent = '(' + size + ' Mo)';
        }
    });
}

function initTagsInput() {
    var tagsInput = document.getElementById('tagsInput');
    if (!tagsInput) return;

    tagsInput.addEventListener('input', function() {
        var tags = this.value.split(',').map(function(t) { return t.trim(); }).filter(function(t) { return t.length > 0; });
        var form = this.closest('form');
        form.querySelectorAll('input[name="tags[]"]').forEach(function(el) { el.remove(); });
        tags.forEach(function(tag) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tags[]';
            input.value = tag;
            form.appendChild(input);
        });
    });
}

function initConfirmActions() {
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
}
