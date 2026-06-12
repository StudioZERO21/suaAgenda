<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produto extends Model
{
    use BelongsToCompany, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'nome',
        'sku',
        'categoria',
        'preco',
        'custo',
        'estoque',
        'estoque_min',
        'unidade',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'custo' => 'decimal:2',
        'estoque' => 'integer',
        'estoque_min' => 'integer',
        'ativo' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function imagens(): HasMany
    {
        return $this->hasMany(ProdutoImagem::class)->orderBy('ordem');
    }

    public function estoqueStatus(): string
    {
        if ($this->estoque <= 0) {
            return 'zerado';
        }
        if ($this->estoque <= $this->estoque_min) {
            return 'baixo';
        }

        return 'ok';
    }
}
