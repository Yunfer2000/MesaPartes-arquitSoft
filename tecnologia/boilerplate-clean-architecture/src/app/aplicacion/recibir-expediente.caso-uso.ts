
// =============================================================
// APLICACION · Caso de uso principal
// =============================================================
// Coordina la recepción de un expediente:
// consultar solicitud -> registrar recepción -> guardar expediente
// -> notificar el estado inicial.

import { Expediente } from '../dominio/modelos/expediente.modelo';
import { RepositorioExpedientes } from '../dominio/contratos/repositorio-expedientes.contrato';
import { RepositorioSolicitudes } from '../dominio/contratos/repositorio-solicitudes.contrato';
import { RegistroRecepcion } from '../dominio/contratos/registro-recepcion.contrato';
import { NotificadorTramite } from '../dominio/contratos/notificador-tramite.contrato';

export interface RecibirExpedienteComando {
  solicitudId: string;
  solicitanteId: string;
}

export class RecibirExpedienteCasoUso {
  constructor(
    private readonly repositorioSolicitudes: RepositorioSolicitudes,
    private readonly repositorioExpedientes: RepositorioExpedientes,
    private readonly registroRecepcion: RegistroRecepcion,
    private readonly notificadorTramite: NotificadorTramite,
  ) {}

  async ejecutar(
    comando: RecibirExpedienteComando,
  ): Promise<Expediente> {
    const solicitud = await this.repositorioSolicitudes.buscarPorId(
      comando.solicitudId,
    );

    if (!solicitud) {
      throw new Error(
        `Solicitud no encontrada: ${comando.solicitudId}`,
      );
    }

    if (solicitud.solicitanteId !== comando.solicitanteId) {
      throw new Error('La solicitud no pertenece al solicitante indicado');
    }

    const resultado = await this.registroRecepcion.registrarRecepcion(
      comando.solicitudId,
      comando.solicitanteId,
    );

    if (!resultado.registrado) {
      throw new Error(
        resultado.observacion ?? 'No se pudo registrar la recepción',
      );
    }

    const expediente = Expediente.crear(
      solicitud.solicitanteId,
      solicitud.asunto,
      resultado.codigoExpediente,
    );

    await this.repositorioExpedientes.guardar(expediente);

    await this.notificadorTramite.notificarCambioEstado(
      expediente.id,
      expediente.estadoActual,
      solicitud.solicitanteId,
    );

    return expediente;
  }
}
