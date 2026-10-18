
 // =============================================================
 // APLICACION · Caso de uso
 // =============================================================

import { Solicitud, TipoTramite } from '../dominio/modelos/solicitud.modelo';
import { RepositorioSolicitudes } from '../dominio/contratos/repositorio-solicitudes.contrato';

export interface RegistrarSolicitudComando {
  id: string;
  solicitanteId: string;
  asunto: string;
  tipoTramite: TipoTramite;
}

export class RegistrarSolicitudCasoUso {
  constructor(
    private readonly repositorioSolicitudes: RepositorioSolicitudes,
  ) {}

  async ejecutar(comando: RegistrarSolicitudComando): Promise<Solicitud> {
    const solicitud = new Solicitud(
      comando.id,
      comando.solicitanteId,
      comando.asunto,
      comando.tipoTramite,
      new Date(),
    );

    await this.repositorioSolicitudes.guardar(solicitud);

    return solicitud;
  }
}
