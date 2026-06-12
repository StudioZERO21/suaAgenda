<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Avaliacao;
use App\Models\User;

class AvaliacaoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, Avaliacao $avaliacao): bool
    {
        return $user->empresa_id === $avaliacao->company_id;
    }

    public function update(User $user, Avaliacao $avaliacao): bool
    {
        return $user->empresa_id === $avaliacao->company_id
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }

    public function delete(User $user, Avaliacao $avaliacao): bool
    {
        return $user->empresa_id === $avaliacao->company_id
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }
}
