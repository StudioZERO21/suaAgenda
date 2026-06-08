<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lancamento extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'agendamento_id',
        'venda_id',
        'tipo',
        'descricao',
        'categoria',
        'valor',
        'data',
        'status',
        'metodo_pagamento',
        'observacao',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class);
    }
}
