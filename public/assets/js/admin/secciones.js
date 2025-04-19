/**
 * Funciones para la gestión de secciones de la página de inicio
 */

// Mostrar/ocultar el campo de categoría según el tipo de sección
function mostrarCampoCategoria() {
    const tipoSelect = document.getElementById('tipo');
    const categoriaGroup = document.getElementById('categoriaGroup');
    
    if (tipoSelect && categoriaGroup) {
        if (tipoSelect.value === 'categoria') {
            categoriaGroup.style.display = 'block';
        } else {
            categoriaGroup.style.display = 'none';
        }
    }
}

// Mostrar formulario para crear sección
function mostrarFormularioCrear() {
    toggleElement('formCrearSeccion', true);
    toggleElement('formEditarSeccion', false);
}

// Ocultar formulario para crear sección
function ocultarFormularioCrear() {
    toggleElement('formCrearSeccion', false);
}

// Mostrar formulario para editar sección
function editarSeccion(id, titulo, tipo, categoriaId, activo) {
    document.getElementById('editSeccionId').value = id;
    document.getElementById('editTituloMostrar').value = titulo;
    document.getElementById('editActivo').checked = activo === 1;
    
    const categoriaGroup = document.getElementById('editCategoriaGroup');
    
    if (tipo === 'categoria') {
        categoriaGroup.style.display = 'block';
        if (categoriaId) {
            document.getElementById('editCategoriaId').value = categoriaId;
        }
    } else {
        categoriaGroup.style.display = 'none';
    }
    
    toggleElement('formEditarSeccion', true);
    toggleElement('formCrearSeccion', false);
}

// Ocultar formulario para editar sección
function ocultarFormularioEditar() {
    toggleElement('formEditarSeccion', false);
}

// Inicializar la página de secciones
function inicializarPaginaSecciones() {
    // Inicializar lista ordenable
    inicializarListaOrdenable('listaSecciones', function() {
        // Actualizar los inputs ocultos con el nuevo orden
        const lista = document.getElementById('listaSecciones');
        const ordenInputs = lista.querySelectorAll('input[name="orden[]"]');
        ordenInputs.forEach(function(input) {
            input.value = input.closest('li').dataset.id;
        });
    });
    
    // Añadir eventos a selectores
    const tipoSelect = document.getElementById('tipo');
    if (tipoSelect) {
        tipoSelect.addEventListener('change', mostrarCampoCategoria);
        // Ejecutar al inicio para establecer estado inicial
        mostrarCampoCategoria();
    }
}

// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', inicializarPaginaSecciones);