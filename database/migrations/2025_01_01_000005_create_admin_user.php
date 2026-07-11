<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    private string $email = 'admin@jetimob.com';

    public function up(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => $this->email],
            [
                'name' => 'Admin',
                'password' => Hash::make('123123123'),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('users')->where('email', $this->email)->delete();
    }
};
