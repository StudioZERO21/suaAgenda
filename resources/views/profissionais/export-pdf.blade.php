<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Funcionários — {{ $company->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        p.meta { font-size: 10px; color: #666; margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f5f5f5; text-align: left; padding: 8px 10px; font-size: 9px;
             text-transform: uppercase; letter-spacing: .4px; color: #666; border-bottom: 1px solid #e2e2e2; }
        td { padding: 8px 10px; border-bottom: 1px solid #e2e2e2; vertical-align: top; }
        tr:nth-child(even) td { background: #fafafa; }
        .footer { margin-top: 16px; font-size: 9px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <h1>Lista de Funcionários</h1>
    <p class="meta">{{ $company->name }} · Gerado em {{ now()->format('d/m/Y H:i') }} · {{ $profissionais->count() }} registro(s)</p>

    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Especialidade</th>
                <th>Telefone</th>
                <th>Comissão</th>
                <th>Serviços</th>
                <th>Agendamentos</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($profissionais as $p)
            <tr>
                <td>{{ $p->name }}</td>
                <td>{{ $p->especialidade ?? '—' }}</td>
                <td>{{ \App\Support\PhoneFormatter::format($p->phone) ?: '—' }}</td>
                <td>{{ $p->comissao_pct !== null ? number_format((float) $p->comissao_pct, 0) . '%' : '—' }}</td>
                <td>{{ $p->servicos->pluck('nome')->join(', ') ?: '—' }}</td>
                <td>{{ $p->agendamentos_count }}</td>
                <td>{{ $p->ativo ? 'Ativo' : 'Inativo' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;color:#999;padding:24px">Nenhum funcionário cadastrado</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">suaAgenda.pro — exportação confidencial</p>
</body>
</html>
