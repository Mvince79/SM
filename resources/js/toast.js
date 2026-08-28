// Global toast utility — shared across all pages (loaded via app.js).
// Defined once here so views/components call window.showToast(...) without
// copy-pasting the implementation. Lazily creates #toast-container if the
// current page does not already have one, so it works even on pages that
// never declared a container (prevents "showToast is not defined").
window.showToast = function (message, type) {
    type = type || 'info';

    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.top = '1rem';
        container.style.left = '50%';
        container.style.transform = 'translateX(-50%)';
        container.style.zIndex = '2147483647';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '0.5rem';
        container.style.alignItems = 'center';
        container.style.pointerEvents = 'none';
        document.body.appendChild(container);
    }

    const palette = {
        success: '#059669', // Tailwind emerald-600
        error: '#e11d48',   // Tailwind rose-600
        info: '#1e293b',    // Tailwind slate-800
    };
    const bg = palette[type] || palette.info;

    const el = document.createElement('div');
    el.style.backgroundColor = bg;
    el.style.color = '#ffffff';
    el.style.fontSize = '12px';
    el.style.fontWeight = '700';
    el.style.padding = '0.75rem 1rem';
    el.style.borderRadius = '0.75rem';
    el.style.boxShadow = '0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1)';
    el.style.maxWidth = '90vw';
    el.style.textAlign = 'center';
    el.style.pointerEvents = 'auto';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-8px)';
    el.style.transition = 'opacity 200ms ease, transform 200ms ease';
    el.innerHTML = String(message).split('\n').join('<br>');
    container.appendChild(el);

    requestAnimationFrame(function () {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
    });

    setTimeout(function () {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-8px)';
        setTimeout(function () {
            if (el.parentNode) el.parentNode.removeChild(el);
        }, 260);
    }, 2600);
};
