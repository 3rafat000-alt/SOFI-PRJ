<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WalletFactory extends Factory
{
    protected $model = \App\Models\Wallet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'uuid' => Str::uuid(),
            'currency' => 'SYP',
            'balance' => $this->faker->randomFloat(2, 100, 10000),
            'available_balance' => fn(array $attrs) => $attrs['balance'] * 0.95,
            'pending_balance' => fn(array $attrs) => $attrs['balance'] * 0.05,
            'is_active' => true,
            'is_default' => false,
            'daily_limit' => 10000,
            'monthly_limit' => 100000,
        ];
    }

    public function usd(): static
    {
        return $this->state(fn(array $attributes) => [
            'currency' => 'USD',
        ]);
    }

    public function default(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Persist factory-made wallets idempotently on (user_id, currency).
     *
     * The User `created` boot hook auto-provisions a default USD wallet, so a
     * plain insert of a second wallet for the same (user_id, currency) violates
     * the unique index. Here we reuse the existing row — filling it with the
     * factory's requested attributes — instead of inserting a duplicate. This
     * keeps `Wallet::factory()->create(['currency' => 'USD', ...])` working
     * whether or not the boot hook already created that wallet.
     */
    protected function store(Collection $results)
    {
        $results->each(function (Wallet $model): void {
            // Include soft-deleted rows: the DB unique index on (user_id,
            // currency) ignores `deleted_at`, so a trashed wallet still blocks
            // a fresh insert. A test that deletes the auto-provisioned USD
            // wallet and recreates one would otherwise collide.
            $existing = Wallet::withTrashed()
                ->where('user_id', $model->user_id)
                ->where('currency', $model->currency)
                ->first();

            // No conflict — persist as a fresh insert.
            if ($existing === null) {
                $model->save();

                return;
            }

            // A wallet already exists for this (user_id, currency) — e.g. the
            // User boot hook auto-provisioned the default USD wallet. Turn the
            // would-be INSERT into an UPDATE of that row carrying the factory's
            // requested values, mutating the made model IN PLACE so the instance
            // the caller receives is the persisted one.
            $requested = collect($model->getAttributes())
                ->except([$model->getKeyName(), 'uuid', 'user_id', 'currency', 'created_at', 'updated_at'])
                ->all();

            // Seed the model from the existing row so `original` reflects the DB
            // state — that makes the requested attributes register as dirty and
            // actually get written on save() (Eloquent only updates dirty cols).
            $model->setRawAttributes($existing->getRawOriginal(), sync: true);
            $model->exists = true;
            $model->wasRecentlyCreated = false;

            // Revive the row if it was soft-deleted, then apply requested values.
            $model->forceFill($requested);
            $model->{$model->getDeletedAtColumn()} = null;
            $model->save();
        });

        return $results;
    }
}
