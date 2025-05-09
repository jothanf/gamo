document.addEventListener('DOMContentLoaded', () => {
    // Sliders para distribución de puntos
    const sliders = document.querySelectorAll('.puntos-slider');
    const puntosRestantesElement = document.getElementById('puntos-restantes');
    const totalPuntosDisponibles = 40;
    
    // Inicializar valores
    function actualizarValores() {
        sliders.forEach(slider => {
            const valueElement = document.getElementById(`${slider.id}-value`);
            valueElement.textContent = slider.value;
        });
        
        actualizarPuntosRestantes();
    }
    
    // Calcular puntos restantes
    function actualizarPuntosRestantes() {
        let totalUsado = 0;
        sliders.forEach(slider => {
            totalUsado += parseInt(slider.value);
        });
        
        const puntosRestantes = totalPuntosDisponibles - totalUsado;
        puntosRestantesElement.textContent = puntosRestantes;
        
        // Marcar error si hay puntos negativos disponibles
        if (puntosRestantes < 0) {
            puntosRestantesElement.classList.add('error');
        } else {
            puntosRestantesElement.classList.remove('error');
        }
    }
    
    // Event listeners para los sliders
    sliders.forEach(slider => {
        slider.addEventListener('input', function() {
            const valueElement = document.getElementById(`${this.id}-value`);
            valueElement.textContent = this.value;
            actualizarPuntosRestantes();
        });
    });
    
    // Validación del formulario
    const form = document.querySelector('.registro-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let totalUsado = 0;
            sliders.forEach(slider => {
                totalUsado += parseInt(slider.value);
            });
            
            if (totalUsado !== totalPuntosDisponibles) {
                e.preventDefault();
                alert(`Debes distribuir exactamente ${totalPuntosDisponibles} puntos entre tus habilidades. Actualmente has asignado ${totalUsado} puntos.`);
            }
        });
    }
    
    // Selección de clase de avatar
    const claseRadios = document.querySelectorAll('input[name="clase"]');
    claseRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Aquí podríamos ajustar valores sugeridos según la clase seleccionada
            const clase = this.value;
            
            if (clase === 'cuidador') {
                // Sugerir distribución para cuidador
                document.getElementById('autocuidado').value = 8;
                document.getElementById('cuidado_hogar').value = 14;
                document.getElementById('cuidado_otro').value = 12;
                document.getElementById('responsabilidad').value = 6;
                actualizarValores();
            } else if (clase === 'sabio') {
                // Sugerir distribución para sabio
                document.getElementById('autocuidado').value = 10;
                document.getElementById('cuidado_hogar').value = 6;
                document.getElementById('cuidado_otro').value = 10;
                document.getElementById('responsabilidad').value = 14;
                actualizarValores();
            } else if (clase === 'explorador') {
                // Sugerir distribución para explorador
                document.getElementById('autocuidado').value = 12;
                document.getElementById('cuidado_hogar').value = 8;
                document.getElementById('cuidado_otro').value = 14;
                document.getElementById('responsabilidad').value = 6;
                actualizarValores();
            }
        });
    });
    
    // Inicializar valores al cargar la página
    actualizarValores();
}); 