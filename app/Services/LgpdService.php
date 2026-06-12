<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Venda;
use Illuminate\Support\Facades\Storage;

/**
 * Direitos do titular (LGPD): portabilidade (exportação) e
 * esquecimento (anonimização irreversível dos dados pessoais).
 */
class LgpdService
{
    /**
     * Exporta todos os dados pessoais e histórico do titular.
     *
     * @return array<string, mixed>
     */
    public function exportarDados(Cliente $cliente): array
    {
        $agendamentos = Agendamento::where('cliente_id', $cliente->id)
            ->with(['servico:id,nome', 'profissional:id,name', 'avaliacao'])
            ->orderByDesc('data_hora')
            ->get();

        $compras = Venda::withoutGlobalScope('company')
            ->where('cliente_id', $cliente->id)
            ->with('itens')
            ->orderByDesc('created_at')
            ->get();

        return [
            'exportado_em' => now()->toIso8601String(),
            'empresa' => $cliente->company?->name,
            'dados_pessoais' => [
                'nome' => $cliente->name,
                'telefone' => $cliente->phone,
                'email' => $cliente->email,
                'data_nascimento' => $cliente->data_nasc?->format('Y-m-d'),
                'observacao' => $cliente->observacao,
                'cadastrado_em' => $cliente->created_at->toIso8601String(),
                'consentimento_lgpd' => $cliente->lgpd_consent,
                'consentimento_em' => $cliente->lgpd_consent_at?->toIso8601String(),
            ],
            'agendamentos' => $agendamentos->map(fn (Agendamento $ag) => [
                'data_hora' => $ag->data_hora->toIso8601String(),
                'servico' => $ag->servico?->nome,
                'profissional' => $ag->profissional?->name,
                'status' => $ag->status,
                'valor' => (float) $ag->valor,
                'avaliacao' => $ag->avaliacao ? [
                    'nota' => $ag->avaliacao->nota,
                    'comentario' => $ag->avaliacao->comentario,
                ] : null,
            ])->values()->all(),
            'compras' => $compras->map(fn (Venda $venda) => [
                'data' => $venda->created_at->toIso8601String(),
                'total' => (float) $venda->total,
                'metodo_pagamento' => $venda->metodo_pagamento,
                'itens' => $venda->itens->map(fn ($item) => [
                    'descricao' => $item->descricao,
                    'qtd' => $item->qtd,
                    'total' => (float) $item->total,
                ])->values()->all(),
            ])->values()->all(),
            'fotos' => $cliente->fotos->map(fn ($foto) => [
                'tipo' => $foto->tipo,
                'legenda' => $foto->legenda,
                'enviada_em' => $foto->created_at->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * Direito ao esquecimento: remove PII de forma irreversível,
     * apaga fotos do storage e arquiva o registro (soft delete).
     * Históricos financeiros são mantidos de forma anonimizada.
     */
    public function anonimizar(Cliente $cliente): void
    {
        foreach ($cliente->fotos as $foto) {
            Storage::disk('public')->delete($foto->imagem_path);
            $foto->delete();
        }

        $cliente->update([
            'name' => 'Cliente anonimizado',
            'phone' => null,
            'email' => null,
            'data_nasc' => null,
            'observacao' => null,
            'lgpd_consent' => false,
            'ativo' => false,
            'anonymized_at' => now(),
        ]);

        activity('lgpd')
            ->performedOn($cliente)
            ->causedBy(auth()->user())
            ->event('anonimizado')
            ->log('Dados do titular anonimizados (direito ao esquecimento)');

        $cliente->delete();
    }

    public function registrarExportacao(Cliente $cliente): void
    {
        activity('lgpd')
            ->performedOn($cliente)
            ->causedBy(auth()->user())
            ->event('exportado')
            ->log('Dados do titular exportados (portabilidade)');
    }
}
