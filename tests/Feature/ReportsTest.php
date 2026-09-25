<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PosDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_page_loads_with_summary(): void
    {
        $this->seed(PosDemoSeeder::class);

        $user = User::where('username', 'admin')->firstOrFail();

        $response = $this->withoutVite()->actingAs($user)->get('/reports');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('reports/index')
            ->has('summary.revenue')
            ->has('summary.orderCount')
            ->has('summary.avgTicket')
            ->has('summary.cogs')
            ->has('summary.grossProfit')
            ->has('summary.expensesTotal')
            ->has('summary.net')
            ->has('topProducts')
            ->has('salesByDay')
        );
    }
}
