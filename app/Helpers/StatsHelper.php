<?php

namespace App\Helpers;

/**
 * @property int $A Assists
 * @property int $AB At Bats
 * @property float $AVG Batting Average
 * @property int $BIP Balls in Play
 * @property float $BABIP Batting Average on Balls in Play
 * @property int $Balls Balls Thrown
 * @property int $BB Walks
 * @property float $BBP9 Walks per 9 Innings
 * @property int $BBs Walks taken
 * @property int $BFP Batters Faced by Pitcher
 * @property int $CCS Caught Stealing as Catcher
 * @property int $CI Reached on Catcher's Interference
 * @property int $CS Caught Stealing
 * @property int $CSB Stolen Bases as Catcher
 * @property int $DO Defensive Outs
 * @property int $E Errors
 * @property int $ER Earned Runs
 * @property float $ERA
 * @property int $FI Fielding Innings
 * @property float $FPCT Fielding Percentage
 * @property int $FPS First Pitch Strikes
 * @property float $FPSPCT First Pitch Strike Percentage
 * @property int $G Games played
 * @property int $GDP Grounded into Double Plays
 * @property int $GP Games Pitched
 * @property int $H Hits
 * @property int $HA Hits Allowed
 * @property int $HBP Batters Hit by Pitch
 * @property int $HPB Hit by Pitch
 * @property int $hStrikes Strikes seen by Batter
 * @property int $hBalls Balls seen by Batter
 * @property int $IP Innings Pitched
 * @property int $IR Inherited Runners
 * @property int $IRS Inherited Runners Scored
 * @property float $ISO Isolated Power
 * @property int $K Strikeouts as Pitcher
 * @property int $KP9 Strikeouts per 9 Innings
 * @property int $KPBB Strikeout to Walk Ratio
 * @property int $Loss Losing Decisions
 * @property float $OBP On Base Percentage
 * @property float $OPS On Base + Slugging
 * @property int $PA Plate Appearances
 * @property int $PB Passed Balls
 * @property int $PCS Caught Stealing as Pitcher
 * @property int $Pitches Pitches Thrown
 * @property int $PO Putouts
 * @property int $POs Pickoffs
 * @property string $Position
 * @property string $Positions
 * @property int $PPA Pitches per Plate Appearance
 * @property int $PPBFP Pitches per Batter Faced
 * @property int $PSB Stolen Bases as Pitcher
 * @property int $R Runs Scored
 * @property int $RA Runs Allowed
 * @property int $RBI Runs Batted In
 * @property float $RF Range Factor
 * @property int $SAB Sacrifice Bunts
 * @property int $SAF Sacrifice Flies
 * @property int $Save Saves
 * @property int $SB Stolen Bases
 * @property float $SLG Slugging Percentage
 * @property int $SO Strikeouts as Batter
 * @property int $Strikes Strikes Thrown
 * @property float $StrkPct Strike Percentage
 * @property int $TB Total Bases
 * @property int $TC Total Chances
 * @property int $TO Total Outs Pitched
 * @property float $WHIP Walks + Hits per Inning Pitched
 * @property int $Win Winning Decisions
 * @property int $WP Wild Pitches
 */
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
            if (is_array($v)) {
                continue;
            }
            $this->stats[$k] = ($this->stats[$k] ?? 0) + $v;
        }
        return $this;
    }

    public function stat(string $stat) {
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
        $this->stats['BIP'] = $this->AB - $this->SO - $this->stat('4');
        if ($this->BIP) {
            $this->stats['BABIP'] = ($this->H - $this->stat('4')) / $this->BIP;
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
            $pitches = $this->Strikes + $this->Balls;
            $this->stats['Pitches'] = $pitches + $this->Pitch;
            if ($pitches) {
                $this->stats['StrkPct'] = $this->Strikes / $pitches;
                $this->stats['FPSPCT'] = $this->FPS / $this->BFP * 100;
                $this->stats['PPBFP'] = $pitches / $this->BFP;
            }
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
            '1', '2', '3', '4' => "{$stat}B",
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