<?php

use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\CuentadePagoController;
use App\Http\Controllers\DiplomadoController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\PagosController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\UsuariosController;
use App\Models\CuentadeDeposito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------z
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {


    return $request->user();
});


Route::get('/v1/alumnos_api2024A', [AlumnoController::class, 'index'])->name('alumnos.index');

Route::get('/v1/usuarios_api2024B', [UsuariosController::class, 'index']);

Route::get('/v1/diplomados_api2024C', [DiplomadoController::class,'index']);


Route::get('/v1/cuentadepository_api2024D', [CuentadePagoController::class, 'index']);
Route::get('/v1/inscripciones_api2024E', [InscripcionController::class,'index']);



Route::post('/alumno@create', [AlumnoController::class,'store']);



Route::get('/v1/pagos_mensualidades_api2024F', [PagosController::class,'index']);



Route::prefix('alumnos')->group( function()
{
});


Route::post('/create-token', [TokenController::class, 'createToken']);


