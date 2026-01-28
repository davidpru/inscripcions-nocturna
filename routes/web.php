<?php

use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\RedsysController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EdicionController;
use App\Http\Controllers\Admin\InscripcionController as AdminInscripcionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Edicion;

// Página de inicio
Route::get('/', function () {
    $edicion = Edicion::where('activa', true)->first();
    
    // Verificar si la IP está en la lista de IPs de prueba (admins)
    $isTestIp = in_array(request()->ip(), config('admin.test_ips', []));
    
    // Si es IP de prueba, forzar inscripciones abiertas
    $inscripcionesAbiertas = $edicion 
        ? ($isTestIp ? true : $edicion->inscripcionesAbiertas()) 
        : false;
    
    return Inertia::render('Home', [
        'edicion' => $edicion,
        'hayEdicion' => $edicion !== null,
        'inscripcionesAbiertas' => $inscripcionesAbiertas,
        'isTestMode' => $isTestIp && $edicion && !$edicion->inscripcionesAbiertas()
    ]);
})->name('home');

// Rutas públicas de inscripción
Route::prefix('inscripcion')->group(function () {
    Route::get('/', [InscripcionController::class, 'index'])->name('inscripcion.index');
    Route::get('/listado', [InscripcionController::class, 'listado'])->name('inscripcion.listado');
    Route::get('/consulta', function () {
        $edicion = Edicion::where('activa', true)->first();
        return Inertia::render('Inscripcion/Consulta', [
            'edicion' => $edicion
        ]);
    })->name('inscripcion.consulta');
    // Redirigir GET a la página de consulta
    Route::get('/buscar-inscripcion', function () {
        return redirect()->route('inscripcion.consulta');
    });
    Route::post('/buscar-participante', [InscripcionController::class, 'buscarParticipante'])->name('inscripcion.buscar');
    Route::post('/buscar-inscripcion', [InscripcionController::class, 'buscarInscripcion'])->name('inscripcion.buscar-inscripcion');
    Route::post('/calcular-precio', [InscripcionController::class, 'calcularPrecio'])->name('inscripcion.calcular-precio');
    Route::post('/', [InscripcionController::class, 'store'])->name('inscripcion.store');
    Route::get('/confirmacion/{inscripcion}', [InscripcionController::class, 'confirmacion'])->name('inscripcion.confirmacion');
    Route::post('/{inscripcion}/contratar-autobus', [InscripcionController::class, 'contratarAutobus'])->name('inscripcion.contratar-autobus');
    Route::post('/{inscripcion}/cambiar-parada', [InscripcionController::class, 'cambiarParada'])->name('inscripcion.cambiar-parada');
});

// Rutas de pago con Redsys
Route::prefix('pago')->name('redsys.')->group(function () {
    // Rutas específicas primero (antes de la ruta con parámetro)
    Route::any('/success', [RedsysController::class, 'success'])->name('success');
    Route::any('/error', [RedsysController::class, 'error'])->name('error');
    Route::post('/notification', [RedsysController::class, 'notification'])->name('notification');
    // Rutas con parámetro al final
    Route::get('/autobus/{inscripcion}', [RedsysController::class, 'procesarPagoAutobus'])->name('procesar-autobus');
    Route::get('/{inscripcion}', [RedsysController::class, 'procesarPago'])->name('procesar');
});

// Rutas de administración
Route::prefix('admin')->name('admin.')->middleware('admin.auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Gestión de ediciones
    Route::resource('ediciones', EdicionController::class)->parameters([
        'ediciones' => 'edicion'
    ]);
    
    // Gestión de inscripciones
    Route::get('inscripciones', [AdminInscripcionController::class, 'index'])->name('inscripciones.index');
    Route::post('inscripciones', [AdminInscripcionController::class, 'store'])->name('inscripciones.store');
    Route::get('inscripciones/{inscripcion}', [AdminInscripcionController::class, 'show'])->name('inscripciones.show');
    Route::get('inscripciones/{inscripcion}/edit', [AdminInscripcionController::class, 'edit'])->name('inscripciones.edit');
    Route::put('inscripciones/{inscripcion}', [AdminInscripcionController::class, 'update'])->name('inscripciones.update');
    Route::delete('inscripciones/{inscripcion}', [AdminInscripcionController::class, 'destroy'])->name('inscripciones.destroy');
    Route::post('inscripciones/exportar', [AdminInscripcionController::class, 'exportar'])->name('inscripciones.exportar');
    Route::post('inscripciones/{inscripcion}/devolucion', [RedsysController::class, 'procesarDevolucion'])->name('inscripciones.devolucion');
    Route::post('inscripciones/{inscripcion}/devolucion-manual', [RedsysController::class, 'devolucionManual'])->name('inscripciones.devolucion-manual');
});