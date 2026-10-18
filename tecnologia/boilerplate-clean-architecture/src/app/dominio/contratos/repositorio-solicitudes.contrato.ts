// =============================================================
// DOMINIO · CONTRATO
// =============================================================
// El dominio define las operaciones que necesita.
// La implementación podrá utilizar memoria, una API REST
// o una base de datos, sin cambiar este contrato.

import { Solicitud } from '../modelos/solicitud.modelo';

export interface FiltroSolicitudes {
  tipoTramite?: string;
}

export interface RepositorioSolicitudes {
  listar(filtro?: FiltroSolicitudes): Promise<Solicitud[]>;
  buscarPorId(id: string): Promise<Solicitud | null>;
  guardar(solicitud: Solicitud): Promise<void>;
}