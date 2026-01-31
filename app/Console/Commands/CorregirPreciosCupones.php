<?php

namespace App\Console\Commands;

use App\Models\Inscripcion;
use App\Services\TarifaService;
use Illuminate\Console\Command;

class CorregirPreciosCupones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inscripciones:corregir-precios-cupones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige los precios de inscripciones con cupones aplicados incorrectamente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Buscando inscripciones con cupones...');
        
        $inscripciones = Inscripcion::with(['edicion', 'cupon'])
            ->whereNotNull('cupon_id')
            ->where('descuento_cupon', '>', 0)
            ->get();
        
        if ($inscripciones->isEmpty()) {
            $this->info('✅ No se encontraron inscripciones con cupones para corregir.');
            return 0;
        }
        
        $this->info("📋 Encontradas {$inscripciones->count()} inscripciones con cupones.");
        $this->newLine();
        
        $tarifaService = new TarifaService();
        $corregidas = 0;
        $yaCorrectas = 0;
        
        foreach ($inscripciones as $inscripcion) {
            if (!$inscripcion->cupon) {
                $this->warn("⚠️  Inscripción #{$inscripcion->id}: cupón no encontrado (cupon_id: {$inscripcion->cupon_id})");
                continue;
            }
            
            // Calcular precio base sin descuento
            $resultadoCalculo = $tarifaService->calcularPrecio(
                $inscripcion->edicion,
                $inscripcion->es_socio_uec,
                $inscripcion->esta_federado,
                $inscripcion->necesita_autobus,
                $inscripcion->seguro_anulacion
            );
            
            // Calcular descuento del cupón
            $descuentoCupon = $inscripcion->cupon->calcularDescuento(
                $inscripcion->edicion,
                $inscripcion->es_socio_uec,
                $inscripcion->esta_federado
            );
            
            // Si incluye autobús, añadir el precio del autobús al descuento
            if ($inscripcion->cupon->incluye_autobus && $inscripcion->necesita_autobus) {
                $descuentoCupon += $resultadoCalculo['precio_autobus'];
            }
            
            $precioTotal = max(0, $resultadoCalculo['precio_total'] - $descuentoCupon);
            
            // Verificar si el precio está incorrecto
            if (abs($inscripcion->precio_total - $precioTotal) > 0.01) {
                $this->line("🔧 Inscripción #{$inscripcion->id}:");
                $this->line("   Precio anterior: {$inscripcion->precio_total}€");
                $this->line("   Precio correcto: {$precioTotal}€");
                $this->line("   Descuento cupón: {$descuentoCupon}€");
                
                $inscripcion->update([
                    'precio_total' => $precioTotal,
                    'descuento_cupon' => $descuentoCupon,
                ]);
                
                $corregidas++;
            } else {
                $yaCorrectas++;
            }
        }
        
        $this->newLine();
        $this->info("✅ Proceso completado:");
        $this->info("   - Inscripciones corregidas: {$corregidas}");
        $this->info("   - Inscripciones ya correctas: {$yaCorrectas}");
        
        return 0;
    }
}
