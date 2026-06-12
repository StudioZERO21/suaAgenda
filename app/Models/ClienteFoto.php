<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClienteFoto extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'cliente_fotos';

    protected $fillable = [
        'cliente_id',
        'imagem_path',
        'legenda',
        'tipo',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
