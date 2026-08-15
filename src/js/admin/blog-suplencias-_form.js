/* blog-suplencias-_form
   El buscador de colaboradores (`[data-picker]`) vivía aquí, atado por una constante a
   /dashboard/suplencias/buscar-colaboradores. Se extrajo a admin-picker.js cuando el
   editor de horario y los intercambios necesitaron el mismo componente con otro
   endpoint y otros guards; el de Suplencias sigue siendo su valor por defecto, así que
   el HTML de _form.php y crear.php no cambió.

   Este archivo se queda como señal: si buscas la lógica del picker, está en
   src/js/admin/admin-picker.js. */
