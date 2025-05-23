<?php
/**
 * 52-53 page PDF week calendar
 *
 * PHP version 8
 *
 * @category Calendar
 * @package  WeekCalendar
 * @author   Frans-Willem Post (FWieP) <fwiep@fwiep.nl>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://www.fwiep.nl/
 */
namespace FWieP;
use \IntlDateFormatter as IDF;
use \Mpdf\Output\Destination as D;

/**
 * 52-53 page PDF week calendar
 *
 * PHP version 8
 *
 * @category Calendar
 * @package  WeekCalendar
 * @author   Frans-Willem Post (FWieP) <fwiep@fwiep.nl>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://www.fwiep.nl/
 */
class PdfCalendar
{
    /**
     * This calendar's year
     *
     * @var integer
     */
    private $_year = 0;

    /**
     * The calendar's papersize (A4 or A5)
     * 
     * @var string
     */
    private $_paperSize = 'A4';

    /**
     * This calendar's weeks
     *
     * @var array
     */
    private $_weeks = [];

    /**
     * This calendar's events
     *
     * @var array
     */
    private $_events = [];

    /**
     * This calendar's locale
     * 
     * @var string
     */
    private $_locale = 'nl_NL';

    /**
     * This calendar's timezone
     * 
     * @var \DateTimeZone
     */
    private $_tz;

    /**
     * Gets the date of Easter sunday in the given year
     *
     * @param int $year the year to display
     *
     * @return \DateTime
     */
    private static function _getEasterDatetime(int $year) : \DateTime
    {
        $base = new \DateTime("$year-03-21");
        $days = easter_days($year);
        return $base->add(new \DateInterval("P{$days}D"));
    }

    /**
     * Adds a given date to this calendar
     *
     * @param string $dateYMD the date in YYYY-MM-DD format
     * @param string $name    the name to print
     *
     * @return void
     */
    private function _addEvent(string $dateYMD, string $name) : void
    {
        $this->_events[$dateYMD][] = $name;
    }

    /**
     * Wraps the given DateTime and adds/subtracts given amount of days
     *
     * @param \DateTime $dt   DateTime to wrap
     * @param int       $days amount of days to add or subtract, can be negative
     *
     * @return \DateTime
     */
    private static function _dtWrap(\DateTime $dt, int $days) : \DateTime
    {
        $outDt = clone $dt;
        $di = new \DateInterval('P'.abs($days).'D');

        if ($days < 0) {
            $outDt->sub($di);
        } else {
            $outDt->add($di);
        }
        return $outDt;
    }

    /**
     * Adds static events to this calendar
     * <br>(Christmas, newyearsday, Valentine's day, etc.)
     *
     * @param int $year the year to display
     *
     * @return void
     */
    private function _addStaticEvents(int $year) : void
    {
        $this->_addEvent(($year-1).'-12-25', '1<sup>e</sup> Kerstdag');
        $this->_addEvent(($year-1).'-12-26', '2<sup>e</sup> Kerstdag');
        $this->_addEvent($year.'-01-01', 'Nieuwjaarsdag');
        $this->_addEvent($year.'-02-14', 'Valentijnsdag');
        
        if ((new \DateTime($year.'-04-27'))->format('N') == '7') {
            $this->_addEvent($year.'-04-26', 'Koningsdag');
        } else {
            $this->_addEvent($year.'-04-27', 'Koningsdag');
        }
        $this->_addEvent($year.'-05-04', 'Dodenherdenking');
        $this->_addEvent($year.'-05-05', 'Bevrijdingsdag');
        $this->_addEvent($year.'-12-25', '1<sup>e</sup> Kerstdag');
        $this->_addEvent($year.'-12-26', '2<sup>e</sup> Kerstdag');
        $this->_addEvent(($year+1).'-01-01', 'Nieuwjaarsdag');
    }

    /**
     * Adds dynamic events to this calendar
     * <br>(mother's day, father's day, Easter, Pentecost, Carnival, etc.)
     *
     * @param int $year the year to display
     *
     * @return void
     */
    private function _addDynamicEvents(int $year) : void
    {
        $dtEaster = static::_getEasterDatetime($year);

        $this->_addEvent(
            static::_dtWrap($dtEaster, -49)->format('Y-m-d'), 'Carnavalszondag'
        );
        $this->_addEvent(
            static::_dtWrap($dtEaster, -46)->format('Y-m-d'), 'Aswoensdag'
        );
        $this->_addEvent(
            static::_dtWrap($dtEaster, -2)->format('Y-m-d'), 'Goede vrijdag'
        );
        $this->_addEvent(
            $dtEaster->format('Y-m-d'), '1<sup>e</sup> Paasdag'
        );
        $this->_addEvent(
            static::_dtWrap($dtEaster, 1)->format('Y-m-d'), '2<sup>e</sup> Paasdag'
        );
        $this->_addEvent(
            static::_dtWrap($dtEaster, 39)->format('Y-m-d'), 'Hemelvaartsdag'
        );
        $this->_addEvent(
            static::_dtWrap($dtEaster, 49)
                ->format('Y-m-d'), '1<sup>e</sup> Pinksterdag'
        );
        $this->_addEvent(
            static::_dtWrap($dtEaster, 50)
                ->format('Y-m-d'), '2<sup>e</sup> Pinksterdag'
        );

        $dtMothersday = new \DateTime($year.'-05-01');
        while ($dtMothersday->format('N') != 7) {
            $dtMothersday->add(new \DateInterval('P1D'));
        }
        $dtMothersday->add(new \DateInterval('P7D'));
        $this->_addEvent(
            $dtMothersday->format('Y-m-d'), 'Moederdag'
        );

        $dtFathersday = new \DateTime($year.'-06-01');
        while ($dtFathersday->format('N') != 7) {
            $dtFathersday->add(new \DateInterval('P1D'));
        }
        $dtFathersday->add(new \DateInterval('P14D'));
        $this->_addEvent(
            $dtFathersday->format('Y-m-d'), 'Vaderdag'
        );
        
        $dtSummertime = new \DateTime($year.'-03-31');
        while ($dtSummertime->format('N') != 7) {
            $dtSummertime->sub(new \DateInterval('P1D'));
        }
        $this->_addEvent(
            $dtSummertime->format('Y-m-d'), 'zomertijd (2:00 &rarr; 3:00)'
        );
        
        $dtWintertime = new \DateTime($year.'-10-31');
        while ($dtWintertime->format('N') != 7) {
            $dtWintertime->sub(new \DateInterval('P1D'));
        }
        $this->_addEvent(
            $dtWintertime->format('Y-m-d'), 'wintertijd (3:00 &rarr; 2:00)'
        );
    }

    /**
     * Gets this calendar's title/name for filename and PDF metadata
     * 
     * @return string
     */
    private function _getTitle() : string
    {
        return sprintf(
            'Weekkalender %d (%s)',
            $this->_year,
            $this->_paperSize
        );
    }

    /**
     * Creates a new week calendar
     *
     * @param int    $year                 the year to display
     * @param string $paperSize            the calendar's papersize (A4 or A5)
     * @param bool   $includePrivateEvents whether to include private events
     */
    public function __construct(
        int $year, string $paperSize = 'A4', bool $includePrivateEvents = false
    ) {
        if ($year < 1582 || $year > 3000) {
            throw new \InvalidArgumentException(
                "Year should be between 1582 and 3000!"
            );
        }
        if (!in_array($paperSize, ['A4', 'A5'])) {
            throw new \InvalidArgumentException(
                "Invalid pagesize selected!"
            );
        }
        $this->_tz = new \DateTimeZone('Europe/Amsterdam');
        $this->_year = $year;
        $this->_paperSize = strtoupper($paperSize);

        $firstJan = new \DateTime($year.'-01-01', $this->_tz);
        $nextFirstJan = new \DateTime(($year+1).'-01-01', $this->_tz);
        $startDate = clone $firstJan;

        while ($startDate->format('N') > 1) {
            $startDate->sub(new \DateInterval('P1D'));
        }
        $loopDate = clone $startDate;

        while ($loopDate <= $firstJan
            or $loopDate->format('o') == $year
            or $loopDate < $nextFirstJan
        ) {
            $week = $loopDate->format('o-W');
            for ($i = 0; $i < 7; $i++) {
                $this->_weeks[$week][] = clone $loopDate;
                $loopDate->add(new \DateInterval('P1D'));
            }
        }
        $this->_addStaticEvents($year);
        $this->_addDynamicEvents($year);

        if ($includePrivateEvents && defined('PRIVATE_EVENTS')) {
            foreach (PRIVATE_EVENTS as $ymd => $eventname) {

                $y = intval(substr($ymd, 0, 4));
                $md = substr($ymd, 4);
                $isAnniversary = ($y != 0);
                $eventFormat = ($isAnniversary ? '%s (%d)' : '%s');
                
                // Add events only if they have already occurred
                if ($year >= $y) {
                    
                    // Add to following year
                    $this->_addEvent(
                        ($year+1).$md,
                        sprintf($eventFormat, $eventname, ($year-$y+1))
                    );
                    // Add to current year
                    $this->_addEvent(
                        $year.$md,
                        sprintf($eventFormat, $eventname, ($year-$y))
                    );
                    // Add to previous year
                    $this->_addEvent(
                        ($year-1).$md,
                        sprintf($eventFormat, $eventname, ($year-$y-1))
                    );
                }
            }
        }
    }

    /**
     * Generates a 52-53 week calendar PDF and outputs it to the browser
     *
     * @return void
     */
    public function getPDF() : void
    {
        $dtfmt = new IDF(
            $this->_locale, IDF::NONE, IDF::NONE, $this->_tz, IDF::GREGORIAN
        );
        $pdfConfig = [
            'format' => $this->_paperSize,
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 24,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9,
            'orientation' => 'P',
        ];
        $pdf = new \Mpdf\Mpdf($pdfConfig);
        $pdf->SetTitle($this->_getTitle());
        $pdf->SetAuthor('Frans-Willem Post (FWieP)');

        $css = file_get_contents('style.css');
        $pdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

        foreach ($this->_weeks as $days) {
            $pdf->AddPage();
            $monday = reset($days);
            $sunday = end($days);

            $dtfmt->setPattern('MMMM');
            $mWeek = $dtfmt->format($monday);
            $dtfmt->setPattern('MMM');
            $mMonday = $dtfmt->format($monday);
            $mSunday = $dtfmt->format($sunday);

            $dtfmt->setPattern('Y');
            $yWeek = $dtfmt->format($monday);
            $yMonday = $dtfmt->format($monday);
            $ySunday = $dtfmt->format($sunday);
            $dtfmt->setPattern('yy');
            $ySundayShort = $dtfmt->format($sunday);

            $html = '<table>';

            $html .= sprintf(
                '<tr><th class="l">Week %d - %s</th>',
                $monday->format('W'),
                ($mMonday == $mSunday ? $mWeek : $mMonday.' / '.$mSunday)
            );
            $html .= sprintf(
                '<th class="r">%s</th></tr>',
                ($ySunday == $yMonday ? $yWeek : $yMonday.'/'.$ySundayShort)
            );

            $dtfmt->setPattern('EEEE');

            foreach ($days as $ix => $day) {
                $lastRow = ($ix == 6);
                $extraClass = ($lastRow ? ' last' : '');
                $dayYMD = $day->format('Y-m-d');
                $isEvent = array_key_exists($dayYMD, $this->_events);

                $html .= '<tr>';
                $html .= sprintf(
                    '<td class="l%s">%s%s</td>',
                    $extraClass,
                    $dtfmt->format($day),
                    ($isEvent ? '<br /><span class="event">'.
                        join('<br />', $this->_events[$dayYMD]).'</span>' : '')
                );
                $html .= sprintf(
                    '<td class="r%s">%d</td>',
                    $extraClass,
                    $day->format('d')
                );
                $html .= '</tr>';
            }
            $html .= '</table>';
            $pdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        }
        // Add a single 'Notes' page
        $pdf->AddPage();
        $html = '<table>';
        $html .= '<tr><th class="l">Notities</th>';
        for ($i = 0; $i < 25; $i++) {
            $html .= '<tr><td class="notes"> </td></tr>';
        }
        $html .= '</table>';
        $pdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

        /**
         * For easy booklet printing, ensure a page count dividable by 4.
         * See, for example
         * https://help.gnome.org/users/evince/stable/duplex-npage.html.en
         * for instructions on how to do this.
         *
         * Full pagenumber sequence for a 52-weeks document
         * 52,1,2,51,50,3,4,49,48,5,6,47,46,7,8,45,44,9,10,43,42,11,12,41,40,13,
         * 14,39,38,15,16,37,36,17,18,35,34,19,20,33,32,21,22,31,30,23,24,29,28,
         * 25,26,27
         * 
         * Full pagenumber sequence for a 53-weeks document
         * 56,1,2,55,54,3,4,53,52,5,6,51,50,7,8,49,48,9,10,47,46,11,12,45,44,13,
         * 14,43,42,15,16,41,40,17,18,39,38,19,20,37,36,21,22,35,34,23,24,33,32,
         * 25,26,31,30,27,28,29
        */
        $weeksCount = count($this->_weeks);
        $weeksCount += 1; // include Notes in page count

        while ($weeksCount % 4 != 0) {
            $pdf->AddPage();
            $weeksCount++;
        }
        $pdf->Output($this->_getTitle().'.pdf', D::DOWNLOAD);
    }
}
