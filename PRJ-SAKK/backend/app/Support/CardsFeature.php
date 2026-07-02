<?php

namespace App\Support;

use App\Models\Integration;

/**
 * Single source of truth for whether the virtual-cards feature is live.
 *
 * Cards are issued through Stripe Issuing, so the whole feature stays
 * disabled — across API, mobile app, and web — until the admin turns on the
 * Stripe gateway in admin → النظام → الطرف الثالث والأمان (the "تفعيل إصدار
 * البطاقات" switch) AND saves a secret key. The moment both are set, the
 * feature turns on everywhere with no code change.
 *
 * Deliberately bound to the admin-managed Integration row — NOT to env/config
 * fallbacks — so a stray STRIPE_SECRET in the environment can never silently
 * re-enable cards behind the admin's back.
 */
class CardsFeature
{
    public static function enabled(): bool
    {
        $stripe = Integration::where('key', 'stripe')->first();

        return $stripe !== null
            && $stripe->is_active
            && !empty($stripe->getCredential('secret'));
    }

    public static function disabledMessage(): string
    {
        return 'ميزة البطاقات الافتراضية غير مفعّلة بعد. ستتوفّر فور تفعيل إصدار البطاقات عبر ستريب.';
    }
}
