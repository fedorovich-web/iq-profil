<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DemoSubscriptionLifecycle
{
    public static function confirm(Request $request, object $attempt): void
    {
        DB::transaction(function () use ($request, $attempt): void {
            $scope = self::scopeKey($request);
            self::lockScope($scope);

            $locked = DB::table('quiz_attempts')->where('id', $attempt->id)->lockForUpdate()->first();
            abort_unless($locked !== null && $locked->status === 'completed', 409);

            // Recheck ownership inside the transaction: a guest attempt may be claimed
            // by an account between the initial HTTP authorization and this lock.
            $owned = $request->user()
                ? (int) $locked->user_id === (int) $request->user()->id
                : $locked->user_id === null
                    && in_array($locked->uuid, $request->session()->get('owned_attempts', []), true);
            abort_unless($owned, 404);

            // Replaying the same checkout must never reactivate a cancelled or superseded subscription.
            if (DB::table('demo_payments')->where('attempt_id', $locked->id)->exists()) {
                return;
            }

            self::supersedeActiveForOwnedAttempts($request);

            DB::table('demo_payments')->insert([
                'attempt_id' => $locked->id,
                'status' => 'succeeded',
                'amount_kopecks' => 2000,
                'currency' => 'RUB',
                'future_monthly_kopecks' => 49900,
                'confirmed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('demo_subscriptions')->insert([
                'attempt_id' => $locked->id,
                'status' => 'active',
                'active_scope_key' => $scope,
                'monthly_amount_kopecks' => 49900,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);
    }

    public static function cancel(Request $request): int
    {
        return DB::transaction(function () use ($request): int {
            self::lockScope(self::scopeKey($request));

            return DB::table('demo_subscriptions')
                ->whereIn('attempt_id', self::ownedAttemptIds($request))
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled',
                    'active_scope_key' => null,
                    'cancelled_at' => now(),
                    'updated_at' => now(),
                ]);
        }, 3);
    }

    /**
     * Claim only guest attempts recorded in this browser session.
     * Resolve multiple active demo subscriptions by keeping the most recent.
     *
     * @param  User  $user  The authenticated account that is receiving guest attempts.
     */
    public static function claimAttempts(Request $request, User $user): void
    {
        $owned = array_values(array_unique(array_filter(
            $request->session()->get('owned_attempts', []),
            static fn ($uuid): bool => is_string($uuid) && Str::isUuid($uuid)
        )));

        DB::transaction(function () use ($user, $owned): void {
            $scope = 'u:'.$user->id;
            self::lockScope($scope);

            if ($owned !== []) {
                DB::table('quiz_attempts')
                    ->whereIn('uuid', $owned)
                    ->whereNull('user_id')
                    ->update(['user_id' => $user->id]);
            }

            $active = DB::table('demo_subscriptions as s')
                ->join('quiz_attempts as a', 'a.id', '=', 's.attempt_id')
                ->where('a.user_id', $user->id)
                ->where('s.status', 'active')
                ->orderByDesc('s.activated_at')
                ->orderByDesc('s.id')
                ->pluck('s.id');

            if ($active->isEmpty()) {
                return;
            }

            $older = $active->skip(1)->all();
            if ($older !== []) {
                DB::table('demo_subscriptions')->whereIn('id', $older)->update([
                    'status' => 'superseded',
                    'active_scope_key' => null,
                    'superseded_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('demo_subscriptions')->where('id', $active->first())->update([
                'active_scope_key' => $scope,
                'updated_at' => now(),
            ]);
        }, 3);
    }

    private static function supersedeActiveForOwnedAttempts(Request $request): void
    {
        DB::table('demo_subscriptions')
            ->whereIn('attempt_id', self::ownedAttemptIds($request))
            ->where('status', 'active')
            ->update([
                'status' => 'superseded',
                'active_scope_key' => null,
                'superseded_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private static function ownedAttemptIds(Request $request): Builder
    {
        $query = DB::table('quiz_attempts')->select('id');

        if ($request->user()) {
            return $query->where('user_id', $request->user()->id);
        }

        $owned = array_values(array_filter(
            $request->session()->get('owned_attempts', []),
            static fn ($uuid): bool => is_string($uuid) && Str::isUuid($uuid)
        ));

        return $query->whereNull('user_id')->whereIn('uuid', $owned);
    }

    private static function scopeKey(Request $request): string
    {
        if ($request->user()) {
            return 'u:'.$request->user()->id;
        }

        // Do not persist raw session IDs or bearer credentials in the database.
        return 'g:'.hash_hmac('sha256', $request->session()->getId(), (string) config('app.key'));
    }

    private static function lockScope(string $scope): void
    {
        DB::table('demo_subscription_scopes')->insertOrIgnore([
            'scope_key' => $scope,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // The unique scope row serializes purchases/cancellations for one owner.
        DB::table('demo_subscription_scopes')
            ->where('scope_key', $scope)->lockForUpdate()->firstOrFail();
    }
}
