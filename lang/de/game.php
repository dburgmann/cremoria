<?php

return [
    /*
     * Success messages
     */
    'construction_started' => 'Gebäude wird gebaut.',
    'recruitment_started'  => 'Einheiten werden rekrutiert.',
    'research_started'     => 'Forschung gestartet.',

    /*
     * Error messages for production/construction/research/recruitment actions
     */
    'errors' => [
        'prodInvalidItem'       => 'Ungültiges Element ausgewählt.',
        'prodForbiddenItem'     => 'Dieses Element ist nicht freigeschaltet.',
        'prodAlreadyBuilding'   => 'Es wird bereits ein Gebäude gebaut.',
        'prodNotEnoughRes'      => 'Nicht genug Ressourcen.',
        'prodNoLab'             => 'Kein Forschungslabor vorhanden.',
        'prodAlreadyResearching'=> 'Es wird bereits etwas erforscht.',
        'prodNotEnoughCap'      => 'Einheitenkapazität erschöpft.',
        'prodNoUnitsSelected'   => 'Keine Einheiten ausgewählt.',

        'movNoUnits'            => 'Keine Einheiten ausgewählt.',
        'movNotEnoughUnits'     => 'Nicht genug Einheiten vorhanden.',
        'movInvalidCoord'       => 'Ungültige Koordinaten oder kein Zielort vorhanden.',
        'movNotEnoughCap'       => 'Keine freie Bewegungskapazität.',
        'movNotEnoughCapacity'  => 'Frachtkapazität der Armee überschritten.',
        'movNotEnoughRes'       => 'Nicht genug Ressourcen für die Fracht.',
        'movInvalidCargoInput'  => 'Ungültige Frachtmenge angegeben.',
        'movInvalidAction'      => 'Ungültiger Bewegungstyp.',
    ],

    /*
     * Battle report labels
     */
    'battle_report'   => 'Kampfbericht',
    'attacker'        => 'Angreifer',
    'defender'        => 'Verteidiger',
    'draw'            => 'Unentschieden',
    'winner'          => 'Gewinner',
    'casualties'      => 'Verluste',
    'booty'           => 'Beute',
    'no_casualties'   => 'Keine Verluste',

    /*
     * Email
     */
    'reg_email_subject' => 'Cremoria – Konto aktivieren',
    'reg_email_message' => 'Bitte aktiviere dein Cremoria-Konto unter: :link',
];
