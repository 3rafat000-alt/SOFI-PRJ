<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Agent types for the Verification & Auto-Repair multi-agent system.
 *
 * Each type maps to a specialised agent class registered in AgentOrchestrator.
 */
enum AgentType: string
{
    case FINANCIAL_RECONCILIATION = 'financial_reconciliation';
    case KYC_VERIFICATION = 'kyc_verification';
    case AML_SCREENING = 'aml_screening';
    case CARD_RECONCILIATION = 'card_reconciliation';
    case PLATFORM_REVENUE = 'platform_revenue';

    public function label(): string
    {
        return match ($this) {
            self::FINANCIAL_RECONCILIATION => 'وكيل التحقق المالي والتسوية',
            self::KYC_VERIFICATION => 'وكيل التحقق من الهوية والامتثال',
            self::AML_SCREENING => 'وكيل فحص مكافحة غسيل الأموال',
            self::CARD_RECONCILIATION => 'وكيل تسوية البطاقات',
            self::PLATFORM_REVENUE => 'وكيل تدقيق الإيرادات',
        };
    }

    public function labelAr(): string
    {
        return $this->label();
    }

    /**
     * Risk tier: higher = more conservative escalation thresholds.
     */
    public function riskTier(): int
    {
        return match ($this) {
            self::FINANCIAL_RECONCILIATION => 3,
            self::KYC_VERIFICATION => 2,
            self::AML_SCREENING => 3,
            self::CARD_RECONCILIATION => 2,
            self::PLATFORM_REVENUE => 1,
        };
    }
}
