<?php

namespace App\Traits;

trait ParsesPlacesData
{
    /**
     * Extract city and region (administrative_area_level_1) from addressComponents.
     * Returns [city, region].
     */
    protected function extractCityRegion(array $addressComponents): array
    {
        $city   = null;
        $region = null;

        foreach ($addressComponents as $component) {
            $types = $component['types'] ?? [];

            if ($city === null && (in_array('locality', $types, true) || in_array('postal_town', $types, true))) {
                $city = $component['longText'] ?? $component['shortText'] ?? null;
            }

            if ($region === null && in_array('administrative_area_level_1', $types, true)) {
                $region = $component['longText'] ?? $component['shortText'] ?? null;
            }
        }

        return [$city, $region];
    }

    /**
     * Convert Google's regularOpeningHours.periods array into a flat 7-day array.
     */
    protected function extractOpeningHours(array $periods): array
    {
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $byDay = [];
        foreach ($periods as $period) {
            $dayIndex = $period['open']['day'] ?? null;
            if ($dayIndex === null) {
                continue;
            }

            $openHour  = $period['open']['hour']   ?? 0;
            $openMin   = $period['open']['minute']  ?? 0;
            $closeHour = $period['close']['hour']  ?? 0;
            $closeMin  = $period['close']['minute'] ?? 0;

            $byDay[$dayIndex] = [
                'open'   => sprintf('%02d:%02d', $openHour, $openMin),
                'close'  => sprintf('%02d:%02d', $closeHour, $closeMin),
                'closed' => false,
            ];
        }

        $result = [];
        foreach ($dayNames as $index => $name) {
            if (isset($byDay[$index])) {
                $result[] = array_merge(['day' => $name], $byDay[$index]);
            } else {
                $result[] = [
                    'day'    => $name,
                    'open'   => '09:00',
                    'close'  => '17:00',
                    'closed' => true,
                ];
            }
        }

        return $result;
    }
}
