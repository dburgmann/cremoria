<?php

namespace App\Services\Game;

use App\Models\Message;
use App\Models\User;

/**
 * Generates and sends battle reports as in-game messages to both participants.
 */
class BattleReportService
{
    private BattleService $battle;
    private AttackerArmy $attacker;
    private DefenderArmy $defender;

    public function __construct(BattleService $battle, AttackerArmy $attacker, DefenderArmy $defender)
    {
        $this->battle   = $battle;
        $this->attacker = $attacker;
        $this->defender = $defender;
    }

    /**
     * Build and send the battle report to both the attacker and the defender.
     *
     * @param iterable<object> $attUnits  original attacker units before battle
     * @param iterable<object> $defUnits  original defender units before battle
     */
    public function sendReport(iterable $attUnits, iterable $defUnits): void
    {
        $attUser = User::find($this->attacker->getOwner());
        $defUser = User::find($this->defender->getOwner());

        $attName = $attUser?->username ?? 'Unbekannt';
        $defName = $defUser?->username ?? 'Unbekannt';

        $title  = "Kampfbericht {$attName} VS {$defName}";
        $report = $this->buildReport($attName, $defName, $attUnits, $defUnits);

        Message::create([
            'sender'   => 0,
            'receiver' => $this->attacker->getOwner(),
            'title'    => $title,
            'text'     => $report,
        ]);

        Message::create([
            'sender'   => 0,
            'receiver' => $this->defender->getOwner(),
            'title'    => $title,
            'text'     => $report,
        ]);
    }

    /**
     * Assembles the full report HTML string.
     */
    private function buildReport(
        string $attName,
        string $defName,
        iterable $attUnits,
        iterable $defUnits
    ): string {
        $booty         = $this->battle->getBooty();
        $winner        = $this->battle->getWinner();
        $attCasualties = $this->attacker->getCasualties();
        $defCasualties = $this->defender->getCasualties();

        $winnerLabel = match ($winner) {
            BattleService::ATTACKER_WINS => 'Angreifer',
            BattleService::DEFENDER_WINS => 'Verteidiger',
            default                      => 'Unentschieden',
        };

        $attStartTable = $this->buildStartTable($attUnits);
        $defStartTable = $this->buildStartTable($defUnits);
        $attCasTable   = $this->buildCasualtyTable($attCasualties);
        $defCasTable   = $this->buildCasualtyTable($defCasualties);

        return "<h2>Kampfbericht</h2>"
            . "<p>{$attName} VS {$defName}</p>"
            . "<p>{$attName}s Armee:</p>{$attStartTable}"
            . "<p>{$defName}s Armee:</p>{$defStartTable}"
            . "<p>Verluste:</p>"
            . "<p>Angreifer:</p>{$attCasTable}"
            . "<p>Verteidiger:</p>{$defCasTable}"
            . "<p>Gewinner: {$winnerLabel}</p>"
            . "<p>Beute: Gold: {$booty['gold']}, "
            . "Holz: {$booty['wood']}, "
            . "Stein: {$booty['stone']}, "
            . "Eisen: {$booty['iron']}</p>";
    }

    /**
     * Renders a simple HTML table for initial army composition.
     */
    private function buildStartTable(iterable $units): string
    {
        $th = $td = '';
        foreach ($units as $unit) {
            $th .= "<th>" . e($unit->name ?? "Einheit {$unit->id}") . "</th>";
            $td .= "<td>{$unit->quantity}</td>";
        }
        return "<table class=\"reportTable\"><tr>{$th}</tr><tr>{$td}</tr></table>";
    }

    /**
     * Renders a casualty table for post-battle losses.
     *
     * @param array<int,object> $casualties  keyed by unit type ID
     */
    private function buildCasualtyTable(array $casualties): string
    {
        if (empty($casualties)) {
            return '<p><em>Keine Verluste</em></p>';
        }
        $th = $td = '';
        foreach ($casualties as $cas) {
            $th .= "<th>" . e($cas->name) . "</th>";
            $td .= "<td>{$cas->quantity}</td>";
        }
        return "<table class=\"reportTable\"><tr>{$th}</tr><tr>{$td}</tr></table>";
    }
}
