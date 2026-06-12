<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Produto;
use App\Models\User;

class ProdutoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, Produto $produto): bool
    {
        return $user->empresa_id === $produto->company_id;
    }

    public function create(User $user): bool
    {
        return $user->empresa_id !== null
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }

    public function update(User $user, Produto $produto): bool
    {
        return $user->empresa_id === $produto->company_id;
    }

    public function delete(User $user, Produto $produto): bool
    {
        return $user->empresa_id === $produto->company_id;
    }
}
