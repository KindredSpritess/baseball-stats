<?php

namespace App\Helpers;

class StatsHelper {

    public function __construct(private array $stats) {

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
            $this->stats['SLG'] = ($this->stat('1') + 2*$this->stat('2') + 3*$this->stat('3') + 4*$this->stat('4')) / ($this->AB);
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

        // ERA
        $this->stats['IP'] = $this->TO / 3;
        if ($this->TO) {
            $this->stats['ERA'] = $this->ER / $this->TO * 27;
            $this->stats['Pitches'] = $this->Strikes + $this->Balls;
            $this->stats['StrkPct'] = $this->Strikes / $this->Pitches;
            $this->stats['KP9'] = $this->K / $this->IP * 9;
            $this->stats['BBP9'] = $this->BB / $this->IP * 9;
            if ($this->BB) $this->stats['KPBB'] = $this->K / $this->BB;
            $this->stats['WHIP'] = ($this->BB + $this->HA) / $this->IP;
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
}