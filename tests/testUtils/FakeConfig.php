<?php
// This file is part of Moodle - http://moodle.org/
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

declare(strict_types=1);

namespace testUtils;

use mod_edusharing\AppConfig;

/**
 * Class FakeConfig
 *
 * This can be used to inject a basic fake config into UtilityFunctions for testing
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 */
class FakeConfig implements AppConfig {

    /**
     * @var array
     */
    private array $entries = [];

    /**
     * Function set
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set(string $name, mixed $value): void {
        $this->entries[$name] = $value;
    }

    /**
     * Function get
     *
     * @param string $name
     * @return mixed
     */
    public function get(string $name): mixed {
        return $this->entries[$name] ?? false;
    }

    /**
     * Function setEntries
     *
     * @param array $entries
     * @return void
     */
    public function set_entries(array $entries): void {
        $this->entries = $entries;
    }
}
