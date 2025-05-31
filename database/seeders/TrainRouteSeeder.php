<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TrainRoute;

class TrainRouteSeeder extends Seeder
{
    public function run()
    {
        $routes = [
            [
                'route_id' => 'train-daugavpils',
                'route_short_name' => 'Daugavpils-Rīga',
                'route_long_name' => 'Daugavpils - Rīga',
                'route_type' => 2, // Train
                'from_station' => 'Daugavpils',
            ],
            [
                'route_id' => 'train-ogre',
                'route_short_name' => 'Ogre-Rīga',
                'route_long_name' => 'Ogre - Rīga',
                'route_type' => 2,
                'from_station' => 'Ogre',
            ],
            [
                'route_id' => 'train-jelgava',
                'route_short_name' => 'Jelgava-Rīga',
                'route_long_name' => 'Jelgava - Rīga',
                'route_type' => 2,
                'from_station' => 'Jelgava',
            ],
            [
                'route_id' => 'train-tukums',
                'route_short_name' => 'Tukums I-Rīga',
                'route_long_name' => 'Tukums I - Rīga',
                'route_type' => 2,
                'from_station' => 'Tukums I',
            ],
            [
                'route_id' => 'train-krustpils',
                'route_short_name' => 'Krustpils-Rīga',
                'route_long_name' => 'Krustpils - Rīga',
                'route_type' => 2,
                'from_station' => 'Krustpils',
            ],
            [
                'route_id' => 'train-sigulda',
                'route_short_name' => 'Sigulda-Rīga',
                'route_long_name' => 'Sigulda - Rīga',
                'route_type' => 2,
                'from_station' => 'Sigulda',
            ],
            [
                'route_id' => 'train-aizkraukle',
                'route_short_name' => 'Aizkraukle-Rīga',
                'route_long_name' => 'Aizkraukle - Rīga',
                'route_type' => 2,
                'from_station' => 'Aizkraukle',
            ],
            [
                'route_id' => 'train-salaspils',
                'route_short_name' => 'Salaspils-Rīga',
                'route_long_name' => 'Salaspils - Rīga',
                'route_type' => 2,
                'from_station' => 'Salaspils',
            ],
            [
                'route_id' => 'train-cesis',
                'route_short_name' => 'Cēsis-Rīga',
                'route_long_name' => 'Cēsis - Rīga',
                'route_type' => 2,
                'from_station' => 'Cēsis',
            ],
            [
                'route_id' => 'train-sloka',
                'route_short_name' => 'Sloka-Rīga',
                'route_long_name' => 'Sloka - Rīga',
                'route_type' => 2,
                'from_station' => 'Sloka',
            ],
        ];

        foreach ($routes as $route) {
            TrainRoute::create($route);
        }
    }
}
