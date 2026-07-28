/**
 * Utilidades para validación y formateo de NIT colombiano.
 * Algoritmo de dígito de verificación oficial del DIAN (módulo 11).
 */

const PESOS = [41, 37, 29, 23, 19, 17, 13, 7, 3];

/**
 * Calcula el dígito de verificación de un NIT colombiano.
 * @param nitSinDv NIT sin puntos, guiones ni DV (solo números)
 * @returns el dígito de verificación (0-9)
 */
export function calcularDv(nitSinDv: string): string {
  const numeros = nitSinDv.replace(/\D/g, '');

  let suma = 0;
  for (let i = 0; i < numeros.length; i++) {
    const peso = PESOS[i % PESOS.length];
    suma += parseInt(numeros[numeros.length - 1 - i], 10) * peso;
  }

  const resto = suma % 11;
  const dv = resto === 0 || resto === 1 ? resto : 11 - resto;

  return String(dv);
}

/**
 * Valida si un NIT tiene el dígito de verificación correcto.
 * Acepta formatos: "9006990868", "900.699.086-8", "900699086-8"
 */
export function validarNit(nit: string): boolean {
  const limpio = nit.replace(/[.\s]/g, '');
  const partes = limpio.split('-');

  if (partes.length !== 2) return false;

  const numero = partes[0].replace(/\D/g, '');
  const dvIngresado = partes[1].replace(/\D/g, '');

  if (numero.length < 5 || !dvIngresado) return false;

  return calcularDv(numero) === dvIngresado;
}

/**
 * Formatea un NIT mientras el usuario escribe.
 * "9006990868" → "900.699.086-8"
 * "900699086"  → "900.699.086" (sin DV aún)
 */
export function formatearNit(valor: string): string {
  const soloNumeros = valor.replace(/\D/g, '');

  if (soloNumeros.length <= 9) {
    // Sin DV todavía, solo formatear con puntos
    return soloNumeros.replace(/(\d{3})(\d{3})(\d{3})/, '$1.$2.$3');
  }

  // Tiene DV (10+ dígitos)
  const numero = soloNumeros.slice(0, 9);
  const dv = soloNumeros.slice(9);

  return `${numero.replace(/(\d{3})(\d{3})(\d{3})/, '$1.$2.$3')}-${dv}`;
}

/**
 * Normaliza un NIT a solo números (sin puntos, guiones ni espacios).
 * "900.699.086-8" → "9006990868"
 */
export function normalizarNit(nit: string): string {
  return nit.replace(/\D/g, '');
}
