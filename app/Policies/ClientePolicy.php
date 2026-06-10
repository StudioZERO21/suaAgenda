<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return $user->empresa_id === $cliente->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin_empresa', 'gestor']);
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->empresa_id === $cliente->company_id
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->empresa_id === $cliente->company_id
            && $user->hasRole('admin_empresa');
    }

    public function deleteAny(User $user): bool
    {
        return $user->empresa_id !== null
            && $user->hasRole('admin_empresa');
    }
}
