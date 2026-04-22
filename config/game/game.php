<?php

return [
    /*
     * Starting resources for new players
     */
    'init_gold'  => 300,
    'init_wood'  => 300,
    'init_stone' => 300,
    'init_iron'  => 300,

    /*
     * Iron smelting costs: iron is derived from stone and wood.
     * These are the multipliers used to reduce wood/stone income per iron income.
     */
    'iron_to_stone' => 2,
    'iron_to_wood'  => 1,

    /*
     * Map size (quadratic: 0..mapSize-1 on each axis)
     */
    'map_size' => 100,

    /*
     * Number of rounds each battle consists of
     */
    'battle_rounds' => 3,

    /*
     * All movement action types (int => name)
     */
    'movement_types' => [
        0 => 'Transfer',
        1 => 'Transport',
        2 => 'Attack',
        3 => 'Espionage',
        4 => 'Return',
    ],

    /*
     * Movement types the player may choose in the UI (excludes Return)
     */
    'movement_types_view' => [
        0 => 'Transfer',
        1 => 'Transport',
        2 => 'Attack',
        3 => 'Espionage',
    ],

    /*
     * Divider used when calculating building time from resource cost
     */
    'building_time_divider' => 1000,
];
