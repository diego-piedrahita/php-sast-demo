// Validacion basica en cliente (no reemplaza la validacion en el servidor)
document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('form');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var requiredInputs = form.querySelectorAll('[required]');
            var valid = true;
            requiredInputs.forEach(function (input) {
                if (!input.value.trim()) {
                    valid = false;
                }
            });
            if (!valid) {
                event.preventDefault();
                alert('Por favor completa todos los campos obligatorios.');
            }
        });
    });
});
