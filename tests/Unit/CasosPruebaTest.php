<?php

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

beforeEach(function () {
    $this->curso = Curso::create([
        'sigla' => 'IF6100',
        'nombre_curso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);
});

function crearGrupo01(Curso $curso, bool $abierto = false): Grupo
{
    return Grupo::create([
        'curso_id' => $curso->id,
        'numero_ciclo' => 2,
        'ano' => 2026,
        'numero_grupo' => 1,
        'abierto' => $abierto,
    ]);
}

function crearDocente(int $codEmpleado): Docente
{
    $docentes = [
        1001 => ['Ana', 'Mora Vargas'],
        1002 => ['Luis', 'Rojas Solano'],
        1003 => ['Carla', 'Jiménez Araya'],
    ];

    return Docente::create([
        'cod_empleado' => $codEmpleado,
        'nombre_empleado' => $docentes[$codEmpleado][0],
        'apellidos_empleado' => $docentes[$codEmpleado][1],
    ]);
}

function matricularEstudiantes(Grupo $grupo, Docente $consejero, int $cantidad): void
{
    $estudiantes = Estudiante::factory()
        ->count($cantidad)
        ->for($consejero, 'consejero')
        ->create();

    foreach ($estudiantes as $estudiante) {
        $grupo->matricular($estudiante);
    }
}


test('CP-01: registrar un grupo con toda su información y su curso', function () {
    $grupoCreado = crearGrupo01($this->curso, abierto: true);

    $grupo = Grupo::find($grupoCreado->id);

    expect($grupo->numero_ciclo)->toBe(2)
        ->and($grupo->ano)->toBe(2026)
        ->and($grupo->numero_grupo)->toBe(1)
        ->and($grupo->abierto)->toBeTrue()
        ->and($grupo->curso->sigla)->toBe('IF6100')
        ->and($grupo->curso->creditos)->toBe(4);

    $this->assertDatabaseCount('grupos', 1);
});

test('CP-02: un curso tiene varios grupos y se distinguen los abiertos', function () {
    foreach ([1 => true, 2 => true, 3 => false] as $numeroGrupo => $abierto) {
        Grupo::create([
            'curso_id' => $this->curso->id,
            'numero_ciclo' => 2,
            'ano' => 2026,
            'numero_grupo' => $numeroGrupo,
            'abierto' => $abierto,
        ]);
    }

    $grupos = $this->curso->grupos;
    $abiertos = $grupos->where('abierto', true);

    expect($grupos)->toHaveCount(3)
        ->and($abiertos)->toHaveCount(2)
        ->and($abiertos->pluck('numero_grupo')->sort()->values()->all())->toBe([1, 2])
        ->and($grupos->pluck('curso_id')->unique()->all())->toBe([$this->curso->id]);
});

test('CP-03: un grupo acepta dos docentes', function () {
    $grupo = crearGrupo01($this->curso);
    $docente1001 = crearDocente(1001);
    $docente1002 = crearDocente(1002);

    $grupo->asignarDocente($docente1001);
    $grupo->asignarDocente($docente1002);

    expect($grupo->docentes)->toHaveCount(2)
        ->and($grupo->docentes->pluck('cod_empleado')->sort()->values()->all())->toBe([1001, 1002])
        ->and($docente1001->grupos->contains($grupo))->toBeTrue();

    $this->assertDatabaseCount('docente_grupo', 2);
});

test('CP-04: un grupo rechaza un tercer docente', function () {
    $grupo = crearGrupo01($this->curso);
    $grupo->asignarDocente(crearDocente(1001));
    $grupo->asignarDocente(crearDocente(1002));
    $docente1003 = crearDocente(1003);

    expect(fn () => $grupo->asignarDocente($docente1003))
        ->toThrow(DomainException::class);

    expect($grupo->docentes()->count())->toBe(2);
    $this->assertDatabaseMissing('docente_grupo', ['docente_id' => $docente1003->id]);
});

test('CP-05: un grupo con 14 estudiantes no puede abrirse', function () {
    $grupo = crearGrupo01($this->curso);
    $docente1001 = crearDocente(1001);
    $grupo->asignarDocente($docente1001);
    matricularEstudiantes($grupo, $docente1001, 14);

    expect($grupo->puedeAbrirse())->toBeFalse()
        ->and(fn () => $grupo->abrir())->toThrow(DomainException::class);

    expect($grupo->fresh()->abierto)->toBeFalse();
});

test('CP-06: un grupo con 15 estudiantes y un docente se abre', function () {
    $grupo = crearGrupo01($this->curso);
    $docente1001 = crearDocente(1001);
    $grupo->asignarDocente($docente1001);
    matricularEstudiantes($grupo, $docente1001, 15);

    expect($grupo->puedeAbrirse())->toBeTrue();

    $grupo->abrir();

    expect($grupo->fresh()->abierto)->toBeTrue();
});