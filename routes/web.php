<?php

use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\Admin\EdicionController;
use App\Http\Controllers\Admin\InscripcionController as AdminInscripcionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Edicion;

// Página de inicio
Route::get('/', function () {
    $edicion = Edicion::where('activa', true)->first();
    return Inertia::render('Home', [
        'edicion' => $edicion
    ]);
})->name('home');

// Rutas públicas de inscripción
Route::prefix('inscripcion')->group(function () {
    Route::get('/', [InscripcionController::class, 'index'])->name('inscripcion.index');
    Route::get('/consulta', function () {
        $edicion = Edicion::where('activa', true)->first();
        return Inertia::render('Inscripcion/Consulta', [
            'edicion' => $edicion
        ]);
    })->name('inscripcion.consulta');
    Route::post('/buscar-participante', [InscripcionController::class, 'buscarParticipante'])->name('inscripcion.buscar');
    Route::post('/buscar-inscripcion', [InscripcionController::class, 'buscarInscripcion'])->name('inscripcion.buscar-inscripcion');
    Route::post('/calcular-precio', [InscripcionController::class, 'calcularPrecio'])->name('inscripcion.calcular-precio');
    Route::post('/', [InscripcionController::class, 'store'])->name('inscripcion.store');
    Route::get('/confirmacion/{inscripcion}', [InscripcionController::class, 'confirmacion'])->name('inscripcion.confirmacion');
});

// Rutas de administración
Route::prefix('admin')->name('admin.')->group(function () {
    // Gestión de ediciones
    Route::resource('ediciones', EdicionController::class);
    
    // Gestión de inscripciones
    Route::get('inscripciones', [AdminInscripcionController::class, 'index'])->name('inscripciones.index');
    Route::get('inscripciones/{inscripcion}', [AdminInscripcionController::class, 'show'])->name('inscripciones.show');
    Route::get('inscripciones/{inscripcion}/edit', [AdminInscripcionController::class, 'edit'])->name('inscripciones.edit');
    Route::put('inscripciones/{inscripcion}', [AdminInscripcionController::class, 'update'])->name('inscripciones.update');
    Route::delete('inscripciones/{inscripcion}', [AdminInscripcionController::class, 'destroy'])->name('inscripciones.destroy');
    Route::post('inscripciones/exportar', [AdminInscripcionController::class, 'exportar'])->name('inscripciones.exportar');
});
