<?php declare(strict_types=1);

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
class DefaultAppConfig implements AppConfig
{
    /**
     * Function set
     *
     * Sets an edusharing specific config value
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set(string $name, mixed $value): void
    {
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
    public function get(string $name): mixed
    {
        return get_config('edusharing');
    }
}
