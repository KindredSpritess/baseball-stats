<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ScoreQuadrant extends Component
{

    public $play = '';
    public $colour = '';

    /**
     * Create a new component instance.
     */
    public function __construct($play = null)
    {
        if (is_null($play)) {
            $play = ['', 'black'];
        }
        [$this->play, $this->colour] = $play;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.score-quadrant');
    }
}
