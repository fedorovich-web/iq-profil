<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

final class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('legal.privacy');
    }

    public function offer(): View
    {
        return view('legal.offer');
    }

    public function consent(): View
    {
        return view('legal.consent');
    }
}
