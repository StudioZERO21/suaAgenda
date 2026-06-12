<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditavel;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venda extends Model
{
    use Auditavel, BelongsToCompany, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'cliente_id',
        'profissional_id',
        'subtotal',
        'desconto',
        'total',
        'metodo_pagamento',
        'observacao',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'desconto' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(VendaItem::class);
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class);
    }
}
