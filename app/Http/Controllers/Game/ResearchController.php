<?php

namespace App\Http\Controllers\Game;

use App\Http\Requests\ProduceRequest;
use App\Services\Game\GameDataRepository;
use App\Services\Game\ResearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ResearchController extends GameController
{
    public function __construct(private GameDataRepository $gameData)
    {
        parent::__construct();
    }

    public function index(): View
    {
        $service = new ResearchService($this->gameData, $this->user, $this->activeTown);

        return view('game.research', [
            'allowed'          => $service->getAllowed(),
            'labs'             => $service->getLabs(),
            'currentResearch'  => $this->user->current_research
                ? $this->gameData->tech($this->user->current_research)
                : null,
            'town'             => $this->activeTown,
        ]);
    }

    public function produce(ProduceRequest $request): RedirectResponse
    {
        $service = new ResearchService($this->gameData, $this->user, $this->activeTown);
        $error   = $service->produce((int) $request->validated('item_id'));

        if ($error !== null) {
            return redirect()->route('game.research')
                ->withErrors(['research' => __("game.errors.{$error}")]);
        }

        return redirect()->route('game.research')
            ->with('success', __('game.research_started'));
    }
}
