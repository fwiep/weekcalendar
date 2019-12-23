<?php
/**
 * 52-53 page PDF week calendar
 *
 * PHP version 7
 *
 * @category Calendar
 * @package  WeekCalendar
 * @author   Frans-Willem Post (FWieP) <fwiep@fwiep.nl>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://fwiep.nl/
 */
namespace FWieP;

use \Mpdf\Output\Destination as D;

/**
 * 52-53 page PDF week calendar
 *
 * PHP version 7
 *
 * @category Calendar
 * @package  WeekCalendar
 * @author   Frans-Willem Post (FWieP) <fwiep@fwiep.nl>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://fwiep.nl/
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
     * This calendar's weeks
     *
     * @var array
     */
    private $_weeks = array();

    /**
     * This calendar's holidays
     *
     * @var array
     */
    private $_holidays = array();

    /**
     * Gets the date of Easter sunday in the given year
     *
     * @param int $year the year to display
     *
     * @return \DateTime
     */
    private function _getEasterDatetime(int $year) : \DateTime
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
    private function _addHoliday(string $dateYMD, string $name) : void
    {
        $this->_holidays[$dateYMD] = $name;
    }

    /**
     * Wraps the given DateTime and adds/subtracts given amount of days
     *
     * @param \DateTime $dt   DateTime to wrap
     * @param int       $days amount of days to add or subtract, can be negative
     *
     * @return \DateTime
     */
    private function _dtWrap(\DateTime $dt, int $days) : \DateTime
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
     * Adds static holidays to this calendar
     * <br>(Christmas, newyearsday, Valentine's day, etc.)
     *
     * @param int $year the year to display
     *
     * @return void
     */
    private function _addStaticHolidays(int $year) : void
    {
        $this->_addHoliday(($year-1).'-12-25', '1<sup>e</sup> Kerstdag');
        $this->_addHoliday(($year-1).'-12-26', '2<sup>e</sup> Kerstdag');
        $this->_addHoliday($year.'-01-01', 'Nieuwjaarsdag');
        $this->_addHoliday($year.'-02-14', 'Valentijnsdag');
        $this->_addHoliday($year.'-04-27', 'Koningsdag');
        $this->_addHoliday($year.'-05-04', 'Dodenherdenking');
        $this->_addHoliday($year.'-05-05', 'Bevrijdingsdag');
        $this->_addHoliday($year.'-12-25', '1<sup>e</sup> Kerstdag');
        $this->_addHoliday($year.'-12-26', '2<sup>e</sup> Kerstdag');
        $this->_addHoliday(($year+1).'-01-01', 'Nieuwjaarsdag');
    }

    /**
     * Adds private holidays to this calendar
     * <br>(wedding days, birthdays, name days, etc.)
     *
     * @param int $year the year to display
     *
     * @return void
     */
    private function _addPrivateHolidays(int $year)
    {
        if ($year >= 1942) {
            $this->_addHoliday(
                $year.'-06-18', 'Paul McCartney ('.($year-1942).')'
            );
        }
    }

    /**
     * Adds dynamic holidays to this calendar
     * <br>(mother's day, father's day, Easter, Pentecost, Carnival, etc.)
     *
     * @param int $year the year to display
     *
     * @return void
     */
    private function _addDynamicHolidays(int $year) : void
    {
        $dtEaster = static::_getEasterDatetime($year);

        $this->_addHoliday(
            static::_dtWrap($dtEaster, -49)->format('Y-m-d'), 'Carnavalszondag'
        );
        $this->_addHoliday(
            static::_dtWrap($dtEaster, -46)->format('Y-m-d'), 'Aswoensdag'
        );
        $this->_addHoliday(
            static::_dtWrap($dtEaster, -2)->format('Y-m-d'), 'Goede vrijdag'
        );
        $this->_addHoliday(
            $dtEaster->format('Y-m-d'), '1<sup>e</sup> Paasdag'
        );
        $this->_addHoliday(
            static::_dtWrap($dtEaster, 1)->format('Y-m-d'), '2<sup>e</sup> Paasdag'
        );
        $this->_addHoliday(
            static::_dtWrap($dtEaster, 39)->format('Y-m-d'), 'Hemelvaartsdag'
        );
        $this->_addHoliday(
            static::_dtWrap($dtEaster, 49)
                ->format('Y-m-d'), '1<sup>e</sup> Pinksterdag'
        );
        $this->_addHoliday(
            static::_dtWrap($dtEaster, 50)
                ->format('Y-m-d'), '2<sup>e</sup> Pinksterdag'
        );

        $dtMothersday = new \DateTime($year.'-05-01');
        while ($dtMothersday->format('N') != 7) {
            $dtMothersday->add(new \DateInterval('P1D'));
        }
        $dtMothersday->add(new \DateInterval('P7D'));

        $this->_addHoliday(
            $dtMothersday->format('Y-m-d'), 'Moederdag'
        );

        $dtFathersday = new \DateTime($year.'-06-01');
        while ($dtFathersday->format('N') != 7) {
            $dtFathersday->add(new \DateInterval('P1D'));
        }
        $dtFathersday->add(new \DateInterval('P14D'));

        $this->_addHoliday(
            $dtFathersday->format('Y-m-d'), 'Vaderdag'
        );
    }

    /**
     * Creates a new week calendar
     *
     * @param int  $year                   the year to display
     * @param bool $includePrivateHolidays whether to include private holidays
     */
    public function __construct(int $year, bool $includePrivateHolidays)
    {
        if ($year < 1582 || $year > 3000) {
            throw new \InvalidArgumentException(
                "Year should be between 1582 and 3000!"
            );
        }
        $this->_year = $year;
        $firstJan = new \DateTime($year.'-01-01');
        $nextFirstJan = new \DateTime(($year+1).'-01-01');
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
        $this->_addStaticHolidays($year);
        $this->_addDynamicHolidays($year);

        if ($includePrivateHolidays) {
            $this->_addPrivateHolidays($year);
        }
    }

    /**
     * Generates a 52-53 week calendar PDF and outputs it to the browser
     *
     * @return void
     */
    public function getPDF() : void
    {
        $pdfConfig = array(
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 24,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9,
            'orientation' => 'P',
        );
        $pdf = new \Mpdf\Mpdf($pdfConfig);
        $css = file_get_contents('style.css');
        $pdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

        foreach ($this->_weeks as $days) {
            $pdf->AddPage();
            $monday = reset($days);
            $sunday = end($days);

            $mWeek = strftime('%B', $monday->format('U'));
            $mMonday = strftime('%b', $monday->format('U'));
            $mSunday = strftime('%b', $sunday->format('U'));

            $yWeek = strftime('%Y', $monday->format('U'));
            $yMonday = strftime('%Y', $monday->format('U'));
            $ySunday = strftime('%Y', $sunday->format('U'));
            $ySundayShort = strftime('%y', $sunday->format('U'));

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

            foreach ($days as $ix => $day) {
                $lastRow = ($ix == 6);
                $extraClass = ($lastRow ? ' last' : '');
                $dayYMD = $day->format('Y-m-d');
                $isHoliday = array_key_exists($dayYMD, $this->_holidays);

                $html .= '<tr>';
                $html .= sprintf(
                    '<td class="l%s">%s%s</td>',
                    $extraClass,
                    strftime('%A', $day->format('U')),
                    ($isHoliday ? '<br /><span class="holiday">'.
                        $this->_holidays[$dayYMD].'</span>' : '')
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
        $pdf->Output('Weekkalender-'.$this->_year.'.pdf', D::DOWNLOAD);
    }
}