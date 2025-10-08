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
 * French strings for edu-sharing
 *
 * @package    mod_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['searchrec'] = 'Recherche dans le {$a} repository ...';
$string['uploadrec'] = 'Télécharger dans le {$a} repository ...';
$string['pagewindow'] = 'Afficher dans la même fenêtre';
$string['newwindow'] = 'Afficher dans une nouvelle fenêtre';
$string['display'] = 'Affichage';

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

$string['pluginname'] = 'objet edu-sharing';
$string['modulenameplural'] = 'edu-sharing';
$string['edusharing'] = 'edu-sharing';
$string['pluginadministration'] = 'edu-sharing';
$string['edusharingname'] = 'titre';
$string['description'] = 'description d\'apprentissage';
$string['object_url'] = 'Lien vers l\'objet';
$string['object_url_help'] = 'Veuillez utiliser les boutons pour rechercher ou télécharger un objet. L\'ID de l\'objet sera alors automatiquement inséré.';
$string['object_title'] = 'Objet choisi';

$string['edusharing:wysiwygvisibility'] = 'Insertion, modification et suppression d\`objets edu-sharing dans les éditeurs WYSIWYG';

$string['object_url_fieldset'] = '{$a} Objet';
$string['object_title_help'] = 'Veuillez sélectionner un objet avec le bouton ci-dessous.';
$string['object_title_help_chooser'] = 'Veuillez sélectionner un objet avec un des boutons ci-dessous.';

$string['object_version_fieldset'] = 'Versionnement des objets';
$string['object_version'] = 'Montre ...';
$string['object_version_help'] = 'Vous pouvez définir ici quelle version de cet objet doit être affichée.';
$string['object_version_use_exact'] = '... exactement cette version.';
$string['object_version_use_latest'] = '...la version la plus récente.';

$string['object_display_fieldset'] = 'Options d\'affichage de l\'objet ';
$string['object_display_fieldset_help'] = 'Différentes options d\'affichage de l\'objet.';

$string['force_download'] = 'Forcer le téléchargement';
$string['force_download_help'] = 'Force le téléchargement de l\'objet.';

$string['show_course_blocks'] = 'Montrer les blocs de cours';
$string['show_course_blocks_help'] = '';

$string['window_allow_resize'] = 'Permettre d\'agrandir/réduire';
$string['window_allow_resize_help'] = '	Permet de réduire/agrandir la fenêtre d\'affichage.';

$string['window_allow_scroll'] = 'Permettre de faire défiler';
$string['window_allow_scroll_help'] = 'permet de faire défiler la fenêtre d\'affichage.';

$string['show_directory_links'] = 'Afficher les liens des dossiers.';
$string['show_directory_links_help'] = 'Affiche les références des dossiers.';

$string['show_menu_bar'] = 'Afficher la barre de menu';
$string['show_menu_bar_help'] = 'Affiche le menu.';

$string['show_location_bar'] = 'Afficher la barre de localisation';
$string['show_location_bar_help'] = 'Affiche la barre d\'adresse dans la fenêtre de destination.';

$string['show_tool_bar'] = 'Afficher la barre d\'outils';
$string['show_tool_bar_help'] = 'Affiche la barre d\'outils dans la fenêtre de destination..';

$string['show_status_bar'] = 'Afficher la barre de statut';
$string['show_status_bar_help'] = 'Affiche la barre d\'état dans la fenêtre de destination.';

$string['window_width'] = 'Largeur d\'affichage';
$string['window_width_help'] = 'Largeur de la fenêtre de destination en pixels.';

$string['window_height'] = 'Hauteur d\'affichage';
$string['window_height_help'] = 'Hauteur de la fenêtre de destination en pixels.';

// General error message.
$string['exc_MESSAGE'] = 'Une erreur s\'est produite lors de l\'utilisation du réseau edu-sharing.net.';

// Beautiful exceptions.
$string['exc_SENDACTIVATIONLINK_SUCCESS'] = 'Lien d\'activation envoyé avec succès.';
$string['exc_APPLICATIONACCESS_NOT_ACTIVATED_BY_USER'] = 'Accès non activé.';
$string['exc_COULD_NOT_CONNECT_TO_HOST'] = 'Échec de la connexion à l\'hôte.';
$string['exc_INTEGRITY_VIOLATION'] = 'Problème d\'intégrité.';
$string['exc_INVALID_APPLICATION'] = 'Application non valide.';
$string['exc_ERROR_FETCHING_HTTP_HEADERS'] = 'Erreur de lecture d\'un en-tête HTTP.';
$string['exc_NODE_DOES_NOT_EXIST'] = 'L\'objet n\'existe plus.';
$string['exc_ACCESS_DENIED'] = 'Accès refusé.';
$string['exc_NO_PERMISSION'] = 'Droits d\'accès insuffisants.';
$string['exc_UNKNOWN_ERROR'] = 'Erreur inconnue.';
$string['exc_NO_PUBLISH_RIGHTS'] = 'Un objet edu-sharing n\'a pas pu être intégré en raison de l\'absence de droits de l\'utilisateur actuel et sera ignoré.';

// Metadata.
$string['conf_linktext'] = 'Connecter Moodle au repository d\'origine:';
$string['conf_btntext'] = 'Connecter';
$string['conf_hinttext'] = 'Cela ouvre une nouvelle fenêtre dans laquelle les métadonnées peuvent être chargées depuis le repo et le plugin peut être enregistré auprès du repo.';
$string['filter_not_authorized'] = 'Vous n\'êtes pas autorisé à accéder à l\'objet demandé.';
$string['currentVersion'] = 'Version actuelle du plugin';
$string['conf_versiontext'] = 'Version:';
$string['connectToHomeRepository'] = 'Se connecter au repository d\'origine';
$string['appProperties'] = 'Configuration du plugin Moodle';
$string['homerepProperties'] = 'Configuration du repository d\'origine';
$string['authparameters'] = 'Paramètres d\'authentification';
$string['guestProperties'] = 'Configuration des comptes invités';
$string['brandingSettings'] = 'Paramètres de l\'interface utilisateur';
$string['brandingInfo'] = 'Personnaliser l\'apparence du plugin edu-sharing.';
$string['appiconDescr'] = 'L\' appIcon remplace le symbole du edu-sharing. Aussi pour l\'éditeur Atto.<br>(Rapport d\'aspect carré)';
$string['info_textDescr'] = 'Le texte d\'aide lors de l\'intégration du module edu-sharing.';
$string['atto_hintDescr'] = 'Le texte d\'aide du popup edu-sharing Atto';
$string['repo_targetDescr'] = 'Définit la page d\'accueil du repository.';
$string['enable_repo_target_chooser'] = 'Page d\'accueil sélectionnable par l\'utilisateur';
$string['enable_repo_target_chooser_help'] = 'Si cette option est activée, l\'utilisateur peut sélectionner dans l\'interface graphique quelle page d\'accueil doit être affichée dans le repository.';
$string['repoSearch'] = 'Recherche';
$string['repoCollection'] = 'Collections';
$string['repoWorkspace'] = 'Mes fichiers';

$string['save'] = 'Sauvegarder les modifications';
$string['emptyForDefault'] = 'vide pour';

// Auth parameters.
$string['convey_global_groups_yes'] = 'Transmettre des groupes globaux';
$string['convey_global_groups_no'] = 'Ne pas transmettre les groupes globaux';
$string['send_additional_auth'] = 'Transmission d\'informations d\'authentification supplémentaires';
$string['send_additional_auth_help'] = 'Si cette option est activée, le prénom, le nom et l\'adresse e-mail sont transmis lors de la demande app auth..';
$string['auth_suffix'] = 'Suffixe d\'authentification';
$string['auth_suffix_help'] = 'Ce suffixe est ajouté à la chaîne d\'authentification transmise.';
$string['obfuscate_auth_param'] = 'Pseudonymisation de l\'ID utilisateur';
$string['obfuscate_auth_param_help'] = 'Si cette option est activée, les utilisateurs de Moodle sont pseudonymisés par rapport à l\'edu-sharing repo..';
$string['require_login_for_metadata'] = 'Connexion requise pour la requête de métadonnées';
$string['require_login_for_metadata_help'] = 'Si cette option est activée, une connexion est requise pour interroger les métadonnées de l\'application';
$string['use_as_idp'] = 'Utiliser Moodle comme IDP pour edu-sharing';
$string['use_as_idp_help'] = 'Si cette option est activée, Moodle peut être utiliser comme IDP pour edu-sharing.';

$string['error_missing_authwsdl'] = 'Le paramètre "authenticationwebservice_wsdl" n\'a pas été configuré.';
$string['error_authservice_not_reachable'] = 'n\'est pas accessible.';
$string['error_invalid_ticket'] = 'Ticket edu-sharing non valide.';
$string['error_auth_failed'] = 'L\'authentification edu-sharing a échoué.';
$string['error_load_course'] = 'Impossible de charger le cours depuis la base de données';
$string['error_load_resource'] = 'Impossible de charger la ressource depuis la base de données.';
$string['error_get_object_id_from_url'] = 'L\'ID de l\'objet ne peut pas être déterminé.';
$string['error_get_repository_id_from_url'] = 'L\'ID du référentiel ne peut pas être déterminé.';
$string['error_detect_course'] = 'L\'identifiant du cours ne peut pas être déterminé.';
$string['error_loading_memento'] = 'Erreur lors du chargement de l\'objet temporaire.';
$string['error_set_soap_headers'] = 'Les en-têtes SOAP n\'ont pas pu être définis - ';
$string['error_get_app_properties'] = 'La configuration du plugin n\'a pas pu être chargée.';
$string['error_encrypt_with_repo_public'] = 'Les données n\'ont pas pu être chiffrées.';
$string['error_missing_rights_on_restore'] = 'Cet objet Edu-Sharing est manquant suite à un manque de droits d\'utilisateur lors de la restauration du cours.';
$string['error_unexpected_on_restore'] = 'Cet élément Edu-Sharing est manquant en raison d\'une erreur inattendue de restauration de cours.';
$string['error_parsing_on_restore'] = 'Cet objet Edu-Sharing est absent de la récupération du cours en raison d\'un objet non analysable.';
