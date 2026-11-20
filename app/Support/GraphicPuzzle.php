<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\HtmlString;

final class GraphicPuzzle
{
    // Frozen vector visual language for revision 4.
    private const INK = '#18304f';

    private const BLUE = '#2766c7';

    private const TEAL = '#0b9299';

    private const DEEP = '#33415f';

    private const MUTED = '#d0def0';

    public static function name(string $kind, int $value): string
    {
        $definition = PuzzleCatalog::find($kind);
        if ($definition !== null) {
            return match ($definition[1]) {
                'dots', 'constellation' => 'Количество синих точек: '.$value,
                'polygon' => 'Многоугольник с '.$value.' сторонами',
                'polygon-tone' => (['Синий', 'Бирюзовый', 'Тёмно-синий'][intdiv($value, 3)] ?? 'Неизвестный').' '.(['треугольник', 'квадрат', 'пятиугольник'][$value % 3]),
                'gem' => (['Синий', 'Бирюзовый', 'Тёмно-синий', 'Серо-синий', 'Голубой', 'Сиреневый'][$value] ?? 'Неизвестный').' шестиугольник',
                'shape-tone' => (['Синяя', 'Бирюзовая', 'Тёмно-синяя'][intdiv($value, 3)] ?? 'Неизвестная').' фигура: '.(['круг', 'квадрат', 'треугольник'][$value % 3]),
                'shape-size' => (['Маленький', 'Средний', 'Большой'][intdiv($value, 3)] ?? 'Неизвестный').' '.(['круг', 'квадрат', 'треугольник'][$value % 3]),
                'nested' => 'Внутренняя фигура: '.(['круг', 'квадрат', 'треугольник', 'ромб', 'звезда', 'шестиугольник', 'трапеция', 'пятиугольник'][$value] ?? 'неизвестная'),
                'direction' => 'Стрелка направлена '.(['вверх', 'вверх вправо', 'вниз вправо', 'вниз', 'вниз влево', 'вверх влево'][$value] ?? 'неизвестно куда'),
                'compass8' => 'Стрелка: '.self::directionName8($value),
                'direction-tone8' => (['Синяя', 'Бирюзовая', 'Тёмно-синяя'][intdiv($value, 8)] ?? 'Неизвестная').' стрелка: '.self::directionName8($value % 8),
                'direction-tone' => (['Синяя', 'Тёмно-синяя', 'Бирюзовая'][intdiv($value, 3)] ?? 'Неизвестная').' стрелка: '.(['вверх', 'вверх вправо', 'вниз вправо'][$value % 3]),
                'orbit' => 'Маркер на окружности: '.(['сверху', 'сверху справа', 'снизу справа', 'снизу', 'снизу слева', 'сверху слева'][$value] ?? 'неизвестно'),
                'orbit-pair' => 'Внешний маркер: '.self::orbitName(intdiv($value, 6)).'; внутренний: '.self::orbitName($value % 6),
                'grid' => 'Маркер в сетке: '.(['верхняя левая', 'верхняя центральная', 'верхняя правая', 'средняя левая', 'центр', 'средняя правая', 'нижняя левая', 'нижняя центральная', 'нижняя правая'][$value] ?? 'неизвестно'),
                'mosaic' => self::mosaicDescription($value),
                'mosaic-mask' => self::maskDescription($value, ['верхний левый квадрат', 'верхний правый квадрат', 'нижний левый квадрат', 'нижний правый квадрат']),
                'shapes3' => self::maskDescription($value, ['круг', 'треугольник', 'квадрат']),
                'bars3' => self::maskDescription($value, ['левая полоса', 'средняя полоса', 'правая полоса']),
                'corner' => 'Г-образный угол '.(['сверху слева', 'сверху справа', 'снизу справа', 'снизу слева'][$value % 4]).', точка '.(['синяя', 'тёмно-синяя', 'бирюзовая'][intdiv($value, 4)] ?? 'неизвестна'),
                'bands' => 'Вертикальных линий: '.($value + 1),
                'cross-bars' => 'Вертикальных полос: '.(intdiv($value, 3) + 1).'; горизонтальных: '.($value % 3 + 1),
                'quantity-grid' => 'Количество закрашенных маркеров: '.$value,
                'radial' => 'Количество лучей: '.($value + 1),
                'color-dots' => 'Точки '.(['синие', 'бирюзовые', 'тёмно-синие'][intdiv($value, 3)] ?? 'неизвестного цвета').', количество: '.($value % 3 + 1),
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
            'compass8' => self::compass8($value),
            'direction-tone8' => self::compass8($value % 8, [self::BLUE, self::TEAL, self::DEEP][intdiv($value, 8)]),
            'direction-tone' => self::direction(($value % 3) * 60, [self::BLUE, self::DEEP, self::TEAL][intdiv($value, 3)]),
            'polygon' => self::polygon($value),
            'polygon-tone' => self::polygonTone($value),
            'orbit' => self::orbit($value),
            'orbit-pair' => self::orbitPair($value),
            'grid' => self::position($value),
            'mosaic' => self::mosaic($value),
            'mosaic-mask' => self::mosaicMask($value),
            'shapes3' => self::shapes3($value),
            'bars3' => self::bars3($value),
            'gem' => self::gem($value),
            'shape-tone' => self::shapeTone($value),
            'shape-size' => self::shapeSize($value),
            'nested' => self::nested($value),
            'corner' => self::corner($value),
            'bands' => self::stripes($value + 1),
            'cross-bars' => self::crossBars($value),
            'quantity-grid' => self::quantityGrid($value),
            'radial' => self::radial($value + 1),
            'color-dots' => self::colorDots($value),
            'quads4' => self::quads4($value),
            'bands4' => self::bands4($value),
            default => '',
        };
    }

    private static function directionName8(int $value): string
    {
        return ['север', 'северо-восток', 'восток', 'юго-восток', 'юг', 'юго-запад', 'запад', 'северо-запад'][$value] ?? 'неизвестно';
    }

    private static function orbitName(int $value): string
    {
        return ['верх', 'верхний правый сектор', 'нижний правый сектор', 'низ', 'нижний левый сектор', 'верхний левый сектор'][$value] ?? 'неизвестно';
    }

    private static function polygonTone(int $value): string
    {
        $sides = 3 + $value % 3;
        $fill = [self::BLUE, self::TEAL, self::DEEP][intdiv($value, 3)];

        return '<polygon points="'.self::balancedPolygonPoints($sides).'" fill="'.$fill.'"'
            .' stroke="'.self::INK.'" stroke-width="2.4" stroke-linejoin="round"/>';
    }

    private static function compass8(int $value, string $fill = self::BLUE): string
    {
        return '<path d="M50 14 L68 43 H57 V79 H43 V43 H32 Z"'
            .' fill="'.$fill.'" stroke="'.self::INK.'" stroke-width="2" stroke-linejoin="round"'
            .' transform="rotate('.($value * 45).' 50 50)"/>';
    }

    private static function orbitPair(int $value): string
    {
        $outer = intdiv($value, 6);
        $inner = $value % 6;
        $out = '<circle cx="50" cy="50" r="34" fill="none" stroke="'.self::MUTED.'" stroke-width="2.3"/>'
            .'<circle cx="50" cy="50" r="19" fill="none" stroke="'.self::MUTED.'" stroke-width="2.3"/>';

        foreach ([[34, $outer, 7.2, self::BLUE], [19, $inner, 5.8, self::TEAL]] as [$radius, $index, $dotRadius, $color]) {
            $angle = -M_PI / 2 + $index * M_PI / 3;
            $x = round(50 + $radius * cos($angle), 2);
            $y = round(50 + $radius * sin($angle), 2);
            $out .= '<circle cx="'.$x.'" cy="'.$y.'" r="'.$dotRadius.'" fill="'.$color
                .'" stroke="'.self::INK.'" stroke-width="1.5"/>';
        }

        return $out;
    }

    private static function crossBars(int $value): string
    {
        $vertical = intdiv($value, 3) + 1;
        $horizontal = $value % 3 + 1;
        $out = '<line x1="19" y1="54" x2="81" y2="54" stroke="'.self::MUTED.'" stroke-width="2"/>';
        for ($i = 0; $i < $vertical; $i++) {
            $x = 50 + ($i - ($vertical - 1) / 2) * 19;
            $out .= '<line x1="'.$x.'" y1="17" x2="'.$x.'" y2="44" stroke="'.self::BLUE
                .'" stroke-width="6" stroke-linecap="round"/>';
        }
        for ($i = 0; $i < $horizontal; $i++) {
            $y = 73 + ($i - ($horizontal - 1) / 2) * 11;
            $out .= '<line x1="29" y1="'.$y.'" x2="71" y2="'.$y.'" stroke="'.self::TEAL
                .'" stroke-width="5" stroke-linecap="round"/>';
        }

        return $out;
    }

    private static function mosaicMask(int $value): string
    {
        $out = '';
        for ($i = 0; $i < 4; $i++) {
            $x = 20 + ($i % 2) * 34;
            $y = 20 + intdiv($i, 2) * 34;
            $on = ($value & (1 << $i)) !== 0;
            $out .= '<rect x="'.$x.'" y="'.$y.'" width="26" height="26" rx="4"'
                .' fill="'.($on ? self::BLUE : '#f4f8ff').'" stroke="'.($on ? self::INK : self::MUTED).'" stroke-width="2"/>';
        }

        return $out;
    }

    private static function quantityGrid(int $value): string
    {
        $out = '';
        for ($i = 0; $i < 16; $i++) {
            $x = 20 + ($i % 4) * 20;
            $y = 20 + intdiv($i, 4) * 20;
            $active = $i < $value;
            // Active markers are distinct at 46 px; inactive slots are only alignment guides.
            $out .= '<circle cx="'.$x.'" cy="'.$y.'" r="'.($active ? 8.2 : 2.6).'"'
                .' fill="'.($active ? self::BLUE : self::MUTED).'"'
                .($active ? ' stroke="'.self::INK.'" stroke-width="1.6"' : '').'/>';
        }

        return $out;
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
        // The three distinct positions remain stable across every 3-bit subset.
        if ($mask & 1) {
            $out .= '<circle cx="50" cy="25" r="13.5" fill="'.self::BLUE.'" stroke="'.self::INK.'" stroke-width="2"/>';
        }
        if ($mask & 2) {
            $out .= '<path d="M31 44 L49 78 H13 Z" fill="'.self::BLUE.'" stroke="'.self::INK.'" stroke-width="2" stroke-linejoin="round"/>';
        }
        if ($mask & 4) {
            $out .= '<rect x="56" y="46" width="29" height="29" rx="3" fill="'.self::BLUE.'" stroke="'.self::INK.'" stroke-width="2"/>';
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
        $colors = [self::BLUE, self::TEAL, self::DEEP, '#657fa0', '#4b8aa8', '#7f79af'];
        $color = $colors[$value] ?? self::INK;

        return '<path d="M50 13 L81 31 L81 68 L50 87 L19 68 L19 31 Z" fill="'.$color.'" stroke="'.self::INK.'" stroke-width="2.8" stroke-linejoin="round"/>';
    }

    private static function shapeTone(int $value): string
    {
        $color = [self::BLUE, self::TEAL, self::DEEP][intdiv($value, 3)];

        // Match visual mass, not only bounding-box dimensions.
        return match ($value % 3) {
            0 => '<circle cx="50" cy="50" r="27.4" fill="'.$color.'" stroke="'.self::INK.'" stroke-width="2.4"/>',
            1 => '<rect x="26" y="26" width="48" height="48" rx="3" fill="'.$color.'" stroke="'.self::INK.'" stroke-width="2.4"/>',
            2 => '<path d="M50 17 L83 81 H17 Z" fill="'.$color.'" stroke="'.self::INK.'" stroke-width="2.4" stroke-linejoin="round"/>',
        };
    }

    private static function shapeSize(int $value): string
    {
        $radius = [15, 21, 27][intdiv($value, 3)];
        $stroke = ' stroke="'.self::INK.'" stroke-width="2.4" stroke-linejoin="round"';

        return match ($value % 3) {
            0 => '<circle cx="50" cy="50" r="'.$radius.'" fill="'.self::BLUE.'"'.$stroke.'/>',
            1 => '<rect x="'.(50 - $radius).'" y="'.(50 - $radius).'" width="'.(2 * $radius)
                .'" height="'.(2 * $radius).'" rx="3" fill="'.self::BLUE.'"'.$stroke.'/>',
            2 => '<path d="M50 '.round(50 - $radius * 1.43, 2)
                .' L'.round(50 + $radius * 1.26, 2).' '.round(50 + $radius * 0.71, 2)
                .' H'.round(50 - $radius * 1.26, 2).' Z" fill="'.self::BLUE.'"'.$stroke.'/>',
        };
    }

    private static function nested(int $value): string
    {
        $fill = self::BLUE;
        $inner = match ($value) {
            0 => '<circle cx="50" cy="50" r="19" fill="'.$fill.'"/>',
            1 => '<rect x="32" y="32" width="36" height="36" rx="2.5" fill="'.$fill.'"/>',
            2 => '<path d="M50 25 L77 74 H23 Z" fill="'.$fill.'"/>',
            3 => '<path d="M50 25 L75 50 L50 75 L25 50 Z" fill="'.$fill.'"/>',
            4 => '<path d="M50 24 L57 42 L76 42 L61 54 L67 73 L50 62 L33 73 L39 54 L24 42 L43 42 Z" fill="'.$fill.'"/>',
            5 => '<path d="M36 28 L64 28 L78 50 L64 72 L36 72 L22 50 Z" fill="'.$fill.'"/>',
            6 => '<path d="M37 31 H63 L74 69 H26 Z" fill="'.$fill.'"/>',
            7 => '<path d="M50 26 L72 45 L65 73 H35 L28 45 Z" fill="'.$fill.'"/>',
            default => throw new \InvalidArgumentException('Unsupported nested figure: '.$value),
        };

        return '<rect x="11" y="11" width="78" height="78" rx="7" fill="none" stroke="'.self::MUTED.'" stroke-width="3"/>'
            .'<g stroke="'.self::INK.'" stroke-width="2" stroke-linejoin="round">'.$inner.'</g>';
    }

    private static function corner(int $value): string
    {
        $rotation = ($value % 4) * 90;
        $tone = [self::BLUE, self::DEEP, self::TEAL][intdiv($value, 4)];

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
        $colors = [self::BLUE, self::TEAL, self::DEEP];
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
            7 => [[23, 29], [50, 29], [77, 29], [50, 50], [23, 71], [50, 71], [77, 71]],
            8 => [[20, 35], [40, 35], [60, 35], [80, 35], [20, 65], [40, 65], [60, 65], [80, 65]],
            default => throw new \InvalidArgumentException('Unsupported dot count: '.$count),
        };

        $out = '';
        foreach ($positions as [$x, $y]) {
            $out .= '<circle cx="'.$x.'" cy="'.$y.'" r="'.($count >= 8 ? 7.7 : 9).'" fill="'.self::BLUE.'" stroke="'.self::INK.'" stroke-width="1.5"/>';
        }

        return $out;
    }

    /**
     * A common area and optical bounding-box center for polygons with
     * different vertex counts. Constant circumradius makes triangles
     * disproportionately smaller than octagons.
     */
    private static function balancedPolygonPoints(int $sides): string
    {
        if ($sides < 3 || $sides > 8) {
            throw new \InvalidArgumentException('Unsupported polygon sides: '.$sides);
        }

        $targetArea = 2200.0;
        $radius = sqrt(2 * $targetArea / ($sides * sin(2 * M_PI / $sides)));
        $coordinates = [];
        for ($i = 0; $i < $sides; $i++) {
            $angle = -M_PI / 2 + 2 * M_PI * $i / $sides;
            $coordinates[] = [50 + $radius * cos($angle), 50 + $radius * sin($angle)];
        }

        $ys = array_column($coordinates, 1);
        $verticalShift = 50 - (min($ys) + max($ys)) / 2;
        $points = [];
        foreach ($coordinates as [$x, $y]) {
            $points[] = round($x, 2).','.round($y + $verticalShift, 2);
        }

        return implode(' ', $points);
    }

    private static function polygon(int $sides): string
    {
        return '<polygon points="'.self::balancedPolygonPoints($sides).'" fill="#d9e7ff"'
            .' stroke="'.self::INK.'" stroke-width="2.8" stroke-linejoin="round"/>';
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
