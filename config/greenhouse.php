<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Topologi Greenhouse
    |--------------------------------------------------------------------------
    |
    | Dua greenhouse dengan total lima node Arduino (LoRa). Setiap node
    | mengirimkan sepuluh parameter sensor yang sama.
    |
    */

    'greenhouses' => [
        'GH1' => [
            'name' => 'Greenhouse 1',
            'location' => 'Blok A',
            'nodes' => ['N1', 'N2', 'N3'],
        ],
        'GH2' => [
            'name' => 'Greenhouse 2',
            'location' => 'Blok B',
            'nodes' => ['N1', 'N2'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Parameter Sensor
    |--------------------------------------------------------------------------
    |
    | NPK 8-in-1 (media tanam) + SHT31 (udara). Urutan array ini dipakai
    | saat melakukan ingest data sensor ke tabel sensor_logs.
    |
    */

    'parameters' => [
        'n' => [
            'label' => 'Nitrogen',
            'unit' => 'mg/kg',
            'group' => 'soil',
            'decimals' => 1,
            'icon' => 'N',
        ],
        'p' => [
            'label' => 'Fosfor',
            'unit' => 'mg/kg',
            'group' => 'soil',
            'decimals' => 1,
            'icon' => 'P',
        ],
        'k' => [
            'label' => 'Kalium',
            'unit' => 'mg/kg',
            'group' => 'soil',
            'decimals' => 1,
            'icon' => 'K',
        ],
        'soil_moisture' => [
            'label' => 'Kelembapan Tanah',
            'unit' => '%',
            'group' => 'soil',
            'decimals' => 1,
            'icon' => 'SM',
        ],
        'soil_temp' => [
            'label' => 'Suhu Tanah',
            'unit' => '°C',
            'group' => 'soil',
            'decimals' => 1,
            'icon' => 'ST',
        ],
        'ph' => [
            'label' => 'pH Tanah',
            'unit' => 'pH',
            'group' => 'soil',
            'decimals' => 2,
            'icon' => 'pH',
        ],
        'ec' => [
            'label' => 'EC Tanah',
            'unit' => 'uS/cm',
            'group' => 'soil',
            'decimals' => 0,
            'icon' => 'EC',
        ],
        'salinity' => [
            'label' => 'Salinitas',
            'unit' => 'mg/L',
            'group' => 'soil',
            'decimals' => 0,
            'icon' => 'SL',
        ],
        'air_temp' => [
            'label' => 'Suhu Udara',
            'unit' => '°C',
            'group' => 'air',
            'decimals' => 1,
            'icon' => 'AT',
        ],
        'air_humidity' => [
            'label' => 'Kelembapan Udara',
            'unit' => '%',
            'group' => 'air',
            'decimals' => 1,
            'icon' => 'RH',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ambang Status Online
    |--------------------------------------------------------------------------
    |
    | Node dianggap online jika mengirim data dalam N menit terakhir.
    |
    */

    'online_threshold_minutes' => 15,

];
