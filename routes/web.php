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
Route::get('/inscripciones', [InscripcionController::class, 'inscripciones'])->name('vista.inscripciones')->middleware('auth');
Route::get('/seguimiento/inscripciones', [InscripcionController::class, 'seguimiento_inscripciones'])->name('seguimiento.inscripciones')->middleware('auth');

Route::get('/crud-alumnos', [AlumnoController::class, 'crudalumnos']);    

Route::get('/crud-pagos', [PagosController::class, 'crudPagos']);    





//Route::get('/estadisticas', [Controller::class, 'estadisticas']);






Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
