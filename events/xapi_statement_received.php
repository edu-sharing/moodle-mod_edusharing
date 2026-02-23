<?php
namespace mod_edusharing\event;

defined('MOODLE_INTERNAL') || die();

final class xapi_statement_received extends \core\event\base {

    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public static function get_name(): string {
        return "Edusharing XAPI Event";
    }

    public function get_description(): string {
        return "An xAPI statement was received by mod_edusharing.";
    }
}
