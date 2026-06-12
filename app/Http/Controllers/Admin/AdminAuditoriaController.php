<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portal de auditoria LGPD — trilha completa de atividades do sistema,
 * com filtros por empresa, evento, tipo de registro e período.
 */
class AdminAuditoriaController extends Controller
{
    public function index(): View
    {
        $empresas = Company::orderBy('name')->get(['id', 'name']);

        $eventos = Activity::select('event')
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        $tipos = Activity::select('subject_type')
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->map(fn (string $type) => ['value' => $type, 'label' => class_basename($type)]);

        return view('admin.auditoria', compact('empresas', 'eventos', 'tipos'));
    }

    public function json(Request $request): JsonResponse
    {
        $request->validate([
            'empresa_id' => ['nullable', 'uuid'],
            'evento' => ['nullable', 'string', 'max:50'],
            'tipo' => ['nullable', 'string', 'max:120'],
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date'],
            'busca' => ['nullable', 'string', 'max:100'],
        ]);

        $atividades = Activity::with(['causer:id,name,email', 'subject'])
            ->when($request->empresa_id, fn ($q) => $q->daEmpresa($request->empresa_id))
            ->when($request->evento, fn ($q) => $q->where('event', $request->evento))
            ->when($request->tipo, fn ($q) => $q->where('subject_type', $request->tipo))
            ->when($request->de, fn ($q) => $q->where('created_at', '>=', $request->date('de')->startOfDay()))
            ->when($request->ate, fn ($q) => $q->where('created_at', '<=', $request->date('ate')->endOfDay()))
            ->when($request->busca, fn ($q) => $q->where('description', 'like', "%{$request->busca}%"))
            ->orderByDesc('created_at')
            ->paginate(30);

        $items = collect($atividades->items())->map(fn (Activity $a) => [
            'id' => $a->id,
            'quando' => $a->created_at->format('d/m/Y H:i:s'),
            'log' => $a->log_name,
            'evento' => $a->event ?? '—',
            'descricao' => $a->description,
            'tipo' => $a->subject_type ? class_basename($a->subject_type) : '—',
            'subject_id' => $a->subject_id,
            'causer' => $a->causer?->name ?? 'Sistema',
            'causer_email' => $a->causer?->email ?? '',
            'company_id' => $a->company_id,
            'mudancas' => $a->attribute_changes,
            'properties' => $a->properties,
        ]);

        return response()->json([
            'total' => $atividades->total(),
            'pagina' => $atividades->currentPage(),
            'ultima_pagina' => $atividades->lastPage(),
            'items' => $items,
        ]);
    }
}
