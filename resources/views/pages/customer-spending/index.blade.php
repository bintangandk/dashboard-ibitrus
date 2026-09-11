@extends('layouts.app')

@section('page-title', 'Customer dengan spending tertinggi')
@section('page-subtitle', 'Customer dengan total pembelian terbesar dari masing-masing cabang.')

@section('content')
    <div class="col-12">
        <section class="row g-4 mb-4" aria-label="Ringkasan customer">
            <div class="col-sm-6 col-xl-4">
                <article class="card metric-card h-100">
                    <div class="metric-icon metric-icon-dark"><i class="bi bi-people"></i></div>
                    <span class="stat-label">Customer aktif</span>
                    <strong class="metric-value">{{ number_format($customerCount, 0, ',', '.') }}</strong>
                    <span class="text-muted-green small">Customer dengan riwayat transaksi</span>
                </article>
            </div>
            <div class="col-sm-6 col-xl-4">
                <article class="card metric-card h-100">
                    <div class="metric-icon metric-icon-orange"><i class="bi bi-wallet2"></i></div>
                    <span class="stat-label">Total spending</span>
                    <strong class="metric-value">Rp {{ number_format($totalSpending, 0, ',', '.') }}</strong>
                    <span class="text-muted-green small">Akumulasi seluruh transaksi</span>
                </article>
            </div>
            <div class="col-sm-6 col-xl-4">
                <article class="card metric-card h-100">
                    <div class="metric-icon metric-icon-lime"><i class="bi bi-shop"></i></div>
                    <span class="stat-label">Cabang dipantau</span>
                    <strong class="metric-value">{{ number_format($branches->count(), 0, ',', '.') }}</strong>
                    <span class="text-muted-green small">Customer teratas per cabang</span>
                </article>
            </div>
        </section>

        <section class="customer-branch-grid" aria-label="Customer dengan spending tertinggi per cabang">
            @forelse ($branches as $branch)
                <article class="card customer-branch-card h-100">
                    <div class="card-header">
                        <div>
                            <span class="customer-branch-label">Cabang</span>
                            <h2 class="card-title">{{ $branch['name'] }}</h2>
                        </div>
                        <span class="customer-branch-icon"><i class="bi bi-trophy"></i></span>
                    </div>

                    @if ($branch['top_customers']->isNotEmpty())
                        <div class="customer-ranking-list">
                            @foreach ($branch['top_customers'] as $rank => $customer)
                                <div class="customer-ranking-row">
                                    <span class="customer-rank">{{ $rank + 1 }}</span>
                                    <div class="customer-avatar">{{ strtoupper(substr($customer['name'], 0, 1)) }}</div>
                                    <div class="customer-highlight-info">
                                        <strong>{{ $customer['name'] }}</strong>
                                        <span>{{ number_format($customer['transaction_count'], 0, ',', '.') }}
                                            transaksi</span>
                                    </div>
                                    <strong
                                        class="customer-ranking-spending">{{ 'Rp ' . number_format($customer['total_spending'], 0, ',', '.') }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="customer-empty-state">
                            <i class="bi bi-person-x"></i>
                            <span>Belum ada transaksi</span>
                        </div>
                    @endif
                </article>
            @empty
                <div class="card customer-empty-state">
                    <i class="bi bi-shop"></i>
                    <span>Belum ada data cabang.</span>
                </div>
            @endforelse
        </section>
    </div>
@endsection
