<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdutoImagem extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'produto_imagens';

    protected $fillable = [
        'produto_id',
        'imagem_path',
        'is_capa',
        'ordem',
    ];

    protected $casts = [
        'is_capa' => 'boolean',
        'ordem' => 'integer',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
