<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class AttemptTimer
{
    public const MAX_SECONDS = 7200;

    public const IDLE_SECONDS = 900;

    /**
     * Old unfinished attempts have no active-time counter. Recover only
     * observable short gaps between recorded answers, never a days-long pause.
     */
    public static function saved(object $attempt): int
    {
        if ($attempt->quiz_started_at === null) {
            return 0;
        }
        $previous = self::timestamp($attempt->quiz_started_at);
        if ($previous > now()->getTimestamp()) {
            return 0;
        }
        if ($attempt->elapsed_seconds !== null) {
            return max(0, min(self::MAX_SECONDS, (int) $attempt->elapsed_seconds));
        }

        $seconds = 0;
        $timestamps = DB::table('attempt_answers')->where('attempt_id', $attempt->id)
            ->whereNotNull('created_at')->orderBy('created_at')->pluck('created_at');
        foreach ($timestamps as $timestamp) {
            $current = self::timestamp($timestamp);
            $gap = $current - $previous;
            if ($gap >= 0 && $gap <= self::IDLE_SECONDS) {
                $seconds += $gap;
            }
            $previous = max($previous, $current);
        }

        return min(self::MAX_SECONDS, $seconds);
    }

    /**
     * Cumulative client timing is presentation telemetry, not a scoring input.
     * Never double-count retries or concurrent tabs, never exceed wall time.
     */
    public static function checkpoint(object $attempt, ?int $reported, mixed $view, int $saved): int
    {
        $now = now()->getTimestamp();
        $started = self::timestamp($attempt->quiz_started_at);
        if ($started > $now) {
            return 0;
        }
        $wall = $now - $started;
        if ($reported === null) {
            // Progressive enhancement: without JS count a short interval from
            // the last rendered question, not time spent away from the quiz.
            $viewed = is_array($view) && ($view['uuid'] ?? null) === $attempt->uuid
                && is_int($view['at'] ?? null) ? $view['at'] : $now;
            $gap = $now - max($started, $viewed, self::timestamp($attempt->updated_at ?? $attempt->quiz_started_at));
            $reported = $saved + ($gap >= 0 && $gap <= self::IDLE_SECONDS ? $gap : 0);
        }

        return max($saved, min(self::MAX_SECONDS, $wall, $reported));
    }

    public static function reported(mixed $value): ?int
    {
        if (! is_int($value) && ! is_string($value)) {
            return null;
        }
        $seconds = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => self::MAX_SECONDS],
        ]);

        return $seconds === false ? null : $seconds;
    }

    public static function format(?int $seconds): string
    {
        if ($seconds === null || $seconds < 0 || $seconds > self::MAX_SECONDS) {
            return '—';
        }

        return sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60)
            .($seconds === self::MAX_SECONDS ? '+' : '');
    }

    private static function timestamp(string $value): int
    {
        return CarbonImmutable::parse($value, 'UTC')->getTimestamp();
    }
}
