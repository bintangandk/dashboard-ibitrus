@extends('layouts.app')

@section('content')
    <script>
        window.dashboardData = {
            branchSales: @json($branchSales),
            salesTrend: @json($salesTrend),
            latestSalesYear: @json($latestSalesYear),
            startDate: @json($startDate->toDateString()),
            endDate: @json($endDate->toDateString()),
            topProductsByBranch: @json($topProductsByBranch),
        };
    </script>

    <div class="col-12">
        {{-- <section class="dashboard-intro mb-4">
            <div>
                <span class="eyebrow">PERFORMA OPERASIONAL</span>
                <h2 class="page-section-title mb-1">Ringkasan penjualan seluruh cabang</h2>
                <p class="text-muted-green mb-0">Pantau kontribusi Kota A sampai Kota D dan tentukan prioritas harian.</p>
            </div>
        </section> --}}

        <section class="row g-4 mb-4" aria-label="Ringkasan penjualan">
            <div class="col-sm-6 col-xl-3">
                <article class="card metric-card h-100">
                    <div class="metric-icon metric-icon-dark"><i class="bi bi-wallet2"></i></div><span class="stat-label">Total
                        penjualan</span><strong class="metric-value">Rp
                        {{ number_format($totalSales, 0, ',', '.') }}</strong><span class="trend-badge trend-up"><i
                            class="bi bi-arrow-up-right"></i> 18,6% bulan ini</span>
                </article>
            </div>
            <div class="col-sm-6 col-xl-3">
                <article class="card metric-card h-100">
                    <div class="metric-icon metric-icon-lime"><i class="bi bi-graph-up-arrow"></i></div><span
                        class="stat-label">Cabang teratas</span><strong
                        class="metric-value">{{ $topBranch['name'] ?? '-' }}</strong><span class="text-muted-green small">Rp
                        {{ number_format($topBranch['total_sales'] ?? 0, 0, ',', '.') }} <span class="text-success">•
                            {{ number_format($topBranchContribution, 1, ',', '.') }}% kontribusi</span></span>
                </article>
            </div>
            <div class="col-sm-6 col-xl-3">
                <article class="card metric-card h-100">
                    <div class="metric-icon metric-icon-orange"><i class="bi bi-bag-check"></i></div><span
                        class="stat-label">Total transaksi</span><strong
                        class="metric-value">{{ number_format($totalTransactions, 0, ',', '.') }}</strong><span
                        class="trend-badge trend-up"><i class="bi bi-arrow-up-right"></i> 12,4% vs bulan lalu</span>
                </article>
            </div>
            <div class="col-sm-6 col-xl-3">
                <article class="card metric-card h-100 metric-card-alert">
                    <div class="metric-icon metric-icon-red"><i class="bi bi-exclamation-triangle"></i></div><span
                        class="stat-label">Perlu perhatian</span><strong
                        class="metric-value">{{ $attentionBranch['name'] ?? '-' }}</strong><span
                        class="text-danger small">Rp {{ number_format($attentionBranch['total_sales'] ?? 0, 0, ',', '.') }}
                        penjualan terendah</span>
                </article>
            </div>
        </section>

        <section class="row g-4 mb-4">
            <div class="col-xl-8">
                <article class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h2 class="card-title mb-1">Tren penjualan</h2>
                            <p class="text-muted-green small mb-0">Tren seluruh cabang dalam rentang tanggal yang dipilih
                            </p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap justify-content-end">
                            <button class="btn-date-picker" type="button" id="date-picker-trigger">
                                <i class="bi bi-calendar4-event"></i>
                                <span id="selected-date-range">{{ $startDate->format('d M Y') }} -
                                    {{ $endDate->format('d M Y') }}</span>
                                <i class="bi bi-chevron-down ms-1"></i>
                            </button>
                            <input type="text" id="dashboard-date-range" class="visually-hidden" tabindex="-1"
                                aria-hidden="true">
                            <select class="form-select form-select-sm dashboard-select" id="trend-period"
                                aria-label="Periode tren penjualan">
                                <option value="monthly" selected>Bulanan</option>
                                <option value="weekly">Mingguan</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="sales-trend-chart" aria-label="Grafik tren penjualan"></div>
                    </div>
                </article>
            </div>
            <div class="col-xl-4">
                <article class="card h-100">
                    <div class="card-header">
                        <h2 class="card-title mb-1">Performa antar cabang</h2>
                        <p class="text-muted-green small mb-0">Kontribusi terhadap total penjualan</p>
                    </div>
                    <div class="card-body pt-0">
                        <div id="branch-performance-chart" aria-label="Grafik performa cabang"></div>
                        <div class="branch-legend"><span><i class="legend-dot legend-a"></i>Kota A</span><span><i
                                    class="legend-dot legend-b"></i>Kota B</span><span><i
                                    class="legend-dot legend-c"></i>Kota C</span><span><i
                                    class="legend-dot legend-d"></i>Kota D</span></div>
                    </div>
                </article>
            </div>
        </section>

        <section class="row g-4 mb-4">
            <div class="col-xl-7">
                <article class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <h2 class="card-title mb-1">Total penjualan per cabang</h2>
                            <p class="text-muted-green small mb-0">Perbandingan realisasi bulan berjalan</p>
                        </div><span class="badge-soft-success">Target tercapai 86%</span>
                    </div>
                    <div class="card-body pt-0">
                        <div id="branch-sales-chart" aria-label="Grafik penjualan per cabang"></div>
                    </div>
                </article>
            </div>
            <div class="col-xl-5">
                <article class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="card-title mb-1">Produk terlaris</h2>
                            <p class="text-muted-green small mb-0">Produk dengan kontribusi tertinggi</p>
                        </div><select class="form-select form-select-sm dashboard-select" id="product-branch"
                            aria-label="Pilih cabang produk">
                            <option value="all" selected>Semua</option>
                            @foreach ($branchSales as $branch)
                                <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="card-body pt-0">
                        <div id="top-products-list" class="top-products-list"></div>
                    </div>
                </article>
            </div>
        </section>

        <section class="row g-4 mb-4">
            <div class="col-12">
                <article class="card recommendation-card">
                    <div class="card-body p-4">
                        {{-- <div class="d-flex flex-column flex-lg-row justify-content-between gap-4">
                            <div class="recommendation-heading">
                                <div class="metric-icon metric-icon-orange mb-3"><i class="bi bi-lightbulb"></i></div>
                                <span class="eyebrow">REKOMENDASI TINDAKAN</span>
                                <h2 class="card-title mt-2 mb-1">Prioritaskan pemulihan Kota D</h2>
                                <p class="text-muted-green mb-0">Penjualan turun 8,2% dan conversion rate berada di bawah
                                    rata-rata jaringan.</p>
                            </div>
                            <div class="recommendation-grid">
                                <div class="recommendation-item"><span class="recommendation-number">01</span>
                                    <div><strong>Audit stok</strong>
                                        <p>Fokus pada produk yang kehilangan penjualan.</p>
                                    </div>
                                </div>
                                <div class="recommendation-item"><span class="recommendation-number">02</span>
                                    <div><strong>Aktifkan promo lokal</strong>
                                        <p>Gunakan bundling produk terlaris minggu ini.</p>
                                    </div>
                                </div>
                                <div class="recommendation-item"><span class="recommendation-number">03</span>
                                    <div><strong>Review target tim</strong>
                                        <p>Evaluasi pipeline dan follow-up pelanggan.</p>
                                    </div>
                                </div>
                            </div>
                        </div> --}}
                    </div>
                </article>
            </div>
        </section>
    </div>
@endsection
