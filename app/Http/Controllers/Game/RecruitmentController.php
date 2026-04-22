<?php

namespace App\Http\Controllers\Game;

use App\Services\Game\GameDataRepository;
use App\Services\Game\RecruitmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruitmentController extends GameController
{
    public function __construct(private GameDataRepository $gameData)
    {
        parent::__construct();
    }

    public function index(): View
    {
        $service    = new RecruitmentService($this->gameData, $this->user, $this->activeTown);
        $production = $service->getProduction();

        return view('game.recruitment', [
            'allowed'    => $service->getAllowed(),
            'production' => $production,
            'town'       => $this->activeTown,
        ]);
    }

    public function produce(Request $request): RedirectResponse
    {
        $units = $request->input('units', []);

        // Build orders array: unit_id => quantity, filter out zero/empty entries
        $orders = [];
        foreach ($units as $unitId => $quantity) {
            $qty = (int) $quantity;
            if ($qty > 0) {
                $orders[(int) $unitId] = $qty;
            }
        }

        if (empty($orders)) {
            return redirect()->route('game.recruitment')
                ->withErrors(['recruitment' => __('game.errors.prodNoUnitsSelected')]);
        }

        $service = new RecruitmentService($this->gameData, $this->user, $this->activeTown);
        $error   = $service->produce($orders);

        if ($error !== null) {
            return redirect()->route('game.recruitment')
                ->withErrors(['recruitment' => __("game.errors.{$error}")]);
        }

        return redirect()->route('game.recruitment')
            ->with('success', __('game.recruitment_started'));
    }
}
