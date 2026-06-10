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
        if ($user->empresa_id !== $agendamento->company_id) {
            return false;
        }
        if ($user->hasRole('admin_empresa')) {
            return true;
        }
        // Profissional vinculado → apenas os próprios agendamentos
        if ($user->profissional_id !== null) {
            return $user->profissional_id === $agendamento->profissional_id;
        }

        return $user->hasRole('gestor');
    }

    public function delete(User $user, Agendamento $agendamento): bool
    {
        return $user->empresa_id === $agendamento->company_id
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }

    public function updateAnyStatus(User $user): bool
    {
        return $user->empresa_id !== null
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }
}
