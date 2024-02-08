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
 * Create dkim selector form
 *
 * @package    tool_emailutils
 * @copyright  2023 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 */

namespace tool_emailutils\form;

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/formslib.php");

/**
 * Selector form
 */
class create_dkim extends \moodleform {

    /**
     * Selector
     * @see moodleform::definition()
     */
    public function definition() {

        global $CFG;

        $mform = $this->_form;
        $noreplydomain = substr($CFG->noreplyaddress, strpos($CFG->noreplyaddress, '@') + 1);

        $group = [];

        $group[] =& $mform->createElement('text', 'domain', '', array("size" => 20));
        $mform->setDefault("domain", $noreplydomain);
        $mform->setType('domain', PARAM_HOST);

        $group[] =& $mform->createElement('text', 'selector', '', array("size" => 20));

        $selector = $this->get_default_selector();
        $mform->setDefault("selector", $selector);
        $mform->setType('selector', PARAM_HOST);

        $mform->addGroup($group, 'selectorgroup',  get_string('selectorcreate', 'tool_emailutils'), '', false);
        $mform->addGroupRule('selectorgroup', get_string('selectornotblank', 'tool_emailutils'), 'required');

        $this->add_action_buttons(true, get_string('selectorcreatesubmit', 'tool_emailutils'));
    }

    /**
     * Validate
     *
     * @param mixed $data date
     * @param mixed $files files
     * @return mixed errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

    /**
     * Gets a selector value to use as a default
     *
     * @return string default selector
     */
    private function get_default_selector() {
        // Add date to default.
        $selector = \userdate(time(), get_string('selectordefault', 'tool_emailutils'));

        // Add suffix.
        $dns = new \tool_emailutils\dns_util();
        if ($suffix = $dns->get_selector_suffix()) {
            $selector .= '-' . $suffix;
        }
        return $selector;
    }

}
