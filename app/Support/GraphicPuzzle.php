<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\HtmlString;

final class GraphicPuzzle
{
    public static function name(string $kind, int $value): string
    {
        $definition = PuzzleCatalog::find($kind);
        if ($definition !== null) {
            return match ($definition[1]) {
                'dots', 'constellation' => 'Количество элементов: '.$value,
                'polygon' => 'Многоугольник с '.$value.' сторонами',
                'direction', 'direction-tone' => 'Направление и цвет стрелки, состояние '.($value + 1),
                'orbit', 'grid' => 'Положение маркера '.($value + 1),
                'bands' => 'Количество полос: '.($value + 1),
                'radial' => 'Количество лучей: '.($value + 1),
                'color-dots' => 'Цвет и количество точек, состояние '.($value + 1),
                default => 'Композиция номер '.($value + 1),
            };
        }

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
        $definition = PuzzleCatalog::find($kind);
        if ($definition !== null) {
            $art = self::modern($definition[1], $value);

            return new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="'.$size.'" height="'.$size.'" aria-hidden="true" focusable="false">'.$art.'</svg>');
        }

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

    /**
     * Deterministic vector artwork for the replacement question set.
     * Only the numeric visual state is rendered; answer keys never reach the client.
     */
    private static function modern(string $motif, int $value): string
    {
        return match ($motif) {
            'dots' => self::dots($value),
            'constellation' => self::constellation($value),
            'direction' => self::direction($value * 60),
            'direction-tone' => self::direction(($value % 3) * 60, ['#1c5ce6', '#d45d4e', '#128a8b'][intdiv($value, 3)]),
            'polygon' => self::polygon($value),
            'orbit' => self::orbit($value),
            'grid' => self::position($value),
            'mosaic' => self::mosaic($value),
            'shapes3' => self::shapes3($value),
            'bars3' => self::bars3($value),
            'gem' => self::gem($value),
            'nested' => self::nested($value),
            'corner' => self::corner($value),
            'bands' => self::stripes($value + 1),
            'radial' => self::radial($value + 1),
            'color-dots' => self::colorDots($value),
            'quads4' => self::quads4($value),
            'bands4' => self::bands4($value),
            default => '',
        };
    }

    private static function direction(int $degrees, string $color = '#1c5ce6'): string
    {
        return '<circle cx="50" cy="50" r="36" fill="#f1f6ff" stroke="#d4e3fc" stroke-width="2"/>'
            .'<path d="M50 18 L67 64 L50 56 L33 64 Z" fill="'.$color.'" stroke="#153e8d" stroke-width="2.4" stroke-linejoin="round" transform="rotate('.$degrees.' 50 50)"/>';
    }

    private static function orbit(int $value): string
    {
        $out = '<circle cx="50" cy="50" r="30" fill="none" stroke="#ccdafa" stroke-width="3" stroke-dasharray="4 5"/>';
        for ($i = 0; $i < 6; $i++) {
            $angle = -M_PI / 2 + $i * M_PI / 3;
            $x = round(50 + 30 * cos($angle), 2);
            $y = round(50 + 30 * sin($angle), 2);
            $active = $i === $value;
            $out .= '<circle cx="'.$x.'" cy="'.$y.'" r="'.($active ? 10 : 4).'" fill="'.($active ? '#125ee9' : '#b5caf2').'"'
                .($active ? ' stroke="#e4efff" stroke-width="3"' : '').'/>';
        }

        return $out;
    }

    private static function mosaic(int $value): string
    {
        $pairs = [[0, 1], [0, 2], [0, 3], [1, 2], [1, 3], [2, 3]];
        $active = $pairs[$value];
        $out = '<rect x="16" y="16" width="68" height="68" rx="14" fill="#e8effe"/>';
        for ($i = 0; $i < 4; $i++) {
            $x = 23 + ($i % 2) * 29;
            $y = 23 + intdiv($i, 2) * 29;
            $out .= '<rect x="'.$x.'" y="'.$y.'" width="25" height="25" rx="7" fill="'
                .(in_array($i, $active, true) ? '#1b64e9' : '#fff').'" stroke="#c2d5f6" stroke-width="1.4"/>';
        }

        return $out;
    }

    private static function shapes3(int $mask): string
    {
        $out = '<circle cx="50" cy="50" r="41" fill="#f5f8ff" stroke="#dce8ff" stroke-width="1.5"/>';
        if ($mask & 1) {
            $out .= '<circle cx="50" cy="32" r="13" fill="#2072f1"/>';
        }
        if ($mask & 2) {
            $out .= '<path d="M24 76 L38 51 L52 76 Z" fill="#0c467a" stroke-linejoin="round"/>';
        }
        if ($mask & 4) {
            $out .= '<rect x="56" y="51" width="26" height="26" rx="5" fill="#53b2c3"/>';
        }
        if ($mask === 0) {
            $out .= '<circle cx="50" cy="50" r="12" fill="none" stroke="#c6d7f5" stroke-width="3" stroke-dasharray="3 4"/>';
        }

        return $out;
    }

    private static function bars3(int $mask): string
    {
        $out = '<rect x="14" y="17" width="72" height="66" rx="13" fill="#f2f6ff" stroke="#d5e2fa"/>';
        foreach ([1, 2, 4] as $index => $bit) {
            $out .= '<rect x="'.(23 + $index * 20).'" y="29" width="14" height="42" rx="6" fill="'
                .(($mask & $bit) ? ['#1d5de1', '#169ba0', '#4f81dc'][$index] : '#e2e9f7').'"/>';
        }

        return $out;
    }

    private static function constellation(int $count): string
    {
        $spots = [[26, 33], [53, 24], [76, 38], [31, 66], [58, 57], [75, 76]];
        $out = '<path d="M26 33 L53 24 L76 38 L75 76 L58 57 L31 66 Z" stroke="#d6e3fc" stroke-width="2" stroke-dasharray="3 5" fill="none"/>';
        foreach (array_slice($spots, 0, $count) as [$x, $y]) {
            $out .= '<circle cx="'.$x.'" cy="'.$y.'" r="7.5" fill="#1d62e7" stroke="#f8fbff" stroke-width="2"/>';
        }

        return $out;
    }

    private static function gem(int $value): string
    {
        $colors = ['#195edc', '#0a9e9a', '#e7a43d'];
        $color = $colors[$value % 3];
        $fill = $value < 3 ? $color : '#eff5ff';

        return '<path d="M50 15 L79 33 L79 67 L50 85 L21 67 L21 33 Z" fill="'.$fill.'" stroke="'.$color.'" stroke-width="5" stroke-linejoin="round"/>'
            .'<path d="M50 15 L50 85 M21 33 L79 67" stroke="'.$color.'" stroke-width="1.5" opacity=".4"/>';
    }

    private static function nested(int $value): string
    {
        $style = $value % 3;
        $inner = match ($style) {
            0 => '<circle cx="50" cy="50" r="19" fill="#1f66e7"/>',
            1 => '<rect x="32" y="32" width="36" height="36" rx="6" fill="#17969e"/>',
            default => '<path d="M50 29 L74 69 H26 Z" fill="#e4a749"/>',
        };

        return '<rect x="12" y="12" width="76" height="76" rx="20" fill="'.($value < 3 ? '#eff5ff' : '#dce9ff').'" stroke="#c5daf8" stroke-width="3"'.($value >= 3 ? ' stroke-dasharray="5 4"' : '').'/>'.$inner;
    }

    private static function corner(int $value): string
    {
        $rotation = ($value % 4) * 90;
        $tone = ['#1c61df', '#e4a64f', '#149c93'][intdiv($value, 4)];

        return '<g transform="rotate('.$rotation.' 50 50)">'
            .'<path d="M24 77 V25 H77" fill="none" stroke="#1e5fdf" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>'
            .'<circle cx="41" cy="57" r="12" fill="'.$tone.'" stroke="#fff" stroke-width="2"/>'
            .'</g>';
    }

    private static function radial(int $count): string
    {
        $out = '<circle cx="50" cy="50" r="13" fill="#1b65e7" stroke="#fff" stroke-width="3"/>';
        for ($i = 0; $i < $count; $i++) {
            $angle = 2 * M_PI * $i / $count - M_PI / 2;
            $x = round(50 + 32 * cos($angle), 2);
            $y = round(50 + 32 * sin($angle), 2);
            $out .= '<line x1="50" y1="50" x2="'.$x.'" y2="'.$y.'" stroke="#246ce4" stroke-width="4.5" stroke-linecap="round"/>';
        }

        return $out.'<circle cx="50" cy="50" r="9" fill="#fff"/>';
    }

    private static function colorDots(int $value): string
    {
        $colors = ['#2064eb', '#169b96', '#e7ab48'];
        $color = $colors[intdiv($value, 3)];
        $count = $value % 3 + 1;
        $out = '<rect x="13" y="20" width="74" height="60" rx="21" fill="#f0f6ff" stroke="#d8e5fb" stroke-width="2"/>';
        for ($i = 0; $i < $count; $i++) {
            $x = 50 + ($i - ($count - 1) / 2) * 25;
            $out .= '<circle cx="'.$x.'" cy="50" r="10" fill="'.$color.'" stroke="#fff" stroke-width="2"/>';
        }

        return $out;
    }

    private static function quads4(int $mask): string
    {
        $out = '<rect x="13" y="13" width="74" height="74" rx="14" fill="#f2f7ff" stroke="#d4e2f9" stroke-width="2"/>';
        foreach ([1, 2, 4, 8] as $index => $bit) {
            $x = 23 + $index % 2 * 29;
            $y = 23 + intdiv($index, 2) * 29;
            $out .= '<circle cx="'.($x + 12).'" cy="'.($y + 12).'" r="11" fill="'
                .(($mask & $bit) ? ['#155bde', '#359ec0', '#e0a94a', '#345a9e'][$index] : '#e1e9f6').'"/>';
        }

        return $out;
    }

    private static function bands4(int $mask): string
    {
        $out = '<rect x="12" y="20" width="76" height="60" rx="12" fill="#f1f6ff" stroke="#d3e4fc" stroke-width="2"/>';
        foreach ([1, 2, 4, 8] as $index => $bit) {
            $out .= '<rect x="'.(19 + $index * 16).'" y="29" width="13" height="43" rx="6" fill="'
                .(($mask & $bit) ? ['#125de1', '#258cc6', '#12a49b', '#dba045'][$index] : '#e3eaf7').'"/>';
        }

        return $out;
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
