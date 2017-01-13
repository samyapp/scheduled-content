<?php
/**
 * Created by PhpStorm.
 * User: yapps
 * Date: 11/01/2017
 * Time: 14:24
 */

namespace samyapp;
require_once dirname(__FILE__) . '/../src/ScheduledContent.php';

class ScheduledContentTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider invalidRangeProvider
     */
    public function testIsValidDayRangeInvalid( $range, $date )
    {
        $instance = new ScheduledContent();
        $this->assertFalse( $instance->is_valid_day_range( $range, $date ) );
    }

    /**
     * @dataProvider validRangeProvider
     */
    public function testIsValidDayRangeValid( $range, $date )
    {
        $instance = new ScheduledContent();
        $this->assertTrue( $instance->is_valid_day_range( $range, $date ) );
    }

    public function testGetValidDayNames()
    {
        $days = [
            [ 'Day', '2016', '2017', '2018'],
            [ 'christmas', '1225' ],
            [ 'eastermonday','20160328','20170417','20180402'],
            [ '17thApril', '0417' ]
        ];

        $instance = new ScheduledContent();

        $nyd = \DateTime::createFromFormat('Ymd', '20170101');

        // xmas occurs same date each year
        $xmas2012 = \DateTime::createFromFormat('Ymd', '20121225' );
        $xmas2017 = \DateTime::createFromFormat('Ymd', '20171225' );
        $xmas2019 = \DateTime::createFromFormat('Ymd', '20191225' );

        // easter monday is different date each year
        $easter2017 = \DateTime::createFromFormat('Ymd', '20170417' );
        $easter2018 = \DateTime::createFromFormat('Ymd', '20180402' );

        $this->assertEquals( [ 'eastermonday', '17thApril' ], $instance->get_valid_day_names( $easter2017, $days ), 'Easter monday 2017 is 17th April' );
        $this->assertEquals( [], $instance->get_valid_day_names( $nyd, $days ), 'Date not in file' );
        $this->assertEquals( [ 'eastermonday' ], $instance->get_valid_day_names( $easter2018, $days ), 'Match single easter monday' );
        $this->assertEquals( [ 'christmas' ], $instance->get_valid_day_names( $xmas2012, $days ), 'Match day of month for year not in file (1)' );
        $this->assertEquals( [ 'christmas' ], $instance->get_valid_day_names( $xmas2017, $days ), 'Match day of month' );
        $this->assertEquals( [ 'christmas' ], $instance->get_valid_day_names( $xmas2019, $days ), 'Match day of month for year not in file (2)' );
    }

    public function testMatch_yyyymmdd_hsms_heme()
    {
        $today = new \DateTime();
        $today->setTime( 12, 0, 0 );
        $tmp = [
            [ 'Correct date - time within range', $today->format('Ymd-0930-1700'), true ],
            [ 'Correct date - time at start of range', $today->format('Ymd-1200-1201'), true],
            [ 'Correct date - time at end of range', $today->format('Ymd-0900-1200'), true],
            [ 'Correct date - time before range', $today->format('Ymd-0900-1159'), false],
            [ 'Correct date - time after range', $today->format('Ymd-1201-1600'), false ]
        ];
        $tests = [];
        // generate some more false inputs
        foreach ( $tmp as $t ) {
            $tests[] = $t;
            $t[0] = str_replace('Correct', 'Incorrect', $t[0] );
            $t[1] = str_replace($today->format('Ymd'), '20170101', $t[1] );
            $t[2] = false;
            $tests[] = $t;
        }
        $data = [];
        // for each input create a version with and without a file extension
        foreach ( $tests as $test ) {
            $data[] = $test;
            $test[ 1 ] .= '.txt.';
            $test[ 0 ] .= ', with file extension.';
            $data[] = $test;
        }
        // get all valid entries (expected result)
        $input = [];
        $expected = [];
        foreach ( $data as $d ) {
            $input[] = $d[1];
            if ( $d[2] ) {
                $expected[] = $d[1];
            }
        }
        $instance = new ScheduledContent();
        $this->assertEquals(  $expected , $instance->match_yyyymmdd_hsms_heme( $input, $today ));
    }

    /**
     * @return array an array of invalid day ranges and dates
     */
    public function invalidRangeProvider()
    {
        $monday = \DateTime::createFromFormat('Y-m-d', '2017-01-02' );
        $thursday = \DateTime::createFromFormat('Y-m-d', '2017-01-05' );
        $sunday = \DateTime::createFromFormat('Y-m-d', '2017-01-08' );
        return [
            [ 'MO', $monday ],
            [ 'TUE-FAR', $thursday ],
            [ 'MON', $thursday ],
            [ 'WED', $thursday ],
            [ 'MON', $sunday ],
            [ 'SAT', $sunday ],
            [ 'FRI', $thursday ],
            [ 'MON-WED', $thursday ],
            [ 'TUE-SUN', $monday ],
            [ 'FRI-WED', $thursday ]
        ];
    }

    /**
     * @return array an array of valid day range and date combinations
     */
    public function validRangeProvider()
    {
        $monday = \DateTime::createFromFormat('Y-m-d', '2017-01-02' );
        $thursday = \DateTime::createFromFormat('Y-m-d', '2017-01-05' );
        $sunday = \DateTime::createFromFormat('Y-m-d', '2017-01-08' );
        return [
            [ 'MON', $monday ], // actual day
            [ 'SUN', $sunday ],
            [ 'TUE-FRI', $thursday ], // range containing
            [ 'THU-FRI', $thursday ], // range starting with
            [ 'SAT-FRI', $thursday ], // range wrapping round containing
            [ 'SAT-THU', $thursday ], // range wrapping round ending with
            [ 'THU-WED', $thursday ], // range wrapping round starting with
            [ 'SUN-MON', $sunday ],
            [ 'SUN-MON', $monday ],
            [ 'SUN-TUE', $monday ],
            [ 'sun', $sunday ], // case insensitive
            [ 'sun-tue', $monday ]
        ];
    }

    public function getNamedPeriods()
    {
        $data = <<<EOL
Period,Start,End
advent,,
christmas,,
epiphany,,
ordinary,,
lent,,
easter,,
pentecost,,
trinity,,
advent,,
christmas,,
epiphany,,
ordinary,,
lent,,
easter,,
pentecost,,
trinity,,
EOL;
    }

}
 