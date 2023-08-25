<?php declare(strict_types=1);

namespace testUtils;

use mod_edusharing\AppConfig;

/**
 * Class FakeConfig
 *
 * This can be used to inject a basic fake config into UtilityFunctions for testing
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class FakeConfig implements AppConfig
{
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
    public function setEntries(array $entries): void {
        $this->entries = $entries;
    }
}
