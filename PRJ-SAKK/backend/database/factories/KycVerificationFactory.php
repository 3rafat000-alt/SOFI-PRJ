<?php

namespace Database\Factories;

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KycVerificationFactory extends Factory
{
    protected $model = KycVerification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'level' => 1,
            // Must be one of the enum values (email|phone|id_document|selfie|address_proof|video);
            // 'basic' violated the column CHECK constraint.
            'verification_type' => 'id_document',
            'document_type' => 'national_id',
            'document_path' => 'kyc/documents/test.pdf',
            'status' => 'pending',
        ];
    }
}
