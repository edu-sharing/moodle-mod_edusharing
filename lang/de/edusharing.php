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

// phpcs:ignoreFile

/**
 * German strings for edu-sharing
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['searchrec'] = 'Suche im {$a} Repositorium ...';
$string['uploadrec'] = 'Hochladen in das {$a} Repositorium ...';
$string['pagewindow'] = 'Anzeige im gleichen Fenster';
$string['newwindow'] = 'Anzeige in neuem Fenster';
$string['display'] = 'Anzeige';

// Modulename seems to be used in admin-panels.
// Pluginname seems to be used in course-view.
try {
    $string['modulename'] = get_config('edusharing', 'application_appname') . ' ' . get_config('edusharing', 'module_type');
    $string['modulename_help'] = get_config('edusharing', 'info_text');
} catch (Exception $exception) {
    $string['modulename'] = '';
    $string['modulename_help'] = '';
    unset($exception);
}

$string['pluginname'] = 'edu-sharing Objekt';
$string['modulenameplural'] = 'edu-sharing';
$string['edusharing'] = 'edu-sharing';
$string['pluginadministration'] = 'edu-sharing';
$string['edusharingname'] = 'Titel';
$string['description'] = 'Beschreibung';

$string['edusharing:wysiwygvisibility'] = 'Einfügen, Bearbeiten und entfernend von Edu-Sharing-Inhalten in WYSIWYG-Editoren';

$string['object_url_fieldset'] = '{$a} Lernobjekt';
$string['object_url'] = 'Link zum Objekt';
$string['object_url_help'] = 'Bitte nutzen Sie die Buttons, um ein Objekt zu suchen oder hochzuladen. Die ID des Objekts wird dann automatisch eingefügt.';
$string['object_title'] = 'Ausgewähltes Objekt';
$string['object_title_help'] = 'Bitte mit dem unterem Button ein Objekt auswählen.';
$string['object_title_help_chooser'] = 'Bitte mit einem der unterem Buttons ein Objekt auswählen.';

$string['object_version_fieldset'] = 'Objekt-Versionierung';
$string['object_version'] = 'Zeige ...';
$string['object_version_help'] = 'Hier könnnen Sie festlegen, welche Version dieses Objekts angezeigt werden soll.';
$string['object_version_use_exact'] = '... genau diese Version.';
$string['object_version_use_latest'] = '... die neueste Version.';

$string['object_display_fieldset'] = 'Objekt Anzeigeoptionen';
$string['object_display_fieldset_help'] = 'Verschiedene Optionen zur Anzeige des Objekts.';

$string['force_download'] = 'Erzwinge download';
$string['force_download_help'] = 'Erzwingt das herunterladen des Objektes.';

$string['show_course_blocks'] = 'Zeige Kurs-Blöcke';
$string['show_course_blocks_help'] = '';

$string['window_allow_resize'] = 'Erlaube vergrößern/verkleinern';
$string['window_allow_resize_help'] = 'Erlaubt das Anzeigefenster zu verkleinern/vergrößern.';

$string['window_allow_scroll'] = 'Erlaube scrollen';
$string['window_allow_scroll_help'] = 'Erlaubt das scrollen im Anzeigefenster.';

$string['show_directory_links'] = 'Zeige Verzeichnis-links';
$string['show_directory_links_help'] = 'Zeige Verzeichnis-Verweise an.';

$string['show_menu_bar'] = 'Zeige Menu-bar';
$string['show_menu_bar_help'] = 'Zeigt das Menu an.';

$string['show_location_bar'] = 'Zeige Location-bar';
$string['show_location_bar_help'] = 'Zeigt die Adressleiste im Zeilfenster.';

$string['show_tool_bar'] = 'Zeige Toolbar';
$string['show_tool_bar_help'] = 'Zeigt die Toolbar im Zielfenster.';

$string['show_status_bar'] = 'Zeige Status-Bar';
$string['show_status_bar_help'] = 'Zeigt den Status-Balken im Zeilfenster.';

$string['window_width'] = 'Anzeige-Breite';
$string['window_width_help'] = 'Breite des Zielfensters in Pixeln.';

$string['window_height'] = 'Anzeige-Höhe';
$string['window_height_help'] = 'Höhe des Zielfensters in Pixeln.';

// General error message.
$string['exc_MESSAGE'] = 'Ein Fehler ist bei der Benutzung des edu-sharing.net Netzwerkes aufgetreten.';

// Beautiful exceptions.
$string['exc_SENDACTIVATIONLINK_SUCCESS'] = 'Aktivierungslink erfolgreich versendet.';
$string['exc_APPLICATIONACCESS_NOT_ACTIVATED_BY_USER'] = 'Zugriff nicht aktiviert.';
$string['exc_COULD_NOT_CONNECT_TO_HOST'] = 'Verbindung zu Host fehlgeschlagen.';
$string['exc_INTEGRITY_VIOLATION'] = 'Integrität verletzt.';
$string['exc_INVALID_APPLICATION'] = 'Ungültige application.';
$string['exc_ERROR_FETCHING_HTTP_HEADERS'] = 'Fehler beim lesen von HTTP-Headern.';
$string['exc_NODE_DOES_NOT_EXIST'] = 'Das Objekt existiert nicht mehr.';
$string['exc_ACCESS_DENIED'] = 'Zugriff verweigert.';
$string['exc_NO_PERMISSION'] = 'Zugriffsrechte nicht ausreichend.';
$string['exc_UNKNOWN_ERROR'] = 'Unbekannter Fehler.';
$string['exc_NO_PUBLISH_RIGHTS'] = 'Ein edu-sharing-Inhalt konnte aufgrund fehlender Rechte des aktuellen Nutzers nicht eingebunden werden und wird übersprungen';

// Metadata.
$string['conf_linktext'] = 'Moodle mit dem Heimat-Repositorium verbinden:';
$string['conf_btntext'] = 'Verbinden';
$string['conf_hinttext'] = 'Dies öffnet ein neues Fenster in dem die Metadaten vom Repo geladen werden können und das Plugin beim Repo registriert werden kann.';
$string['filter_not_authorized'] = 'Sie sind nicht authorisiert auf den angefragten Inhalt zuzugreifen.';
$string['currentVersion'] = 'Aktuelle Plugin Version';
$string['conf_versiontext'] = 'Version:';
$string['connectToHomeRepository'] = 'Mit Heimat-Repositorium verbinden';
$string['appProperties'] = 'Konfiguration Moodle-Plugin';
$string['homerepProperties'] = 'Konfiguration Heim-Repositorium';
$string['authparameters'] = 'Authentifizierungsparameter';
$string['guestProperties'] = 'Konfiguration Gäste';
$string['brandingSettings'] = 'UI Einstellungen';
$string['brandingInfo'] = 'Passe das Aussehen vom edu-sharing Plugin an.';
$string['appiconDescr'] = 'Das "appIcon" ersetzt das edu-sharing Icon. Auch beim Atto-Editor.<br>(Quadratisches Seitenverhältnis)';
$string['info_textDescr'] = 'Der Hilfstext beim einbinden des edu-sharing Moduls.';
$string['atto_hintDescr'] = 'Der Hilfstext beim edu-sharing Atto Popup.';
$string['repo_targetDescr'] = 'Legt die Einstiegsseite beim Repositorium fest.';
$string['enable_repo_target_chooser'] = 'Einstiegsseite vom Nutzer auswählbar.';
$string['enable_repo_target_chooser_help'] = 'Ist diese Option aktiviert, so kann der Nutzer in der GUI auswählen, welche Einstiegsseite im Repositorium angezeigt werden soll.';
$string['repoSearch'] = 'Suche';
$string['repoCollection'] = 'Sammlungen';
$string['repoWorkspace'] = 'Eigene Dateien';

$string['save'] = 'Änderungen sichern';
$string['emptyForDefault'] = 'leer für';

// Auth parameters.
$string['convey_global_groups_yes'] = 'Globale Gruppen übermitteln';
$string['convey_global_groups_no'] = 'Globale Gruppen nicht übermitteln';
$string['send_additional_auth'] = 'Übermittlung zusätzlicher Authentifizierungsinfos';
$string['send_additional_auth_help'] = 'Ist diese Option aktiviert, werden bei der app auth Anfrage Vor- und Nachname sowie E-Mail-Addresse übermittelt.';
$string['auth_suffix'] = 'Authentifizierungssuffix';
$string['auth_suffix_help'] = 'Dieses Suffix wird an den übermittelten Authentifzierungsstring angehängt';
$string['obfuscate_auth_param'] = 'Pseudonymisierung der Nutzer ID';
$string['obfuscate_auth_param_help'] = 'Ist diese Option aktiviert, werden die Moodle-Nutzer gegenüber dem Edu-Sharing Repo pseudonymisiert.';
$string['require_login_for_metadata'] = 'Login für Metadatenabfrage nötig';
$string['require_login_for_metadata_help'] = 'Ist diese Option aktiviert, ist ein Login nötig, um die App-Metadaten abzufragen';
$string['use_as_idp'] = 'Moodle als IDP für Edu-Sharing verwenden';
$string['use_as_idp_help'] = 'Ist diese Funktion aktiviert, kann Moodle zum Login bei Edu-Sharing verwendet werden.';

$string['error_missing_authwsdl'] = 'Parameter "authenticationwebservice_wsdl" wurde nicht konfiguriert.';
$string['error_authservice_not_reachable'] = 'ist nicht erreichbar.';
$string['error_invalid_ticket'] = 'Ungültiges edu-sharing Ticket.';
$string['error_auth_failed'] = 'edu-sharing Authentifizierung ist fehlgeschlagen.';
$string['error_load_course'] = 'Kurs kann nicht aus der Datenbank geladen werden';
$string['error_load_resource'] = 'Ressource kann nicht aus der Datenbank geladen werden.';
$string['error_get_object_id_from_url'] = 'Objekt id kann nicht ermittelt werden.';
$string['error_get_repository_id_from_url'] = 'Repositorium id kann nicht ermittelt werden.';
$string['error_detect_course'] = 'Kurs id kann nicht ermittelt werden.';
$string['error_loading_memento'] = 'Fehler beim Laden des temporären Objektes.';
$string['error_set_soap_headers'] = 'SOAP-Header konnten nicht gesetzt werden - ';
$string['error_get_app_properties'] = 'Pluginkonfiguration konnte nicht geladen werden.';
$string['error_encrypt_with_repo_public'] = 'Daten konnten nicht verschlüsselt werden.';
$string['error_missing_rights_on_restore'] = 'Dieses Edu-Sharing-Objekt fehlt aufgrund mangelnder Nutzerrechter bei der Kurswiederherstellung.';
$string['error_unexpected_on_restore'] = 'Dieses Edu-Sharing-Objekt fehlt aufgrund eines unerwarteten Fehlers bei der Kurswiederherstellung.';
$string['error_parsing_on_restore'] = 'Dieses Edu-Sharing-Objekt fehlt aufgrund eines nicht parsebaren Objekts bei der Kurswiederherstellung.';
$string['error_invalid_config'] = 'Edu-Sharing ist nicht korrekt konfiguriert (kein Ticket konnte abgerufen werden). Repository Auswahl ist derzeit nicht verfügbar. Bitte überprüfen Sie die Pluginkonfiguration.';
