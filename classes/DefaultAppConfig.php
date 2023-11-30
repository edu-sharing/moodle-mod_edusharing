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


declare(strict_types=1);

namespace mod_edusharing;

use dml_exception;

/**
 * Class DefaultAppConfig
 *
 * This is the standard implementation of the AppConfig interface.
 * It merely wraps moodle standard functions.
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class DefaultAppConfig implements AppConfig {
    /**
     * Function set
     *
     * Sets an edusharing specific config value
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set(string $name, mixed $value): void {
        set_config($name, $value, 'edusharing');
    }

    /**
     * Function get
     *
     * gets an edusharing specific config value
     *
     * @param string $name
     * @return mixed
     * @throws dml_exception
     */
    public function get(string $name): mixed {
        return get_config('edusharing', $name);
    }
}
