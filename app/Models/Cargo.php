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

class Cargo extends Model
{
    use BelongsToCompany, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'nome',
        'nivel',
        'cor',
        'descricao',
        'comissao_pct',
    ];

    protected $casts = [
        'comissao_pct' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function profissionais(): HasMany
    {
        return $this->hasMany(Profissional::class);
    }
}
