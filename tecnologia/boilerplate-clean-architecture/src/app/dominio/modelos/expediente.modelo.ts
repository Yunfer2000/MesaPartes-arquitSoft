
// =============================================================
// DOMINIO · Entidad Expediente
// =============================================================

export type EstadoExpediente =
  | 'recibido'
  | 'en_revision'
  | 'derivado'
  | 'atendido'
  | 'observado'
  | 'archivado';

export class Expediente {
  private constructor(
    public readonly id: string,
    public readonly codigo: string,
    public readonly solicitanteId: string,
    public readonly asunto: string,
    public readonly fechaRecepcion: Date,
    private estado: EstadoExpediente,
  ) {}

  // REGLA DE NEGOCIO: el expediente requiere solicitante y asunto.
  static crear(
    solicitanteId: string,
    asunto: string,
    codigo?: string,
  ): Expediente {
    if (!solicitanteId.trim()) {
      throw new Error('El expediente debe tener un solicitante');
    }

    if (!asunto.trim()) {
      throw new Error('El asunto del expediente es obligatorio');
    }

    const id =
      'EXP-' + Math.random().toString(36).slice(2, 8).toUpperCase();

    const codigoExpediente =
      codigo?.trim() ||
      'EXP-' + Date.now().toString(36).toUpperCase();

    return new Expediente(
      id,
      codigoExpediente,
      solicitanteId,
      asunto.trim(),
      new Date(),
      'recibido',
    );
  }

  get estadoActual(): EstadoExpediente {
    return this.estado;
  }

  // REGLA DE NEGOCIO: solo se puede archivar un expediente atendido.
  puedeArchivarse(): boolean {
    return this.estado === 'atendido';
  }

  archivar(): void {
    if (!this.puedeArchivarse()) {
      throw new Error(
        `Un expediente en estado ${this.estado} no puede archivarse`,
      );
    }

    this.estado = 'archivado';
  }
}
