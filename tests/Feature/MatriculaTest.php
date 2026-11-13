<?php

use App\Models\Curso;
use App\Models\EstudianteMatriculado;
use App\Models\Grupo;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->curso = Curso::create([
        'sigla' => 'IF6100',
        'nombre_curso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);
});

test('CP-07: un grupo sin docente no se abre aunque tenga cupo completo', function () {
    $grupo = crearGrupo01($this->curso);
    matricularEstudiantes($grupo, crearDocente(1001), 20);

    expect($grupo->docentes()->count())->toBe(0)
        ->and($grupo->estudiantes()->count())->toBe(20)
        ->and($grupo->puedeAbrirse())->toBeFalse()
        ->and(fn () => $grupo->abrir())->toThrow(DomainException::class);

    expect($grupo->fresh()->abierto)->toBeFalse();
});

test('CP-08: un estudiante queda ligado a su profesor consejero', function () {
    $docente1001 = crearDocente(1001);

    $estudiante = crearEstudianteC12345($docente1001);

    expect($estudiante->consejero->cod_empleado)->toBe(1001)
        ->and($docente1001->estudiantes)->toHaveCount(1)
        ->and($docente1001->estudiantes->first()->carne)->toBe('C12345');

    $this->assertDatabaseHas('estudiantes', [
        'carne' => 'C12345',
        'consejero_id' => $docente1001->id,
    ]);
});

test('CP-09: cambiar de consejero reemplaza al anterior, no lo suma', function () {
    $docente1001 = crearDocente(1001);
    $docente1002 = crearDocente(1002);
    $estudiante = crearEstudianteC12345($docente1001);

    $estudiante->consejero()->associate($docente1002);
    $estudiante->save();

    expect($estudiante->fresh()->consejero->cod_empleado)->toBe(1002)
        ->and($docente1001->fresh()->estudiantes)->toHaveCount(0)
        ->and($docente1002->fresh()->estudiantes)->toHaveCount(1);

    $this->assertDatabaseCount('estudiantes', 1);
    $this->assertDatabaseHas('estudiantes', [
        'carne' => 'C12345',
        'consejero_id' => $docente1002->id,
    ]);
});

test('CP-10: al matricular, la nota queda vacía', function () {
    $grupo = crearGrupo01($this->curso);
    $estudiante = crearEstudianteC12345(crearDocente(1001));

    $grupo->matricular($estudiante);

    $pivote = $grupo->estudiantes->first()->pivot;

    expect($pivote)->toBeInstanceOf(EstudianteMatriculado::class)
        ->and($pivote->nota)->toBeNull();

    $this->assertDatabaseCount('estudiantes_matriculados', 1);
    $this->assertDatabaseHas('estudiantes_matriculados', [
        'grupo_id' => $grupo->id,
        'estudiante_id' => $estudiante->id,
        'nota' => null,
    ]);
});

test('CP-11: registrar la nota al finalizar el curso', function () {
    $grupo = crearGrupo01($this->curso);
    $docente1001 = crearDocente(1001);
    $estudiante = crearEstudianteC12345($docente1001);
    $grupo->matricular($estudiante);
    matricularEstudiantes($grupo, $docente1001, 1);

    $matriculaAntes = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    $grupo->registrarNota($estudiante, 8.5);

    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    expect($matricula->nota)->toBe(8.5)
        ->and($matricula->id)->toBe($matriculaAntes->id)
        ->and($matricula->grupo_id)->toBe($grupo->id)
        ->and($matricula->estudiante_id)->toBe($estudiante->id);

    $otraMatricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', '!=', $estudiante->id)
        ->first();

    expect($otraMatricula->nota)->toBeNull();
    $this->assertDatabaseCount('estudiantes_matriculados', 2);
});

test('CP-12: rechazar notas menores que 0 o mayores que 10', function (float $nota) {
    $grupo = crearGrupo01($this->curso);
    $estudiante = crearEstudianteC12345(crearDocente(1001));
    $grupo->matricular($estudiante);

    expect(fn () => $grupo->registrarNota($estudiante, $nota))
        ->toThrow(InvalidArgumentException::class);

    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    expect($matricula->nota)->toBeNull();
})->with([-1, 10.5]);

test('CP-13: un estudiante no se matricula dos veces en el mismo grupo', function () {
    $grupo = crearGrupo01($this->curso);
    $estudiante = crearEstudianteC12345(crearDocente(1001));
    $grupo->matricular($estudiante);

    expect(fn () => $grupo->matricular($estudiante))
        ->toThrow(QueryException::class);

    $this->assertDatabaseCount('estudiantes_matriculados', 1);
    expect(Grupo::find($grupo->id)->estudiantes()->where('estudiante_id', $estudiante->id)->count())->toBe(1);
});