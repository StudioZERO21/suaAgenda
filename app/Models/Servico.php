<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Servico extends Model
{
    use Auditavel, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'nome',
        'descricao',
        'duracao_minutos',
        'preco',
        'categoria',
        'cor',
        'icone',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'ativo' => 'boolean',
            'duracao_minutos' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function profissionais(): BelongsToMany
    {
        return $this->belongsToMany(Profissional::class, 'profissional_servico');
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function duracaoFormatada(): string
    {
        $h = intdiv($this->duracao_minutos, 60);
        $m = $this->duracao_minutos % 60;

        return $h > 0 ? "{$h}h".($m > 0 ? "{$m}min" : '') : "{$m}min";
    }

    public function precoFormatado(): string
    {
        return 'R$ '.number_format((float) $this->preco, 2, ',', '.');
    }
}
