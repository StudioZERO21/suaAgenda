<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BloqueioAgenda;
use App\Models\User;

class BloqueioAgendaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, BloqueioAgenda $bloqueio): bool
    {
        return $user->empresa_id === $bloqueio->company_id;
    }

    public function create(User $user): bool
    {
        return $user->empresa_id !== null
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }

    public function update(User $user, BloqueioAgenda $bloqueio): bool
    {
        return $user->empresa_id === $bloqueio->company_id
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }

    public function delete(User $user, BloqueioAgenda $bloqueio): bool
    {
        return $user->empresa_id === $bloqueio->company_id
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }
}
