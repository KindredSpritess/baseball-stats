<?php

namespace App\Helpers;

class StatsHelper {

    public function __construct(private array $stats, private ?int $position = null) {
        if (!is_null($position)) {
            $stats = [];
            foreach ($this->stats as $k => $v) {
                if (str_ends_with($k, ".$position")) {
                    $stats[substr($k, 0, -2)] = $v;
                }
                if ($position == 2 && in_array($k, ['CSB', 'CCS', 'PB'])) {
                    $stats[$k] = $v;
                }
            }
            $this->stats = $stats;
        }
    }

    public function merge(StatsHelper|array|null $other): StatsHelper {
        if (is_null($other)) return $this;
        if ($other instanceof self) return $this->merge($other->stats);
        foreach ($other as $k => $v) {
            $this->stats[$k] = ($this->stats[$k] ?? 0) + $v;
        }
        return $this;
    }

    public function stat($stat) {
        return $this->stats[$stat] ?? 0;
    }

    public function __get(string $stat) {
        return $this->stat($stat);
    }

    public function toArray(): array {
        return $this->stats;
    }

    /**
     * @return StatsHelper[]
     */
    public function positional(): array {
        $out = [];
        for ($i = 1; $i <= 9; $i++) {
            $stats = new StatsHelper($this->stats, $i);
            $stats->stats['Position'] = $i;
            if ($stats->G) $out[] = $stats->derive();
        }
        return $out;
    }

    public function derive(): StatsHelper {
        // H
        $this->stats['H'] = $this->stat('1') + $this->stat('2') + $this->stat('3') + $this->stat('4');

        // AVG
        if ($this->AB) {
            $this->stats['AVG'] = $this->H / $this->AB;
        }

        // OBP
        if ($this->PA - $this->SAB) {
            $this->stats['OBP'] = ($this->H + $this->BBs + $this->HPB + $this->CI) / ($this->PA - $this->SAB);
        }

        // SLG
        if ($this->AB) {
            $this->stats['TB'] = $this->stat('1') + 2*$this->stat('2') + 3*$this->stat('3') + 4*$this->stat('4');
            $this->stats['SLG'] = $this->TB / ($this->AB);
        }

        $this->stats['OPS'] = $this->OBP + $this->SLG;
        $this->stats['ISO'] = $this->SLG - $this->AVG;

        if ($this->PA) {
            $this->stats['PPA'] = ($this->hStrikes + $this->hBalls) / $this->PA;
        }

        // FPCT
        $this->stats['FI'] = $this->DO / 3;
        $this->stats['TC'] = $this->PO + $this->A + $this->E;
        if ($this->TC) {
            $this->stats['FPCT'] = ($this->PO + $this->A) / $this->TC;
        }
        if ($this->FI) {
            $this->stats['RF'] = ($this->PO + $this->A) / $this->FI * 9;
        }

        // Positions
        $this->stats['Positions'] = [];
        for ($i = 1; $i <= 9; $i++) {
            if ($this->{"DO.$i"}) {
                if ($this->{"DO.$i"} > ($this->DO / 5)) {
                    $this->stats['Positions'][] = $i;
                }
            }
        }

        // ERA
        $this->stats['IP'] = $this->TO / 3;
        if ($this->TO) {
            $this->stats['ERA'] = $this->ER / $this->TO * 27;
            $this->stats['KP9'] = $this->K / $this->IP * 9;
            $this->stats['BBP9'] = $this->BB / $this->IP * 9;
            if ($this->BB) $this->stats['KPBB'] = $this->K / $this->BB;
            $this->stats['WHIP'] = ($this->BB + $this->HA) / $this->IP;
        }

        if ($this->BFP) {
            $this->stats['Pitches'] = $this->Strikes + $this->Balls;
            $this->stats['StrkPct'] = $this->Strikes / $this->Pitches;
            $this->stats['FPSPCT'] = $this->FPS / $this->BFP * 100;
        }

        return $this;
    }

    public static function innings_format(float|int $n): string {
        $w = floor($n);
        $p = $n - $w;
        if (!$p) {
            return $w;
        } elseif($p < 0.5) {
            return "{$w}⅓";
        } else {
            return "{$w}⅔";
        }
    }

    public function humanStat(string $stat): string {
        $val = $this->stat($stat);
        $stat = match ($stat) {
            'BBs' => 'BB',
            '1', '2', '3', '4' => $stat . 'B',
            default => $stat,
        };
        if ($val) {
            return trans_choice(":stat|:value:stat", $val, ['value' => $val, 'stat' => $stat]);
        }
        return '';
    }

    public static function position(int $n): string {
        if ($n < 1 || $n > 9) return '';
        return ['P', 'C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF'][$n-1];
    }

    public static function positions(array $poisitions): string {
        return implode(', ', array_map(fn($p) => self::position($p), $poisitions));
    }
}