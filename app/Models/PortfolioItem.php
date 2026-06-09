<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PortfolioItem extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['company_id', 'profissional_id', 'titulo', 'categoria', 'destaque', 'tags', 'imagem_path'];

    protected function casts(): array
    {
        return [
            'destaque' => 'boolean',
            'tags' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }
}
