<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;

class StorageAccessService
{
    public function canAccess(User $user, string $path): bool
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if ($path === '' || str_contains($path, '..')) {
            return false;
        }

        if (str_starts_with($path, 'payments/slips/')) {
            return $this->canAccessPaymentSlip($user, $path);
        }

        if (str_starts_with($path, 'enrollments/discount-files/')) {
            return $this->canAccessDiscountFile($user, $path);
        }

        if (str_starts_with($path, 'therapists/documents/')) {
            return $this->canAccessTherapistDocument($user, $path);
        }

        if (str_starts_with($path, 'children/documents/')) {
            return $this->canAccessChildDocument($user, $path);
        }

        return false;
    }

    private function canAccessPaymentSlip(User $user, string $path): bool
    {
        $payment = Payment::query()->where('payment_slip', $path)->first();
        if ($payment === null) {
            return false;
        }

        if ($user->isChild() && (int) $payment->child_id === (int) $user->id) {
            return true;
        }

        return $user->hasPermission('manage_payments')
            || $user->hasPermission('verify_payments')
            || $user->hasPermission('view_finance_reports');
    }

    private function canAccessDiscountFile(User $user, string $path): bool
    {
        if (! Enrollment::query()->where('discount_file', $path)->exists()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('manage_enrollments')
            || $user->hasPermission('approve_high_discount');
    }

    private function canAccessTherapistDocument(User $user, string $path): bool
    {
        $exists = \App\Models\TherapistProfile::query()
            ->whereJsonContains('documents', $path)
            ->exists();

        if (! $exists) {
            return false;
        }

        if ($user->hasPermission('manage_therapists')) {
            return true;
        }

        if ($user->isTherapist()) {
            return \App\Models\TherapistProfile::query()
                ->where('user_id', $user->id)
                ->whereJsonContains('documents', $path)
                ->exists();
        }

        return false;
    }

    private function canAccessChildDocument(User $user, string $path): bool
    {
        if ($user->isChild()) {
            return false;
        }

        $child = User::query()
            ->children()
            ->whereJsonContains('documents', $path)
            ->first();

        if ($child === null) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin() && $user->branch_id) {
            return (int) $child->branch_id === (int) $user->branch_id;
        }

        return false;
    }
}
