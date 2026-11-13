<?php

use App\Models\Curso;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('crear un curso lo guarda en la base de datos', function () {
    $datosDelCurso = [
        'sigla' => 'IF6000',
        'nombre_curso' => 'Redes en los Negocios',
        'creditos' => 4,
    ];

    $response = $this->postJson('/api/cursos', $datosDelCurso);

    $response->assertCreated()
        ->assertJsonFragment($datosDelCurso);

    $this->assertDatabaseHas('cursos', $datosDelCurso);
    $this->assertDatabaseCount('cursos', 1);
});

test('no guarda un curso si faltan datos obligatorios', function () {
    $response = $this->postJson('/api/cursos', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['sigla', 'nombre_curso', 'creditos']);

    $this->assertDatabaseCount('cursos', 0);
});

test('no guarda un curso con una sigla repetida', function () {
    Curso::create([
        'sigla' => 'IF6000',
        'nombre_curso' => 'Redes en los Negocios',
        'creditos' => 4,
    ]);

    $response = $this->postJson('/api/cursos', [
        'sigla' => 'IF6000',
        'nombre_curso' => 'Otro curso',
        'creditos' => 3,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['sigla']);

    $this->assertDatabaseCount('cursos', 1);
});