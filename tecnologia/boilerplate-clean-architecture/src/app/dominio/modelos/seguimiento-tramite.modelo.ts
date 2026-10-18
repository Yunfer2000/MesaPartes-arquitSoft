// =============================================================
// DOMINIO · Entidad de seguimiento de trámite
// =============================================================
// El seguimiento conserva el historial de estados de un trámite.
// Las transiciones válidas se controlan desde el dominio.

export interface EventoTramite {
  estado: string;
  fecha: Date;
  observacion?: string;
}

export class SeguimientoTramite {
  private constructor(
    private readonly eventos: EventoTramite[],
  ) {}

  static iniciar(estadoInicial: string): SeguimientoTramite {
    if (!estadoInicial.trim()) {
      throw new Error('El estado inicial es obligatorio');
    }

    return new SeguimientoTramite([
      {
        estado: estadoInicial,
        fecha: new Date(),
      },
    ]);
  }

  get historial(): ReadonlyArray<EventoTramite> {
    return this.eventos;
  }

  get estadoActual(): string {
    return this.eventos[this.eventos.length - 1].estado;
  }

  registrarCambio(
    nuevoEstado: string,
    observacion?: string,
  ): SeguimientoTramite {
    if (!nuevoEstado.trim()) {
      throw new Error('El nuevo estado es obligatorio');
    }

    const nuevoEvento: EventoTramite = {
      estado: nuevoEstado,
      fecha: new Date(),
      observacion,
    };

    return new SeguimientoTramite([
      ...this.eventos,
      nuevoEvento,
    ]);
  }
}
