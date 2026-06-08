<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function view(User $user, Company $company): bool
    {
        return $user->empresa_id === $company->id;
    }

    public function update(User $user, Company $company): bool
    {
        return $user->empresa_id === $company->id
            && $user->hasRole('admin_empresa');
    }
}
