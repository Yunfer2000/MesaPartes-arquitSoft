/*
 * Comportamiento compartido del formulario público (CU05) y del registro presencial (CU17):
 * datos del solicitante (persona natural o jurídica, formatos, búsqueda por documento o RUC),
 * avisos al salir de un campo y zona de archivo (PDF o ZIP, máximo 10 MB).
 * Usa los id de resources/views/partials/formulario/solicitante.blade.php y archivo.blade.php.
 * El servidor vuelve a validar todo: esto solo avisa antes de enviar.
 */
window.Formulario = (() => {
    const $ = (id) => document.getElementById(id);
    const MAXIMO = 10 * 1024 * 1024;

    const tamano = (b) => b < 1024 * 1024 ? Math.max(1, Math.round(b / 1024)) + ' KB' : (b / 1024 / 1024).toFixed(1).replace('.', ',') + ' MB';

    /**
     * Datos del solicitante.
     * opciones.buscarUrl  ruta que responde JSON con los datos del documento
     * opciones.contacto   true: también rellena celular y correo (solo el personal recibe esos datos completos)
     * opciones.textos     mensajes de la búsqueda (para hablarle al ciudadano o al operador)
     */
    function solicitante(opciones) {
        const t = Object.assign({
            escribeDocumento: 'Escribe tu número de documento y presiona Buscar.',
            noEncontrado: 'No encontramos datos con ese documento. <b>Escríbelos a mano</b>, solo la primera vez.',
            encontrado: 'Datos encontrados',
            errorDocumento: 'No se pudo buscar ahora. Puedes escribir tus datos a mano.',
            escribeRuc: 'Escribe los 11 dígitos del RUC y presiona Buscar.',
            rucNoEncontrado: 'No encontramos ese RUC. <b>Escribe la razón social</b>, solo la primera vez.',
            rucEncontrado: 'Entidad encontrada',
            errorRuc: 'No se pudo buscar ahora. Puedes escribir la razón social a mano.',
        }, opciones.textos || {});
        const personas = document.querySelectorAll('input[name="tipo_persona"]'), tipo = $('tipo_doc_identidad'), numero = $('numero_documento'), resultado = $('resultado');
        const datos = ['nombres', 'apellido_paterno', 'apellido_materno'].concat(opciones.contacto ? ['celular', 'correo'] : []);
        const esJuridica = () => document.querySelector('input[name="tipo_persona"]:checked')?.value === 'juridica';
        let buscado = numero.value.trim();

        // Muestra los campos del tipo de persona elegido. Los ocultos se deshabilitan: no se validan ni se envían.
        // El N.° de documento no se toca: en persona jurídica es el DNI del representante.
        function segunPersona() {
            const juridica = esJuridica();
            document.querySelectorAll('[data-persona]').forEach((bloque) => {
                const visible = bloque.dataset.persona === (juridica ? 'juridica' : 'natural');
                bloque.hidden = !visible;
                const campos = bloque.matches('input, select, button') ? [bloque] : bloque.querySelectorAll('input, select, button');
                campos.forEach((c) => { c.disabled = !visible; });
            });
            resultado.hidden = juridica;
            $('documento-ayuda').hidden = !juridica;
            $('titulo_persona').textContent = juridica ? 'Datos del representante' : $('titulo_persona').dataset.natural;
            // Los datos de la persona se escriben a mano para el representante
            if (juridica) desbloquear();
            segunDocumento();
        }

        // Con DNI se piden los dos apellidos (el representante siempre usa DNI); con carné o pasaporte el materno puede quedar vacío
        function segunDocumento() {
            const conDni = esJuridica() || tipo.value === 'DNI';
            $('apellido_materno').required = conDni;
            $('materno-opcional').hidden = conDni;
            $('materno-ayuda').hidden = conDni;
            if (conDni) {
                Object.assign(numero, {minLength: 8, maxLength: 8, pattern: '[0-9]{8}', inputMode: 'numeric'});
            } else {
                Object.assign(numero, {minLength: 6, maxLength: 12, pattern: '[A-Za-z0-9]{6,12}', inputMode: 'text'});
            }
        }
        const desbloquear = () => datos.forEach((c) => { $(c).readOnly = false; });
        personas.forEach((r) => r.addEventListener('change', segunPersona));
        tipo.addEventListener('change', segunDocumento);
        // Si se cambia el documento después de buscar, los datos dejan de estar bloqueados
        numero.addEventListener('input', () => { if (numero.value.trim() !== buscado) desbloquear(); });
        segunPersona();

        async function consultar(valor) {
            const r = await fetch(opciones.buscarUrl + '?documento=' + encodeURIComponent(valor), {headers: {Accept: 'application/json'}});
            if (!r.ok) throw new Error(r.status);
            return r.json();
        }

        // Persona natural: si el documento ya está registrado, se usan sus datos (no se pueden cambiar desde aquí)
        async function buscar() {
            const valor = numero.value.trim();
            if (valor.length < 6) { resultado.textContent = t.escribeDocumento; return; }
            resultado.textContent = 'Buscando…';
            try {
                const d = await consultar(valor);
                desbloquear();
                buscado = valor;
                if (!d.encontrado || d.tipo_doc_identidad === 'RUC') {
                    resultado.innerHTML = t.noEncontrado;
                    return;
                }
                if ([...tipo.options].some((o) => o.value === d.tipo_doc_identidad)) { tipo.value = d.tipo_doc_identidad; segunDocumento(); }
                datos.forEach((c) => { if (d[c] !== null && d[c] !== undefined) { $(c).value = d[c]; $(c).readOnly = true; } });
                resultado.innerHTML = t.encontrado;
            } catch (e) {
                resultado.textContent = t.errorDocumento;
            }
        }
        $('buscar').addEventListener('click', buscar);

        // Persona jurídica: si el RUC ya está registrado, se usa su razón social
        const ruc = $('ruc'), resultadoRuc = $('resultado_ruc'), razon = $('razon_social');
        async function buscarRuc() {
            const valor = ruc.value.trim();
            if (valor.length !== 11) { resultadoRuc.textContent = t.escribeRuc; return; }
            resultadoRuc.textContent = 'Buscando…';
            try {
                const d = await consultar(valor);
                razon.readOnly = false;
                if (!d.encontrado || !d.razon_social) {
                    resultadoRuc.innerHTML = t.rucNoEncontrado;
                    return;
                }
                razon.value = d.razon_social; razon.readOnly = true;
                resultadoRuc.innerHTML = t.rucEncontrado;
            } catch (e) {
                resultadoRuc.textContent = t.errorRuc;
            }
        }
        $('buscar_ruc').addEventListener('click', buscarRuc);
        ruc.addEventListener('input', () => { razon.readOnly = false; });
        numero.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); buscar(); } });
        ruc.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); buscarRuc(); } });

        return {esJuridica};
    }

    // Campo con error: al salir de él se pinta en rojo y se explica cómo corregirlo
    function avisos(campos, esJuridica) {
        const mensajes = {
            correo: 'Escribe un correo completo, por ejemplo rosa@gmail.com',
            celular: 'Escribe los 9 dígitos, empezando por 9.',
            ruc: 'El RUC tiene 11 dígitos y empieza con 10, 15, 17 o 20.',
        };
        const tipo = $('tipo_doc_identidad');
        // El N.° de documento depende del tipo elegido (en persona jurídica siempre es DNI)
        const mensajeDe = (campo) => {
            if (campo.id !== 'numero_documento') return mensajes[campo.id];
            if (esJuridica() || tipo.value === 'DNI') return 'El DNI tiene exactamente 8 dígitos.';
            return tipo.value === 'Pasaporte' ? 'El pasaporte tiene de 6 a 12 letras o números.' : 'El carné tiene de 6 a 12 letras o números.';
        };
        campos.forEach((campo) => {
            const aviso = document.createElement('div');
            aviso.className = 'error'; aviso.hidden = true; aviso.setAttribute('role', 'alert');
            (campo.parentElement.style.display === 'flex' ? campo.parentElement : campo).after(aviso);
            const revisar = () => {
                const mal = campo.value !== '' && !campo.checkValidity();
                aviso.textContent = mal ? (mensajeDe(campo) || campo.validationMessage) : '';
                aviso.hidden = !mal;
            };
            campo.addEventListener('blur', revisar);
            campo.addEventListener('input', () => { if (!aviso.hidden) revisar(); });
        });
    }

    // Zona de archivo: muestra nombre y tamaño, y avisa al instante si no es PDF/ZIP o pesa más de 10 MB
    function archivo(opciones = {}) {
        const listo = opciones.listo || 'listo para enviar';
        const entrada = $('archivo'), zona = $('zona-archivo'), estado = $('archivo-estado');
        function revisarArchivo() {
            const f = entrada.files[0];
            let error = '';
            if (f) {
                const ext = f.name.split('.').pop().toLowerCase();
                if (!['pdf', 'zip'].includes(ext)) error = '«' + f.name + '» no es PDF ni ZIP. Elige un archivo .pdf o .zip.';
                else if (f.size > MAXIMO) error = '«' + f.name + '» pesa ' + tamano(f.size) + '. El máximo es 10 MB.';
                else if (f.size === 0) error = '«' + f.name + '» está vacío. Elige otro archivo.';
            }
            entrada.setCustomValidity(error); // el navegador no deja enviar mientras haya error
            estado.textContent = error; estado.hidden = !error;
            zona.classList.toggle('con-archivo', !!f); zona.classList.toggle('con-error', !!error);
            $('zona-vacia').hidden = !!f; $('zona-elegido').hidden = !f;
            if (f) {
                const tipo = f.name.split('.').pop().toUpperCase().slice(0, 3);
                $('archivo-tipo').textContent = tipo;
                $('archivo-icono').setAttribute('class', 'ico-archivo ' + (tipo === 'ZIP' ? 'tipo-zip' : tipo === 'PDF' ? 'tipo-pdf' : 'tipo-otro'));
                $('archivo-nombre').textContent = f.name; $('archivo-detalle').textContent = tamano(f.size) + (error ? '' : ' · ' + listo);
            }
        }
        entrada.addEventListener('change', revisarArchivo);
        $('archivo-quitar').addEventListener('click', () => { entrada.value = ''; revisarArchivo(); entrada.focus(); });
        ['dragenter', 'dragover'].forEach((ev) => zona.addEventListener(ev, () => zona.classList.add('arrastrando')));
        ['dragleave', 'drop'].forEach((ev) => zona.addEventListener(ev, () => zona.classList.remove('arrastrando')));
    }

    return {tamano, solicitante, avisos, archivo};
})();
