
 // =============================================================
// APLICACION · Caso de uso
// =============================================================

import { Solicitud } from '../dominio/modelos/solicitud.modelo';
import {
  FiltroSolicitudes,
  RepositorioSolicitudes,
} from '../dominio/contratos/repositorio-solicitudes.contrato';

export class ConsultarSolicitudesCasoUso {
  constructor(
    private readonly repositorioSolicitudes: RepositorioSolicitudes,
  ) {}

  async ejecutar(filtro?: FiltroSolicitudes): Promise<Solicitud[]> {
    return this.repositorioSolicitudes.listar(filtro);
  }
}
