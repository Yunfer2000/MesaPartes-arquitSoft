import { Expediente } from '../dominio/modelos/expediente.modelo';
import { Solicitud } from '../dominio/modelos/solicitud.modelo';

function comprobar(condicion: boolean, mensaje: string): void {
  if (!condicion) {
    throw new Error(`PRUEBA FALLIDA: ${mensaje}`);
  }

  console.log(`PRUEBA CORRECTA: ${mensaje}`);
}

// Prueba 1: crear una solicitud válida.
const solicitud = new Solicitud(
  'SOL-001',
  'USR-001',
  'Solicitud de constancia de estudios',
  'academico',
  new Date(),
);

comprobar(
  solicitud.id === 'SOL-001',
  'Se puede crear una solicitud válida',
);

// Prueba 2: rechazar una solicitud sin solicitante.
let solicitudRechazada = false;

try {
  new Solicitud(
    'SOL-002',
    '',
    'Solicitud de constancia de estudios',
    'academico',
    new Date(),
  );
} catch {
  solicitudRechazada = true;
}

comprobar(
  solicitudRechazada,
  'Se rechaza una solicitud sin solicitante',
);

// Prueba 3: crear un expediente en estado recibido.
const expediente = Expediente.crear(
  'USR-001',
  'Solicitud de constancia de estudios',
  'EXP-000001',
);

comprobar(
  expediente.estadoActual === 'recibido',
  'Un expediente nuevo inicia en estado recibido',
);

// Prueba 4: impedir archivar un expediente no atendido.
let archivoRechazado = false;

try {
  expediente.archivar();
} catch {
  archivoRechazado = true;
}

comprobar(
  archivoRechazado,
  'No se puede archivar un expediente que no está atendido',
);

console.log('\nTodas las pruebas del dominio finalizaron correctamente.');