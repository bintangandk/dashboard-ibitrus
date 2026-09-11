<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Query: ambil tanggal transaksi penjualan paling awal dan paling terbaru.
        $salesDateRange = DB::table('transactions')
            ->join('transaction_details', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->selectRaw('MIN(transactions.transaction_date) AS first_date')
            ->selectRaw('MAX(transactions.transaction_date) AS latest_date')
            ->first();
        $firstSalesDate = $salesDateRange?->first_date
            ? Carbon::parse($salesDateRange->first_date)
            : now();
        $latestSalesDate = $salesDateRange?->latest_date
            ? Carbon::parse($salesDateRange->latest_date)
            : now();
        $latestSalesYear = $latestSalesDate->year;

        // Filter: baca dan validasi date range dari request untuk fitur tren penjualan.
        $startDate = $this->parseDate($request->input('start_date'));
        $endDate = $this->parseDate($request->input('end_date'));

        // Default: gunakan seluruh histori penjualan jika date range belum dipilih atau tidak valid.
        if (! $startDate || ! $endDate || $startDate->greaterThan($endDate)) {
            $startDate = $firstSalesDate->copy()->startOfDay();
            $endDate = $latestSalesDate->copy()->endOfMonth()->endOfDay();
        }

        $dateRange = [$startDate->toDateString(), $endDate->toDateString()];

        // Query: hitung total penjualan dan jumlah transaksi untuk setiap cabang.
        $branchSales = DB::table('branches')
            ->leftJoin('transactions', 'transactions.branch_id', '=', 'branches.id')
            ->leftJoin('transaction_details', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->select([
                'branches.id',
                'branches.name',
                DB::raw('COALESCE(SUM(transaction_details.net_sales_amount), 0) AS total_sales'),
                DB::raw('COUNT(DISTINCT transactions.id) AS transaction_count'),
            ])
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('total_sales')
            ->get()
            ->map(fn($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'total_sales' => (float) $branch->total_sales,
                'transaction_count' => (int) $branch->transaction_count,
            ])
            ->values();

        // Kalkulasi: siapkan nilai ringkasan dashboard dari hasil agregasi cabang.
        $totalSales = $branchSales->sum('total_sales');
        $totalTransactions = $branchSales->sum('transaction_count');
        $topBranch = $branchSales->first();
        $attentionBranch = $branchSales->last();
        $topBranchContribution = $totalSales > 0
            ? ($topBranch['total_sales'] / $totalSales) * 100
            : 0;
        $branchSales = $branchSales->map(fn($branch) => [
            ...$branch,
            'contribution' => $totalSales > 0
                ? ($branch['total_sales'] / $totalSales) * 100
                : 0,
        ]);

        // Query: agregasikan penjualan per tahun dan bulan sesuai date range tren.
        $monthlySales = DB::table('transactions')
            ->join('transaction_details', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->whereBetween('transactions.transaction_date', $dateRange)
            ->selectRaw('YEAR(transactions.transaction_date) AS year')
            ->selectRaw('MONTH(transactions.transaction_date) AS month')
            ->selectRaw('SUM(transaction_details.net_sales_amount) AS total_sales')
            ->groupByRaw('YEAR(transactions.transaction_date), MONTH(transactions.transaction_date)')
            ->get()
            ->mapWithKeys(fn($row) => [
                sprintf('%04d-%02d', $row->year, $row->month) => (float) $row->total_sales,
            ]);

        // Transformasi: isi bulan tanpa transaksi dengan nilai penjualan 0.
        $monthlyTrend = collect(CarbonPeriod::create(
            $startDate->copy()->startOfMonth(),
            '1 month',
            $endDate->copy()->startOfMonth(),
        ))->map(fn(Carbon $month) => [
            'month' => $month->format('Y-m'),
            'total_sales' => (float) ($monthlySales[$month->format('Y-m')] ?? 0),
        ]);

        // Query: agregasikan penjualan berdasarkan minggu ISO, dimulai hari Senin.
        $weeklySales = DB::table('transactions')
            ->join('transaction_details', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->whereBetween('transactions.transaction_date', $dateRange)
            ->selectRaw('YEARWEEK(transactions.transaction_date, 1) AS week, SUM(transaction_details.net_sales_amount) AS total_sales')
            ->groupByRaw('YEARWEEK(transactions.transaction_date, 1)')
            ->orderBy('week')
            ->get()
            ->mapWithKeys(fn($row) => [(string) $row->week => (float) $row->total_sales]);

        // Transformasi: isi minggu tanpa transaksi agar urutan minggu tetap lengkap.
        $weeklyTrend = collect(CarbonPeriod::create(
            $startDate->copy()->startOfWeek(),
            '1 week',
            $endDate->copy()->startOfWeek(),
        ))->map(fn(Carbon $weekStart) => [
            'week' => $weekStart->isoWeek,
            'label' => $weekStart->format('d M') . ' - ' . $weekStart->copy()->endOfWeek()->format('d M'),
            'total_sales' => (float) ($weeklySales[$weekStart->format('oW')] ?? 0),
        ]);

        // Payload: gabungkan data tren bulanan dan mingguan untuk ApexCharts.
        $salesTrend = [
            'monthly' => $monthlyTrend,
            'weekly' => $weeklyTrend,
        ];

        // Query: hitung produk terlaris berdasarkan total qty per cabang.
        $productSales = DB::table('branches')
            ->join('transactions', 'transactions.branch_id', '=', 'branches.id')
            ->join('transaction_details', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('products', 'products.id', '=', 'transaction_details.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->select([
                'branches.id AS branch_id',
                'products.id AS product_id',
                'products.name AS product_name',
                'categories.id AS category_id',
                'categories.name AS category_name',
                DB::raw('SUM(transaction_details.qty) AS total_qty'),
                DB::raw('SUM(transaction_details.net_sales_amount) AS total_sales'),
            ])
            ->groupBy('branches.id', 'products.id', 'products.name', 'categories.id', 'categories.name')
            ->orderByDesc('total_qty')
            ->get()
            ->map(fn($product) => [
                'branch_id' => (string) $product->branch_id,
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'category_id' => (string) $product->category_id,
                'category_name' => $product->category_name,
                'total_qty' => (float) $product->total_qty,
                'total_sales' => (float) $product->total_sales,
            ]);

        // Transformasi: gabungkan produk yang sama dari seluruh cabang dan ambil 5 teratas.
        $topProducts = $productSales
            ->groupBy('product_id')
            ->map(fn($products) => [
                'product_id' => $products->first()['product_id'],
                'product_name' => $products->first()['product_name'],
                'total_qty' => $products->sum('total_qty'),
                'total_sales' => $products->sum('total_sales'),
            ])
            ->sortByDesc('total_qty')
            ->take(5)
            ->values();

        // Payload: siapkan daftar produk terlaris untuk pilihan Semua dan setiap cabang.
        $topProductsByBranch = ['all' => $topProducts];

        foreach ($productSales->groupBy('branch_id') as $branchId => $products) {
            $topProductsByBranch[$branchId] = $products
                ->sortByDesc('total_qty')
                ->take(5)
                ->values();
        }

        // Payload: siapkan produk terlaris berdasarkan cabang lalu kategori.
        $topProductsByBranchAndCategory = ['all' => ['all' => $this->topProducts($productSales)]];
        $categoriesByBranch = ['all' => $productSales
            ->unique('category_id')
            ->sortBy('category_name')
            ->map(fn($product) => [
                'id' => $product['category_id'],
                'name' => $product['category_name'],
            ])
            ->values()];

        foreach ($productSales->groupBy('branch_id') as $branchId => $products) {
            $topProductsByBranchAndCategory[$branchId] = ['all' => $this->topProducts($products)];
            $categoriesByBranch[$branchId] = $products
                ->unique('category_id')
                ->sortBy('category_name')
                ->map(fn($product) => [
                    'id' => $product['category_id'],
                    'name' => $product['category_name'],
                ])
                ->values();

            foreach ($products->groupBy('category_id') as $categoryId => $categoryProducts) {
                $topProductsByBranchAndCategory[$branchId][$categoryId] = $this->topProducts($categoryProducts);
            }
        }

        foreach ($productSales->groupBy('category_id') as $categoryId => $categoryProducts) {
            $topProductsByBranchAndCategory['all'][$categoryId] = $this->topProducts($categoryProducts);
        }

        // Response: kirim seluruh data dashboard ke view Blade.
        return view('pages.dashboard.index', compact(
            'branchSales',
            'totalSales',
            'totalTransactions',
            'topBranch',
            'attentionBranch',
            'topBranchContribution',
            'latestSalesYear',
            'salesTrend',
            'startDate',
            'endDate',
            'topProductsByBranch',
            'topProductsByBranchAndCategory',
            'categoriesByBranch',
        ));
    }

    private function topProducts(Collection $products): Collection
    {
        return $products
            ->groupBy('product_id')
            ->map(fn($productGroup) => [
                'product_id' => $productGroup->first()['product_id'],
                'product_name' => $productGroup->first()['product_name'],
                'total_qty' => $productGroup->sum('total_qty'),
                'total_sales' => $productGroup->sum('total_sales'),
            ])
            ->sortByDesc('total_qty')
            ->take(5)
            ->values();
    }

    private function parseDate(?string $date): ?Carbon
    {
        // Helper: validasi format tanggal YYYY-MM-DD tanpa normalisasi tanggal invalid.
        try {
            if (! $date) {
                return null;
            }

            $parsedDate = Carbon::createFromFormat('!Y-m-d', $date);
            $errors = Carbon::getLastErrors();

            if (($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
                || $parsedDate->format('Y-m-d') !== $date
            ) {
                return null;
            }

            return $parsedDate->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
