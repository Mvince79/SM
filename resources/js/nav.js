
document.addEventListener('DOMContentLoaded', () => {
    const menu = document.getElementById('mobile-menu');
    const btn = document.getElementById('mobile-hamburger');
    const bar1 = document.getElementById('bar1');
    const bar2 = document.getElementById('bar2');
    const bar3 = document.getElementById('bar3');

    if (!menu || !btn) return;

    menu.style.transition = 'max-height 0.3s cubic-bezier(0.4,0,0.2,1), opacity 0.25s ease';
    menu.style.overflow = 'hidden';


    btn.addEventListener('click', () => {
        const isOpen = menu.dataset.open === 'true';

        if (isOpen) {
            menu.style.maxHeight = menu.scrollHeight + 'px';

            requestAnimationFrame(() => requestAnimationFrame(() => {
                menu.style.maxHeight = '0px';
                menu.style.opacity = '0';
            }));

            setTimeout(() => {
                menu.classList.add('hidden');
                menu.dataset.open = 'false';
            }, 300);

            bar1.style.transform = '';
            bar2.style.opacity = '1';
            bar2.style.width = '1.25rem';
            bar3.style.transform = '';
            btn.setAttribute('aria-expanded', 'false');

        } else {
            menu.classList.remove('hidden');
            menu.dataset.open = 'true';
            menu.style.maxHeight = '0px';
            menu.style.opacity = '0';

            requestAnimationFrame(() => requestAnimationFrame(() => {
                menu.style.maxHeight = menu.scrollHeight + 'px';
                menu.style.opacity = '1';
            }));

            setTimeout(() => {
                menu.style.maxHeight = '';
            }, 320);

            bar1.style.transform = 'translateY(7px) rotate(45deg)';
            bar2.style.opacity = '0';
            bar2.style.width = '0';
            bar3.style.transform = 'translateY(-7px) rotate(-45deg)';
            btn.setAttribute('aria-expanded', 'true');
        }
    });

    document.addEventListener('click', (e) => {
        if (menu.dataset.open === 'true' && !menu.contains(e.target) && !btn.contains(e.target)) {
            btn.click();
        }
    });
});