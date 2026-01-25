<?php

namespace App\Services;

use App\Models\Edicion;

class TarifaService
{
    // Tarifas base de inscripción
    private const TARIFAS = [
        'publico_federado' => ['normal' => 35, 'tardia' => 40],
        'publico_no_federado' => ['normal' => 40, 'tardia' => 45],
        'socio_federado' => ['normal' => 30, 'tardia' => 35],
        'socio_no_federado' => ['normal' => 35, 'tardia' => 40],
    ];

    // Tarifas de servicios adicionales
    private const AUTOBUS = ['normal' => 12, 'tardia' => 14];
    private const SEGURO = 9;

    public function calcularPrecio(
        Edicion $edicion,
        bool $esSocioUEC,
        bool $estaFederado,
        bool $necesitaAutobus,
        bool $seguroAnulacion
    ): array {
        $esTarifaTardia = $edicion->esTarifaTardia();
        $tipoTarifa = $esTarifaTardia ? 'tardia' : 'normal';

        // Determinar tarifa base según perfil
        $perfilClave = $this->obtenerClavePerfil($esSocioUEC, $estaFederado);
        $tarifaBase = self::TARIFAS[$perfilClave][$tipoTarifa];

        // Calcular extras
        $precioAutobus = $necesitaAutobus ? self::AUTOBUS[$tipoTarifa] : 0;
        $precioSeguro = $seguroAnulacion ? self::SEGURO : 0;

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

    private function obtenerClavePerfil(bool $esSocioUEC, bool $estaFederado): string
    {
        if ($esSocioUEC && $estaFederado) {
            return 'socio_federado';
        } elseif ($esSocioUEC && !$estaFederado) {
            return 'socio_no_federado';
        } elseif (!$esSocioUEC && $estaFederado) {
            return 'publico_federado';
        } else {
            return 'publico_no_federado';
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