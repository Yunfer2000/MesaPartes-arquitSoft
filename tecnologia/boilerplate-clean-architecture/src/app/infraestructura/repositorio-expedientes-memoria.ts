
// =============================================================
// INFRAESTRUCTURA · Adaptador
// =============================================================
// Implementa el contrato RepositorioExpedientes en memoria.

import { Expediente } from '../dominio/modelos/expediente.modelo';
import { RepositorioExpedientes } from '../dominio/contratos/repositorio-expedientes.contrato';

export class RepositorioExpedientesMemoria
  implements RepositorioExpedientes {
  private readonly expedientes = new Map<string, Expediente>();

  constructor(iniciales: Expediente[] = []) {
    iniciales.forEach((expediente) => {
      this.expedientes.set(expediente.id, expediente);
    });
  }

  async guardar(expediente: Expediente): Promise<void> {
    this.expedientes.set(expediente.id, expediente);
  }

  async buscarPorId(id: string): Promise<Expediente | null> {
    return this.expedientes.get(id) ?? null;
  }

  async listarPorSolicitante(
    solicitanteId: string,
  ): Promise<Expediente[]> {
    return [...this.expedientes.values()].filter(
      (expediente) => expediente.solicitanteId === solicitanteId,
    );
  }
}
