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

        // Determinar tarifa base según perfil (desde la base de datos)
        $tarifaBase = $this->obtenerTarifaBase($edicion, $esSocioUEC, $estaFederado, $esTarifaTardia);

        // Calcular extras
        $precioAutobus = $necesitaAutobus 
            ? ($esTarifaTardia ? $edicion->precio_autobus_tardia : $edicion->precio_autobus_normal) 
            : 0;
        $precioSeguro = $seguroAnulacion ? $edicion->precio_seguro : 0;

        $precioTotal = $tarifaBase + $precioAutobus + $precioSeguro;

        // Nombre descriptivo de la tarifa
        $nombreTarifa = $this->obtenerNombreTarifa($esSocioUEC, $estaFederado);

        return [
            'tarifa_base' => $tarifaBase,
            'nombre_tarifa' => $nombreTarifa,
            'precio_autobus' => $precioAutobus,
            'precio_seguro' => $precioSeguro,
            'precio_total' => $precioTotal,
            'es_tarifa_tardia' => $esTarifaTardia,
        ];
    }

    private function obtenerTarifaBase(Edicion $edicion, bool $esSocioUEC, bool $estaFederado, bool $esTarifaTardia): float
    {
        if ($esSocioUEC && $estaFederado) {
            return $esTarifaTardia ? (float) $edicion->tarifa_socio_federado_tardia : (float) $edicion->tarifa_socio_federado_normal;
        } elseif ($esSocioUEC && !$estaFederado) {
            return $esTarifaTardia ? (float) $edicion->tarifa_socio_no_federado_tardia : (float) $edicion->tarifa_socio_no_federado_normal;
        } elseif (!$esSocioUEC && $estaFederado) {
            return $esTarifaTardia ? (float) $edicion->tarifa_publico_federado_tardia : (float) $edicion->tarifa_publico_federado_normal;
        } else {
            return $esTarifaTardia ? (float) $edicion->tarifa_publico_no_federado_tardia : (float) $edicion->tarifa_publico_no_federado_normal;
        }
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