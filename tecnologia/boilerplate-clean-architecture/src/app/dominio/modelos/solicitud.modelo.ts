// =============================================================
// DOMINIO · Entidad Solicitud
// =============================================================
// Sin Angular, sin HttpClient y sin base de datos.
// Contiene reglas propias del registro de solicitudes.

export const TIPOS_TRAMITE_PERMITIDOS = [
  'documentario',
  'academico',
  'administrativo',
  'otros',
] as const;

export type TipoTramite =
  (typeof TIPOS_TRAMITE_PERMITIDOS)[number];

export class Solicitud {
  constructor(
    public readonly id: string,
    public readonly solicitanteId: string,
    public readonly asunto: string,
    public readonly tipoTramite: TipoTramite,
    public readonly fechaRegistro: Date,
  ) {
    if (!solicitanteId.trim()) {
      throw new Error('La solicitud debe tener un solicitante');
    }

    if (asunto.trim().length < 5) {
      throw new Error('El asunto debe tener al menos 5 caracteres');
    }

    if (
      !(TIPOS_TRAMITE_PERMITIDOS as readonly string[])
        .includes(tipoTramite)
    ) {
      throw new Error(`Tipo de trámite no permitido: ${tipoTramite}`);
    }

    if (Number.isNaN(fechaRegistro.getTime())) {
      throw new Error('La fecha de registro no es válida');
    }
  }
}