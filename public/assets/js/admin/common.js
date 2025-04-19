/**
 * Funciones comunes para el panel de administración
 */

// Confirmar eliminación de elementos
function confirmarEliminacion(mensaje) {
    return confirm(mensaje || '¿Está seguro que desea eliminar este elemento?');
}

// Mostrar alerta temporal
function mostrarAlerta(mensaje, tipo, duracion = 5000) {
    const alertaExistente = document.querySelector('.alert');
    if (alertaExistente) {
        alertaExistente.remove();
    }
    
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo}`;
    alerta.textContent = mensaje;
    
    const contenido = document.querySelector('.admin-content');
    contenido.insertBefore(alerta, contenido.firstChild);
    
    // Ocultar después del tiempo especificado
    setTimeout(() => {
        alerta.style.display = 'none';
    }, duracion);
}

// Vista previa de imágenes
function mostrarVistaPrevia(inputFile, contenedor, maxWidth = 200, maxHeight = 200) {
    const input = document.getElementById(inputFile);
    const container = document.getElementById(contenedor);
    
    if (!input || !container) return;
    
    input.addEventListener('change', function(e) {
        // Limpiar contenedor
        container.innerHTML = '';
        
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.maxWidth = maxWidth + 'px';
                img.style.maxHeight = maxHeight + 'px';
                img.style.objectFit = 'contain';
                img.style.border = '1px solid #ddd';
                img.style.borderRadius = '4px';
                img.style.padding = '5px';
                img.style.marginTop = '10px';
                
                container.appendChild(img);
                
                const texto = document.createElement('p');
                texto.textContent = 'Vista previa de la imagen. Se actualizará al guardar.';
                texto.style.marginTop = '5px';
                container.appendChild(texto);
            };
            
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// Funciones para listas ordenables (drag & drop)
function inicializarListaOrdenable(idLista, callbackOrden) {
    const lista = document.getElementById(idLista);
    if (!lista) return;
    
    let draggedItem = null;
    
    lista.querySelectorAll('li').forEach(function(item) {
        item.setAttribute('draggable', 'true');
        
        item.addEventListener('dragstart', function() {
            draggedItem = this;
            setTimeout(() => this.style.opacity = '0.5', 0);
        });
        
        item.addEventListener('dragend', function() {
            draggedItem = null;
            this.style.opacity = '1';
            
            // Actualizar orden si hay callback
            if (typeof callbackOrden === 'function') {
                callbackOrden();
            }
        });
        
        item.addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        
        item.addEventListener('dragenter', function(e) {
            e.preventDefault();
            this.style.background = '#f5f5f5';
        });
        
        item.addEventListener('dragleave', function() {
            this.style.background = '';
        });
        
        item.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.background = '';
            if (draggedItem !== this) {
                const items = Array.from(lista.querySelectorAll('li'));
                const indexDragged = items.indexOf(draggedItem);
                const indexTarget = items.indexOf(this);
                
                if (indexDragged < indexTarget) {
                    lista.insertBefore(draggedItem, this.nextElementSibling);
                } else {
                    lista.insertBefore(draggedItem, this);
                }
            }
        });
    });
}

// Función para mostrar/ocultar elementos del DOM
function toggleElement(elementId, visible) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.display = visible ? 'block' : 'none';
    }
}