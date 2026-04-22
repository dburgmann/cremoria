<?php

/*
 * IDs of buildings / units / techs that have special gameplay effects.
 */
return [
    /*
     * Buildings with tick-relevant side effects
     */
    'tick_relevant_buildings' => [1, 2, 3, 4, 5, 10],

    'gold_building'    => 1,   // gold production
    'wood_building'    => 2,   // wood production
    'stone_building'   => 3,   // stone production
    'iron_building'    => 4,   // iron production
    'unit_building'    => 5,   // barracks / unit cap
    'research_building' => 6,  // laboratory
    'mov_recog_building' => 8, // movement recognition
    'mov_cap_building'  => 10, // movement capacity
    'spio_def_building' => 9,  // espionage defence

    /*
     * Units
     */
    'spio_unit' => 9, // spy unit

    /*
     * Techs
     */
    'spio_off_tech' => 25, // espionage power tech (repeatable)
];
