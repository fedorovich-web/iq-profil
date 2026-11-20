<?php

declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
use App\Support\GraphicPuzzle;
use App\Support\PuzzleCatalog;

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$out = '<!doctype html><html lang="ru"><meta charset="utf-8"><title>IQ.Профиль — контакт-лист 25 SVG матриц</title><style>'
.'.sheet{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr));gap:18px}.card{padding:15px;border:1px solid #d5e2f5;border-radius:13px;background:white;min-width:0;font-family:Arial,sans-serif;color:#18304f}'
.'.card h2{font-size:16px;margin:0 0 8px}.prompt{min-height:44px;font-size:11px;color:#547;line-height:1.4;margin:0 0 8px}'
.'.matrix{width:max-content;display:grid;grid-template-columns:repeat(3,58px);gap:4px;background:#e7f0fc;padding:5px;border-radius:8px}.cell{width:58px;height:58px;background:#fff;display:grid;place-items:center;border-radius:5px}.cell svg{width:51px;height:51px}'
.'.missing{color:#2569c7;border:1px dashed #9ba9c4;font-size:27px}'
.'.opts{display:grid;grid-template-columns:repeat(6,1fr);gap:2px;margin-top:10px}.opt{border:1px solid #d4dfef;text-align:center;border-radius:4px;font-size:9px;padding:3px}.opt svg{width:38px;height:38px;display:block;margin:auto}.solution{margin-top:8px;font-size:10px;color:#53657e}'
.'body{margin:0;padding:20px;background:#f1f5fb}@media(max-width:650px){.opts{grid-template-columns:repeat(3,minmax(0,1fr))}}</style><body><main class="sheet">';
foreach (PuzzleCatalog::definitions() as $index => [$kind,$motif,$prompt,$cells,$choices,$explanation]) {
    $out .= '<section class="card"><h2>№ '.($index + 1).' · '.esc($kind).'</h2><p class="prompt">'.esc($prompt).'</p><div class="matrix">';
    foreach ($cells as $i => $state) {
        $out .= '<div class="cell'.($i === 8 ? ' missing' : '').'">';
        $out .= $i === 8 ? '?' : (string) GraphicPuzzle::image($kind, $state, 52);
        $out .= '</div>';
    }
    $out .= '</div><div class="opts">';
    foreach ($choices as $i => $state) {
        $out .= '<div class="opt">'.(string) GraphicPuzzle::image($kind, $state, 40).chr(65 + $i).'</div>';
    }
    $out .= '</div><p class="solution">Верный SVG: '.esc(GraphicPuzzle::name($kind, $cells[8])).'</p></section>';
}
$out .= '</main></body></html>';
echo $out;
