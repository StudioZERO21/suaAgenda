<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Venda;

class VendaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, Venda $venda): bool
    {
        return $user->empresa_id === $venda->company_id;
    }

    public function create(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function update(User $user, Venda $venda): bool
    {
        return $user->empresa_id === $venda->company_id
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }

    public function delete(User $user, Venda $venda): bool
    {
        return $user->empresa_id === $venda->company_id
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }
}
