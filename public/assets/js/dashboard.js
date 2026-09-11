document.addEventListener('DOMContentLoaded', () => {
    const dashboardData = window.dashboardData ?? {};
    const branchSalesData = dashboardData.branchSales ?? [];
    const branchNames = branchSalesData.map(branch => branch.name);
    const branchSales = branchSalesData.map(branch => branch.total_sales);
    const salesTrend = dashboardData.salesTrend ?? {};
    const topProductsByBranch = dashboardData.topProductsByBranch ?? {};
    const topProductsByBranchAndCategory = dashboardData.topProductsByBranchAndCategory ?? {};
    const categoriesByBranch = dashboardData.categoriesByBranch ?? {};
    const trendSeries = {
        monthly: {
            labels: (salesTrend.monthly ?? []).map(month => formatMonthLabel(month.month)),
            values: (salesTrend.monthly ?? []).map(month => month.total_sales),
            hasData: (salesTrend.monthly ?? []).some(month => month.total_sales > 0),
        },
        weekly: {
            labels: (salesTrend.weekly ?? []).map(week => `Minggu ${String(week.week).padStart(2, '0')} (${week.label})`),
            values: (salesTrend.weekly ?? []).map(week => week.total_sales),
            hasData: (salesTrend.weekly ?? []).some(week => week.total_sales > 0),
        },
    };
    // const palette = ['#B4F105', '#5A8F79', '#F97316', '#EF4444'];
    const palette = ['#829548', '#5A8F79', '#F97316', '#EF4444'];

    const chartDefaults = {
        chart: { toolbar: { show: false }, fontFamily: 'Plus Jakarta Sans, sans-serif' },
        dataLabels: { enabled: false },
        grid: { borderColor: '#E9EFEF', strokeDashArray: 4 },
        tooltip: { theme: 'dark' },
    };

    const branchSalesChart = document.querySelector('#branch-sales-chart');
    if (branchSalesChart && branchNames.length > 0) {
        new ApexCharts(branchSalesChart, {
            ...chartDefaults,
            chart: { ...chartDefaults.chart, type: 'bar', height: 300 },
            series: [{ name: 'Penjualan', data: branchSales }],
            colors: palette,
            plotOptions: { bar: { horizontal: true, borderRadius: 5, distributed: true, barHeight: '48%' } },
            xaxis: { categories: branchNames, labels: { formatter: value => formatCurrency(value) } },
            legend: { show: false },
            tooltip: {
                ...chartDefaults.tooltip,
                shared: false,
                intersect: true,
                y: { formatter: value => formatCurrency(value) },
            },
        }).render();
    }

    const trendChart = renderSalesTrend();
    renderBranchPerformance();
    renderTopProducts();
    updateCategoryOptions();
    renderCategoryTopProducts();

    document.querySelector('#trend-period')?.addEventListener('change', event => {
        const period = event.target.value;
        trendChart?.updateOptions({ xaxis: { categories: trendSeries[period].labels } });
        trendChart?.updateSeries([{ name: 'Total penjualan', data: chartValues(period) }]);
    });

    document.querySelector('#product-branch')?.addEventListener('change', event => {
        renderTopProducts(event.target.value);
    });

    document.querySelector('#category-product-branch')?.addEventListener('change', event => {
        updateCategoryOptions(event.target.value);
        renderCategoryTopProducts(event.target.value);
    });

    document.querySelector('#category-product-category')?.addEventListener('change', event => {
        const branch = document.querySelector('#category-product-branch')?.value ?? 'all';
        renderCategoryTopProducts(branch, event.target.value);
    });

    initializeDateRangeFilter();

    function renderSalesTrend() {
        const element = document.querySelector('#sales-trend-chart');
        if (!element) return;

        const chart = new ApexCharts(element, {
            ...chartDefaults,
            chart: { ...chartDefaults.chart, type: 'area', height: 300 },
            series: [{ name: 'Total penjualan', data: chartValues('monthly') }],
            colors: ['#072F1F'],
            stroke: { curve: 'smooth', width: 3 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.04, stops: [0, 90, 100] } },
            markers: { size: 0, hover: { size: 6 } },
            xaxis: { categories: trendSeries.monthly.labels },
            yaxis: { labels: { show: false } },
            tooltip: {
                ...chartDefaults.tooltip,
                shared: false,
                intersect: false,
                y: { formatter: value => formatCurrency(value) },
            },
            noData: { text: 'Tidak ada data pada rentang tanggal ini' },
        });

        chart.render();
        return chart;
    }

    function renderBranchPerformance() {
        const element = document.querySelector('#branch-performance-chart');
        if (!element || branchNames.length === 0) return;

        new ApexCharts(element, {
            ...chartDefaults,
            chart: { ...chartDefaults.chart, type: 'donut', height: 270 },
            series: branchSales,
            labels: branchNames,
            colors: palette,
            legend: { show: false },
            tooltip: { ...chartDefaults.tooltip, y: { formatter: value => formatCurrency(value) } },
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%',
                        labels: {
                            show: true,
                            value: { formatter: value => formatCurrency(value) },
                            total: {
                                show: true,
                                label: 'Total Penjualan',
                                formatter: () => formatCurrency(branchSales.reduce((total, value) => total + value, 0)),
                            },
                        },
                    },
                },
            },
        }).render();
    }

    function renderTopProducts(branch = 'all') {
        const element = document.querySelector('#top-products-list');
        if (!element) return;

        const products = topProductsByBranch[branch] ?? [];

        element.innerHTML = products.length > 0
            ? products.map((product, index) => `
            <div class="top-product-row">
                <span class="product-rank">0${index + 1}</span>
                <div class="product-info"><strong>${product.product_name}</strong><span>${formatQuantity(product.total_qty)} unit</span></div>
                <strong class="product-revenue">${formatCurrency(product.total_sales)}</strong>
            </div>`).join('')
            : '<p class="text-muted-green small mb-0">Belum ada data penjualan.</p>';
    }

    function renderCategoryTopProducts(branch = 'all', category = 'all') {
        const element = document.querySelector('#category-top-products-list');
        if (!element) return;

        const products = topProductsByBranchAndCategory[branch]?.[category] ?? [];

        element.innerHTML = products.length > 0
            ? products.map((product, index) => `
            <div class="top-product-row">
                <span class="product-rank">0${index + 1}</span>
                <div class="product-info"><strong>${product.product_name}</strong><span>${formatQuantity(product.total_qty)} unit</span></div>
                <strong class="product-revenue">${formatCurrency(product.total_sales)}</strong>
            </div>`).join('')
            : '<p class="text-muted-green small mb-0">Belum ada data penjualan untuk filter ini.</p>';
    }

    function updateCategoryOptions(branch = 'all') {
        const select = document.querySelector('#category-product-category');
        if (!select) return;

        const categories = categoriesByBranch[branch] ?? [];
        select.innerHTML = '<option value="all" selected>Semua kategori</option>'
            + categories.map(category => `<option value="${category.id}">${category.name}</option>`).join('');
        select.disabled = categories.length === 0;
    }

    function initializeDateRangeFilter() {
        const trigger = document.querySelector('#date-picker-trigger');
        const input = document.querySelector('#dashboard-date-range');
        if (!trigger || !input || typeof flatpickr === 'undefined') return;

        const picker = flatpickr(input, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            defaultDate: [dashboardData.startDate, dashboardData.endDate],
            onClose: selectedDates => {
                if (selectedDates.length !== 2) return;

                const params = new URLSearchParams(window.location.search);
                params.set('start_date', formatDate(selectedDates[0]));
                params.set('end_date', formatDate(selectedDates[1]));
                window.location.search = params.toString();
            },
        });

        trigger.addEventListener('click', () => picker.open());
    }

    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatMonthLabel(month) {
        return new Intl.DateTimeFormat('id-ID', {
            month: 'short',
            year: 'numeric',
        }).format(new Date(`${month}-01T00:00:00`));
    }

    function chartValues(period) {
        return trendSeries[period].hasData ? trendSeries[period].values : [];
    }

    function formatCurrency(value) {
        const amount = Number(value);
        return Number.isFinite(amount)
            ? `Rp ${new Intl.NumberFormat('id-ID').format(amount)}`
            : 'Rp 0';
    }

    function formatQuantity(value) {
        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(value);
    }
});
