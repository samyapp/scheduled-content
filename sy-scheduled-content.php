<?php

class SY_Scheduled_Content
{
    public $content_path = null;

    public function __construct( $content_path = null )
    {
        $this->content_path = $content_path;
    }

    /**
     * Get the content filename that best matches the specified date and time criteria.
     * File name order of preference (descending priority):
     * YYYMMDD-HSMS-HEME.txt
     * DAY-NAME-HSMS-HEME.txt
     * YYYYMMDD-AM.txt (if the current time is before midday)
     * YYYYMMDD-PM.txt (if the current time is midday or later)
     * DAY-NAME-AM.txt (if the current time is before midday)
     * DAY-NAME-PM.txt (if the current time is midday or later)
     * PERIOD/DAY-RANGE/HSMS-HEME/<filename>.txt - if multiple files exist, one will be selected using the process below (1)
     * PERIOD/DAY-RANGE/AM/<filename>.txt (if the current time is before midday)
     * PERIOD/DAY-RANGE/PM/<filename>.txt (if the current time is midday or later)
     * PERIOD/DAY-RANGE/<filename>.txt
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

    public function get_candidate_files( $date, $time_of_day )
    {
        $am_pm = $time_of_day < 1200 ? 'AM' : 'PM';
        $today = (new \DateTime())->format('Y-m-d');
        $paths = [];

        $today_paths = glob( $this->content_path . '/' . $today . '*'); // files matching YYYYMMDD*
        $day_names = $this->get_valid_day_names( $date , $this->content_path . '/named-days.csv' );
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

        $periods = $this->get_valid_periods( $date );
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
     * @param array $date An array of strings.
     * @param string Path to CSV file.
     * @return array Array of strings, each representing a possible file name.
     */
    public function get_valid_day_names( $date , $path )
    {
        $fp = fopen( $path , 'r' );
        // the year represented by the second column in the csv file.
        $first_year = 2016;
        $year_column = $date->format('Y') - $first_year + 1;
        if ( $fp ) {
            while( false !== ( $row = fgetcsv( $fp, 4096, ',' ) ) ) {
                // if the second 0-indexed column contains a date with just month and day, then
                // assume that this named day always occurs on the same date each year.
                if ( $date->format('md') == $row[ 1 ] ) {
                    fclose( $fp );
                    return $row[ 0 ];
                }
                // otherwise look in the column for the current year.
                else if ( $date->format('Ymd') == $row[ $year_column ] ) {
                    fclose( $fp );
                    return $row[ 0 ];
                }
            }
            fclose( $fp );
        }
        return false;
    }

    /**
     * Get an array of "period names" which are valid for the current $date based on the 'named-periods.csv' file.
     * @param array $date An array of strings.
     */
    public function get_valid_periods( $date )
    {

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