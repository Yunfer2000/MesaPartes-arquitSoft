
// =============================================================
// INFRAESTRUCTURA · Adaptador
// =============================================================
// Implementa el contrato RepositorioSolicitudes en memoria.
// El dominio define el contrato; esta clase define cómo
// se almacenan y consultan las solicitudes.

import { Solicitud } from '../dominio/modelos/solicitud.modelo';
import {
  FiltroSolicitudes,
  RepositorioSolicitudes,
} from '../dominio/contratos/repositorio-solicitudes.contrato';

export class RepositorioSolicitudesMemoria
  implements RepositorioSolicitudes {
  private readonly solicitudes = new Map<string, Solicitud>();

  constructor(iniciales: Solicitud[] = []) {
    iniciales.forEach((solicitud) => {
      this.solicitudes.set(solicitud.id, solicitud);
    });
  }

  async listar(filtro?: FiltroSolicitudes): Promise<Solicitud[]> {
    let resultado = [...this.solicitudes.values()];

    if (filtro?.tipoTramite) {
      resultado = resultado.filter(
        (solicitud) => solicitud.tipoTramite === filtro.tipoTramite,
      );
    }

    return resultado;
  }

  async buscarPorId(id: string): Promise<Solicitud | null> {
    return this.solicitudes.get(id) ?? null;
  }

  async guardar(solicitud: Solicitud): Promise<void> {
    this.solicitudes.set(solicitud.id, solicitud);
  }
}
