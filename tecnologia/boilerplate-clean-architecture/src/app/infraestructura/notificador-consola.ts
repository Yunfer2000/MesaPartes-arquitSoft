
// =============================================================
// INFRAESTRUCTURA · Adaptador
// =============================================================
// Implementa el contrato NotificadorTramite mediante consola.
// Simula una notificación sobre el estado de un expediente.

import { NotificadorTramite } from '../dominio/contratos/notificador-tramite.contrato';

export class NotificadorConsola implements NotificadorTramite {
  async notificarCambioEstado(
    expedienteId: string,
    estado: string,
    destinatarioId: string,
  ): Promise<void> {
    console.info(
      `[NOTIFICACIÓN] Expediente: ${expedienteId} | ` +
      `Estado: ${estado} | Destinatario: ${destinatarioId}`,
    );
  }
}
