<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Agendamento;
use App\Models\User;

class AgendamentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, Agendamento $agendamento): bool
    {
        return $user->empresa_id === $agendamento->company_id;
    }

    public function create(User $user): bool
    {
        return $user->empresa_id !== null
            && $user->hasAnyRole(['admin_empresa', 'gestor', 'analista']);
    }

    public function update(User $user, Agendamento $agendamento): bool
    {
        return $user->empresa_id === $agendamento->company_id
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }

    public function delete(User $user, Agendamento $agendamento): bool
    {
        return $user->empresa_id === $agendamento->company_id
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }
}
