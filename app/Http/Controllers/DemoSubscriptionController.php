<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DemoSubscriptionLifecycle;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class DemoSubscriptionController extends Controller
{
    public function show(Request $request): View
    {
        $subscriptions = $this->query($request)
            ->join('quiz_attempts as a', 'a.id', '=', 's.attempt_id')
            ->select('s.id', 's.status', 's.activated_at', 's.cancelled_at', 'a.uuid')
            ->orderByDesc('s.id')->get();

        return view('legal.cancel', compact('subscriptions'));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->validate(['confirm_cancel' => ['required', 'accepted']]);

        $count = DemoSubscriptionLifecycle::cancel($request);

        return redirect()->route('subscription.cancel')
            ->with('status', $count > 0
                ? 'Демонстрационное продление отменено. Списаний не было и не будет.'
                : 'Активных демонстрационных подписок нет. Никаких списаний нет.');
    }

    private function query(Request $request): Builder
    {
        $query = DB::table('demo_subscriptions as s')
            ->join('quiz_attempts as owner', 'owner.id', '=', 's.attempt_id');

        if ($request->user()) {
            return $query->where('owner.user_id', $request->user()->id);
        }

        $owned = array_values(array_filter(
            $request->session()->get('owned_attempts', []),
            fn ($uuid) => is_string($uuid)
        ));

        return $query->whereNull('owner.user_id')->whereIn('owner.uuid', $owned);
    }
}
