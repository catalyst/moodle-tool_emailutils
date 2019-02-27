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
 * @package    local_sescomplaints
 * @copyright  2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Harry Barnard <harry.barnard@catalyst-eu.net>
 */

defined('MOODLE_INTERNAL') || die;

class admin_setting_configpasswordhashed extends admin_setting
{

    public $minlength;
    private $_is_hashed;

    /**
     * Constructor
     * @param string $name Unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in config_plugins.
     * @param string $visiblename Localised name
     * @param string $description Localised long description
     * @param string $defaultsetting
     * @param integer $minlength Minimum password length
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, $minlength = 8)
    {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
        $this->minlength = (int) $minlength;
        $this->_is_hashed = true;
    }

    /**
     * Return the setting
     *
     * @return mixed Returns config if successful else null
     */
    public function get_setting()
    {
        return $this->config_read($this->name);
    }

    public function write_setting($data)
    {
        // Is the password valid?
        $is_valid = $this->validate($data);
        if (!$is_valid) {
            return $is_valid;
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
     * @param string data
     * @return mixed true if ok string if error found
     */
    public function validate($data)
    {
        if (empty($data) || (is_string($data) && (strlen($data) >= $this->minlength))) {
            return true;
        }

        return new lang_string('validateerror', 'admin');
    }

    /**
     * Return an XHTML string for the setting
     * @return string Returns an XHTML string
     */
    public function output_html($data, $query = '')
    {
        $default = $this->get_defaultsetting();

        // Password is hashed so don't display it.
        if ($this->_is_hashed) {
            $data = null;
        }

        return format_admin_setting($this, $this->visiblename,
            '<div class="form-text defaultsnext"><input type="password" minlength="' . $this->minlength . '" id="' . $this->get_id() . '" name="' . $this->get_full_name() . '" value="" /></div>',
            $this->description, true, '', $default, $query);
    }
}
