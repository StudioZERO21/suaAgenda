<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notificacao;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificacaoController extends Controller
{
    public function listar(Request $request): View
    {
        $companyId = auth()->user()->empresa_id;

        $tipo = $request->input('tipo', '');
        $lida = $request->input('lida', '');

        $notifs = Notificacao::where('company_id', $companyId)
            ->when($tipo !== '', fn ($q) => $q->where('tipo', $tipo))
            ->when($lida === '0', fn ($q) => $q->whereNull('read_at'))
            ->when($lida === '1', fn ($q) => $q->whereNotNull('read_at'))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $tipos = Notificacao::where('company_id', $companyId)
            ->select('tipo')
            ->distinct()
            ->pluck('tipo')
            ->sort()
            ->values();

        return view('notificacoes.index', compact('notifs', 'tipos', 'tipo', 'lida'));
    }

    public function index(): JsonResponse
    {
        $companyId = auth()->user()->empresa_id;

        if (! $companyId) {
            return response()->json([]);
        }

        $notifs = Notificacao::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'tipo' => $n->tipo,
                'title' => $n->titulo,
                'msg' => $n->mensagem,
                'time' => $this->humanTime($n->created_at),
                'read' => $n->read_at !== null,
                'color' => Notificacao::colorFor($n->tipo),
            ]);

        return response()->json($notifs);
    }

    public function unreadCount(): JsonResponse
    {
        $companyId = auth()->user()->empresa_id;

        $count = $companyId
            ? Notificacao::where('company_id', $companyId)->whereNull('read_at')->count()
            : 0;

        return response()->json(['count' => $count]);
    }

    public function destroy(Notificacao $notificacao): JsonResponse
    {
        $this->ensureOwnership($notificacao);

        $notificacao->delete();

        return response()->json(['ok' => true]);
    }

    public function markRead(Notificacao $notificacao): JsonResponse
    {
        $this->ensureOwnership($notificacao);

        $notificacao->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(): JsonResponse
    {
        $companyId = auth()->user()->empresa_id;

        Notificacao::where('company_id', $companyId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function ensureOwnership(Notificacao $notificacao): void
    {
        abort_if($notificacao->company_id !== auth()->user()->empresa_id, 403);
    }

    private function humanTime(Carbon $dt): string
    {
        $diff = now()->diffInSeconds($dt);
        if ($diff < 60) {
            return 'agora';
        }
        if ($diff < 3600) {
            return now()->diffInMinutes($dt).'min';
        }
        if ($diff < 86400) {
            return now()->diffInHours($dt).'h';
        }

        return $dt->format('d/m');
    }
}
