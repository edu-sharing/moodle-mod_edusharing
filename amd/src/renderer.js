import Config from 'core/config';
import {getCurrentUser, getSecuredNode, sendXapiStatement} from "./repository";

export const init = async(repoUrl) => {
    const element = document.getElementById('edusharing_view');
    window.addEventListener('message', async(e) => {
        if (!e.data || e.data.type !== 'H5P_XAPI') {
            return;
        }
        const moodleUser = await getCurrentUser().catch(error => {
            window.console.error(error);
        });
        const statement = e.data.statement;
        statement.object.id = window.location.href;
        statement.actor.mbox = `mailto:${moodleUser.email}`;
        const ajaxParams = {
            component: 'mod_edusharing',
            requestjson: JSON.stringify(statement)
        };
        sendXapiStatement(ajaxParams);
    });
    await renderObject(element, repoUrl);
};

/**
 * @param {Element} element
 * @param {string} repoUrl
 */
export const renderObject = async(element, repoUrl) => {
    const wrapper = element.parentElement;
    if (!wrapper) {
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
        }
    };

    const response = await getSecuredNode(ajaxParams).catch(error => {
        window.console.error(error);
    });

    const customWidth = response.customWidth;
    if (customWidth) {
        if (customWidth !== 'none') {
            wrapper.style.width = customWidth;
        }
    } else {
        wrapper.style.width = width ? (width + "px") : '';
    }
    const moodleUser = await getCurrentUser().catch(error => {
        window.console.error(error);
    });

    const eduUser = {
        authorityName: moodleUser.username,
        firstName: moodleUser.firstname,
        surName: moodleUser.lastname,
        userEMail: moodleUser.email
    };
    const serviceWorkerPhp = `${Config.wwwroot}/mod/edusharing/getServiceWorker.php`;
    if ('serviceWorker' in navigator) {
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
    renderComponent.encoded_user = btoa(JSON.stringify(eduUser));
    renderComponent.service_worker_url = serviceWorkerPhp;
    renderComponent.activate_service_worker = false;
    renderComponent.assets_url = repoUrl + '/web-components/rendering-service-amd/assets';
    renderComponent.resource_url = resourceUrl;
    renderComponent.preview_url = response.previewUrl;
    wrapper.innerHTML = "";
    wrapper.appendChild(renderComponent);
};
