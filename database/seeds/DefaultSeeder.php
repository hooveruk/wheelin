<?php

use Illuminate\Database\Seeder;
use App\Models\Employee;

class DefaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Employee::create(['name' => 'Peter', 'active' => true]);
        Employee::create(['name' => 'Marina', 'active' => true]);
        Employee::create(['name' => 'Mark', 'active' => true]);
        Employee::create(['name' => 'Valdemort', 'active' => true]);
        Employee::create(['name' => 'Sybill', 'active' => true]);
        Employee::create(['name' => 'Augustus', 'active' => true]);
        Employee::create(['name' => 'Andree', 'active' => true]);
        Employee::create(['name' => 'Maria', 'active' => true]);
        Employee::create(['name' => 'Abigail', 'active' => true]);
        Employee::create(['name' => 'Ivan', 'active' => true]);
    }
}
