<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\HtmlString;

final class GraphicPuzzle
{
    // Frozen vector visual language for revision 4.
    private const INK = '#18304f';

    private const BLUE = '#2766c7';

    private const TEAL = '#087e85';

    private const AMBER = '#b96f12';

    private const MUTED = '#d0def0';

    public static function name(string $kind, int $value): string
    {
        $definition = PuzzleCatalog::find($kind);
        if ($definition !== null) {
            return match ($definition[1]) {
                'dots', 'constellation' => 'Количество синих точек: '.$value,
                'polygon' => 'Многоугольник с '.$value.' сторонами',
                'gem' => (['Синий', 'Бирюзовый', 'Золотистый', 'Фиолетовый', 'Коралловый', 'Графитовый'][$value] ?? 'Неизвестный').' шестиугольник',
                'shape-tone' => (['Синяя', 'Бирюзовая', 'Янтарная'][intdiv($value, 3)] ?? 'Неизвестная').' фигура: '.(['круг', 'квадрат', 'треугольник'][$value % 3]),
                'nested' => 'Внутренняя фигура: '.(['круг', 'квадрат', 'треугольник', 'ромб', 'звезда', 'шестиугольник'][$value] ?? 'неизвестная'),
                'direction' => 'Стрелка направлена '.(['вверх', 'вверх вправо', 'вниз вправо', 'вниз', 'вниз влево', 'вверх влево'][$value] ?? 'неизвестно куда'),
                'direction-tone' => (['Синяя', 'Янтарная', 'Бирюзовая'][intdiv($value, 3)] ?? 'Неизвестная').' стрелка: '.(['вверх', 'вверх вправо', 'вниз вправо'][$value % 3]),
                'orbit' => 'Маркер на окружности: '.(['сверху', 'сверху справа', 'снизу справа', 'снизу', 'снизу слева', 'сверху слева'][$value] ?? 'неизвестно'),
                'grid' => 'Маркер в сетке: '.(['верхняя левая', 'верхняя центральная', 'верхняя правая', 'средняя левая', 'центр', 'средняя правая', 'нижняя левая', 'нижняя центральная', 'нижняя правая'][$value] ?? 'неизвестно'),
                'mosaic' => self::mosaicDescription($value),
                'shapes3' => self::maskDescription($value, ['круг', 'треугольник', 'квадрат']),
                'bars3' => self::maskDescription($value, ['левая полоса', 'средняя полоса', 'правая полоса']),
                'corner' => 'Г-образный угол '.(['сверху слева', 'сверху справа', 'снизу справа', 'снизу слева'][$value % 4]).', точка '.(['синяя', 'янтарная', 'бирюзовая'][intdiv($value, 4)] ?? 'неизвестна'),
                'bands' => 'Вертикальных линий: '.($value + 1),
                'radial' => 'Количество лучей: '.($value + 1),
                'color-dots' => 'Точки '.(['синие', 'бирюзовые', 'янтарные'][intdiv($value, 3)] ?? 'неизвестного цвета').', количество: '.($value % 3 + 1),
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

            return new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="'.$size.'" height="'.$size.'" aria-hidden="true" focusable="false" shape-rendering="geometricPrecision">'.$art.'</svg>');
        }

        $content = match ($kind) {
            'dots' => self::dots($value + 1),
            'polygon' => self::polygon($value + 3),
            'rotation' => '<path d="M 50 18 L 69 65 L 50 55 L 31 65 Z" fill="#1655ef" stroke="#1241b7" stroke-width="2" transform="rotate('.($value * 60).' 50 50)" />',
            'stripes' => self::stripes($value + 1),
            'position' => self::position($value),
            default => '',
        };

        return new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="'.$size.'" height="'.$size.'" aria-hidden="true" focusable="false" shape-rendering="geometricPrecision">'.$content.'</svg>');
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
            'direction-tone' => self::direction(($value % 3) * 60, [self::BLUE, self::AMBER, self::TEAL][intdiv($value, 3)]),
            'polygon' => self::polygon($value),
            'orbit' => self::orbit($value),
            'grid' => self::position($value),
            'mosaic' => self::mosaic($value),
            'shapes3' => self::shapes3($value),
            'bars3' => self::bars3($value),
            'gem' => self::gem($value),
            'shape-tone' => self::shapeTone($value),
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

    private static function direction(int $degrees, string $color = self::BLUE): string
    {
        return '<path d="M50 15 L68 40 H58 V80 H42 V40 H32 Z" fill="'.$color.'" stroke="'.self::INK.'" stroke-width="2" stroke-linejoin="round" transform="rotate('.$degrees.' 50 50)"/>';
    }

    private static function orbit(int $value): string
    {
        $out = '<circle cx="50" cy="50" r="32" fill="none" stroke="'.self::MUTED.'" stroke-width="2.5"/>';
        for ($i = 0; $i < 6; $i++) {
            $angle = -M_PI / 2 + $i * M_PI / 3;
            $x = round(50 + 32 * cos($angle), 2);
            $y = round(50 + 32 * sin($angle), 2);
            $active = $i === $value;
            $out .= '<circle cx="'.$x.'" cy="'.$y.'" r="'.($active ? 8.8 : 3.5).'" fill="'.($active ? self::BLUE : '#a9bedc').'"'
                .($active ? ' stroke="'.self::INK.'" stroke-width="1.8"' : '').'/>';
        }

        return $out;
    }

    private static function mosaic(int $value): string
    {
        $pairs = [[0, 1], [0, 2], [0, 3], [1, 2], [1, 3], [2, 3]];
        $active = $pairs[$value];
        $out = '';
        for ($i = 0; $i < 4; $i++) {
            $x = 20 + ($i % 2) * 33;
            $y = 20 + intdiv($i, 2) * 33;
            $on = in_array($i, $active, true);
            $out .= '<rect x="'.$x.'" y="'.$y.'" width="27" height="27" rx="4" fill="'.($on ? self::BLUE : '#f4f7fc').'" stroke="'.($on ? self::INK : self::MUTED).'" stroke-width="2"/>';
        }

        return $out;
    }

    private static function shapes3(int $mask): string
    {
        $out = '';
        if ($mask & 1) {
            $out .= '<circle cx="50" cy="25" r="12" fill="'.self::BLUE.'" stroke="'.self::INK.'" stroke-width="1.8"/>';
        }
        if ($mask & 2) {
            $out .= '<path d="M15 75 L31 44 L47 75 Z" fill="'.self::BLUE.'" stroke="'.self::INK.'" stroke-width="1.8" stroke-linejoin="round"/>';
        }
        if ($mask & 4) {
            $out .= '<rect x="54" y="46" width="30" height="30" rx="3" fill="'.self::BLUE.'" stroke="'.self::INK.'" stroke-width="1.8"/>';
        }
        if ($mask === 0) {
            $out = '<circle cx="50" cy="50" r="20" fill="none" stroke="'.self::MUTED.'" stroke-width="2" stroke-dasharray="5 5"/>';
        }

        return $out;
    }

    private static function bars3(int $mask): string
    {
        $out = '';
        foreach ([1, 2, 4] as $index => $bit) {
            $active = ($mask & $bit) !== 0;
            $out .= '<rect x="'.(21 + 20 * $index).'" y="24" width="17" height="52" rx="3" fill="'.($active ? self::BLUE : '#f4f7fc').'" stroke="'.($active ? self::INK : self::MUTED).'" stroke-width="1.8"/>';
        }

        return $out;
    }

    private static function constellation(int $count): string
    {
        return self::dots($count);
    }

    private static function gem(int $value): string
    {
        $colors = [self::BLUE, self::TEAL, self::AMBER, '#7756ad', '#b34866', '#50657f'];
        $color = $colors[$value] ?? self::INK;

        return '<path d="M50 13 L81 31 L81 68 L50 87 L19 68 L19 31 Z" fill="'.$color.'" stroke="'.self::INK.'" stroke-width="2.8" stroke-linejoin="round"/>';
    }

    private static function shapeTone(int $value): string
    {
        $color = [self::BLUE, self::TEAL, self::AMBER][intdiv($value, 3)];

        return match ($value % 3) {
            0 => '<circle cx="50" cy="50" r="27" fill="'.$color.'" stroke="'.self::INK.'" stroke-width="2.3"/>',
            1 => '<rect x="24" y="24" width="52" height="52" rx="3" fill="'.$color.'" stroke="'.self::INK.'" stroke-width="2.3"/>',
            2 => '<path d="M50 20 L79 75 H21 Z" fill="'.$color.'" stroke="'.self::INK.'" stroke-width="2.3" stroke-linejoin="round"/>',
        };
    }

    private static function nested(int $value): string
    {
        $fill = self::BLUE;
        $inner = match ($value) {
            0 => '<circle cx="50" cy="50" r="19" fill="'.$fill.'"/>',
            1 => '<rect x="32" y="32" width="36" height="36" rx="3" fill="'.$fill.'"/>',
            2 => '<path d="M50 29 L74 69 H26 Z" fill="'.$fill.'"/>',
            3 => '<path d="M50 25 L75 50 L50 75 L25 50 Z" fill="'.$fill.'"/>',
            4 => '<path d="M50 24 L57 42 L76 42 L61 54 L67 73 L50 62 L33 73 L39 54 L24 42 L43 42 Z" fill="'.$fill.'"/>',
            5 => '<path d="M36 28 L64 28 L78 50 L64 72 L36 72 L22 50 Z" fill="'.$fill.'"/>',
            default => '',
        };

        return '<rect x="11" y="11" width="78" height="78" rx="7" fill="none" stroke="'.self::MUTED.'" stroke-width="3"/>'.$inner;
    }

    private static function corner(int $value): string
    {
        $rotation = ($value % 4) * 90;
        $tone = [self::BLUE, self::AMBER, self::TEAL][intdiv($value, 4)];

        return '<g transform="rotate('.$rotation.' 50 50)">'
            .'<path d="M23 76 V24 H76" fill="none" stroke="'.self::INK.'" stroke-width="7" stroke-linecap="square" stroke-linejoin="miter"/>'
            .'<circle cx="42" cy="58" r="11" fill="'.$tone.'" stroke="'.self::INK.'" stroke-width="1.5"/>'
            .'</g>';
    }

    private static function radial(int $count): string
    {
        $out = '';
        for ($i = 0; $i < $count; $i++) {
            $angle = 2 * M_PI * $i / $count - M_PI / 2;
            $x = round(50 + 35 * cos($angle), 2);
            $y = round(50 + 35 * sin($angle), 2);
            $out .= '<line x1="50" y1="50" x2="'.$x.'" y2="'.$y.'" stroke="'.self::BLUE.'" stroke-width="5" stroke-linecap="round"/>';
        }

        return $out.'<circle cx="50" cy="50" r="7.5" fill="'.self::INK.'"/>';
    }

    private static function colorDots(int $value): string
    {
        $colors = [self::BLUE, self::TEAL, self::AMBER];
        $color = $colors[intdiv($value, 3)];
        $count = $value % 3 + 1;
        $out = '';
        for ($i = 0; $i < $count; $i++) {
            $x = 50 + ($i - ($count - 1) / 2) * 27;
            $out .= '<circle cx="'.$x.'" cy="50" r="11" fill="'.$color.'" stroke="'.self::INK.'" stroke-width="1.6"/>';
        }

        return $out;
    }

    private static function quads4(int $mask): string
    {
        $out = '';
        foreach ([1, 2, 4, 8] as $index => $bit) {
            $x = 24 + ($index % 2) * 33;
            $y = 24 + intdiv($index, 2) * 33;
            $active = ($mask & $bit) !== 0;
            $out .= '<circle cx="'.($x + 9).'" cy="'.($y + 9).'" r="12" fill="'.($active ? self::BLUE : '#f4f7fc').'" stroke="'.($active ? self::INK : self::MUTED).'" stroke-width="2"/>';
        }

        return $out;
    }

    private static function bands4(int $mask): string
    {
        $out = '';
        foreach ([1, 2, 4, 8] as $index => $bit) {
            $active = ($mask & $bit) !== 0;
            $out .= '<rect x="'.(17 + $index * 18).'" y="23" width="13" height="54" rx="2.5" fill="'.($active ? self::BLUE : '#f4f7fc').'" stroke="'.($active ? self::INK : self::MUTED).'" stroke-width="1.7"/>';
        }

        return $out;
    }

    private static function dots(int $count): string
    {
        // Optically balanced layouts, rather than left-filled rows.
        $positions = match ($count) {
            1 => [[50, 50]],
            2 => [[36, 50], [64, 50]],
            3 => [[23, 50], [50, 50], [77, 50]],
            4 => [[36, 36], [64, 36], [36, 64], [64, 64]],
            5 => [[23, 36], [50, 36], [77, 36], [36, 64], [64, 64]],
            6 => [[23, 36], [50, 36], [77, 36], [23, 64], [50, 64], [77, 64]],
            default => throw new \InvalidArgumentException('Unsupported dot count: '.$count),
        };

        $out = '';
        foreach ($positions as [$x, $y]) {
            $out .= '<circle cx="'.$x.'" cy="'.$y.'" r="9" fill="'.self::BLUE.'" stroke="'.self::INK.'" stroke-width="1.5"/>';
        }

        return $out;
    }

    private static function polygon(int $sides): string
    {
        $points = [];
        for ($i = 0; $i < $sides; $i++) {
            $angle = -M_PI / 2 + 2 * M_PI * $i / $sides;
            $points[] = round(50 + 34 * cos($angle), 2).','.round(50 + 34 * sin($angle), 2);
        }

        return '<polygon points="'.implode(' ', $points).'" fill="#d9e7ff" stroke="'.self::INK.'" stroke-width="3.3" stroke-linejoin="round"/>';
    }

    private static function stripes(int $count): string
    {
        $out = '';
        $gap = 55 / max($count - 1, 1);
        for ($i = 0; $i < $count; $i++) {
            $x = $count === 1 ? 50 : 22.5 + $gap * $i;
            $out .= '<line x1="'.$x.'" y1="22" x2="'.$x.'" y2="78" stroke="'.self::BLUE.'" stroke-width="6" stroke-linecap="round"/>';
        }

        return $out;
    }

    private static function position(int $value): string
    {
        $out = '<rect x="12" y="12" width="76" height="76" rx="7" fill="none" stroke="'.self::MUTED.'" stroke-width="2"/>';
        for ($i = 0; $i < 9; $i++) {
            $x = 29 + ($i % 3) * 21;
            $y = 29 + intdiv($i, 3) * 21;
            $active = $i === $value;
            $out .= '<circle cx="'.$x.'" cy="'.$y.'" r="'.($active ? 8 : 3.2).'" fill="'.($active ? self::BLUE : '#b9cbe3').'"'
                .($active ? ' stroke="'.self::INK.'" stroke-width="1.8"' : '').'/>';
        }

        return $out;
    }
}
