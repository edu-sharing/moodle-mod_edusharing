<?php declare(strict_types=1);

namespace mod_edusharing;

use dml_exception;

/**
 * Interface AppConfig
 *
 * Defines interaction with plugin config data.
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
interface AppConfig
{
    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set(string $name, mixed $value): void;

    /**
     * @param string $name
     * @return string
     * @throws dml_exception
     */
    public function get(string $name): mixed;
}
