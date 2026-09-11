<div class="grow overflow-y-auto">
    <!-- Group: Menu -->
    <div class="sidebar-menu-section">
        <div class="sidebar-menu-title">Menu</div>
        <ul class="sidebar-menu-list">
            <li class="sidebar-menu-item">
                <a href="{{ route('dashboard') }}"
                    class="sidebar-menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" id="menu-overview"
                    title="Dashboard">
                    <i class="bi bi-grid-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="{{ route('customer-spending.index') }}"
                    class="sidebar-menu-link {{ request()->routeIs('customer-spending.*') ? 'active' : '' }}"
                    id="menu-customer-spending" title="Customer teratas">
                    <i class="bi bi-person-lines-fill"></i>
                    <span>Customer teratas</span>
                </a>
            </li>
        </ul>
    </div>
</div>
