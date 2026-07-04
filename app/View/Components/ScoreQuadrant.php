<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ScoreQuadrant extends Component
{
    public $lines = [];

    /**
     * Create a new component instance.
     */
    public function __construct($play = null, $reverse = false)
    {
        if (is_null($play)) {
            $play = ['', 'black'];
        } else {
            // dump($play);
        }

        reset($play);
        $this->lines[] = new ScoreLine();
        for (reset($play); (current($play) !== false); next($play)) {
            $p = current($play);
            if ($p === true) {
                end($this->lines)->pinchRunner = true;
            } else {
                end($this->lines)->play = $p;
                if (preg_match('/^\(.*\)$/', $p)) {
                    end($this->lines)->circled = true;
                    end($this->lines)->play = preg_replace('/^\((.*)\)$/', '$1', $p);
                }
                end($this->lines)->colour = next($play) ?? 'black';
                end($this->lines)->padding = \strlen($p) > 2 ? '3px' : '1px';
                end($this->lines)->margin = \strlen($p) > 2 ? '0px' : '0px';
                $this->lines[] = new ScoreLine();
            }
        }
        if (empty(end($this->lines)->play) && !end($this->lines)->pinchRunner) {
            array_pop($this->lines);
        }
        if ($reverse) {
            $this->lines = array_reverse($this->lines);
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.score-quadrant');
    }
}

class ScoreLine {
    public $play = '';
    public $colour = '';
    public $circled = false;
    public $pinchRunner = false;
    public $padding = '1px';
    public $margin = '0px';
}