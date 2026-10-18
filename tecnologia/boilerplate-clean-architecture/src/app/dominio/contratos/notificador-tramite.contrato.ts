// =============================================================
// DOMINIO · CONTRATO
// =============================================================
// El contrato define QUÉ se necesita: notificar un evento
// relacionado con un trámite o expediente.
// El adaptador definirá CÓMO: correo u otro canal.

export interface NotificadorTramite {
  notificarCambioEstado(
    expedienteId: string,
    estado: string,
    destinatarioId: string
  ): Promise<void>;
}