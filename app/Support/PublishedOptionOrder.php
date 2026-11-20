<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class PublishedOptionOrder
{
    /**
     * Frozen canonical placement for matrix-reasoning revision 8.
     * This is intentionally separate from each attempt's private display order.
     * Never change this key for a published revision.
     */
    private const RELEASE_KEY = '573552bb52328db45c63c52e649fa9cf';

    /**
     * @param  array<int, int>  $values
     * @return array<int, int>
     */
    public static function forQuestion(string $slug, array $values): array
    {
        if (count($values) < 2 || count($values) !== count(array_unique($values))) {
            throw new InvalidArgumentException('Published option values must be distinct.');
        }

        usort($values, static fn (int $left, int $right): int => strcmp(
            hash_hmac('sha256', $slug.':'.$left, self::RELEASE_KEY),
            hash_hmac('sha256', $slug.':'.$right, self::RELEASE_KEY)
        ));

        return $values;
    }
}
