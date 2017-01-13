<?php

namespace samyapp;

class ScheduledContent
{
    public $content_path = null;

    public function __construct( $content_path = null )
    {
        $this->content_path = $content_path;
    }

    /**
     * Get the content filename that best matches the specified date and time criteria.
     * File name order of preference (descending priority):
     * tl;dr;
     *   - Specific date
     *   - Named date (e.g. EasterMonday)
     *   - Day of Month (e.g. 1225)
     *   - Day of week in current period (e.g. 'MON-WED', 'SUN')
     *
     * YYYMMDD-HSMS-HEME.txt (if the current time is between HSMS-HEME)
     * YYYYMMDD-AM.txt (if the current time is before midday)
     * YYYYMMDD-PM.txt (if the current time is midday or later)
     * YYYYMMDD.txt
     * DAY-NAME-HSMS-HEME.txt (if the current time is between HSMS-HEME)
     * DAY-NAME-AM.txt (if the current time is before midday)
     * DAY-NAME-PM.txt (if the current time is midday or later)
     * DAY-NAME.txt
     * MMDD-HSMS-HEME.txt (if the current time is between HSMS-HEME)
     * MMDD-AM.txt (if the current time is before midday)
     * MMDD-PM.txt (if the current time is after midday)
     * MMDD.txt
     * PERIOD/DAY-RANGE/HSMS-HEME/<filename>.txt - if multiple files exist, one will be selected using the process below (1)
     * PERIOD/DAY-RANGE/AM/<filename>.txt (if the current time is before midday)
     * PERIOD/DAY-RANGE/PM/<filename>.txt (if the current time is midday or later)
     * PERIOD/DAY-RANGE/<filename>.txt
     *
     * @param null|\DateTime $date The date to get content for. Defaults to current date if null.
     * @param null|string $time_of_day The time of the day in HHMM format. Defaults to current time if null.
     * @return string|bool Filename to display or false if nothing matches.
     */
    public function get_content_for_date( $date = null, $time_of_day = null )
    {
        $time_of_day = $time_of_day ? $time_of_day : date('Hi');
        if ( ! ( $date instanceof \DateTime ) ) {
            if ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ) {
                $date = \DateTime::createFromFormat( 'Y-m-d', $date );
            }
            else {
                $date = new \DateTime();
            }
        }
        $candidates = $this->get_candidate_files( $date, $time_of_day );
        return count( $candidates ) ? $candidates[ 0 ] : false;
    }

    /**
     * Create an array of arrays from a CSV file
     * @param $filename
     * @return array
     */
    public function get_csv_data( $filename )
    {
        $fp = fopen( $filename, 'r' );
        $data = [];
        if ( $fp ) {
            while( false !== ( $row = fgetcsv( $fp, 4096, ',' ) ) ) {
                $data[] = $row;
            }
            fclose( $fp );
        }
        return $data;
    }

    public function matchDateTimeRange( $date, $time, $path )
    {
        if ( preg_match('/^' . $date . '-(\d{4})-(\d{4})\.?$/', $path, $matches ) ) { // YYMMDD-HHMM-HHMM
            if ( $matches[1] <= $time && $matches[2] >= $time ) {
                return true;
            }
        }
        return false;
    }

    public function matchDateAMPM( $date, $time, $path )
    {
        return preg_match( '/^' . $date . '-' . ($time < 1200 ? 'am' : 'pm') . '\.?$/i', $path );
    }

    public function matchExact( $name, $path )
    {
        return preg_match('/^' . $name . '\.?$/', $path );
    }

    public function get_candidate_files( $date, $time_of_day )
    {
        $am_pm = $time_of_day < 1200 ? 'AM' : 'PM';
        $today = (new \DateTime())->format('Y-m-d');

        $paths = [];
        $day_names = $this->get_valid_day_names( $date , $this->get_csv_data( $this->content_path . '/named-days.csv' ) );

        foreach ( $paths as $path ) {
            if ( $this->matchDateTimeRange( $today, $time_of_day, $path ) ) { // YYYYMMDD-HHMM-HHMM.txt
                $dated_paths[] = $path;
            }
            else if ( $this->matchDateAMPM( $today, $time_of_day, $path ) ) { // YYYYMMDD-AM.txt, YYYYMMDD-PM.txt
                $dated_paths[] = $path;
            }
            else if ( $this->matchExact( $today, $path ) ) {  // YYYYMMDD.txt
                $dated_paths[] = $path;
            }
            else {
                $matched = false;
                foreach ( $day_names as $name ) {
                    if ( $this->matchDateTimeRange( $name, $time_of_day, $path ) ||  // christmas-HHMM-HHMM.txt
                        $this->matchDateAMPM( $name, $time_of_day, $path ) ||   // christmas-AM.txt, christmas-PM.txt
                        $this->matchExact( $name, $path ) ) {   // christmas.txt
                        $dated_paths[] = $path;
                        $matched = true;
                        break;
                    }
                }
                if ( ! $matched ) {

                }
            }
        }


        foreach ( $day_names as $name ) {
            $today_paths = array_merge( $today_paths, glob( $this->content_path . '/' . $name . '*' ) ); // files matching eg christmasday*
        }

        foreach ( $today_paths as $path ) {
            if ( preg_match( '/^' . $today . '-(\d{4})-(\d{4})/', $path, $matches ) ) {
                if ( $matches[1] <= $time_of_day && $matches[2] >= $time_of_day ) {
                    $dated_paths[] = $path;
                }
            }
            else if ( preg_match('/^' . $today . '-(AM|PM)/i', $path, $matches ) ) {
                if ( strtoupper( $matches[1] ) == $am_pm ) {
                    $ampm_paths[] = $path;
                }
            }
        }

        $paths = array_merge( $dated_paths, $ampm_paths );

        $periods = $this->get_valid_periods( $date, $this->get_csv_data( $this->content_path . '/named-periods.csv' ) );
        foreach ( $periods as $period ) {
            $period_path = $this->content_path . '/' . $period . '/';
            $period_ranges = glob( $period_path . '*' );
            foreach ( $period_ranges as $range ) {
                if ( $this->is_valid_day_range( $range, $date ) ) {
                }
            }
        }

    }

    /**
     * Get an array of "day names" which are valid for the current $date based on the 'named-days.csv' file.
     * @param \DateTime $date
     * @param array of arrays, each representing a row from a csv file. The second column of the first row is assumed to be the first year.
     * @return array Array of strings, each representing a possible file name.
     */
    public function get_valid_day_names(  $date , $data )
    {
        $first_year = $data[ 0 ][ 1 ];
        $year_column = ((int)$date->format('Y')) - $first_year + 1;
        $n_rows = count( $data );
        $md = $date->format( 'md' );
        $ymd = $date->format( 'Ymd' );
        $results = [];
        for( $i = 1; $i < $n_rows; ++$i ) {
            if ( ! empty( $data[ $i ][ 1 ] ) && $md == $data[ $i ][ 1 ] ) {
                $results[] =  $data[ $i ][ 0 ];
            }
            else if ( ! empty( $data[ $i ][ $year_column ] ) && $ymd == $data[ $i ][ $year_column ] ) {
                $results[] = $data[ $i ][ 0 ];
            }
        }
        return $results;
    }

    /**
     * Get an array of "period names" which are valid for the current $date based on the 'named-periods.csv' file.
     * @param array $date An array of strings.
     */
    public function get_valid_periods( $date, $data )
    {

    }

    /**
     * Find any entries in input which match the given date and contain the specified time.
     * @param $input Array of strings
     * @param $datetime
     * @return array
     */
    public function match_yyyymmdd_hsms_heme( $input , $datetime )
    {
        $time = $datetime->format('Hi');
        $date = $datetime->format('Ymd');
        $matches = [];
        foreach ( $input as $in ) {
            if ( preg_match('/^(?P<date>\d{8})-(?P<start>\d{4})-(?P<end>\d{4})/', $in, $match ) ) {
                if ( $match['date'] == $date &&
                    $match['start'] <= $time &&
                    $match['end'] >= $time ) {
                    $matches[] = $in;
                }
            }
        }
        return $matches;
    }

    /**
     * Does the specified weekday range include the day of the week represented by $date?
     * @param string $range e.g. 'MON-THU', 'SUN', 'FRI-TUE', etc.
     * @param \DateTime $date The date to check if the weekday is in the range.
     * @return bool True if the day of the week specified by $date is in the $range.
     */
    public function is_valid_day_range( $range, $date )
    {
        $days = 'MON|TUE|WED|THU|FRI|SAT|SUN';
        $day_to_number = [
            'SUN' => 0,
            'MON' => 1,
            'TUE' => 2,
            'WED' => 3,
            'THU' => 4,
            'FRI' => 5,
            'SAT' => 6
        ];
        if ( preg_match("/^($days)(-($days))?/i", $range, $matches ) ) {
            $from = $day_to_number[ strtoupper( $matches[1] ) ];
            $to = ! empty( $matches[3] ) ? $day_to_number[ strtoupper( $matches[3] ) ] : $from;
            $day = $date->format('w');
            if ( ( $from <= $to && $from <= $day && $to >= $day) ||
                 ( $from > $to && ( $day >= $from || $day <= $to ) ) ) { // e.g. today is MON but range is FRI-TUE
                return true;
            }
        }
        return false;
    }
}