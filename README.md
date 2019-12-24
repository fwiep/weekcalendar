# WeekCalendar

Generate Dutch PDF week calendars using PHP

## About

This project generates an A4 PDF document containing one page for every
ISO-8601 week of the given year. It uses [mPDF][1] for the PDF dirty
work.

All major Dutch holidays are added to the corresponding days, both static
and dynamic. For example Easter, Christmas, mother's and father's day.

There is also the option to add one or more private, additional holidays
to the calendar. You could put your own birthday in there, your wedding
anniversary&hellip;

## Example

A week calendar of the year 2023, having no additional holidays added
(except for the demo I put in there on june 18th) is part of this
project and can be [downloaded right here][2].

## Installation

To install the script, first clone the repository. Then install the
mPDF-dependency using `composer`. Finally, launch the PHP-server and
open up your browser to generate the document.
```
git clone https://github.com/fwiep/weekcalendar.git;
cd weekcalendar;
composer install;
php -S localhost:8080;
```
That's it. Enjoy!

[1]: https://github.com/mpdf/mpdf
[2]: Weekkalender-2023.pdf
