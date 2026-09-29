<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            // Identity only — zero extra queries, so the layout shell
            // renders instantly on every click.
            'auth' => [
                'user' => $request->user()?->only(['id', 'username', 'email', 'status']),
            ],
            // Heavy profile (employee, branch, roles, permissions) streams
            // in right after via a deferred request.
            'authProfile' => Inertia::defer(fn () => $request->user()?->loadMissing([
                'employee.branch:id,name,company_id',
                'employee.company:id,name',
                'roles.permissions:id,code,name,module',
            ])),
        ];
    }
}
