<?php

namespace App\Services;

use App\Models\Edicion;

class TarifaService
{
    public function calcularPrecio(
        Edicion $edicion,
        bool $esSocioUEC,
        bool $estaFederado,
        bool $necesitaAutobus,
        bool $seguroAnulacion
    ): array {
        $esTarifaTardia = $edicion->esTarifaTardia();

        // Obtener precio de inscripción base
        $precioInscripcion = $this->obtenerPrecioInscripcion($edicion, $esSocioUEC, $esTarifaTardia);
        
        // Calcular coste de licencia federativa
        // Si ya está federado, no paga licencia (0€)
        // Si no está federado, paga la licencia (5€)
        $precioLicencia = $estaFederado ? 0 : $this->obtenerPrecioLicencia($edicion, $esSocioUEC);

        // Tarifa base = inscripción + licencia (si no está federado)
        $tarifaBase = $precioInscripcion + $precioLicencia;

        // Calcular extras
        $precioAutobus = $necesitaAutobus 
            ? ($esTarifaTardia ? $edicion->precio_autobus_tardia : $edicion->precio_autobus_normal) 
            : 0;
        $precioSeguro = $seguroAnulacion ? $edicion->precio_seguro : 0;

        $precioTotal = $tarifaBase + $precioAutobus + $precioSeguro;

        // Nombre descriptivo de la tarifa
        $nombreTarifa = $this->obtenerNombreTarifa($esSocioUEC, $estaFederado);

        return [
            'precio_inscripcion' => $precioInscripcion,
            'precio_licencia' => $precioLicencia,
            'tarifa_base' => $tarifaBase,
            'nombre_tarifa' => $nombreTarifa,
            'precio_autobus' => $precioAutobus,
            'precio_seguro' => $precioSeguro,
            'precio_total' => $precioTotal,
            'es_tarifa_tardia' => $esTarifaTardia,
        ];
    }

    private function obtenerPrecioInscripcion(Edicion $edicion, bool $esSocioUEC, bool $esTarifaTardia): float
    {
        if ($esSocioUEC) {
            return $esTarifaTardia 
                ? (float) $edicion->precio_inscripcion_socio_tardia 
                : (float) $edicion->precio_inscripcion_socio_normal;
        } else {
            return $esTarifaTardia 
                ? (float) $edicion->precio_inscripcion_publico_tardia 
                : (float) $edicion->precio_inscripcion_publico_normal;
        }
    }

    private function obtenerPrecioLicencia(Edicion $edicion, bool $esSocioUEC): float
    {
        return $esSocioUEC 
            ? (float) $edicion->precio_licencia_federativa_socio 
            : (float) $edicion->precio_licencia_federativa_publico;
    }

    private function obtenerNombreTarifa(bool $esSocioUEC, bool $estaFederado): string
    {
        if ($esSocioUEC && $estaFederado) {
            return 'Soci UEC + Federat';
        } elseif ($esSocioUEC && !$estaFederado) {
            return 'Soci UEC';
        } elseif (!$esSocioUEC && $estaFederado) {
            return 'Federat';
        } else {
            return 'General';
        }
    }
}
