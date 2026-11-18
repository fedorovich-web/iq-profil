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
                'dots', 'constellation' => 'Количество синих точек: '.$value,
                'polygon' => 'Многоугольник с '.$value.' сторонами',
                'gem' => (['Синий', 'Бирюзовый', 'Золотистый', 'Фиолетовый', 'Коралловый', 'Графитовый'][$value] ?? 'Неизвестный').' шестиугольник',
                'nested' => 'Внутренняя фигура: '.(['круг', 'квадрат', 'треугольник', 'ромб', 'звезда', 'шестиугольник'][$value] ?? 'неизвестная'),
                'direction' => 'Стрелка направлена '.(['вверх', 'вверх вправо', 'вниз вправо', 'вниз', 'вниз влево', 'вверх влево'][$value] ?? 'неизвестно куда'),
                'direction-tone' => (['Синяя', 'Красная', 'Бирюзовая'][intdiv($value, 3)] ?? 'Неизвестная').' стрелка: '.(['вверх', 'вверх вправо', 'вниз вправо'][$value % 3]),
                'orbit' => 'Маркер на окружности: '.(['сверху', 'сверху справа', 'снизу справа', 'снизу', 'снизу слева', 'сверху слева'][$value] ?? 'неизвестно'),
                'grid' => 'Маркер в сетке: '.(['верхняя левая', 'верхняя центральная', 'верхняя правая', 'средняя левая', 'центр', 'средняя правая', 'нижняя левая', 'нижняя центральная', 'нижняя правая'][$value] ?? 'неизвестно'),
                'mosaic' => self::mosaicDescription($value),
                'shapes3' => self::maskDescription($value, ['круг', 'треугольник', 'квадрат']),
                'bars3' => self::maskDescription($value, ['левая полоса', 'средняя полоса', 'правая полоса']),
                'corner' => 'Г-образный угол '.(['сверху слева', 'сверху справа', 'снизу справа', 'снизу слева'][$value % 4]).', точка '.(['синяя', 'жёлтая', 'бирюзовая'][intdiv($value, 4)] ?? 'неизвестна'),
                'bands' => 'Вертикальных линий: '.($value + 1),
                'radial' => 'Количество лучей: '.($value + 1),
                'color-dots' => 'Точки '.(['синие', 'бирюзовые', 'жёлтые'][intdiv($value, 3)] ?? 'неизвестного цвета').', количество: '.($value % 3 + 1),
                'quads4' => self::maskDescription($value, ['верхняя левая позиция', 'верхняя правая позиция', 'нижняя левая позиция', 'нижняя правая позиция']),
                'bands4' => self::maskDescription($value, ['первая полоса', 'вторая полоса', 'третья полоса', 'четвёртая полоса']),
                default => 'Неизвестная фигура',
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
     * Describe the occupied visual positions, so a screen reader can access the puzzle.
     *
     * @param  array<int, string>  $labels
     */
    private static function maskDescription(int $mask, array $labels): string
    {
        $active = [];
        foreach ($labels as $index => $label) {
            if (($mask & (1 << $index)) !== 0) {
                $active[] = $label;
            }
        }

        return $active === [] ? 'Нет закрашенных элементов' : 'Закрашены: '.implode(', ', $active);
    }

    private static function mosaicDescription(int $value): string
    {
        $pairs = [[0, 1], [0, 2], [0, 3], [1, 2], [1, 3], [2, 3]];
        $labels = ['верхний левый', 'верхний правый', 'нижний левый', 'нижний правый'];

        return isset($pairs[$value])
            ? 'Закрашены квадраты: '.$labels[$pairs[$value][0]].' и '.$labels[$pairs[$value][1]]
            : 'Неизвестная мозаика';
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
            $out .= '<circle cx="'.$x.'" cy="'.$y.'" r="10" fill="#1d62e7" stroke="#f8fbff" stroke-width="1.5"/>';
        }

        return $out;
    }

    private static function gem(int $value): string
    {
        $colors = ['#195edc', '#0a9e9a', '#e7a43d', '#7847b6', '#d45d68', '#4f627f'];
        $color = $colors[$value] ?? '#4f627f';

        return '<path d="M50 15 L79 33 L79 67 L50 85 L21 67 L21 33 Z" fill="'.$color.'" stroke="'.$color.'" stroke-width="5" stroke-linejoin="round"/>'
            .'<path d="M50 15 L50 85 M21 33 L79 67" stroke="#fff" stroke-width="1.6" opacity=".26"/>';
    }

    private static function nested(int $value): string
    {
        $inner = match ($value) {
            0 => '<circle cx="50" cy="50" r="19" fill="#1f66e7"/>',
            1 => '<rect x="32" y="32" width="36" height="36" rx="6" fill="#17969e"/>',
            2 => '<path d="M50 29 L74 69 H26 Z" fill="#e4a749"/>',
            3 => '<path d="M50 25 L77 50 L50 75 L23 50 Z" fill="#845bb6"/>',
            4 => '<path d="M50 24 L57 42 L76 42 L61 54 L67 73 L50 62 L33 73 L39 54 L24 42 L43 42 Z" fill="#d65c6c"/>',
            5 => '<path d="M36 28 L64 28 L78 50 L64 72 L36 72 L22 50 Z" fill="#526987"/>',
            default => '',
        };

        return '<rect x="12" y="12" width="76" height="76" rx="20" fill="#eff5ff" stroke="#c5daf8" stroke-width="3"/>'.$inner;
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
