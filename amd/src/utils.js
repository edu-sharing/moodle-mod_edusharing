/**
 * Mimetypes of objects which are rendered at a fixed width of 100% and at a height chosen
 * by the user.
 *
 * Keep in sync with mod_edusharing\EduSharingService::CUSTOM_HEIGHT_MIMETYPES.
 *
 * @type {string[]}
 */
export const CUSTOM_HEIGHT_MIMETYPES = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.oasis.opendocument.text',
    'application/vnd.oasis.opendocument.presentation',
    'application/vnd.oasis.opendocument.spreadsheet',
    'application/rtf',
    'application/vnd.oasis.opendocument.text-template',
    'text/plain'
];

/**
 * Node properties that mark an object as serlo. Serlo objects are rendered like pdf documents.
 *
 * ccm:ccressourcetype is what the repository actually delivers for them; ccm:replicationsource
 * is checked as well, because that is what the rendering service identifies serlo by.
 *
 * Keep in sync with mod_edusharing\EduSharingService::SERLO_PROPERTIES.
 *
 * @type {string[]}
 */
export const SERLO_PROPERTIES = ['ccm:ccressourcetype', 'ccm:replicationsource'];

/**
 * Property value marking a serlo object.
 *
 * @type {string}
 */
export const SERLO_SOURCE = 'serlo';

/**
 * Node aspect marking an lti 1.3 tool object. Those are rendered like pdf documents too.
 *
 * Keep in sync with mod_edusharing\EduSharingService::LTI_TOOL_ASPECT.
 *
 * @type {string}
 */
export const LTI_TOOL_ASPECT = 'ccm:ltitool_node';

/**
 * Types of remote repositories whose objects are rendered like pdf documents as well.
 *
 * Unlike serlo, these are not recognisable by a node property: their objects come from a
 * repository edu-sharing connects to as a whole, and that is what the rendering service
 * identifies them by.
 *
 * Keep in sync with mod_edusharing\EduSharingService::CUSTOM_HEIGHT_REPOSITORY_TYPES.
 *
 * @type {string[]}
 */
export const CUSTOM_HEIGHT_REPOSITORY_TYPES = ['learningapps', 'brockhaus'];

export const CUSTOM_HEIGHT_MIN = 300;
export const CUSTOM_HEIGHT_MAX = 1200;
export const CUSTOM_HEIGHT_DEFAULT = 600;

export const validateOrigin = (origin, configuredUrl) => {
    try {
        const originUrl = new URL(origin);
        const configUrl = new URL(configuredUrl);
        return originUrl.protocol === configUrl.protocol && originUrl.hostname === configUrl.hostname;
    } catch (e) {
        return false;
    }
};

/**
 * Restricts a height to the range the user is allowed to choose from.
 *
 * Anything unusable falls back to the default height instead of to a broken layout.
 *
 * @param {string|number|null} value
 * @returns {number}
 */
export const clampCustomHeight = (value) => {
    const height = parseInt(value, 10);
    if (isNaN(height)) {
        return CUSTOM_HEIGHT_DEFAULT;
    }
    return Math.min(CUSTOM_HEIGHT_MAX, Math.max(CUSTOM_HEIGHT_MIN, height));
};

/**
 * Whether the given mimetype is rendered at full width with a user defined height.
 *
 * @param {string|null} mimeType
 * @returns {boolean}
 */
export const isCustomHeightMimeType = (mimeType) => CUSTOM_HEIGHT_MIMETYPES.includes(mimeType);

/**
 * Whether the given repository node is rendered at full width with a user defined height.
 *
 * Pdf-like documents, serlo objects, lti 1.3 tool objects and objects from a learningapps or
 * brockhaus repository are treated this way. Mirrors
 * mod_edusharing\EduSharingService::uses_custom_height for the repository nodes the editor
 * plugins receive from the repository picker.
 *
 * @param {object} node
 * @returns {boolean}
 */
export const usesCustomHeight = (node) => isCustomHeightMimeType(node.mimetype)
    || CUSTOM_HEIGHT_REPOSITORY_TYPES.includes(getRemoteRepositoryType(node))
    || isSerloNode(node)
    || hasAspect(node, LTI_TOOL_ASPECT);

/**
 * The type of the remote repository a node was fetched from, lower cased and trimmed for
 * comparison. Empty for nodes of the connected repository itself.
 *
 * @param {object} node
 * @returns {string}
 */
const getRemoteRepositoryType = (node) => {
    const type = node.remote?.repository?.repositoryType;
    return typeof type === 'string' ? type.trim().toLowerCase() : '';
};

/**
 * Whether the given repository node carries the given aspect.
 *
 * Aspects are exact qualified names, so they are compared as a whole - unlike the property
 * values in isSerloNode, which have variants to cover.
 *
 * @param {object} node
 * @param {string} aspect
 * @returns {boolean}
 */
const hasAspect = (node, aspect) => {
    const aspects = node.aspects ?? [];
    const values = Array.isArray(aspects) ? aspects : [aspects];
    return values.some(entry => typeof entry === 'string'
        && entry.trim().toLowerCase() === aspect.toLowerCase());
};

/**
 * Whether the given repository node is a serlo object.
 *
 * Properties come as string arrays and may carry more than one value, so every entry is looked
 * at; a plain string is tolerated too. Values are matched by prefix, so variants such as
 * 'serlo_spider' count as serlo as well.
 *
 * @param {object} node
 * @returns {boolean}
 */
const isSerloNode = (node) => SERLO_PROPERTIES.some(property => {
    const value = node.properties?.[property] ?? [];
    const values = Array.isArray(value) ? value : [value];
    return values.some(entry => typeof entry === 'string'
        && entry.trim().toLowerCase().startsWith(SERLO_SOURCE));
});
