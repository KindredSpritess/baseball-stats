<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ScoreQuadrant extends Component
{

    public $play = '';
    public $colour = '';
    public $circled = false;
    public $pinchRunner = false;

    /**
     * Create a new component instance.
     */
    public function __construct($play = null)
    {
        if (is_null($play)) {
            $play = ['', 'black'];
        }
        [$this->play, $this->colour] = $play;
        if (preg_match('/^\(.*\)$/', $this->play)) {
            $this->circled = true;
            $this->play = preg_replace('/^\((.*)\)$/', '$1', $this->play);
        }
        $this->pinchRunner = $play[2] ?? false;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.score-quadrant');
    }
}
