<?php

namespace Database\Seeders;

use App\Models\MarketingSite;
use Illuminate\Database\Seeder;

class MarketingSiteSeeder extends Seeder
{
    public function run(): void
    {
        $csv  = database_path('data/barbers.csv');
        $file = fopen($csv, 'r');

        // Read header row
        $headers = fgetcsv($file);

        $inserted = 0;
        $skipped  = 0;

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($headers, $row);

            $placesId = trim($data['place_id'] ?? '');
            if (! $placesId) {
                $skipped++;
                continue;
            }

            MarketingSite::firstOrCreate(
                ['places_id' => $placesId],
                [
                    'business_name'            => trim($data['name'] ?? '') ?: null,
                    'business_type'            => trim($data['type'] ?? '') ?: null,
                    'phone'                    => trim($data['phone'] ?? '') ?: null,
                    'website'                  => trim($data['website'] ?? '') ?: null,
                    'formatted_address'        => trim($data['address'] ?? '') ?: null,
                    'street'                   => trim($data['street'] ?? '') ?: null,
                    'city'                     => trim($data['city'] ?? '') ?: null,
                    'county'                   => trim($data['county'] ?? '') ?: null,
                    'postal_code'              => trim($data['postal_code'] ?? '') ?: null,
                    'country'                  => trim($data['country'] ?? '') ?: null,
                    'booking_appointment_link' => trim($data['booking_appointment_link'] ?? '') ?: null,
                    'status'                   => 'pending',
                ]
            );

            $inserted++;
        }

        fclose($file);

        $this->command->info("MarketingSiteSeeder: {$inserted} rows processed, {$skipped} skipped (no place_id).");
    }
}
