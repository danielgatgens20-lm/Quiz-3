<?php

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

test('se instancia un grupo con toda su información asociada', function () {
    
    $curso = Curso::create([
        'sigla' => 'IF6100',
        'nombre_curso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);

    
    $grupo = Grupo::create([
        'numero_ciclo' => 2,
        'ano' => 2026,
        'numero_grupo' => 1,
        'abierto' => true,
        'curso_id' => $curso->id,
    ]);

    
    $docente1 = Docente::create([
        'cod_empleado' => 10234,
        'nombre_empleado' => 'María',
        'apellidos_empleado' => 'Rojas Vargas',
    ]);
    $docente2 = Docente::create([
        'cod_empleado' => 10587,
        'nombre_empleado' => 'Carlos',
        'apellidos_empleado' => 'Jiménez Solís',
    ]);
    $grupo->asignarDocente($docente1);
    $grupo->asignarDocente($docente2);

    
    $estudiantes = Estudiante::factory()
        ->count(15)
        ->for($docente1, 'consejero')
        ->create();

    foreach ($estudiantes as $estudiante) {
        $grupo->matricular($estudiante);
    }

    
    foreach ($estudiantes as $estudiante) {
        $grupo->registrarNota($estudiante, 85.5);
    }

    $grupo->refresh()->load(['curso', 'docentes', 'estudiantes.consejero']);

    
    expect($grupo->numero_ciclo)->toBe(2)
        ->and($grupo->ano)->toBe(2026)
        ->and($grupo->numero_grupo)->toBe(1)
        ->and($grupo->abierto)->toBeTrue();

    
    expect($grupo->curso->sigla)->toBe('IF6100')
        ->and($grupo->curso->nombre_curso)->toBe('Análisis y Diseño de Sistemas')
        ->and($grupo->curso->creditos)->toBe(4);

    
    expect($grupo->docentes)->toHaveCount(2)
        ->and($grupo->docentes->pluck('cod_empleado')->all())->toContain(10234, 10587);

    
    expect($grupo->estudiantes)->toHaveCount(15);

    foreach ($grupo->estudiantes as $estudiante) {
        expect($estudiante->consejero->is($docente1))->toBeTrue()
            ->and($estudiante->pivot->nota)->toBe(85.5);
    }
});

test('un grupo no puede ser impartido por más de 2 docentes', function () {
    $grupo = Grupo::factory()->create();
    $grupo->asignarDocente(Docente::factory()->create());
    $grupo->asignarDocente(Docente::factory()->create());

    $grupo->asignarDocente(Docente::factory()->create());
})->throws(DomainException::class);