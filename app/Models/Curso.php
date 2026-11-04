<?php

namespace App\Models;

use Database\Factories\CursoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    
    use HasFactory;

    protected $table = 'cursos';

    protected $fillable = ['sigla', 'nombre_curso', 'creditos'];

    protected function casts(): array
    {
        return [
            'creditos' => 'integer',
        ];
    }
}
