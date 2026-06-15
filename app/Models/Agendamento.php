<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agendamento extends Model
{
    use Auditavel, HasFactory, HasUuids, SoftDeletes;

    const STATUS_AGUARDANDO_SINAL = 'aguardando_sinal';

    const STATUS_PENDENTE = 'pendente';

    const STATUS_CONFIRMADO = 'confirmado';

    const STATUS_FINALIZADO = 'finalizado';

    const STATUS_CANCELADO = 'cancelado';

    const STATUS_EM_ATENDIMENTO = 'em_atendimento';

    const STATUS_NO_SHOW = 'no_show';

    const SINAL_NENHUM = 'nenhum';

    const SINAL_PENDENTE = 'pendente';

    const SINAL_PAGO = 'pago';

    const SINAL_EXPIRADO = 'expirado';

    /** Status que não contam como agendamento ativo. */
    const STATUSES_INATIVOS = [self::STATUS_CANCELADO, self::STATUS_NO_SHOW];

    protected $fillable = [
        'company_id',
        'profissional_id',
        'cliente_id',
        'servico_id',
        'data_hora',
        'duracao',
        'valor',
        'status',
        'observacao',
        'cancel_token',
        'sinal_pct',
        'sinal_valor',
        'sinal_status',
        'sinal_payment_id',
        'sinal_payment_url',
        'sinal_pago_em',
        'aprovacao_manual',
    ];

    public static function generateCancelToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    protected function casts(): array
    {
        return [
            'data_hora' => 'datetime',
            'duracao' => 'integer',
            'valor' => 'decimal:2',
            'sinal_pct' => 'decimal:2',
            'sinal_valor' => 'decimal:2',
            'sinal_pago_em' => 'datetime',
            'aprovacao_manual' => 'boolean',
        ];
    }

    public function aguardandoSinal(): bool
    {
        return $this->status === self::STATUS_AGUARDANDO_SINAL;
    }

    public function sinalPago(): bool
    {
        return $this->sinal_status === self::SINAL_PAGO;
    }

    public function saldoDevido(): float
    {
        if ($this->aprovacao_manual) {
            return (float) $this->valor;
        }

        return max(0.0, (float) $this->valor - (float) $this->sinal_valor);
    }

    public function expiradoSemPagamento(): bool
    {
        return $this->status === self::STATUS_AGUARDANDO_SINAL
            && $this->created_at !== null
            && $this->created_at->diffInMinutes(now()) >= 10;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function avaliacao(): HasOne
    {
        return $this->hasOne(Avaliacao::class);
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::STATUSES_INATIVOS);
    }

    public function scopePorData(Builder $query, string $data): Builder
    {
        return $query->whereDate('data_hora', $data);
    }

    public function scopePorProfissional(Builder $query, string $profissionalId): Builder
    {
        return $query->where('profissional_id', $profissionalId);
    }
}
