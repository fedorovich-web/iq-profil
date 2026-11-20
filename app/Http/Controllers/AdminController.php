<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $section = $request->query('section', 'overview');
        abort_unless(is_string($section) && in_array($section, [
            'overview', 'users', 'attempts', 'transactions', 'reports', 'audit',
        ], true), 404);

        $rawSearch = $request->query('search', '');
        abort_unless(is_string($rawSearch), 422);
        $search = mb_substr(trim($rawSearch), 0, 100);

        // Each section reads only its own data. The overview needs just five
        // recent records, not four independent paginators and count queries.
        $users = collect();
        $attempts = collect();
        $payments = collect();
        $audit = collect();
        $stats = [];

        if ($section === 'overview' || $section === 'users') {
            $query = DB::table('users')
                ->select('id', 'name', 'email', 'created_at', 'is_admin');
            if ($section === 'users' && $search !== '') {
                $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%'));
            }
            $query->orderByDesc('id');
            $users = $section === 'users'
                ? $query->paginate(10, ['*'], 'usersPage')->withQueryString()
                : $query->limit(5)->get();
        }

        if ($section === 'overview' || $section === 'attempts') {
            $query = DB::table('quiz_attempts as a')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->select('a.id', 'a.uuid', 'a.status', 'a.correct_count', 'a.started_at', 'a.completed_at',
                    'u.name as user_name', 'u.email as user_email')
                ->orderByDesc('a.id');
            $attempts = $section === 'attempts'
                ? $query->paginate(10, ['*'], 'attemptsPage')->withQueryString()
                : $query->limit(5)->get();
        }

        if ($section === 'overview' || $section === 'transactions') {
            $query = DB::table('demo_payments as p')
                ->join('quiz_attempts as a', 'a.id', '=', 'p.attempt_id')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->select('p.id', 'p.status', 'p.amount_kopecks', 'p.currency',
                    'p.confirmed_at', 'a.uuid', 'u.name as user_name')
                ->orderByDesc('p.id');
            $payments = $section === 'transactions'
                ? $query->paginate(10, ['*'], 'paymentsPage')->withQueryString()
                : $query->limit(5)->get();
        }

        if ($section === 'audit') {
            $audit = DB::table('admin_audit_logs as l')
                ->join('users as u', 'u.id', '=', 'l.admin_id')
                ->select('l.*', 'u.email')
                ->orderByDesc('l.id')
                ->paginate(10, ['*'], 'auditPage')->withQueryString();
        }

        if ($section === 'overview' || $section === 'reports') {
            $stats = [
                'users' => DB::table('users')->count(),
                'attempts' => DB::table('quiz_attempts')->count(),
                'completed' => DB::table('quiz_attempts')->where('status', 'completed')->count(),
                'payments' => DB::table('demo_payments')->where('status', 'succeeded')->count(),
            ];
        }

        return view('admin.home', compact('section', 'users', 'attempts', 'payments', 'audit', 'stats', 'search'));
    }
}
