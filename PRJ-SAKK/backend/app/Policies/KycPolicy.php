<?php

namespace App\Policies;

use App\Models\KycVerification;
use App\Models\User;

class KycPolicy
{
    /**
     * Determine if user can view any KYC verifications
     */
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine if user can view a KYC verification
     */
    public function view(User $user, KycVerification $kyc): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine if user can create KYC verifications
     */
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine if user can update a KYC verification
     */
    public function update(User $user, KycVerification $kyc): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine if user can delete a KYC verification
     */
    public function delete(User $user, KycVerification $kyc): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine if user can approve KYC
     */
    public function approve(User $user, KycVerification $kyc): bool
    {
        return $user->is_admin && $kyc->status === 'pending';
    }

    /**
     * Determine if user can reject KYC
     */
    public function reject(User $user, KycVerification $kyc): bool
    {
        return $user->is_admin && $kyc->status === 'pending';
    }
}
