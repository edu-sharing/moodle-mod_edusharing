export const init = (repoUrl) => {
    window.__env = {
        EDU_SHARING_API_URL: `${repoUrl}/rest`
    };

    window.__EDUSHARING_PUBLIC_PATH__ = `${repoUrl}/web-components/rendering-service/`;

    // Helper to add scripts
    /**
     * @param {string} src
     * @param {string} type
     */
    function loadScript(src, type) {
        const script = document.createElement('script');
        script.src = src;
        if (type) {
            script.type = type;
        }
        script.async = false;
        document.head.appendChild(script);
    }

    /**
     * Function to add CSS to the page.
     * @param {string} href
     */
    function loadCSS(href) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.type = 'text/css';
        link.href = href;
        document.head.appendChild(link);
    }

    // Add the main module
    loadScript(repoUrl + '/web-components/rendering-service/main.js', 'module');

    // Add styles
    loadCSS(repoUrl + '/web-components/rendering-service/styles.css');
};
