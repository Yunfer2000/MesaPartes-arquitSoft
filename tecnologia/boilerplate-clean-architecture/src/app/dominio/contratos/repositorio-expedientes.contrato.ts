// =============================================================
// DOMINIO · CONTRATO
// =============================================================

import { Expediente } from '../modelos/expediente.modelo';

export interface RepositorioExpedientes {
  guardar(expediente: Expediente): Promise<void>;
  buscarPorId(id: string): Promise<Expediente | null>;
  listarPorSolicitante(solicitanteId: string): Promise<Expediente[]>;
}