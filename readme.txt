=== SY Scheduled Content ===
Contributors: samyapp
Tags: scheduler, scheduled content, calendar, daily content, daily prayer
Requires at least: 4.0
Tested up to: 4.7
Stable tag: 0.1.0

This plugin will display content from one of a number of text files based on the date of the year and time of day.
It was originally developed to select a prayer of the day, but can be used for other types of scheduled content.

== Description ==

This plugin will display the content from one of the following text files in the following order (descending priority), where:

  - YYYY is the current year in 4 digits.
  - MM is the current month 2 digit month
  - DD is the 2 digit current day of the month
  - DAY is the three digit English abbreviation for the current day of the week (e.g. MON, TUE, SUN)
  - HSMS is a time in 2 digit hour, 2 digit minute format which is <= the current time.
  - HEME is a time in 2 digit hour, 2 digit minute format which is >= the current time.
  - DAY-RANGE is either DAY, or a range of consecutive days of the week which includes the current day of the week,
    with the first and last day represented as 3 letter English
    abbreviation, for example MON-SAT.
  - <filename> is an arbitrary filename.
  - DAY-NAME is an alphanumeric identifier, listed in the named-days.csv file with an entry for the current date.
    The named-days.csv file has the following format, where a "named day" is something like "EasterMonday", "ChristmasDay", etc.
    - A column listing day names.
    - A column for each year, headed with the four-digit representation of that year.
    - A single row for each named day, consisting of:
      - The name (which should match a file <name>.txt)
      - For each year column, the date in that year that this day occurs.
        For days that occur on the same day each year, only the first year column should have a date, and it should be
        in MMDD format.
  - PERIOD is an alphanumeric identifier, listed in the named-periods.csv file with a start and end date that encloses
    the current date. The named-periods.csv file has three columns, start-date, end-date and period name,
    sorted in ascending date order of start date.
    If no PERIOD exists, then the PERIOD="default".
    - Start and end date are in YYYYMMDD format.
    - Period name is an alphanumeric identifier, such as "Lent", and can be repeated multiple times throughout the file.
      It should map to a folder with the same name containing prayers which should be displayed during the relevant
      date periods.

  File name order of preference (descending priority):

  - YYYYMMDD-HSMS-HEME.txt
  - DAY-NAME-HSMS-HEME.txt
  - YYYYMMDD-AM.txt (if the current time is before midday)
  - YYYYMMDD-PM.txt (if the current time is midday or later)
  - DAY-NAME-AM.txt (if the current time is before midday)
  - DAY-NAME-PM.txt (if the current time is midday or later)
  - PERIOD/DAY-RANGE/HSMS-HEME/<filename>.txt - if multiple files exist, one will be selected using the process below (1)
  - PERIOD/DAY-RANGE/AM/<filename>.txt (if the current time is before midday)
  - PERIOD/DAY-RANGE/PM/<filename>.txt (if the current time is midday or later)
  - PERIOD/DAY-RANGE/<filename>.txt

  If multiple day ranges exist for a PERIOD and HSMS-HEME, AM, PM, etc then the preference is undefined.

=== (1) selection process for multiple matches at the same level: ===

If multiple candidate files exist at the day-range level of the hierarchy, one will be selected using the following:

  - Files will be sorted alphabetically by filename.
  - The number of days that match the DAY-RANGE which have already occured during the PERIOD will be calculated <day-number>.
  - 0-indexed file number (<day-number> - 1) % <number-of-files> will be selected.

== Installation ==

1. Upload the entire `cc-scheduled-prayer` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

none

== Changelog ==

**0.1.0 - 20170106**

Began.