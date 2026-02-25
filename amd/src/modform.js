import {getTicket} from "./repository";

export const init = async(repoUrl, mediatype, hasRendering2) => {
    const gradeForm = document.getElementById('id_modstandardgrade');
    const gradeCompletionInput = document.getElementById('id_completionusegrade')?.closest('.row.fitem');
    if (mediatype !== 'file-h5p' || !hasRendering2) {
        if (gradeForm !== null) {
            gradeForm.classList.add('d-none');
        }
        if (gradeCompletionInput !== null) {
            gradeCompletionInput.classList.add('d-none');
        }
    }
    if (typeof window.openRepo !== 'function') {
        window.openRepo = function(url) {
            window.addEventListener('message', function handleRepo(event) {
                if (event.data.event === 'APPLY_NODE') {
                    const node = event.data.data;
                    const isH5p = node.mediatype === 'file-h5p';
                    if (isH5p && hasRendering2 && gradeForm !== null && gradeCompletionInput !== null) {
                        gradeForm.classList.remove('d-none');
                        gradeCompletionInput.classList.remove('d-none');
                    } else {
                        const gradeTypeSelect = document.getElementById('id_grade_modgrade_type');
                        gradeTypeSelect.value = 'none';
                        gradeTypeSelect.dispatchEvent(new Event('change', {bubbles: true}));
                    }
                    window.win.close();
                    window.document.getElementById('id_object_url').value = node.objectUrl;
                    let title = node.title;
                    if (!title) {
                        title = node.properties['cm:name'];
                    }
                    let version = -1;
                    let versionArray = node.properties['cclom:version'];
                    if (versionArray !== undefined) {
                        version = node.properties['cclom:version'][0];
                        window.document.getElementById('id_object_version_1').value = version;
                    }
                    let aspects = node.aspects;
                    if (aspects.includes('ccm:published') || aspects.includes('ccm:collection_io_reference') || version === -1) {
                        window.document.getElementById('id_object_version_0').checked = true;
                        window.document.getElementById('id_object_version_1').closest('label').hidden = true;
                    } else {
                        window.document.getElementById('id_object_version_1').closest('label').hidden = false;
                    }
                    window.document.getElementById('fitem_id_object_title')
                        .getElementsByClassName('form-control-static')[0].innerHTML = title;
                    if (window.document.getElementById('id_name').value === '') {
                        window.document.getElementById('id_name').value = title;
                    }
                    window.removeEventListener('message', handleRepo, false );
                }
            }, false);
            window.win = window.open(url);
        };
    }
    const repoSearch = new URL(repoUrl + '/components/search?applyDirectories=false&reurl=WINDOW');
    const repoWorkspace = new URL(repoUrl + '/components/workspace/files?applyDirectories=false&reurl=WINDOW');
    const repoCollections = new URL(repoUrl + '/components/collections?applyDirectories=false&reurl=WINDOW');
    const ajaxParams = {
        eduTicketStructure: {
            courseId: 0
        }
    };
    const repoSearchButton = document.getElementById('id_edu_search_button');
    repoSearchButton.addEventListener('click', async() => {
        const ticket = await getTicket(ajaxParams);
        const repoSearchWithTicket = new URL(repoSearch);
        repoSearchWithTicket.searchParams.set('ticket', ticket.ticket);
        window.openRepo(repoSearchWithTicket.toString());
    });
    const repoWorkspaceButton = document.getElementById('id_edu_workspace_button');
    if (repoWorkspaceButton !== null) {
        repoWorkspaceButton.addEventListener('click', async() => {
            const ticket = await getTicket(ajaxParams);
            const repoWorkspaceWithTicket = new URL(repoWorkspace);
            repoWorkspaceWithTicket.searchParams.set('ticket', ticket.ticket);
            window.openRepo(repoWorkspaceWithTicket.toString());
        });
    }
    const repoCollectionsButton = document.getElementById('id_edu_collections_button');
    if (repoCollectionsButton !== null) {
        repoCollectionsButton.addEventListener('click', async() => {
            const ticket = await getTicket(ajaxParams);
            const repoCollectionsWithTicket = new URL(repoCollections);
            repoCollectionsWithTicket.searchParams.set('ticket', ticket.ticket);
            window.openRepo(repoCollectionsWithTicket.toString());
        });
    }
};
