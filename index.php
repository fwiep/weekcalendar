<?php
/**
 * Generate a 52-53 page PDF week calendar
 *
 * PHP version 8
 *
 * @category Calendar
 * @package  WeekCalendar
 * @author   Frans-Willem Post (FWieP) <fwiep@fwiep.nl>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://www.fwiep.nl/
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('date.timezone', 'Europe/Amsterdam');
date_default_timezone_set('Europe/Amsterdam');
ini_set('intl.default_locale', 'nl-NL');
setlocale(LC_ALL, array('nl_NL.utf8', 'nl_NL', 'nl', 'dutch', 'nld'));
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__.'/private-events.php')) {
    include_once __DIR__.'/private-events.php';
}
$year = (date('Y') + 1);

if ($_POST) {
    if (array_key_exists('inpYear', $_POST) and is_numeric($_POST['inpYear'])) {
        $year = intval($_POST['inpYear']);
    }
    $includePrivateHolidays = array_key_exists('inpIncludePrivate', $_POST);
    
    $paperSize = array_key_exists('inpPaperSize', $_POST)
      ? strtoupper(trim($_POST['inpPaperSize'])) : null;
    
    $yearValid = ($year >= 1582 && $year <= 3000);
    $paperSizeValid = ($paperSize == 'A4' || $paperSize == 'A5');

    if ($yearValid && $paperSizeValid) {
        $c = new FWieP\PdfCalendar($year, $paperSize, $includePrivateHolidays);
        $c->getPDF();
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<title>PDF weekkalender</title>
</head>
<body>
  <h1>PDF weekkalender</h1>
  <form action="<?php print basename($_SERVER['PHP_SELF'])?>" method="post">
    <fieldset>
      <legend>Kies een jaartal en druk op "Genereren"</legend>

      <div style="margin: 1em 0;">
        <label for="inpYear">Jaar:</label> <input type="number"
          name="inpYear" id="inpYear" min="1582" max="3000"
          value="<?php print $year ?>" />
      </div>

      <div style="margin: 1em 0;">
        <span>Papierformaat:</span>
        <label for="inpPaperSizeA4"><input type="radio"
          name="inpPaperSize" id="inpPaperSizeA4"
          value="A4" checked="checked" />A4</label>
        <label for="inpPaperSizeA5"><input type="radio"
          name="inpPaperSize" id="inpPaperSizeA5"
          value="A5" />A5</label>
      </div>

      <div style="margin: 1em 0;">
        <label for="inpIncludePrivate"><input type="checkbox"
          name="inpIncludePrivate" id="inpIncludePrivate"
          checked="checked" />Persoonlijke feestdagen toevoegen</label>
      </div>

      <input type="submit" id="inpSubmit" name="inpSubmit"
        value="Genereren" />

    </fieldset>
  </form>
</body>
</html>