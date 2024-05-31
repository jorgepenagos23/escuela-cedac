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


Route::get('/v1/usuarios_api2024B', [UsuariosController::class, 'index']); // LISTAR USUARIOS
Route::get('/v1/diplomados_api2024C', [DiplomadoController::class, 'index']); //LISTAR DIPLOMADOS

Route::get('/v1/cuentadepository_api2024D', [CuentadePagoController::class, 'index']);  //LISTAR NUMEROS DE CUENTA

Route::get('/v1/inscripciones_api2024E', [InscripcionController::class, 'index']); //LISTAR ALUMNOS INSCRITOS



Route::post('/alumno@create', [AlumnoController::class, 'store']);



Route::get('/v1/pagos_mensualidades_api2024F', [PagosController::class, 'index']); //listar todos los pagos de la tabla


Route::get('/v1/pagosmensualidatestotal/api2024G', [PagosController::class, 'sumaPagos']); // SUMA EL TOTAL DE COLEGIATURAS DE X DIPLOMADO SIN FECHA


Route::get('/v1/pagosmensualidadespendientes/api2024H', [PagosController::class, 'AlumnosAbonosTotalesporPeriodo']); // LISTAR EL TOTAL DE COLEGIATURAS MAS INSCRIPCION POR ALUMNO SIN FECHA

Route::get('/v1/pagospendientes/api2024H', [PagosController::class, 'AlumnosPendientes']); // LISTAR EL TOTAL DE COLEGIATURAS MAS INSCRIPCION POR ALUMNO SIN FECHA


Route::get('/v1/historial/alumno/', [PagosController::class, 'historialAlumno']);


//Route::get('/v1/alumnos/index/2024/nombres', [AlumnoController::class,'index_alumnos']);

Route::get('/v1/diplomados/index/2024/diplomados', [DiplomadoController::class, 'index_diplomados']); //MOMBRE DIPLOMADO CON ID


Route::get('/v1/inscripciones/recaudacion/suma', [InscripcionController::class, 'sumaIncripciones']); // suma INSCRIPCIONES TOTALES SIN FECHA

Route::get('/v1/cuentadeposito/index/2024/numero', [CuentadePagoController::class, 'index_cuenta']);




Route::post('/v1/pagosabonos/crear', [PagosController::class, 'store']);
Route::post('/v1/inscripcion/crear', [InscripcionController::class, 'store']);

Route::get('v1/listar/alumnos/parapagos/', [InscripcionController::class, 'index2']);






Route::prefix('alumnos')->group(function () {
});


Route::post('/create-token', [TokenController::class, 'createToken']);


Route::get('/v1/grupos/listar', [DiplomadoController::class, 'listarGrupos']);
Route::get('/v1/tutores/listar', [UsuariosController::class, 'listarUsuarios']);



Route::get('/v1/mostrar/alumno/status/{id}', [PagosController::class, 'show']);

Route::get('v1/directorio/pagos/mensualidades', [PagosController::class, 'directorio']);


Route::post('/v1/diplomados/fechaconsulta', [PagosController::class, 'diplomadostotalfecha']);


Route::get('v1/mensualidad/seguimiento', [PagosController::class,'pendientesPagar']);
Route::get('/v1/mostrar/alumno/show_sinpago/{id}', [PagosController::class, 'show_sinpago']);

Route::get('v1/listar/asesores', [UsuariosController::class,'listarAsesores']);
