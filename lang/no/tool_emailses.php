<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    tool_emailses
 * @copyright  2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Harry Barnard <harry.barnard@catalyst-eu.net>
 */

$string['pluginname'] = 'Amazon Simple Email Service Klager (Amazon SES)';

$string['list'] = 'Klageliste';
$string['settings'] = 'Innstillinger';

$string['enabled'] = 'På';
$string['enabled_help'] = 'La pluginen håndtere innkommende meldinger';

$string['authorisationcategory'] = 'Autorisasjonsinnstillinger';
$string['header'] = 'Header';
$string['header_help'] = 'HTTP Basic Auth Header';
$string['username'] = 'Brukernavn';
$string['username_help'] = 'HTTP Basic Auth brukernavn';
$string['password'] = 'Passord';
$string['password_help'] = 'HTTP Basic Auth passord - La stå tomt om du ikke endrer passord';
$string['incorrect_access'] = 'Ugyldig tilgang oppdaget. Skal bare brukes av Amazon Simple Notification Service (Amazon SNS).';

$string['event:notificationreceived'] = 'Amazon Simple Notification Service varsling mottatt (AWS SNS)';

// Complaints list strings
$string['not_implemented'] = 'Ikke implementert ennå. Søk i brukerrapporten etter e-post som slutter med ".b.invalid" and ".c.invalid".';
$string['bounces'] = 'For en liste med avviste meldinger (bounces), besøk {$a} og søk etter e-post som slutter med ".b.invalid."';
$string['complaints'] = 'For en liste med klager (complaints), søk etter ".c.invalid"';
