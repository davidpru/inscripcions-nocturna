export interface Parada {
  value: string;
  label: string;
  descripcion: string;
  url?: string;
}

export const PARADAS: Parada[] = [
  {
    value: 'tortosa',
    label: 'Sortida des de Tortosa',
    descripcion: 'Carrer Carretera TV-3421 (a 50 metres de la Rotonda 4 camins)',
    url: 'https://maps.app.goo.gl/b2LfLncdGfHDroMEA',
  },
  {
    value: 'pauls',
    label: 'Sortida des de Paüls',
    descripcion: 'Bàscula municipal, entrada de Paüls',
    url: 'https://maps.app.goo.gl/XZiBuKtVWx7pWeP79',
  },
];

// Helper para obtener el nombre completo de una parada
export function getParadaLabel(value: string | null): string {
  if (!value) return '';
  const parada = PARADAS.find((p) => p.value === value);
  return parada ? `${parada.label} - ${parada.descripcion}` : value;
}

// Helper para obtener solo el label corto
export function getParadaShortLabel(value: string | null): string {
  if (!value) return '';
  const parada = PARADAS.find((p) => p.value === value);
  return parada ? parada.label : value;
}
