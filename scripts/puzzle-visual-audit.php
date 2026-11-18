<?php
declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use App\Support\GraphicPuzzle;
use App\Support\PuzzleCatalog;

$folder = dirname(__DIR__).'/storage/app/qa';
if (!is_dir($folder)) {
    mkdir($folder, 0770, true);
}
foreach (array_chunk(PuzzleCatalog::definitions(), 5, true) as $index => $group) {
    $html = <<<'HTML'
<!doctype html><html lang="ru"><meta charset="utf-8"><title>Visual QA</title>
<style>
*{box-sizing:border-box}body{background:#edf3fe;margin:0;padding:22px;font:14px Arial,sans-serif;color:#102344}
.layout{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.card{background:#fff;border:1px solid #cfdaf0;border-radius:18px;padding:14px 16px;min-height:355px}
h2{font-size:17px;margin:0 0 6px}.prompt{font-size:13px;color:#3d5077;line-height:1.4;min-height:38px;margin:0 0 12px}
.matrix{display:grid;grid-template-columns:repeat(3,70px);gap:4px;margin:auto;width:max-content;padding:7px;background:#f7f9ff;border-radius:12px}
.cell{display:grid;place-items:center;width:70px;height:70px;background:white;border:1px solid #d6e5fa;border-radius:8px}
.cell svg{width:62px;height:62px}.missing{background:#e2ecff;border:2px dashed #276af0;color:#1660dc;font-size:29px;font-weight:700}
.choices{display:flex;justify-content:center;gap:5px;margin-top:9px}
.opt{width:64px;height:76px;border:1px solid #d4dff3;border-radius:8px;display:grid;place-items:center;background:white;font-weight:700;color:#576f94}
.opt svg{width:48px;height:48px}.opt span{font-size:11px}
</style><div class="layout">
HTML;
    foreach ($group as $offset => [$slug, $motif, $prompt, $cells, $candidates]) {
        $position = $offset + 1;
        $esc = static fn ($value) => htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
        $html .= '<section class="card"><h2>№ '.$position.' — '.$esc($slug).'</h2><p class="prompt">'.$esc($prompt).'</p><div class="matrix">';
        foreach ($cells as $cellIndex => $value) {
            $html .= $cellIndex === 8 ? '<div class="cell missing">?</div>' :
                '<div class="cell">'.GraphicPuzzle::image($slug, $value, 70).'</div>';
        }
        $html .= '</div><div class="choices">';
        $answer = $cells[8];
        $choices = array_values(array_filter($candidates, static fn (int $v) => $v !== $answer));
        array_splice($choices, ($offset * 5 + 2) % 6, 0, [$answer]);
        foreach ($choices as $slot => $value) {
            $html .= '<div class="opt">'.GraphicPuzzle::image($slug, $value, 50).'<span>'.chr(65+$slot).'</span></div>';
        }
        $html .= '</div></section>';
    }
    $html .= '</div></html>';
    $path = $folder.'/graphic-'.($index + 1).'.html';
    file_put_contents($path, $html);
    echo $path.PHP_EOL;
}
