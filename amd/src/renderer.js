import Config from 'core/config';
import {getCurrentUser, getSecuredNode, sendXapiStatement} from "./repository";
import {clampCustomHeight} from "./utils";

export const init = async(repoUrl, contextId, useServiceWorker) => {
    const element = document.getElementById('edusharing_view');
    window.addEventListener('message', async(e) => {
        if (!e.data || e.data.type !== 'H5P_XAPI') {
            return;
        }
        const statement = e.data.statement;
        const moodleUser = await getCurrentUser().catch(error => {
            window.console.error(error);
        });
        statement.actor.mbox = `mailto:${moodleUser.email}`;
        const originalId = statement?.object?.id;
        const originalUrl = new URL(originalId, window.location.href);
        const subContentId = originalUrl.searchParams.get('subContentId');
        const customUrl = new URL(`${Config.wwwroot}/mod/edusharing/xapi`);
        customUrl.searchParams.set('id', String(contextId));
        if (subContentId) {
            customUrl.searchParams.set('subContentId', subContentId);
        }
        statement.object.id = customUrl.toString();
        const ajaxParams = {
            component: 'mod_edusharing',
            requestjson: JSON.stringify(statement)
        };
        sendXapiStatement(ajaxParams);
    });
    await renderObject(element, repoUrl, useServiceWorker);
};

/**
 * @param {Element} element
 * @param {string} repoUrl
 * @param {boolean} useServiceWorker
 */
export const renderObject = async(element, repoUrl, useServiceWorker) => {
    if (!element.parentElement) {
        return;
    }
    const width = element.getAttribute('data-width');
    const nodeId = element.getAttribute('data-node');
    const containerId = element.getAttribute('data-container');
    const version = element.getAttribute('data-version');
    const usage = element.getAttribute('data-usage');
    const resourceId = element.getAttribute('data-resource');

    const resourceUrl = `${Config.wwwroot}/mod/edusharing/contentRedirect.php?` +
        `nodeId=${nodeId}&nodeVersion=${version}&usageId=${usage}&resourceId=${resourceId}&containerId=${containerId}`;

    const ajaxParams = {
        eduSecuredNodeStructure: {
            nodeId: nodeId,
            resourceId: resourceId,
            version: version,
            containerId: containerId,
            usageId: usage,
        }
    };

    let response;
    try {
        response = await getSecuredNode(ajaxParams);
    } catch (error) {
        window.console.error(error);
        return;
    }
    if (!response) {
        window.console.error(`No secured node returned for edu-sharing object ${nodeId}.`);
        return;
    }

    // The parent is resolved only now, and only if the object is still part of the page.
    // Course formats that load their content by ajax - format_tiles for one - throw a whole
    // section away and replace it with a freshly filtered copy, which can happen while the
    // secured node above is still on its way. Everything below writes into the parent, so a
    // parent looked up before the request would by then belong to a detached tree: the write
    // succeeds, nothing is raised, and the copy that is actually on screen keeps its spinner
    // forever. Dropping the render is safe - the new copy carries a new placeholder, and that
    // one is observed anew, see filter_edusharing/edu.
    const wrapper = element.isConnected ? element.parentElement : null;
    if (!wrapper) {
        return;
    }

    const customWidth = response.customWidth;
    if (customWidth) {
        if (customWidth !== 'none') {
            wrapper.style.width = customWidth;
        }
    } else {
        wrapper.style.width = width ? (width + "px") : '';
    }
    // Objects rendered at full width take the height the user has chosen; which objects those
    // are is decided server side, see mod_edusharing\EduSharingService::uses_custom_height.
    // Where no height was chosen - the activity view, or an object inserted before the choice
    // existed - the rendering service keeps deciding.
    const fixedHeight = element.getAttribute('data-fixed-height');
    const useCustomHeight = Boolean(response.useCustomHeight) && Boolean(fixedHeight);
    if (useCustomHeight) {
        wrapper.style.height = clampCustomHeight(fixedHeight) + 'px';
    }

    const serviceWorkerPhp = `${Config.wwwroot}/mod/edusharing/getServiceWorker.php`;
    if (useServiceWorker && 'serviceWorker' in navigator) {
        await navigator.serviceWorker.register(serviceWorkerPhp, {
            scope: '/'
        });
        await navigator.serviceWorker.ready;
    }

    const renderComponent = document.createElement('edu-sharing-render');
    renderComponent.classList.add('edu-sharing-render');
    renderComponent.encoded_node = response.securedNode;
    renderComponent.signature = response.signature;
    renderComponent.jwt = response.jwt;
    renderComponent.render_url = response.renderingBaseUrl;
    renderComponent.service_worker_url = serviceWorkerPhp;
    renderComponent.activate_service_worker = useServiceWorker;
    renderComponent.assets_url = repoUrl + '/web-components/rendering-service/assets';
    renderComponent.resource_url = resourceUrl;
    renderComponent.preview_url = response.previewUrl;
    renderComponent.signature_algorithm = response.signingAlgorithm;
    if (useCustomHeight) {
        renderComponent.style.display = 'block';
        renderComponent.style.height = '100%';
        renderComponent.component_height = fixedHeight;
        renderComponent.footer_height = 60;
    }
    wrapper.innerHTML = "";
    wrapper.appendChild(renderComponent);
};
