// ============================================
// PLANER SESI - JAVASCRIPT PRINCIPAL
// ============================================

// Menu Mobile
function toggleMenu() {
    const nav = document.getElementById('navMobile');
    const opened = nav.classList.toggle('active');
    const button = document.querySelector('.menu-mobile');
    if (button) button.setAttribute('aria-expanded', opened ? 'true' : 'false');
}

// Fechar menu ao clicar fora
document.addEventListener('click', function(e) {
    const nav = document.getElementById('navMobile');
    const btn = document.querySelector('.menu-mobile');
    if (nav && btn && !nav.contains(e.target) && !btn.contains(e.target)) {
        nav.classList.remove('active');
    }
});

// Animação nos cards ao scroll
function animarElementos() {
    const elementos = document.querySelectorAll('.jogo-card, .placar-card, .dash-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.5s ease forwards';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    elementos.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        observer.observe(el);
    });
}


// Inicializar animações
document.addEventListener('DOMContentLoaded', function() {
    animarElementos();
    
    // Auto-hide alertas
    const alertas = document.querySelectorAll('.alerta');
    alertas.forEach(alerta => {
        setTimeout(() => {
            alerta.style.transition = 'opacity 0.5s';
            alerta.style.opacity = '0';
            setTimeout(() => alerta.remove(), 500);
        }, 5000);
    });
});

// Scroll suave para âncoras
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href === '#') return;
        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// Confirmar ações
function confirmar(msg, callback) {
    if (confirm(msg)) callback();
}