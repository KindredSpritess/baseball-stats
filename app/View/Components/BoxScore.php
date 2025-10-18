<?php

namespace App\View\Components;

use App\Helpers\StatsHelper;
use App\Models\Game;
use App\Models\Team;
use Illuminate\View\Component;

class BoxScore extends Component
{
    public StatsHelper $totals;

    public Team $team;
    public array $lineup;
    public array $pitchers;
    public array $defenders = [];
    public array $stats = [];
    public int $atbat;
    public bool $defending;

    const POSITIONS = [
        1 => 'P',
        2 => 'C',
        3 => '1B',
        4 => '2B',
        5 => '3B',
        6 => 'SS',
        7 => 'LF',
        8 => 'CF',
        9 => 'RF',
        'DH' => 'DH',
        'EH' => 'EH',
    ];

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(public Game $game, bool $home)
    {
        $this->team = $home ? $game->home_team : $game->away_team;
        $this->lineup = $game->lineup[$home ? 1 : 0];
        $this->pitchers = $game->pitchers[$home ? 1 : 0];
        $this->atbat = $game->atBat[$home ? 1 : 0];
        $this->defending = boolval($home ? !$game->half : $game->half);
        $defense = collect($game->defense[$home ? 1 : 0])->map(function($p) {
            return $p ? $p->id : null;
        })->flip();

        $this->totals = new StatsHelper([]);
        foreach ($this->lineup as $spot) {
            foreach ($spot as $player) {
                if (!$player) continue;
                $this->stats[$player->id] = new StatsHelper($player->stats);
                $this->stats[$player->id]->derive();
                $this->totals->merge($player->stats);
                if (isset($defense[$player->id])) {
                    $this->defenders[$player->id] = self::POSITIONS[$defense[$player->id]];
                }
            }
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
