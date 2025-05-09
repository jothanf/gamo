document.addEventListener('DOMContentLoaded', function() {
  // Añadir clase para elementos que queremos animar al hacer scroll
  const sections = document.querySelectorAll('section');
  sections.forEach(section => {
    const elements = section.querySelectorAll('h2, .features, .feature-card, .mockups, .mockup, form');
    elements.forEach(el => {
      el.classList.add('scroll-reveal');
    });
  });
  
  // Función para detectar elementos en viewport
  function checkReveal() {
    const reveals = document.querySelectorAll('.scroll-reveal');
    const windowHeight = window.innerHeight;
    
    reveals.forEach(reveal => {
      const revealTop = reveal.getBoundingClientRect().top;
      const revealPoint = 150;
      
      if (revealTop < windowHeight - revealPoint) {
        reveal.classList.add('revealed');
      }
    });
  }
  
  // Ejecución inicial y al hacer scroll
  checkReveal();
  window.addEventListener('scroll', checkReveal);
  
  // Animación de navegación suave
  document.querySelectorAll('nav a, .btn-cta').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      if (this.getAttribute('href').startsWith('#')) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
          window.scrollTo({
            top: targetElement.offsetTop,
            behavior: 'smooth'
          });
        }
      }
    });
  });
}); 