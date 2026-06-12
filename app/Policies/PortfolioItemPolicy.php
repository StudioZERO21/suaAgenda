<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PortfolioItem;
use App\Models\User;

class PortfolioItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, PortfolioItem $item): bool
    {
        return $user->empresa_id === $item->company_id;
    }

    public function create(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function update(User $user, PortfolioItem $item): bool
    {
        return $user->empresa_id === $item->company_id;
    }

    public function delete(User $user, PortfolioItem $item): bool
    {
        return $user->empresa_id === $item->company_id;
    }
}
