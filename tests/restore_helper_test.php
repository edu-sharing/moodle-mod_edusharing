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

// Namespace does not match PSR. But Moodle likes it this way.
namespace mod_edusharing;

use DOMDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');

/**
 * Class restore_helper_test
 *
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(RestoreHelper::class)]
final class restore_helper_test extends \advanced_testcase {
    /**
     * Function setUp
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        RestoreRightsChecker::reset_cache();
    }

    /**
     * Function get_service_mock
     *
     * @return EduSharingService&MockObject
     */
    private function get_service_mock(): EduSharingService {
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
        $service  = $this->getMockBuilder(EduSharingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_node_for_user', 'get_ticket', 'get_ticket_for_user', 'create_usage'])
            ->getMock();
        $service->method('get_ticket')->willReturn('ticket');
        $service->method('get_ticket_for_user')->willReturn('ticket');
        $service->method('get_node_for_user')->willReturn(['node' => ['access' => ['CCPublish']]]);
        $service->method('create_usage')->willReturnCallback(
            fn(\stdClass $d): \EduSharingApiClient\Usage => new \EduSharingApiClient\Usage(
                (string)$d->nodeId,
                $d->nodeVersion === null ? null : (string)$d->nodeVersion,
                (string)$d->containerId,
                (string)$d->resourceId,
                'usage-' . $d->resourceId
            )
        );
        return $service;
    }

    /**
     * Function get_embed
     *
     * A regular insert of the Tiny plugin, carrying title, object_url and window_version in its url.
     *
     * @param string $nodeid
     * @param string $title
     * @param int $foreignresourceid
     * @param string $host
     * @return string
     */
    private function get_embed(string $nodeid, string $title, int $foreignresourceid, string $host = 'https://other.site'): string {
        $query = http_build_query([
            'resourceId'     => $foreignresourceid,
            'nodeId'         => $nodeid,
            'mimetype'       => 'image/png',
            'mediatype'      => 'file-image',
            'object_url'     => 'ccrep://repo/' . $nodeid,
            'window_version' => '1.0',
            'title'          => $title,
        ], '', '&amp;');
        $attribute = htmlspecialchars($title);
        return '<div class="edusharing-placeholder" contenteditable="false" style="">'
            . '<img class="edusharing_atto" alt="' . $attribute . '" title="' . $attribute . '"'
            . ' src="' . $host . '/mod/edusharing/preview.php?' . $query . '" width="300" height="200">'
            . '</div>';
    }

    /**
     * Function get_iframe_embed
     *
     * Mimics getIframeReplacementContent of the Tiny plugin: the url has neither title, object_url nor window_version.
     *
     * @param string $nodeid
     * @param string $title
     * @param int $foreignresourceid
     * @param string $host
     * @return string
     */
    private function get_iframe_embed(string $nodeid, string $title, int $foreignresourceid, string $host = 'https://other.site'): string {
        $query = http_build_query([
            'resourceId' => $foreignresourceid,
            'nodeId'     => $nodeid,
            'mimetype'   => 'text/html',
            'mediatype'  => 'link',
            'width'      => 300,
            'height'     => 200,
        ], '', '&amp;');
        $attribute = htmlspecialchars($title);
        return '<div class="edusharing-placeholder" contenteditable="false" style="">'
            . '<img class="edusharing_atto" alt="' . $attribute . '" title="' . $attribute . '"'
            . ' src="' . $host . '/mod/edusharing/preview.php?' . $query . '" width="300" height="200">'
            . '</div>';
    }

    /**
     * Function get_object_elements
     *
     * @param string $text
     * @return array the img/a elements with class edusharing_atto
     */
    private function get_object_elements(string $text): array {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $text);
        libxml_clear_errors();
        $elements = [];
        foreach (['img', 'a'] as $tag) {
            foreach ($doc->getElementsByTagName($tag) as $element) {
                if (in_array('edusharing_atto', explode(' ', $element->getAttribute('class')), true)) {
                    $elements[] = $element;
                }
            }
        }
        return $elements;
    }

    /**
     * Function get_object_url
     *
     * @param \DOMElement $element
     * @return string
     */
    private function get_object_url(\DOMElement $element): string {
        return html_entity_decode($element->getAttribute($element->tagName === 'a' ? 'href' : 'src'));
    }

    /**
     * Function convert_course
     *
     * Builds a course with embedded objects, runs the conversion and checks what has to hold in every case.
     *
     * @param bool $withiframe whether an embed created from a pasted iframe sits between the first and second object
     * @return array [course, section0, section1, texts, iframe record or null]
     */
    private function convert_course(bool $withiframe): array {
        global $CFG, $DB;
        $user     = $this->getDataGenerator()->create_user();
        $course   = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $section0 = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0], '*', MUST_EXIST);
        $section1 = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1], '*', MUST_EXIST);

        $section0text = '<p>before</p>'
            . $this->get_embed('node-first', 'First', 101)
            . ($withiframe ? $this->get_iframe_embed('node-iframe', 'Iframe', 102) : '')
            . $this->get_embed('node-second', 'Second "quoted"', 103)
            . '<p>after</p>';
        $section1text = $this->get_embed('node-third', 'Third', 104);
        $labeltext    = $this->get_embed('node-fourth', 'Fourth', 105);

        // Set directly, the observers of the plugin would react to events.
        $DB->set_field('course_sections', 'summary', $section0text, ['id' => $section0->id]);
        $DB->set_field('course_sections', 'summary', $section1text, ['id' => $section1->id]);
        $label = $this->getDataGenerator()->create_module('label', ['course' => $course->id, 'intro' => 'plain']);
        $DB->set_field('label', 'intro', $labeltext, ['id' => $label->id]);

        (new RestoreHelper($this->get_service_mock()))->convert_inline_options((int)$course->id, (int)$user->id);

        $texts = [
            's0' => $DB->get_field('course_sections', 'summary', ['id' => $section0->id]),
            's1' => $DB->get_field('course_sections', 'summary', ['id' => $section1->id]),
            'label' => $DB->get_field('label', 'intro', ['id' => $label->id]),
        ];
        $this->assertStringContainsString('before', $texts['s0']);
        $this->assertStringContainsString('after', $texts['s0']);

        $elementcount = 0;
        $referenced   = [];
        $urls         = [];
        foreach ($texts as $key => $text) {
            $this->assertStringNotContainsString('other.site', $text, "Unconverted object left in $key");
            foreach ($this->get_object_elements($text) as $element) {
                $elementcount++;
                $url = $this->get_object_url($element);
                $this->assertStringStartsWith($CFG->wwwroot . '/mod/edusharing/preview.php?', $url);
                parse_str((string)parse_url($url, PHP_URL_QUERY), $params);
                $this->assertTrue(
                    $DB->record_exists('edusharing', ['id' => $params['resourceId'], 'course' => $course->id]),
                    "Element in $key points to a missing edusharing record"
                );
                $referenced[$key][] = (int)$params['resourceId'];
                $urls[(int)$params['resourceId']] = $params;
            }
        }

        $expected = [
            'ccrep://repo/node-first'  => ['section_id' => $section0->id, 'key' => 's0'],
            'ccrep://repo/node-second' => ['section_id' => $section0->id, 'key' => 's0'],
            'ccrep://repo/node-third'  => ['section_id' => $section1->id, 'key' => 's1'],
            'ccrep://repo/node-fourth' => ['module_id' => $label->cmid, 'key' => 'label'],
        ];
        foreach ($expected as $objecturl => $where) {
            $key = $where['key'];
            unset($where['key']);
            $record = $DB->get_record('edusharing', ['course' => $course->id, 'object_url' => $objecturl] + $where);
            $this->assertNotFalse($record, "No edusharing record for $objecturl");
            $this->assertSame('usage-' . $record->id, $record->usage_id);
            $this->assertContains((int)$record->id, $referenced[$key] ?? [], "$objecturl is not referenced in $key");
        }

        // The title with double quotes is stored as &quot; by the editor, and its object is converted like the others.
        $this->assertStringContainsString('title="Second &quot;quoted&quot;"', $section0text);
        $this->assertSame(
            'Second "quoted"',
            $DB->get_field('edusharing', 'name', ['course' => $course->id, 'object_url' => 'ccrep://repo/node-second'])
        );

        // The error message of an object that could not be stored is no element, so the counts still match.
        $this->assertSame($elementcount, $DB->count_records('edusharing', ['course' => $course->id]));
        return [$course, $section0, $texts, $urls];
    }

    /**
     * Function test_convert_inline_options_converts_regular_embeds
     *
     * @return void
     */
    public function test_convert_inline_options_converts_regular_embeds(): void {
        $this->resetAfterTest();
        [, , $texts] = $this->convert_course(false);
        $this->assertCount(4, $this->get_object_elements(implode('', $texts)));
    }

    /**
     * Function test_convert_inline_options_converts_iframe_embeds
     *
     * @return void
     */
    public function test_convert_inline_options_converts_iframe_embeds(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('application_homerepid', 'repo', 'edusharing');
        [$course, $section0, $texts, $urls] = $this->convert_course(true);

        $record = $DB->get_record('edusharing', ['course' => $course->id, 'object_url' => 'ccrep://repo/node-iframe']);
        $this->assertNotFalse($record);
        $this->assertSame('Iframe', $record->name);
        $this->assertSame('0', (string)$record->object_version);
        $this->assertEquals($section0->id, $record->section_id);
        $this->assertSame('usage-' . $record->id, $record->usage_id);
        $this->assertArrayHasKey((int)$record->id, $urls);
        $this->assertSame('Iframe', $urls[(int)$record->id]['title']);
        $this->assertSame('ccrep://repo/node-iframe', $urls[(int)$record->id]['object_url']);
        $this->assertSame('0', $urls[(int)$record->id]['window_version']);
    }

    /**
     * Function test_convert_inline_options_continues_when_object_cannot_be_stored
     *
     * @return void
     */
    public function test_convert_inline_options_continues_when_object_cannot_be_stored(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('application_homerepid', '', 'edusharing');
        [$course, , $texts] = $this->convert_course(true);
        $this->assertDebuggingCalled();

        $this->assertStringContainsString(
            get_string('error_unexpected_on_restore', 'edusharing') . ': node-iframe',
            $texts['s0']
        );
        $this->assertFalse($DB->record_exists('edusharing', ['course' => $course->id, 'name' => 'Iframe']));
    }

    /**
     * Function get_captioned_embed
     *
     * @param string $nodeid
     * @param string $title
     * @param int $foreignresourceid
     * @return string an embed with the caption Tiny adds to the wrapper
     */
    private function get_captioned_embed(string $nodeid, string $title, int $foreignresourceid): string {
        return str_replace(
            '></div>',
            '><p class="edusharing_caption">Caption of ' . $title . '</p></div>',
            $this->get_embed($nodeid, $title, $foreignresourceid)
        );
    }

    /**
     * Function test_convert_inline_options_removes_wrapper_of_stripped_object
     *
     * @return void
     */
    public function test_convert_inline_options_removes_wrapper_of_stripped_object(): void {
        global $DB;
        $this->resetAfterTest();
        $user    = $this->getDataGenerator()->create_user();
        $course  = $this->getDataGenerator()->create_course();
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0], '*', MUST_EXIST);
        $kept    = $this->get_captioned_embed('node-kept', 'Kept', 201);
        $denied  = $this->get_captioned_embed('node-denied', 'Denied', 202);
        $text    = '<p>before</p>' . $denied . '<p>between</p>' . $kept . '<p>after</p>';
        $DB->set_field('course_sections', 'summary', $text, ['id' => $section->id]);

        $service = $this->getMockBuilder(EduSharingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_node_for_user', 'get_ticket', 'get_ticket_for_user', 'create_usage'])
            ->getMock();
        $service->method('get_ticket')->willReturn('ticket');
        $service->method('get_ticket_for_user')->willReturn('ticket');
        $service->method('get_node_for_user')->willReturnCallback(fn(string $ticket, string $nodeid): array => [
            'node' => ['access' => $nodeid === 'node-kept' ? ['CCPublish'] : ['Read']],
        ]);
        $service->method('create_usage')->willReturnCallback(
            fn(\stdClass $d): \EduSharingApiClient\Usage => new \EduSharingApiClient\Usage(
                (string)$d->nodeId,
                (string)$d->nodeVersion,
                (string)$d->containerId,
                (string)$d->resourceId,
                'usage-' . $d->resourceId
            )
        );
        (new RestoreHelper($service))->convert_inline_options((int)$course->id, (int)$user->id);

        $summary = $DB->get_field('course_sections', 'summary', ['id' => $section->id]);
        $this->assertStringNotContainsString('Caption of Denied', $summary);
        $this->assertStringNotContainsString('node-denied', $summary);
        $this->assertStringStartsWith('<p>before</p><p>between</p><div class="edusharing-placeholder"', $summary);
        $this->assertStringEndsWith('</div><p>after</p>', $summary);
        // The converted object keeps its wrapper and caption.
        $this->assertSame(1, substr_count($summary, 'class="edusharing-placeholder"'));
        $this->assertStringContainsString('<p class="edusharing_caption">Caption of Kept</p></div>', $summary);
        $this->assertCount(1, $this->get_object_elements($summary));
    }

    /**
     * Function test_convert_inline_options_replaces_wrapper_of_object_that_cannot_be_stored
     *
     * @return void
     */
    public function test_convert_inline_options_replaces_wrapper_of_object_that_cannot_be_stored(): void {
        $this->resetAfterTest();
        set_config('application_homerepid', '', 'edusharing');
        [, , $texts] = $this->convert_course(true);
        $this->assertDebuggingCalled();

        $error = get_string('error_unexpected_on_restore', 'edusharing') . ': node-iframe';
        $this->assertStringContainsString('</div>' . $error . '<div', $texts['s0']);
        // Only the wrappers of the two converted objects are left.
        $this->assertSame(2, substr_count($texts['s0'], 'class="edusharing-placeholder"'));
        $this->assertSame(2, substr_count($texts['s0'], '</div>'));
    }

    /**
     * Function test_convert_inline_options_strips_bare_element_without_wrapper
     *
     * @return void
     */
    public function test_convert_inline_options_strips_bare_element_without_wrapper(): void {
        global $DB;
        $this->resetAfterTest();
        $user    = $this->getDataGenerator()->create_user();
        $course  = $this->getDataGenerator()->create_course();
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0], '*', MUST_EXIST);
        $embed   = '<a class="edusharing_atto"'
            . ' href="https://other.site/mod/edusharing/preview.php?resourceId=1&amp;nodeId=n">x</a>';
        $DB->set_field('course_sections', 'summary', '<p>a</p>' . $embed . '<div>b</div>', ['id' => $section->id]);

        $service = $this->getMockBuilder(EduSharingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_node_for_user', 'get_ticket', 'get_ticket_for_user'])
            ->getMock();
        $service->method('get_ticket')->willReturn('ticket');
        $service->method('get_ticket_for_user')->willReturn('ticket');
        $service->method('get_node_for_user')->willReturn(['node' => ['access' => ['Read']]]);
        (new RestoreHelper($service))->convert_inline_options((int)$course->id, (int)$user->id);

        $this->assertSame(
            '<p>a</p><div>b</div>',
            $DB->get_field('course_sections', 'summary', ['id' => $section->id])
        );
    }

    /**
     * Function create_record
     *
     * @param \stdClass $course
     * @param string $nodeid
     * @return int id of the new edusharing record, pinned to version 1.3
     */
    private function create_record(\stdClass $course, string $nodeid = 'node-iframe'): int {
        global $DB;
        return (int)$DB->insert_record('edusharing', (object)[
            'course'         => $course->id,
            'section_id'     => 0,
            'name'           => 'Iframe',
            'introformat'    => 0,
            'object_url'     => 'ccrep://repo/' . $nodeid,
            'object_version' => '1.3',
            'timecreated'    => time(),
            'timemodified'   => time(),
        ]);
    }

    /**
     * Function backup_text
     *
     * @param \stdClass $course
     * @param string $text
     * @return string
     */
    private function backup_text(\stdClass $course, string $text): string {
        return (new \backup_xml_transformer((int)$course->id))->process($text);
    }

    /**
     * Function test_backup_completes_iframe_embed_from_its_record
     *
     * @return void
     */
    public function test_backup_completes_iframe_embed_from_its_record(): void {
        global $CFG;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $id     = $this->create_record($course);
        $text   = '<p>before</p>' . $this->get_iframe_embed('node-iframe', 'Iframe', $id, $CFG->wwwroot) . '<p>after</p>';

        $output   = $this->backup_text($course, $text);
        $elements = $this->get_object_elements($output);
        $this->assertCount(1, $elements);
        parse_str((string)parse_url($this->get_object_url($elements[0]), PHP_URL_QUERY), $params);
        $this->assertSame('Iframe', $params['title']);
        $this->assertSame('ccrep://repo/node-iframe', $params['object_url']);
        $this->assertSame('1.3', $params['window_version']);
        $this->assertSame((string)$id, $params['resourceId']);
        $this->assertSame('node-iframe', $params['nodeId']);
        $this->assertSame('text/html', $params['mimetype']);
        $this->assertSame('link', $params['mediatype']);
        $this->assertSame('300', $params['width']);
        $this->assertSame('200', $params['height']);
        $this->assertStringContainsString('<p>before</p>', $output);
        $this->assertStringContainsString('<p>after</p>', $output);
    }

    /**
     * Function test_backup_leaves_regular_embeds_untouched
     *
     * @return void
     */
    public function test_backup_leaves_regular_embeds_untouched(): void {
        global $CFG;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $id     = $this->create_record($course, 'node-first');
        $text   = '<p>x</p>' . $this->get_embed('node-first', 'First', $id, $CFG->wwwroot);
        $this->assertSame($text, $this->backup_text($course, $text));
    }

    /**
     * Function test_backup_leaves_iframe_embed_without_matching_record_untouched
     *
     * @return void
     */
    public function test_backup_leaves_iframe_embed_without_matching_record_untouched(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $other  = $this->create_record($course, 'node-other');
        $missing = $other + 1000;
        $this->assertFalse($DB->record_exists('edusharing', ['id' => $missing]));
        foreach ([$missing, $other] as $resourceid) {
            $text = '<p>x</p>' . $this->get_iframe_embed('node-iframe', 'Iframe', $resourceid, $CFG->wwwroot);
            $this->assertSame($text, $this->backup_text($course, $text));
        }
    }

    /**
     * Function add_ticket
     *
     * @param string $text
     * @param string $position 'first', 'middle' or 'last': where the ticket sits in the query
     * @return string
     */
    private function add_ticket(string $text, string $position): string {
        $ticket = 'ticket=TICKET_abc123';
        if ($position === 'first') {
            return preg_replace('/preview\.php\?/', 'preview.php?' . $ticket . '&amp;', $text, 1);
        }
        if ($position === 'last') {
            return preg_replace('/(preview\.php\?[^"]*)"/', '$1&amp;' . $ticket . '"', $text, 1);
        }
        return preg_replace('/(preview\.php\?[^&]*&amp;[^&]*)&amp;/', '$1&amp;' . $ticket . '&amp;', $text, 1);
    }

    /**
     * Function test_backup_removes_ticket_from_regular_embed
     *
     * @return void
     */
    public function test_backup_removes_ticket_from_regular_embed(): void {
        global $CFG;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $id     = $this->create_record($course, 'node-first');
        $plain  = '<p>x</p>' . $this->get_embed('node-first', 'First', $id, $CFG->wwwroot) . '<p>y</p>';
        foreach (['first', 'middle', 'last'] as $position) {
            $text = $this->add_ticket($plain, $position);
            $this->assertStringContainsString('ticket=TICKET_abc123', $text);
            $this->assertSame($plain, $this->backup_text($course, $text), "Ticket at $position position");
        }
    }

    /**
     * Function test_backup_removes_ticket_from_iframe_embed_and_completes_it
     *
     * @return void
     */
    public function test_backup_removes_ticket_from_iframe_embed_and_completes_it(): void {
        global $CFG;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $id     = $this->create_record($course);
        $text   = $this->add_ticket($this->get_iframe_embed('node-iframe', 'Iframe', $id, $CFG->wwwroot), 'middle');

        $output = $this->backup_text($course, $text);
        $this->assertStringNotContainsString('ticket', $output);
        $elements = $this->get_object_elements($output);
        $this->assertCount(1, $elements);
        parse_str((string)parse_url($this->get_object_url($elements[0]), PHP_URL_QUERY), $params);
        $this->assertSame('Iframe', $params['title']);
        $this->assertSame('ccrep://repo/node-iframe', $params['object_url']);
        $this->assertSame('1.3', $params['window_version']);
        $this->assertSame('node-iframe', $params['nodeId']);
        $this->assertSame('300', $params['width']);
    }

    /**
     * Function test_restore_uses_version_from_completed_backup
     *
     * @return void
     */
    public function test_restore_uses_version_from_completed_backup(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        set_config('application_homerepid', 'other-repo', 'edusharing');
        $user   = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $id     = $this->create_record($course);
        $backup = $this->backup_text(
            $course,
            $this->get_iframe_embed('node-iframe', 'Iframe', $id, $CFG->wwwroot)
        );
        $restored = str_replace($CFG->wwwroot, 'https://other.site', $backup);

        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1], '*', MUST_EXIST);
        $DB->set_field('course_sections', 'summary', $restored, ['id' => $section->id]);
        $DB->delete_records('edusharing', ['id' => $id]);

        $received = [];
        $service  = $this->getMockBuilder(EduSharingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_node_for_user', 'get_ticket', 'get_ticket_for_user', 'create_usage'])
            ->getMock();
        $service->method('get_ticket')->willReturn('ticket');
        $service->method('get_ticket_for_user')->willReturn('ticket');
        $service->method('get_node_for_user')->willReturn(['node' => ['access' => ['CCPublish']]]);
        $service->method('create_usage')->willReturnCallback(
            function (\stdClass $d) use (&$received): \EduSharingApiClient\Usage {
                $received[] = $d;
                return new \EduSharingApiClient\Usage(
                    (string)$d->nodeId,
                    (string)$d->nodeVersion,
                    (string)$d->containerId,
                    (string)$d->resourceId,
                    'usage-' . $d->resourceId
                );
            }
        );
        (new RestoreHelper($service))->convert_inline_options((int)$course->id, (int)$user->id);

        $record = $DB->get_record('edusharing', ['course' => $course->id, 'section_id' => $section->id], '*', MUST_EXIST);
        $this->assertSame('1.3', $record->object_version);
        $this->assertSame('Iframe', $record->name);
        $this->assertSame('ccrep://repo/node-iframe', $record->object_url);
        $this->assertCount(1, $received);
        $this->assertSame('1.3', $received[0]->nodeVersion);
    }

    /**
     * Function get_service_denying
     *
     * @param array $deniednodes node ids the restoring user has no publish right on
     * @return EduSharingService&MockObject
     */
    private function get_service_denying(array $deniednodes): EduSharingService {
        $service = $this->getMockBuilder(EduSharingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_node_for_user', 'get_ticket', 'get_ticket_for_user', 'create_usage'])
            ->getMock();
        $service->method('get_ticket')->willReturn('ticket');
        $service->method('get_ticket_for_user')->willReturn('ticket');
        $service->method('get_node_for_user')->willReturnCallback(fn(string $ticket, string $nodeid): array => [
            'node' => ['access' => in_array($nodeid, $deniednodes, true) ? ['Read'] : ['CCPublish']],
        ]);
        $service->method('create_usage')->willReturnCallback(
            fn(\stdClass $d): \EduSharingApiClient\Usage => new \EduSharingApiClient\Usage(
                (string)$d->nodeId,
                (string)$d->nodeVersion,
                (string)$d->containerId,
                (string)$d->resourceId,
                'usage-' . $d->resourceId
            )
        );
        return $service;
    }

    /**
     * Function test_convert_inline_options_removes_wrapper_of_stripped_object_with_quoted_title
     *
     * @return void
     */
    public function test_convert_inline_options_removes_wrapper_of_stripped_object_with_quoted_title(): void {
        global $DB;
        $this->resetAfterTest();
        $user    = $this->getDataGenerator()->create_user();
        $course  = $this->getDataGenerator()->create_course();
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0], '*', MUST_EXIST);
        $denied  = $this->get_captioned_embed('node-denied', 'Go "Rolling" Video', 301);
        $this->assertStringContainsString('title="Go &quot;Rolling&quot; Video"', $denied);
        $DB->set_field('course_sections', 'summary', '<p>before</p>' . $denied . '<p>after</p>', ['id' => $section->id]);

        (new RestoreHelper($this->get_service_denying(['node-denied'])))->convert_inline_options((int)$course->id, (int)$user->id);

        $this->assertSame('<p>before</p><p>after</p>', $DB->get_field('course_sections', 'summary', ['id' => $section->id]));
    }

    /**
     * Function test_convert_inline_options_keeps_converted_object_sharing_a_wrapper_with_a_stripped_one
     *
     * @return void
     */
    public function test_convert_inline_options_keeps_converted_object_sharing_a_wrapper_with_a_stripped_one(): void {
        global $DB;
        $this->resetAfterTest();
        $user    = $this->getDataGenerator()->create_user();
        $course  = $this->getDataGenerator()->create_course();
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0], '*', MUST_EXIST);
        $denied  = $this->get_embed('node-denied', 'Denied "one"', 401);
        $kept    = $this->get_embed('node-kept', 'Kept', 402);
        // One placeholder holding both objects: strip the wrapper of the denied one and the kept one would go with it.
        $inner = fn(string $embed): string => substr($embed, strpos($embed, '<img'), -strlen('</div>'));
        $wrapper = '<div class="edusharing-placeholder" contenteditable="false" style="">'
            . $inner($denied) . $inner($kept) . '</div>';
        $DB->set_field('course_sections', 'summary', '<p>before</p>' . $wrapper . '<p>after</p>', ['id' => $section->id]);

        (new RestoreHelper($this->get_service_denying(['node-denied'])))->convert_inline_options((int)$course->id, (int)$user->id);

        $summary = $DB->get_field('course_sections', 'summary', ['id' => $section->id]);
        $this->assertStringNotContainsString('node-denied', $summary);
        $this->assertStringNotContainsString('other.site', $summary);
        $elements = $this->get_object_elements($summary);
        $this->assertCount(1, $elements);
        parse_str((string)parse_url($this->get_object_url($elements[0]), PHP_URL_QUERY), $params);
        $where  = ['course' => $course->id, 'object_url' => 'ccrep://repo/node-kept'];
        $record = $DB->get_record('edusharing', $where, '*', MUST_EXIST);
        $this->assertSame((string)$record->id, $params['resourceId']);
        $this->assertFalse($DB->record_exists('edusharing', ['course' => $course->id, 'object_url' => 'ccrep://repo/node-denied']));
        $this->assertStringContainsString('<p>before</p>', $summary);
        $this->assertStringContainsString('<p>after</p>', $summary);
    }

    /**
     * Function test_backup_removes_ticket_from_embed_with_quoted_title
     *
     * @return void
     */
    public function test_backup_removes_ticket_from_embed_with_quoted_title(): void {
        global $CFG;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $id     = $this->create_record($course, 'node-first');
        $plain  = '<p>x</p>' . $this->get_embed('node-first', 'Go "Rolling" Video', $id, $CFG->wwwroot) . '<p>y</p>';
        $this->assertStringContainsString('title="Go &quot;Rolling&quot; Video"', $plain);
        foreach (['first', 'middle', 'last'] as $position) {
            $text = $this->add_ticket($plain, $position);
            $this->assertStringContainsString('ticket=TICKET_abc123', $text);
            $output = $this->backup_text($course, $text);
            // The rewritten element keeps libxml's quoting, so compare its url instead of the whole text.
            $this->assertStringNotContainsString('ticket', $output, "Ticket at $position position");
            $elements = $this->get_object_elements($output);
            $this->assertCount(1, $elements);
            $this->assertSame($this->get_object_url($this->get_object_elements($plain)[0]), $this->get_object_url($elements[0]));
            $this->assertSame('Go "Rolling" Video', $elements[0]->getAttribute('title'));
        }
    }
}
