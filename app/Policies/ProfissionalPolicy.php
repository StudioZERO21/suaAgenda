<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Profissional;
use App\Models\User;

class ProfissionalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, Profissional $profissional): bool
    {
        return $user->empresa_id === $profissional->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin_empresa', 'gestor']);
    }

    public function update(User $user, Profissional $profissional): bool
    {
        return $user->empresa_id === $profissional->company_id
            && $user->hasAnyRole(['admin_empresa', 'gestor']);
    }

    public function delete(User $user, Profissional $profissional): bool
    {
        return $user->empresa_id === $profissional->company_id
            && $user->hasRole('admin_empresa');
    }
}
