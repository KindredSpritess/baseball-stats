<?php

namespace App\View\Components;

use App\Helpers\StatsHelper;
use App\Models\Game;
use Illuminate\View\Component;

class LineScore extends Component
{
    public StatsHelper $away;
    public StatsHelper $home;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(public Game $game)
    {
        $this->away = new StatsHelper([]);
        foreach ($this->game->lineup[0] as $spots) {
            foreach ($spots as $player) {
                if (!$player) continue;
                $this->away->merge($player->stats);
            }
        }
        $this->away->derive();
        $this->home = new StatsHelper([]);
        foreach ($this->game->lineup[1] as $spots) {
            foreach ($spots as $player) {
                if (!$player) continue;
                $this->home->merge($player->stats);
            }
        }
        $this->home->derive();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.line-score');
    }
}
