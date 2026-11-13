<?php

use App\Http\Controllers\Api\CursoController;
use Illuminate\Support\Facades\Route;

Route::post('/cursos', [CursoController::class, 'store'])->name('cursos.store');