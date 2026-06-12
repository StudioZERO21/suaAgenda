<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClienteFoto;
use App\Models\User;

class ClienteFotoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, ClienteFoto $foto): bool
    {
        return $user->empresa_id === $foto->cliente?->company_id;
    }

    public function create(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function update(User $user, ClienteFoto $foto): bool
    {
        return $user->empresa_id === $foto->cliente?->company_id;
    }

    public function delete(User $user, ClienteFoto $foto): bool
    {
        return $user->empresa_id === $foto->cliente?->company_id;
    }
}
