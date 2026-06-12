<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Cargo;
use App\Models\User;

class CargoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, Cargo $cargo): bool
    {
        return $user->empresa_id === $cargo->company_id;
    }

    public function create(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function update(User $user, Cargo $cargo): bool
    {
        return $user->empresa_id === $cargo->company_id;
    }

    public function delete(User $user, Cargo $cargo): bool
    {
        return $user->empresa_id === $cargo->company_id;
    }
}
