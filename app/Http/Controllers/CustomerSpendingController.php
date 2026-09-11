<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerSpendingController extends Controller
{
    public function index(): View
    {
        $customerSpending = DB::table('branches')
            ->leftJoin('transactions', 'transactions.branch_id', '=', 'branches.id')
            ->leftJoin('customers', 'customers.id', '=', 'transactions.customer_id')
            ->leftJoin('transaction_details', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->select([
                'branches.id AS branch_id',
                'branches.name AS branch_name',
                'customers.id AS customer_id',
                'customers.name AS customer_name',
                DB::raw('COALESCE(SUM(transaction_details.net_sales_amount), 0) AS total_spending'),
                DB::raw('COUNT(DISTINCT transactions.id) AS transaction_count'),
            ])
            ->groupBy('branches.id', 'branches.name', 'customers.id', 'customers.name')
            ->orderBy('branches.name')
            ->orderByDesc('total_spending')
            ->orderBy('customers.name')
            ->get();

        $branches = $customerSpending
            ->groupBy('branch_id')
            ->map(function (Collection $customers): array {
                $first = $customers->first();
                $topCustomers = $customers
                    ->filter(fn ($customer) => $customer->customer_id !== null)
                    ->take(3)
                    ->values()
                    ->map(fn ($customer) => [
                        'name' => $customer->customer_name,
                        'total_spending' => (float) $customer->total_spending,
                        'transaction_count' => (int) $customer->transaction_count,
                    ]);

                return [
                    'id' => $first->branch_id,
                    'name' => $first->branch_name,
                    'top_customers' => $topCustomers,
                ];
            })
            ->values();

        $totalSpending = $customerSpending->sum('total_spending');
        $customerCount = $customerSpending
            ->pluck('customer_id')
            ->filter()
            ->unique()
            ->count();

        return view('pages.customer-spending.index', [
            'branches' => $branches,
            'totalSpending' => (float) $totalSpending,
            'customerCount' => $customerCount,
        ]);
    }
}
