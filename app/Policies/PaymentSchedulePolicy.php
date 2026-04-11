<?php

namespace App\Policies;

use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentSchedulePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the model (Editar histórico/pagos).
     */
    public function update(User $user, PaymentSchedule $paymentSchedule): bool
    {
        // Bloqueo estricto: Solo el rol 'TI' o usuarios con el permiso explícito pueden editar.
        return $user->hasRole('TI') || $user->hasPermissionTo('editar_pagos_manual');
    }

    /**
     * Determine whether the user can create models (Registrar pago nuevo).
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('registrar_pagos_nuevos');
    }
}
