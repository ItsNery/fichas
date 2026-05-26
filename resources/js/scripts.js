// =========================================================
// 1. LOADER (Igual que tu original)
// =========================================================
window.onload = function () {
    let loaderSection = document.querySelector(".loader-section");
    if (loaderSection) {
        loaderSection.classList.add("loaded");
    }
};

document.addEventListener("DOMContentLoaded", function () {
    
    // =========================================================
    // 2. LÓGICA DEL MENÚ DE NAVEGACIÓN
    // =========================================================
    
    const menuButton = document.getElementById("hamburger-menu");
    const mainNav = document.getElementById("main-nav");

    // A) ABRIR / CERRAR MENÚ PRINCIPAL (Móvil)
    if (menuButton && mainNav) {
        menuButton.addEventListener("click", function () {
            mainNav.classList.toggle("active");
            // Opcional: Animar el icono de hamburguesa
            menuButton.classList.toggle("open");
        });
    }

    // B) DROPDOWNS EN MÓVIL (Solución al problema de cerrarse al scrollear)
    // Seleccionamos los enlaces que activan menús desplegables
    const dropdownToggles = document.querySelectorAll('.dropdown > a, .dropdown-nested > a');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            // Esta lógica SOLO aplica en pantallas móviles/tablets
            if (window.innerWidth <= 991) {
                e.preventDefault(); // Evita navegar
                e.stopPropagation(); // Evita que el clic suba al padre

                const parentLi = this.parentElement;
                
                // Lógica de acordeón: Cerrar hermanos si se abre uno nuevo (Opcional, pero recomendado)
                const siblings = parentLi.parentElement.children;
                for (let sibling of siblings) {
                    if (sibling !== parentLi) {
                        sibling.classList.remove('active');
                    }
                }

                // Abrir o cerrar el actual
                parentLi.classList.toggle('active');
            }
        });
    });

    // =========================================================
    // 3. EFECTO STICKY BAR (Sombra al pegar al techo)
    // =========================================================
    // Usamos IntersectionObserver porque es más eficiente que 'scroll' para position: sticky
    
    const nav = document.getElementById("main-nav");
    
    if (nav) {
        // Creamos un elemento invisible justo antes del nav para vigilarlo
        const sentinel = document.createElement('div');
        sentinel.setAttribute('aria-hidden', true);
        nav.parentNode.insertBefore(sentinel, nav);

        const stickyObserver = new IntersectionObserver((entries) => {
            // Si el sentinel sale de pantalla (scrolleamos abajo), activamos la sombra
            if (!entries[0].isIntersecting) {
                nav.classList.add('is-sticky');
            } else {
                nav.classList.remove('is-sticky');
            }
        }, {
            threshold: 0,
            rootMargin: "-1px 0px 0px 0px" // Ajuste fino para disparar exacto
        });

        stickyObserver.observe(sentinel);
    }

    // =========================================================
    // 4. SCROLL TOP & PROGRESS CIRCLE (Tu lógica original)
    // =========================================================

    const scrollTopBtn = document.getElementById("scrollTopBtn");
    const progressCircle = document.querySelector(".progress-ring__circle");

    // Validamos existencia para no causar errores
    if (!scrollTopBtn || !progressCircle) {
        return; // Salimos si falta el botón o el círculo
    }

    // Configuración inicial del círculo
    const radius = progressCircle.r.baseVal.value;
    const circumference = 2 * Math.PI * radius;
    progressCircle.style.strokeDasharray = `${circumference} ${circumference}`;
    progressCircle.style.strokeDashoffset = circumference;

    // Listener de Scroll para Botón y Círculo
    window.addEventListener("scroll", () => {
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;

        // A) Botón "Volver Arriba"
        if (currentScroll > 300) {
            scrollTopBtn.classList.add("show");
        } else {
            scrollTopBtn.classList.remove("show");
        }

        // B) Círculo de Progreso
        const progress = currentScroll / scrollHeight;
        const dashOffset = circumference - progress * circumference;
        progressCircle.style.strokeDashoffset = dashOffset;
    });

    // Acción Click del Botón
    scrollTopBtn.addEventListener("click", () => {
        window.scrollTo({
            top: 0,
            behavior: "smooth",
        });
    });
});