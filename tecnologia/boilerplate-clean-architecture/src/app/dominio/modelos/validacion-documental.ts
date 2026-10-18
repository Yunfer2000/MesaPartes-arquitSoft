// =============================================================
// DOMINIO · Reglas de validación documental
// =============================================================
// Este archivo no depende de Angular ni de otras librerías.
// Centraliza las reglas iniciales de validación documental.

export const TAMANO_MAXIMO_DOCUMENTO_MB = 10;

export const TIPOS_DOCUMENTO_PERMITIDOS = [
  'application/pdf',
] as const;

/** Comprueba si el tipo de archivo está permitido. */
export function tipoDocumentoPermitido(tipoMime: string): boolean {
  return (TIPOS_DOCUMENTO_PERMITIDOS as readonly string[]).includes(tipoMime);
}

/** Comprueba si el documento respeta el tamaño máximo. */
export function tamanoDocumentoValido(tamanoBytes: number): boolean {
  if (!Number.isFinite(tamanoBytes) || tamanoBytes < 0) {
    return false;
  }

  return tamanoBytes <= TAMANO_MAXIMO_DOCUMENTO_MB * 1024 * 1024;
}

/** Comprueba si un documento cumple las reglas iniciales. */
export function documentoValido(
  tipoMime: string,
  tamanoBytes: number,
): boolean {
  return tipoDocumentoPermitido(tipoMime)
    && tamanoDocumentoValido(tamanoBytes);
}