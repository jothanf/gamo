document.addEventListener('DOMContentLoaded', function() {
  // Menú móvil
  const menuToggle = document.querySelector('.mobile-menu-toggle');
  const mainNav = document.querySelector('.main-nav');
  
  if (menuToggle && mainNav) {
    menuToggle.addEventListener('click', function() {
      mainNav.classList.toggle('active');
    });
  }
  
  // Formulario de contacto
  const contactForm = document.querySelector('.contact-form');
  
  if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Aquí podrías agregar la lógica para enviar el formulario
      // mediante AJAX o mostrar un mensaje de confirmación
      
      alert('¡Gracias por contactarnos! Te responderemos a la brevedad.');
      contactForm.reset();
    });
  }
}); 