<?php

namespace App\View\Components;

use App\Helpers\StatsHelper;
use App\Models\Team;
use Illuminate\View\Component;

class BoxScore extends Component
{
    public StatsHelper $totals;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(public Team $team, public array $lineup)
    {
        $this->totals = new StatsHelper([]);
        foreach ($lineup as $player) {
            if (!$player) continue;
            $this->totals->merge($player->stats);
        }
        $this->totals->derive();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.box-score');
    }
}
