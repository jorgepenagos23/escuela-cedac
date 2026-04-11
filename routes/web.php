<?php

use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DiplomadoController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\PagosController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RolesPermisosController;
use App\Http\Controllers\DescuentoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');




Route::get('/estadisticas', function () {
    return Inertia::render('Estadisticas');
})->middleware(['auth', 'verified'])->name('Estadisticas');




Route::get('/diplomados', [DiplomadoController::class, 'diplomado'])->name('index.diplomado')->middleware('auth');
Route::get('/alumnos', [AlumnoController::class, 'alumno'])->name('vista.alumnos')->middleware('auth');
Route::post('/alumnos/import/admissions', [AlumnoController::class, 'importAdmissions'])->name('import.admissions')->middleware('auth');
Route::post('/alumnos/import/historical', [AlumnoController::class, 'importHistorical'])->name('import.historical')->middleware('auth');
Route::get('/inscripciones', [InscripcionController::class, 'inscripciones'])->name('vista.inscripciones')->middleware('auth');
Route::get('/seguimiento/inscripciones', [InscripcionController::class, 'seguimiento_inscripciones'])->name('seguimiento.inscripciones')->middleware('auth');
Route::get('/seguimiento/inscripciones/comprobante/{id}', [InscripcionController::class, 'imprimirFichaInscripcion'])->name('seguimiento.comprobante_pdf')->middleware('auth');
Route::get('/buscador-alumnos', [InscripcionController::class, 'buscadorAlumnos'])->name('buscador.alumnos')->middleware('auth');

// ── Módulo Descuentos ────────────────────────────────────────────────────────
Route::middleware('auth')->prefix('descuentos')->name('descuentos.')->group(function () {
    Route::get('/',            [DescuentoController::class, 'index'])->name('index');
    Route::post('/',           [DescuentoController::class, 'store'])->name('store');
    Route::put('/{descuento}', [DescuentoController::class, 'update'])->name('update');
    Route::patch('/{descuento}/estado', [DescuentoController::class, 'cambiarEstado'])->name('estado');
    Route::delete('/{descuento}',       [DescuentoController::class, 'destroy'])->name('destroy');
});


Route::get('/crud-alumnos', [AlumnoController::class, 'crudalumnos']);

Route::get('/crud-pagos', [PagosController::class, 'crudPagos']);

Route::get('/contabilidad', [PagosController::class, 'vistaContabilidad'])->name('vista.contabilidad')->middleware('auth');


Route::get('/mensualidades/pagos',[PagosController::class,'vistaPagos'])->name('vista.pagos')->middleware( 'auth');

Route::get('/mi-panel', [\App\Http\Controllers\MiPanelController::class,'indexView'])->name('vista.mi_panel')->middleware('auth');
Route::get('/mi-panel/data', [\App\Http\Controllers\MiPanelController::class,'getData'])->middleware('auth');

Route::get('/alumnos-liquidados', [\App\Http\Controllers\AlumnosLiquidadosController::class,'indexView'])->name('vista.alumnos_liquidados')->middleware('auth');

Route::get('/alumnos-liquidados/certificado/{id}', [\App\Http\Controllers\AlumnosLiquidadosController::class,'imprimirCertificado'])->name('certificado')->middleware('auth');

Route::get('/resumen', [PagosController::class,'vistaResumen']);

Route::get( '/seguimiento/tutorias', [PagosController::class,'seguimientotutorias']);

Route::get('/pagos/{pago}/pdf', [PagosController::class, 'generarPdfPago'])->name('pagos.pdf')->middleware('auth');

//Route::get('/estadisticas', [Controller::class, 'estadisticas']);

Route::get('v1/dialog/pagos/mensualidades/alumnos', [PagosController::class,'vistaPagosAgregar']);

Route::get('/agregar/diplomado' , [DiplomadoController::class,'diplomadosCrud']);


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    //Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    //Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas protegidas solo para TI usando middleware de Spatie Permission
    Route::middleware(['role:TI'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');

        // Gestión de Roles y Permisos
        Route::get('/roles-permisos', [RolesPermisosController::class, 'index'])->name('roles_permisos.index');
        Route::post('/roles-permisos/{role}', [RolesPermisosController::class, 'update'])->name('roles_permisos.update');
    });
});

require __DIR__ . '/auth.php';
