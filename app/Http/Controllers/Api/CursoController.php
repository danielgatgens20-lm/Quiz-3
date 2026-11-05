<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    /**
     * Crea un curso y lo guarda en la base de datos.
     */
    public function store(Request $request): JsonResponse
    {
        $datosValidados = $request->validate([
            'sigla' => ['required', 'string', 'max:10', 'unique:cursos,sigla'],
            'nombre_curso' => ['required', 'string', 'max:255'],
            'creditos' => ['required', 'integer', 'min:1', 'max:18'],
        ]);

        $curso = Curso::create($datosValidados);

        return response()->json($curso, 201);
    }
}