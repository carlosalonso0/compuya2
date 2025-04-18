</main>
    
    <script>
        // Funciones comunes para el panel de administración
        function confirmarEliminacion(mensaje) {
            return confirm(mensaje || '¿Está seguro que desea eliminar este elemento?');
        }

        // Función para mostrar o ocultar alertas
        function mostrarAlerta(mensaje, tipo) {
            const alertaExistente = document.querySelector('.alert');
            if (alertaExistente) {
                alertaExistente.remove();
            }
            
            const alerta = document.createElement('div');
            alerta.className = `alert alert-${tipo}`;
            alerta.textContent = mensaje;
            
            const contenido = document.querySelector('.admin-content');
            contenido.insertBefore(alerta, contenido.firstChild);
            
            // Ocultar después de 5 segundos
            setTimeout(() => {
                alerta.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>