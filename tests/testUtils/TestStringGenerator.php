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

/**
 * Class TestStringGenerator
 *
 * Get your strings while they're hot!
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class TestStringGenerator {
    /**
     * Function getattoteststring
     *
     * @return String
     */
    public static function getattoteststring(): String {
        return '<p dir="ltr" style="text-align: left;"><a class="edusharing_atto"'
            . ' style="" href="http://test.de/lib/editor/atto/plugins/edusharing/preview.php?resourceId=6&amp;'
            . 'nodeId=0fc4e8bb-3837-474b-a975-255ee9de65ee&amp;storeProtocol=workspace&amp;' .
            'storeId=SpacesStore&amp;dontcache=1690546402658&amp;caption=&amp;object_url=ccrep%3A%2F%2F' .
            'enterprise-docker-maven-fixes-8-0%2F0fc4e8bb-3837-474b-a975-255ee9de65ee&amp;mediatype=file-image' .
            '&amp;mimetype=image%2Fjpeg&amp;window_version=0&amp;title=Khinkali_551.jpg&amp;width=1000&amp;'
            . 'height=582" contenteditable="false"></a></p><p dir="ltr" style="text-align: left;">' .
            '<a class="edusharing_atto" style="" '
            . 'href="http://test.de/lib/editor/atto/plugins/edusharing/preview.php?resourceId=6&amp;' .
            'nodeId=0fc4e8bb-3837-474b-a975-255ee9de65ee&amp;storeProtocol=workspace&amp;storeId=SpacesStore&amp;'
            .'dontcache=1690546402658&amp;caption=&amp;object_url=ccrep%3A%2F%2Fenterprise-docker-maven-fixes-8-0'
            . '%2F0fc4e8bb-3837-474b-a975-255ee9de65ee&amp;mediatype=file-image&amp;mimetype=image%2Fjpeg&amp;'
            . 'window_version=0&amp;title=Khinkali_551.jpg&amp;width=1000&amp;height=582" contenteditable="false">'
            . '</a></p><p></p><div><img alt="chernihiv.jpg" width="275" height="183" class="edusharing_atto" '
            . 'style="" title="chernihiv.jpg" contenteditable="false" src="http://test.de/lib/editor/atto/plugins/'
            . 'edusharing/preview.php?resourceId=9&amp;nodeId=6d351b14-f313-43a6-b107-86615d6dcb37&amp;'.
             'storeProtocol=workspace&amp;storeId=SpacesStore&amp;dontcache=1691051530812&amp;caption=&amp;'
            . 'object_url=ccrep%3A%2F%2Fenterprise-docker-maven-fixes-8-0%2F6d351b14-f313-43a6-b107-86615d6dcb37'
            . '&amp;mediatype=file-image&amp;mimetype=image%2Fjpeg&amp;window_version=0&amp;title=chernihiv.jpg'
            . '&amp;width=275&amp;height=183"><p></p>Mehr text hier</div><div><br></div><div>'
            . '<img alt="chernihiv.jpg" class="edusharing_atto" style="" title="chernihiv.jpg" '
            . 'src="http://test.de/lib/editor/atto/plugins/edusharing/preview.php?resourceId=7&amp;'
            . 'nodeId=6d351b14-f313-43a6-b107-86615d6dcb37&amp;storeProtocol=workspace&amp;storeId=SpacesStore&amp;'
            . 'dontcache=1690804225011&amp;caption=&amp;object_url=ccrep%3A%2F%2Fenterprise-docker-maven-fixes'
            .'-8-0%2F6d351b14-f313-43a6-b107-86615d6dcb37&amp;mediatype=file-image&amp;mimetype=image%2Fjpeg&amp;'
            . 'window_version=0&amp;title=chernihiv.jpg&amp;width=275&amp;height=183" width="275" height="183" '
            . 'contenteditable="false"><p></p><br></div><p></p>';
    }
}
