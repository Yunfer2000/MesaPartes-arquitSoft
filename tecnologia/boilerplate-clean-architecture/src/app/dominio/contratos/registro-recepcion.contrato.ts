// =============================================================
// DOMINIO · CONTRATO
// =============================================================
// Define la operación de negocio para registrar la recepción
// de una solicitud, sin depender de una tecnología concreta.

export interface ResultadoRecepcion {
  registrado: boolean;
  codigoExpediente: string;
  observacion?: string;
}

export interface RegistroRecepcion {
  registrarRecepcion(
    solicitudId: string,
    usuarioId: string
  ): Promise<ResultadoRecepcion>;
}