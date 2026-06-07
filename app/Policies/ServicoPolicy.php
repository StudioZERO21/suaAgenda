<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Servico;
use App\Models\User;

class ServicoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, Servico $servico): bool
    {
        return $user->empresa_id === $servico->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin_empresa', 'gestor']);
    }

    public function update(User $user, Servico $servico): bool
    {
        return $user->empresa_id === $servico->company_id
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }

    public function delete(User $user, Servico $servico): bool
    {
        return $user->empresa_id === $servico->company_id
            && $user->hasRole('admin_empresa');
    }
}
