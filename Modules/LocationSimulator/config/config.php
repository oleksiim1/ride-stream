<?php

return [
    /*
     | Seconds between two simulation ticks.
     */
    'interval_seconds' => (float) env('LOCATION_INTERVAL_SECONDS', 5),

    /*
     | Default number of simulated drivers.
     */
    'drivers' => 10,

    /*
     | Default driver speed range in km/h. Every driver gets its own random speed in this range.
     */
    'min_speed_kmh' => 30,
    'max_speed_kmh' => 60,

    /*
     | Prefix of generated driver ids (drv_001, drv_002, ...).
     */
    'driver_id_prefix' => 'drv_',

    /*
     | Per-tick speed variation, as a fraction of the driver's base speed.
     */
    'speed_variation' => 0.1,

    /*
     | Directory with the route JSON files (OSRM geometry, a list of [lng, lat] points).
     */
    'routes_path' => dirname(__DIR__).'/resources/routes',

    /*
     | Used only by simulator:fetch-routes. The simulator itself never calls OSRM.
     | Each route is a start and an end point as [lng, lat] (longitude first).
     */
    'osrm' => [
        'base_url' => 'https://router.project-osrm.org',
        'routes' => [
            'maidan-zhuliany' => [[30.5234, 50.4501], [30.4519, 50.4017]],
            'lavra-obolon' => [[30.5586, 50.4346], [30.4985, 50.5017]],
            'central-station-livoberezhna' => [[30.4894, 50.4413], [30.6628, 50.4520]],
            'podil-expocenter' => [[30.5160, 50.4655], [30.4778, 50.3820]],
            'lukianivska-darnytsia' => [[30.4817, 50.4623], [30.6310, 50.4560]],
        ],
    ],
];
