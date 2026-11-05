<?php

namespace App\Models;

use Database\Factories\GrupoFactory;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Grupo extends Model
{
    
    use HasFactory;

    public const MAX_DOCENTES = 2;

    public const MIN_ESTUDIANTES = 15;

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
        return $this->belongsToMany(Docente::class, 'docente_grupo')
            ->withTimestamps();
    }

    
    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'estudiantes_matriculados')
            ->using(EstudianteMatriculado::class)
            ->withPivot('nota')
            ->withTimestamps();
    }

    
    public function asignarDocente(Docente $docente): void
    {
        if ($this->docentes()->count() >= self::MAX_DOCENTES) {
            throw new DomainException('Un grupo solo puede ser impartido por 1 o 2 docentes.');
        }

        $this->docentes()->attach($docente);
    }

    
    public function matricular(Estudiante $estudiante): void
    {
        $this->estudiantes()->attach($estudiante, ['nota' => null]);
    }

    
    public function registrarNota(Estudiante $estudiante, float $nota): void
    {
        $this->estudiantes()->updateExistingPivot($estudiante->id, ['nota' => $nota]);
    }

    public function puedeAbrirse(): bool
    {
        return $this->docentes()->count() >= 1
            && $this->estudiantes()->count() >= self::MIN_ESTUDIANTES;
    }

    public function abrir(): void
    {
        if (! $this->puedeAbrirse()) {
            throw new DomainException('Un grupo necesita al menos 1 docente y 15 estudiantes para abrirse.');
        }

        $this->update(['abierto' => true]);
    }
}