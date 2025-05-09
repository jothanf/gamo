document.addEventListener('DOMContentLoaded', () => {
    // Toggle para el menú móvil
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            mobileMenu.classList.toggle('active');
        });
    }
    
    // Dropdown toggle para móvil
    const mobileDropdownToggles = document.querySelectorAll('.mobile-dropdown-toggle');
    
    mobileDropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const parent = toggle.parentElement;
            parent.classList.toggle('active');
        });
    });
    
    // Cerrar menú móvil cuando se hace clic en un enlace
    const mobileLinks = document.querySelectorAll('.mobile-nav-links a:not(.mobile-dropdown-toggle)');
    
    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (menuToggle && mobileMenu) {
                menuToggle.classList.remove('active');
                mobileMenu.classList.remove('active');
            }
        });
    });
    
    // Despliegue automático de dropdown en desktop
    // No es necesario agregar listeners para hover en desktop ya que se maneja con CSS
    
    // Para soportar toque en dispositivos táctiles
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const parent = toggle.parentElement;
            
            // Cerrar otros dropdowns
            document.querySelectorAll('.dropdown.active').forEach(dropdown => {
                if (dropdown !== parent) {
                    dropdown.classList.remove('active');
                }
            });
            
            // Toggle el actual
            parent.classList.toggle('active');
        });
    });
    
    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown.active').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
}); 