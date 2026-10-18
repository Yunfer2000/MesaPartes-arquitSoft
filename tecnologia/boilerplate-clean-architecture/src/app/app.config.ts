
// =============================================================
// RAÍZ DE COMPOSICIÓN
// =============================================================
// Aquí se conectan los contratos con sus adaptadores concretos
// y se registran los casos de uso de la aplicación.

import { ApplicationConfig } from '@angular/core';

import {
  REPOSITORIO_SOLICITUDES,
  REPOSITORIO_EXPEDIENTES,
  REGISTRO_RECEPCION,
  NOTIFICADOR_TRAMITE,
} from './infraestructura/tokens';

import { RepositorioSolicitudesMemoria } from './infraestructura/repositorio-solicitudes-memoria';
import { RepositorioExpedientesMemoria } from './infraestructura/repositorio-expedientes-memoria';
import { RegistroRecepcionMemoria } from './infraestructura/registro-recepcion-memoria';
import { NotificadorConsola } from './infraestructura/notificador-consola';

import { RegistrarSolicitudCasoUso } from './aplicacion/registrar-solicitud.caso-uso';
import { ConsultarSolicitudesCasoUso } from './aplicacion/consultar-solicitudes.caso-uso';
import { RecibirExpedienteCasoUso } from './aplicacion/recibir-expediente.caso-uso';

import { RepositorioSolicitudes } from './dominio/contratos/repositorio-solicitudes.contrato';
import { RepositorioExpedientes } from './dominio/contratos/repositorio-expedientes.contrato';
import { RegistroRecepcion } from './dominio/contratos/registro-recepcion.contrato';
import { NotificadorTramite } from './dominio/contratos/notificador-tramite.contrato';

export const appConfig: ApplicationConfig = {
  providers: [
    // =========================================================
    // ELECCIÓN DE ADAPTADORES
    // =========================================================

    {
      provide: REPOSITORIO_SOLICITUDES,
      useFactory: () => new RepositorioSolicitudesMemoria(),
    },
    {
      provide: REPOSITORIO_EXPEDIENTES,
      useFactory: () => new RepositorioExpedientesMemoria(),
    },
    {
      provide: REGISTRO_RECEPCION,
      useFactory: () => new RegistroRecepcionMemoria(),
    },
    {
      provide: NOTIFICADOR_TRAMITE,
      useFactory: () => new NotificadorConsola(),
    },

    // =========================================================
    // CASOS DE USO
    // =========================================================

    {
      provide: RegistrarSolicitudCasoUso,
      useFactory: (repositorio: RepositorioSolicitudes) =>
        new RegistrarSolicitudCasoUso(repositorio),
      deps: [REPOSITORIO_SOLICITUDES],
    },
    {
      provide: ConsultarSolicitudesCasoUso,
      useFactory: (repositorio: RepositorioSolicitudes) =>
        new ConsultarSolicitudesCasoUso(repositorio),
      deps: [REPOSITORIO_SOLICITUDES],
    },
    {
      provide: RecibirExpedienteCasoUso,
      useFactory: (
        solicitudes: RepositorioSolicitudes,
        expedientes: RepositorioExpedientes,
        recepcion: RegistroRecepcion,
        notificador: NotificadorTramite,
      ) =>
        new RecibirExpedienteCasoUso(
          solicitudes,
          expedientes,
          recepcion,
          notificador,
        ),
      deps: [
        REPOSITORIO_SOLICITUDES,
        REPOSITORIO_EXPEDIENTES,
        REGISTRO_RECEPCION,
        NOTIFICADOR_TRAMITE,
      ],
    },
  ],
};
