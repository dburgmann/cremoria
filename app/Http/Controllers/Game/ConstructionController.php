<?php

namespace App\Http\Controllers\Game;

use App\Http\Requests\ProduceRequest;
use App\Services\Game\ConstructionService;
use App\Services\Game\GameDataRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConstructionController extends GameController
{
    public function __construct(private GameDataRepository $gameData)
    {
        parent::__construct();
    }

    public function index(): View
    {
        $service = new ConstructionService($this->gameData, $this->user, $this->activeTown);

        return view('game.construction', [
            'allowed'      => $service->getAllowed(),
            'town'         => $this->activeTown,
            'currentBuilding' => $this->activeTown->current_building
                ? $this->gameData->building($this->activeTown->current_building)
                : null,
        ]);
    }

    public function produce(ProduceRequest $request): RedirectResponse
    {
        $service = new ConstructionService($this->gameData, $this->user, $this->activeTown);
        $error   = $service->produce((int) $request->validated('item_id'));

        if ($error !== null) {
            return redirect()->route('game.construction')
                ->withErrors(['construction' => __("game.errors.{$error}")]);
        }

        return redirect()->route('game.construction')
            ->with('success', __('game.construction_started'));
    }
}
