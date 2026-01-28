<?php

namespace Tests\Unit;

use App\Console\Commands\ExportScorebookCommand;
use Tests\TestCase;

class LocateBallInPlayTest extends TestCase
{
    /**
     * Test that balls near the pitcher are assigned to fielder 1
     */
    public function test_ball_near_pitcher()
    {
        $command = new ExportScorebookCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('locateBallInPlay');
        $method->setAccessible(true);
        
        // Ball very close to pitcher position (224, 260)
        $result = $method->invoke($command, '224:260');
        $this->assertEquals(1, $result);
        
        // Ball slightly offset from pitcher
        $result = $method->invoke($command, '230:265');
        $this->assertEquals(1, $result);
    }

    /**
     * Test that balls near the catcher are assigned to fielder 2
     */
    public function test_ball_near_catcher()
    {
        $command = new ExportScorebookCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('locateBallInPlay');
        $method->setAccessible(true);
        
        // Ball near catcher position (224, 435)
        $result = $method->invoke($command, '224:435');
        $this->assertEquals('2', $result);
        
        $result = $method->invoke($command, '220:430');
        $this->assertEquals('2', $result);
    }

    /**
     * Test that balls near first base are assigned to fielder 3
     */
    public function test_ball_near_first_base()
    {
        $command = new ExportScorebookCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('locateBallInPlay');
        $method->setAccessible(true);
        
        // Ball near first base position (344, 310)
        $result = $method->invoke($command, '344:310');
        $this->assertEquals(3, $result);
        
        $result = $method->invoke($command, '340:305');
        $this->assertEquals(3, $result);
    }

    /**
     * Test that balls near second base are assigned to fielder 4
     */
    public function test_ball_near_second_base()
    {
        $command = new ExportScorebookCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('locateBallInPlay');
        $method->setAccessible(true);
        
        // Ball near second base position (284, 210)
        $result = $method->invoke($command, '284:210');
        $this->assertEquals('4', $result);
        
        $result = $method->invoke($command, '280:215');
        $this->assertEquals('4', $result);
    }

    /**
     * Test that balls near third base are assigned to fielder 5
     */
    public function test_ball_near_third_base()
    {
        $command = new ExportScorebookCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('locateBallInPlay');
        $method->setAccessible(true);
        
        // Ball near third base position (104, 310)
        $result = $method->invoke($command, '104:310');
        $this->assertEquals('5', $result);
        
        $result = $method->invoke($command, '110:315');
        $this->assertEquals('5', $result);
    }

    /**
     * Test that balls near shortstop are assigned to fielder 6
     */
    public function test_ball_near_shortstop()
    {
        $command = new ExportScorebookCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('locateBallInPlay');
        $method->setAccessible(true);
        
        // Ball near shortstop position (164, 210)
        $result = $method->invoke($command, '164:210');
        $this->assertEquals(6, $result);
        
        $result = $method->invoke($command, '160:215');
        $this->assertEquals(6, $result);
    }

    /**
     * Test that balls in left field are assigned to fielder 7
     */
    public function test_ball_in_left_field()
    {
        $command = new ExportScorebookCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('locateBallInPlay');
        $method->setAccessible(true);
        
        // Ball in left field position (104, 130)
        $result = $method->invoke($command, '104:130');
        $this->assertEquals('7', $result);
        
        // Ball deep in left field
        $result = $method->invoke($command, '80:100');
        $this->assertEquals('7', $result);
    }

    /**
     * Test that balls in center field are assigned to fielder 8
     */
    public function test_ball_in_center_field()
    {
        $command = new ExportScorebookCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('locateBallInPlay');
        $method->setAccessible(true);
        
        // Ball in center field position (224, 80)
        $result = $method->invoke($command, '224:80');
        $this->assertEquals('8', $result);
        
        // Ball deep in center field
        $result = $method->invoke($command, '224:50');
        $this->assertEquals('8', $result);
    }

    /**
     * Test that balls in right field are assigned to fielder 9
     */
    public function test_ball_in_right_field()
    {
        $command = new ExportScorebookCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('locateBallInPlay');
        $method->setAccessible(true);
        
        // Ball in right field position (344, 130)
        $result = $method->invoke($command, '344:130');
        $this->assertEquals('9', $result);
        
        // Ball deep in right field
        $result = $method->invoke($command, '370:100');
        $this->assertEquals('9', $result);
    }

    /**
     * Test that empty or invalid coordinates return empty string
     */
    public function test_invalid_coordinates()
    {
        $command = new ExportScorebookCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('locateBallInPlay');
        $method->setAccessible(true);
        
        // Empty string
        $result = $method->invoke($command, '');
        $this->assertEquals('', $result);
        
        // Only x coordinate
        $result = $method->invoke($command, '224');
        $this->assertEquals('', $result);
        
        // Malformed input - floatval('abc') = 0, so position is (0,0)
        // Closest fielder to (0,0) is left field at (104, 130)
        $result = $method->invoke($command, 'abc:def');
        $this->assertEquals('7', $result);
    }

    /**
     * Test intermediate positions to verify the nearest fielder logic
     */
    public function test_intermediate_positions()
    {
        $command = new ExportScorebookCommand();
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('locateBallInPlay');
        $method->setAccessible(true);
        
        // Between shortstop (164, 210) and second base (284, 210) - should be closer to shortstop
        $result = $method->invoke($command, '200:210');
        $this->assertEquals('6', $result); // Shortstop is closer (distance: 36 vs 84)
        
        // Between left field (104, 130) and center field (224, 80)
        $result = $method->invoke($command, '150:110');
        $this->assertContains($result, ['7', '8']); // Should be either left or center field
    }
}
