<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Rajesh Sharma',
                'company_name' => 'TechSource Nepal',
                'email' => 'contact@techsource.com',
                'phone' => '9801000001',
                'address' => 'New Road, Kathmandu',
            ],
            [
                'name' => 'Suman Thapa',
                'company_name' => 'Himalayan Electronics',
                'email' => 'sales@himalayanelectronics.com',
                'phone' => '9801000002',
                'address' => 'Putalisadak, Kathmandu',
            ],
            [
                'name' => 'Amit Gupta',
                'company_name' => 'Global IT Suppliers',
                'email' => 'info@globalitsuppliers.com',
                'phone' => '9801000003',
                'address' => 'Lalitpur, Nepal',
            ],
            [
                'name' => 'Ramesh Karki',
                'company_name' => 'SmartTech Distribution',
                'email' => 'hello@smarttech.com',
                'phone' => '9801000004',
                'address' => 'Baneshwor, Kathmandu',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['email' => $supplier['email']],
                $supplier
            );
        }
    }
}
