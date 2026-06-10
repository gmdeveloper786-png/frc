<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->role?->name === 'child') {
            return (int) $payment->child_id === (int) $user->id;
        }

        return $user->hasPermission('manage_payments')
            || $user->hasPermission('verify_payments');
    }

    public function viewReceipt(User $user, Payment $payment): bool
    {
        if ($user->role?->name === 'child') {
            return (int) $payment->child_id === (int) $user->id;
        }

        return $user->hasPermission('manage_payments');
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'child'
            || $user->hasPermission('manage_payments');
    }

    public function uploadSlip(User $user, Payment $payment): bool
    {
        return $user->role?->name === 'child' && $payment->child_id === $user->id;
    }

    public function verify(User $user, Payment $payment): bool
    {
        return $user->hasPermission('verify_payments');
    }

    public function reject(User $user, Payment $payment): bool
    {
        return $user->hasPermission('verify_payments');
    }
}
