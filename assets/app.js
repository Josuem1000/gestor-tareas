// Gestor de Tareas - interacción del lado del cliente
document.addEventListener('DOMContentLoaded', () => {
  const modalTarea = document.getElementById('modalTarea');
  const form = document.getElementById('formTarea');

  // Abrir modales
  document.querySelectorAll('[data-abrir]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = document.getElementById(btn.dataset.abrir);
      if (modal === modalTarea) {
        form.reset();
        form.id.value = 0;
        document.getElementById('tituloModal').textContent = 'Nueva tarea';
      }
      modal.showModal();
      modal.querySelector('input:not([type=hidden])')?.focus();
    });
  });

  // Cerrar modales
  document.querySelectorAll('[data-cerrar]').forEach(btn =>
    btn.addEventListener('click', () => btn.closest('dialog').close()));
  document.querySelectorAll('dialog').forEach(d =>
    d.addEventListener('click', ev => { if (ev.target === d) d.close(); }));

  // Editar: rellena el formulario con los datos de la tarea
  document.querySelectorAll('[data-editar]').forEach(btn => {
    btn.addEventListener('click', () => {
      const t = JSON.parse(btn.dataset.editar);
      form.id.value = t.id;
      form.titulo.value = t.titulo;
      form.descripcion.value = t.descripcion || '';
      form.prioridad.value = t.prioridad;
      form.estado.value = t.estado;
      form.fecha_limite.value = t.fecha_limite || '';
      form.categoria_id.value = t.categoria_id || '';
      document.getElementById('tituloModal').textContent = 'Editar tarea';
      modalTarea.showModal();
    });
  });

  // Confirmación antes de eliminar
  document.querySelectorAll('form[data-confirmar]').forEach(f =>
    f.addEventListener('submit', ev => {
      if (!confirm(f.dataset.confirmar)) ev.preventDefault();
    }));

  // Validación simple del título
  form.addEventListener('submit', ev => {
    if (!form.titulo.value.trim()) {
      ev.preventDefault();
      form.titulo.focus();
    }
  });

  // Oculta la alerta después de unos segundos
  const alerta = document.querySelector('.alerta');
  if (alerta) setTimeout(() => alerta.classList.add('alerta--oculta'), 3500);
});
