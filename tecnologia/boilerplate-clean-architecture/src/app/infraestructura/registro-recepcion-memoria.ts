
// =============================================================
// INFRAESTRUCTURA · Adaptador
// =============================================================
// Implementa el contrato RegistroRecepcion en memoria.
// Genera un código para identificar el expediente recibido.

import {
  RegistroRecepcion,
  ResultadoRecepcion,
} from '../dominio/contratos/registro-recepcion.contrato';

export class RegistroRecepcionMemoria implements RegistroRecepcion {
  private siguienteNumero = 1;

  async registrarRecepcion(
    solicitudId: string,
    usuarioId: string,
  ): Promise<ResultadoRecepcion> {
    if (!solicitudId.trim() || !usuarioId.trim()) {
      return {
        registrado: false,
        codigoExpediente: '',
        observacion: 'La solicitud y el usuario son obligatorios',
      };
    }

    const codigoExpediente =
      `EXP-${String(this.siguienteNumero).padStart(6, '0')}`;

    this.siguienteNumero += 1;

    return {
      registrado: true,
      codigoExpediente,
    };
  }
}
