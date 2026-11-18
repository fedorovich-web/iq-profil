<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\HtmlString;

final class GraphicPuzzle
{
    public static function name(string $kind, int $value): string
    {
        return match ($kind) {
            'dots' => 'Точек: '.($value + 1),
            'polygon' => 'Углов: '.($value + 3),
            'rotation' => 'Направление '.($value + 1).' из 6',
            'stripes' => 'Полос: '.($value + 1),
            'position' => 'Позиция '.($value + 1).' из 9',
            default => 'Фигура',
        };
    }

    public static function image(string $kind, int $value, int $size = 80): HtmlString
    {
        $content = match ($kind) {
            'dots' => self::dots($value + 1),
            'polygon' => self::polygon($value + 3),
            'rotation' => '<path d="M 50 18 L 69 65 L 50 55 L 31 65 Z" fill="#1655ef" stroke="#1241b7" stroke-width="2" transform="rotate('.($value * 60).' 50 50)" />',
            'stripes' => self::stripes($value + 1),
            'position' => self::position($value),
            default => '',
        };

        return new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="'.$size.'" height="'.$size.'" aria-hidden="true" focusable="false">'.$content.'</svg>');
    }

    private static function dots(int $count): string
    {
        $result = '';
        for ($i = 0; $i < $count; $i++) {
            $x = 26 + ($i % 3) * 24;
            $y = 36 + intdiv($i, 3) * 28;
            $result .= '<circle cx="'.$x.'" cy="'.$y.'" r="9" fill="#1655ef"/>';
        }

        return $result;
    }

    private static function polygon(int $sides): string
    {
        $points = [];
        for ($i = 0; $i < $sides; $i++) {
            $angle = -M_PI / 2 + 2 * M_PI * $i / $sides;
            $points[] = round(50 + 32 * cos($angle), 2).','.round(50 + 32 * sin($angle), 2);
        }

        return '<polygon points="'.implode(' ', $points).'" fill="#dce7ff" stroke="#1655ef" stroke-width="5" stroke-linejoin="round"/>';
    }

    private static function stripes(int $count): string
    {
        $out = '';
        $gap = 50 / max($count - 1, 1);
        for ($i = 0; $i < $count; $i++) {
            $x = $count === 1 ? 50 : 25 + $gap * $i;
            $out .= '<line x1="'.$x.'" y1="24" x2="'.$x.'" y2="76" stroke="#1655ef" stroke-width="7" stroke-linecap="round"/>';
        }

        return $out;
    }

    private static function position(int $value): string
    {
        $out = '<rect x="15" y="15" width="70" height="70" rx="7" fill="#f3f7ff" stroke="#bfd2fa"/>';
        for ($i = 0; $i < 9; $i++) {
            $x = 30 + ($i % 3) * 20;
            $y = 30 + intdiv($i, 3) * 20;
            $out .= '<circle cx="'.$x.'" cy="'.$y.'" r="'.($i === $value ? 8 : 3).'" fill="'.($i === $value ? '#1655ef' : '#c7d7fa').'"/>';
        }

        return $out;
    }
}
