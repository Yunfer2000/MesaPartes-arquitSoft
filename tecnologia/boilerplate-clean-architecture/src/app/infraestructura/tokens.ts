
// =============================================================
// INFRAESTRUCTURA · Tokens de inyección de Angular
// =============================================================
// Los contratos viven en el dominio.
// Los tokens pertenecen a infraestructura porque dependen de Angular.
// Permiten conectar los contratos con sus adaptadores concretos.

import { InjectionToken } from '@angular/core';

import { RepositorioSolicitudes } from '../dominio/contratos/repositorio-solicitudes.contrato';
import { RepositorioExpedientes } from '../dominio/contratos/repositorio-expedientes.contrato';
import { RegistroRecepcion } from '../dominio/contratos/registro-recepcion.contrato';
import { NotificadorTramite } from '../dominio/contratos/notificador-tramite.contrato';

export const REPOSITORIO_SOLICITUDES =
  new InjectionToken<RepositorioSolicitudes>('RepositorioSolicitudes');

export const REPOSITORIO_EXPEDIENTES =
  new InjectionToken<RepositorioExpedientes>('RepositorioExpedientes');

export const REGISTRO_RECEPCION =
  new InjectionToken<RegistroRecepcion>('RegistroRecepcion');

export const NOTIFICADOR_TRAMITE =
  new InjectionToken<NotificadorTramite>('NotificadorTramite');
