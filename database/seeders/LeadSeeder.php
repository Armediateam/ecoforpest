<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');
        // Ambil jumlah data dari CLI argument, default 10
        $total = (int) (request()->input('total') ?? 10);

        // Ambil id relasi yang sudah ada
        $statusIds = DB::table('statuses')->pluck('id')->toArray();
        $sourceIds = DB::table('sources')->pluck('id')->toArray();
        $userIds = DB::table('users')->pluck('id')->toArray();
        $countryIds = DB::table('countries')->pluck('id')->toArray();

        $leads = [];
        for ($i = 0; $i < $total; $i++) {
            $leads[] = [
                'status_id' => $faker->randomElement($statusIds),
                'source_id' => $faker->randomElement($sourceIds),
                'assigned_id' => $faker->randomElement($userIds),
                'name' => $faker->company,
                'address' => $faker->address,
                'position' => $faker->jobTitle,
                'phone' => $faker->phoneNumber,
                'email' => $faker->unique()->companyEmail,
                'city' => $faker->city,
                'state' => $faker->state,
                'country_id' => $faker->randomElement($countryIds),
                'zip_code' => $faker->postcode,
                'default_language' => 'Indonesia',
                'lead_value' => $faker->numberBetween(1000000, 20000000),
                'website' => $faker->url,
                'company' => $faker->company,
                'description' => $faker->sentence,
                'date_contacted' => $faker->dateTimeBetween('-1 month', 'now'),
                'is_public' => $faker->boolean,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Lead::insert($leads);
    }
} 