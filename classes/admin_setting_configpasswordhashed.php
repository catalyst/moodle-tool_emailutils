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
 * Hashed password formlib form element
 *
 * @package    tool_emailutils
 * @copyright  2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Harry Barnard <harry.barnard@catalyst-eu.net>
 */

namespace tool_emailutils;

/**
 * Hashed password formlib form element
 */
class admin_setting_configpasswordhashed extends \admin_setting {

    /** @var Min length of password */
    public $minlength;

    /** @var Is the password hashed */
    protected $ishashed;

    /**
     * Constructor
     * @param string $name Unique ascii name, either 'mysetting' for settings that in config, or
     *                     'myplugin/mysetting' for ones in config_plugins.
     * @param string $visiblename Localised name
     * @param string $description Localised long description
     * @param string $defaultsetting
     * @param integer $minlength Minimum password length
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, $minlength = 8) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
        $this->minlength = (int) $minlength;
        $this->ishashed = true;
    }

    /**
     * Return the setting
     *
     * @return mixed Returns config if successful else null
     */
    public function get_setting() {
        return $this->config_read($this->name);
    }

    /**
     * Writes the settings
     * @param mixed $data data
     */
    public function write_setting($data) {
        // Is the password valid?
        $isvalid = $this->validate($data);
        if (!$isvalid) {
            return $isvalid;
        }

        if (empty($data)) {
            // Password field is empty so just reuse existing hash.
            $password = $this->config_read($this->name);
        } else {
            // Hash new password.
            $password = password_hash($data, PASSWORD_DEFAULT);
        }
        return ($this->config_write($this->name, $password) ? '' : new lang_string('errorsetting', 'admin'));
    }

    /**
     * Validate data before storage
     * @param string $data data
     * @return mixed true if ok string if error found
     */
    public function validate($data) {
        if (empty($data) || (is_string($data) && (strlen($data) >= $this->minlength))) {
            return true;
        }

        return new lang_string('validateerror', 'admin');
    }

    /**
     * Return an XHTML string for the setting
     *
     * @param string $data data
     * @param string $query
     * @return string Returns an XHTML string
     */
    public function output_html($data, $query = '') {
        global $OUTPUT;

        $default = $this->get_defaultsetting();

        // Password is hashed so don't display it.
        if ($this->ishashed) {
            $data = null;
        }

        $context = [
            'minlength' => $this->minlength,
            'id' => $this->get_id(),
            'fullname' => $this->get_full_name(),
        ];

        $element = $OUTPUT->render_from_template('tool_emailutils/admin_setting_configpasswordhashed', $context);

        return format_admin_setting($this, $this->visiblename,
            $element,
            $this->description, true, '', $default, $query);
    }
}
