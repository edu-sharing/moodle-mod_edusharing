export const validateOrigin = (origin, configuredUrl) => {
    try {
        const originUrl = new URL(origin);
        const configUrl = new URL(configuredUrl);
        return originUrl.protocol === configUrl.protocol && originUrl.hostname === configUrl.hostname;
    } catch (e) {
        return false;
    }
};
