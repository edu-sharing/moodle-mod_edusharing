/**
 * Shields Moodle's RequireJS from the AMD wrapper of the edu-sharing web components.
 *
 * The web-component bundles contain a UMD build of lodash. As soon as such a bundle is
 * evaluated on a page that carries RequireJS, lodash sees the global `define` plus
 * `define.amd` and registers itself as an *anonymous* AMD module. RequireJS can only
 * attribute an anonymous define to a module name while it is evaluating a script it has
 * injected itself, so the entry ends up in RequireJS' global define queue. From that
 * moment on
 *
 *  - the next `require([...])` call aborts with
 *    "Mismatched anonymous define() module: function(){return Ca}", which silently kills
 *    unrelated Moodle features (the activity chooser, modals, the icon system, ...), or
 *  - the next Moodle module that finishes loading gets lodash assigned as its export.
 *
 * Because inline objects are rendered lazily (IntersectionObserver), the bundle is often
 * evaluated long after page load - while the user is interacting with the page - which is
 * why the breakage looks random and gets more likely the more objects a page contains.
 *
 * Every Moodle AMD module carries its own name, so an anonymous define can only come from
 * a foreign bundle. Dropping those - and only those - keeps the queue clean.
 *
 * Note: do NOT try to fix this by hiding `define.amd` from foreign scripts instead. Moodle
 * compiles dynamic import() through its system-import-transformer, and the resulting code
 * reads `window.define.amd` to decide whether to go through RequireJS. That read happens in
 * an async continuation, i.e. with no current script, so it is indistinguishable from a
 * foreign caller - hiding `define.amd` makes core/icon_system fall back to a global lookup
 * and fail with "IconSystem is not a constructor".
 */

const GUARD_FLAG = '__eduSharingRequireJsGuard';

/**
 * A define() call belongs to RequireJS when it happens while RequireJS is evaluating a
 * script it has injected itself - those script nodes carry a data-requiremodule attribute.
 * Bundles loaded as ES modules or from an async callback have no current script at all.
 *
 * @returns {boolean}
 */
const calledByRequireJs = () => {
    const script = document.currentScript;
    return Boolean(script && script.getAttribute('data-requiremodule') !== null);
};

/**
 * Installs the guard. Safe to call more than once.
 *
 * @returns {void}
 */
export const install = () => {
    const originalDefine = window.define;
    if (typeof originalDefine !== 'function' || !originalDefine.amd || originalDefine[GUARD_FLAG]) {
        return;
    }
    const guardedDefine = function(...args) {
        // An anonymous define (no module name as first argument) that RequireJS cannot
        // attribute to a script it is loading is exactly what poisons the queue - drop it
        // instead of handing it over.
        if (typeof args[0] !== 'string' && !calledByRequireJs()) {
            return undefined;
        }
        return originalDefine.apply(this, args);
    };
    Object.keys(originalDefine).forEach(key => {
        guardedDefine[key] = originalDefine[key];
    });
    guardedDefine[GUARD_FLAG] = true;
    window.define = guardedDefine;
};
