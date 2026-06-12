<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lancamento;
use App\Models\User;

class LancamentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, Lancamento $lancamento): bool
    {
        return $user->empresa_id === $lancamento->company_id;
    }

    public function create(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function update(User $user, Lancamento $lancamento): bool
    {
        return $user->empresa_id === $lancamento->company_id;
    }

    public function delete(User $user, Lancamento $lancamento): bool
    {
        return $user->empresa_id === $lancamento->company_id
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }
}
