<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Notificacao;
use App\Models\User;

class NotificacaoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null;
    }

    public function view(User $user, Notificacao $notificacao): bool
    {
        return $user->empresa_id === $notificacao->company_id;
    }

    public function update(User $user, Notificacao $notificacao): bool
    {
        return $user->empresa_id === $notificacao->company_id;
    }

    public function delete(User $user, Notificacao $notificacao): bool
    {
        return $user->empresa_id === $notificacao->company_id;
    }
}
