<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Agendamento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Saúde do sistema — métricas operacionais para o super_admin.
 */
class AdminSaudeController extends Controller
{
    public function index(): View
    {
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $jobsPendentes = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $sessoesAtivas = Schema::hasTable('sessions')
            ? DB::table('sessions')->where('last_activity', '>=', now()->subMinutes(30)->getTimestamp())->count()
            : 0;

        $tamanhoBancoMb = null;
        if (DB::connection()->getDriverName() === 'mysql') {
            $row = DB::selectOne(
                'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS mb
                 FROM information_schema.tables WHERE table_schema = DATABASE()'
            );
            $tamanhoBancoMb = (float) ($row->mb ?? 0);
        }

        $storageMb = round($this->tamanhoDiretorio(storage_path('app/public')) / 1024 / 1024, 1);

        $ultimoAgendamento = Agendamento::orderByDesc('created_at')->value('created_at');
        $atividades24h = Activity::where('created_at', '>=', now()->subDay())->count();
        $logins24h = Activity::where('log_name', 'auth')->where('event', 'login')
            ->where('created_at', '>=', now()->subDay())->count();
        $loginsFalhos24h = Activity::where('log_name', 'auth')->where('event', 'login_falho')
            ->where('created_at', '>=', now()->subDay())->count();

        return view('admin.saude', [
            'failedJobs' => $failedJobs,
            'jobsPendentes' => $jobsPendentes,
            'sessoesAtivas' => $sessoesAtivas,
            'tamanhoBancoMb' => $tamanhoBancoMb,
            'storageMb' => $storageMb,
            'ultimoAgendamento' => $ultimoAgendamento,
            'atividades24h' => $atividades24h,
            'logins24h' => $logins24h,
            'loginsFalhos24h' => $loginsFalhos24h,
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
            'ambiente' => app()->environment(),
        ]);
    }

    private function tamanhoDiretorio(string $caminho): int
    {
        if (! is_dir($caminho)) {
            return 0;
        }

        $total = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($caminho, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $arquivo) {
            $total += $arquivo->getSize();
        }

        return $total;
    }
}
