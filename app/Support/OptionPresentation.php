<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Collection;
use InvalidArgumentException;

final class OptionPresentation
{
    /**
     * Give each attempt a stable, unbiased-by-position answer order.
     * The seed is stored with the attempt, never sent to the browser.
     * Historical attempts (seed = null) retain their published A-F labels.
     *
     * @param  Collection<int, object>  $options
     * @return Collection<int, object>
     */
    public static function ordered(Collection $options, ?string $seed): Collection
    {
        if ($seed === null) {
            return $options->sortBy('position')->values();
        }

        if (! preg_match('/^[0-9a-f]{32}$/D', $seed)) {
            throw new InvalidArgumentException('Invalid option shuffle seed.');
        }

        $key = hex2bin($seed);

        return $options
            ->sortBy(static fn (object $option): string => hash_hmac('sha256', (string) $option->id, $key))
            ->values()
            ->map(static function (object $option, int $index): object {
                $presented = clone $option;
                $presented->code = self::label($index);

                return $presented;
            });
    }

    /**
     * @param  Collection<int, object>  $options
     * @return array<int, string>
     */
    public static function labels(Collection $options, ?string $seed): array
    {
        return self::ordered($options, $seed)
            ->mapWithKeys(static fn (object $option): array => [(int) $option->id => $option->code])
            ->all();
    }

    private static function label(int $index): string
    {
        $label = '';

        do {
            $label = chr(65 + $index % 26).$label;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return $label;
    }
}
