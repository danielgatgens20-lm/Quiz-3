<?php

namespace App\Models;

use Database\Factories\EstudianteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Estudiante extends Model
{
    
    use HasFactory;

    protected $table = 'estudiantes';

    protected $fillable = ['carne', 'nombre', 'apellidos', 'consejero_id'];

   
    public function consejero(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'consejero_id');
    }
}