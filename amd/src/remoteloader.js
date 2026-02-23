export const init = (repoUrl) => {
    window.__env = {
        EDU_SHARING_API_URL: `${repoUrl}/rest`
    };

    window.__EDUSHARING_PUBLIC_PATH__ = `${repoUrl}/web-components/rendering-service-amd/`;

    require.config({
        paths: {
            esrendering: `${repoUrl}/web-components/rendering-service-amd/main`
        }
    });

    require(['esrendering']);
};
