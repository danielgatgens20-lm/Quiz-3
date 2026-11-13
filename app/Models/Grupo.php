<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;

class Grupo extends Model
{
    use HasFactory;

    protected $table = 'grupos';

    protected $fillable = ['numero_ciclo', 'ano', 'numero_grupo', 'abierto', 'curso_id'];

    protected function casts(): array
    {
        return [
            'numero_ciclo' => 'integer',
            'ano' => 'integer',
            'numero_grupo' => 'integer',
            'abierto' => 'boolean',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(Docente::class, 'grupo_docente')
            ->withTimestamps();
    }

    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'estudiantes_matriculados')
            ->using(EstudianteMatriculado::class)
            ->withPivot('nota')
            ->withTimestamps();
    }

    /**
     * RN-02: un grupo lo imparten como máximo 2 docentes.
     */
    public function asignarDocente(Docente $docente): void
    {
        if ($this->docentes()->count() >= 2) {
            throw new DomainException('Un grupo solo puede ser impartido por 1 o 2 docentes.');
        }

        $this->docentes()->attach($docente);
    }

    public function matricular(Estudiante $estudiante): void
    {
        $this->estudiantes()->attach($estudiante, ['nota' => null]);
    }

    /**
     * RN-06: la nota va de 0 a 10 (escala UCR).
     */
    public function registrarNota(Estudiante $estudiante, float $nota): void
    {
        if ($nota < 0 || $nota > 10) {
            throw new InvalidArgumentException('La nota debe estar entre 0 y 10.');
        }

        $this->estudiantes()->updateExistingPivot($estudiante->id, ['nota' => $nota]);
    }

    /**
     * RN-02 y RN-05: al menos 1 docente y al menos 15 estudiantes matriculados.
     */
    public function puedeAbrirse(): bool
    {
        return $this->docentes()->count() >= 1
            && $this->estudiantes()->count() >= 15;
    }

    public function abrir(): void
    {
        if (! $this->puedeAbrirse()) {
            throw new DomainException('Un grupo necesita al menos 1 docente y 15 estudiantes para abrirse.');
        }

        $this->update(['abierto' => true]);
    }
}
