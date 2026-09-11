<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerSpendingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_the_highest_spending_customer_for_each_branch(): void
    {
        $now = now();
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Minuman',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'code' => 'MIN-001',
            'name' => 'Teh',
            'unit' => 'botol',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $branchId = DB::table('branches')->insertGetId([
            'name' => 'Kota A',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('branches')->insert([
            'name' => 'Kota B',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $topCustomerId = DB::table('customers')->insertGetId([
            'name' => 'Customer Teratas',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $otherCustomerId = DB::table('customers')->insertGetId([
            'name' => 'Customer Lain',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $thirdCustomerId = DB::table('customers')->insertGetId([
            'name' => 'Customer Ketiga',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $fourthCustomerId = DB::table('customers')->insertGetId([
            'name' => 'Customer Keempat',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $topTransactionId = DB::table('transactions')->insertGetId([
            'branch_id' => $branchId,
            'customer_id' => $topCustomerId,
            'bill_no' => 'INV-001',
            'transaction_date' => '2026-09-11',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $otherTransactionId = DB::table('transactions')->insertGetId([
            'branch_id' => $branchId,
            'customer_id' => $otherCustomerId,
            'bill_no' => 'INV-002',
            'transaction_date' => '2026-09-11',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $thirdTransactionId = DB::table('transactions')->insertGetId([
            'branch_id' => $branchId,
            'customer_id' => $thirdCustomerId,
            'bill_no' => 'INV-003',
            'transaction_date' => '2026-09-11',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $fourthTransactionId = DB::table('transactions')->insertGetId([
            'branch_id' => $branchId,
            'customer_id' => $fourthCustomerId,
            'bill_no' => 'INV-004',
            'transaction_date' => '2026-09-11',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('transaction_details')->insert([
            [
                'transaction_id' => $topTransactionId,
                'product_id' => $productId,
                'qty' => 1,
                'unit_price' => 250000,
                'net_sales_amount' => 250000,
                'tax_amount' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'transaction_id' => $otherTransactionId,
                'product_id' => $productId,
                'qty' => 1,
                'unit_price' => 100000,
                'net_sales_amount' => 100000,
                'tax_amount' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'transaction_id' => $thirdTransactionId,
                'product_id' => $productId,
                'qty' => 1,
                'unit_price' => 75000,
                'net_sales_amount' => 75000,
                'tax_amount' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'transaction_id' => $fourthTransactionId,
                'product_id' => $productId,
                'qty' => 1,
                'unit_price' => 50000,
                'net_sales_amount' => 50000,
                'tax_amount' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $response = $this->get(route('customer-spending.index'));

        $response->assertOk()
            ->assertSee('Customer Teratas')
            ->assertSee('Rp 250.000')
            ->assertSee('Customer Ketiga')
            ->assertDontSee('Customer Keempat')
            ->assertSee('Kota B')
            ->assertSee('Belum ada transaksi');
    }
}
