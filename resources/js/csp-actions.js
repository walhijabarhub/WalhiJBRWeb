/**
 * Delegated replacements for inline event handlers (onclick=, onsubmit=, onmouseover=, ...).
 *
 * The Content Security Policy does not allow inline handlers, so views declare behaviour
 * with data attributes instead:
 *
 *   data-on-click="fnName"        calls window.fnName(...args, element, event) on click
 *   data-on-change / data-on-input   same, for change / input events
 *   data-args='[1, "two"]'          optional JSON array of leading arguments
 *   data-confirm="Message?"         on a <form>: asks for confirmation before submitting
 *   data-hover-style="color:#fff"   inline styles applied on mouseenter …
 *   data-rest-style="color:#000"    … and restored on mouseleave
 *   data-toggle-target="#id"        toggles the target's display between none and block
 *   data-submit-closest-form        prevents default and submits the enclosing form
 *
 * Only named global functions are called; no strings are evaluated.
 */

function parseArgs(element) {
    if (!element.dataset.args) {
        return [];
    }

    try {
        const args = JSON.parse(element.dataset.args);
        return Array.isArray(args) ? args : [args];
    } catch {
        return [];
    }
}

function callAction(element, attribute, event) {
    const handler = window[element.getAttribute(attribute)];
    if (typeof handler === 'function') {
        handler(...parseArgs(element), element, event);
    }
}

function applyStyles(element, declarations) {
    (declarations || '').split(';').forEach((declaration) => {
        const index = declaration.indexOf(':');
        if (index > 0) {
            element.style.setProperty(declaration.slice(0, index).trim(), declaration.slice(index + 1).trim());
        }
    });
}

['click', 'change', 'input'].forEach((type) => {
    const attribute = `data-on-${type}`;
    document.addEventListener(type, (event) => {
        const element = event.target.closest?.(`[${attribute}]`);
        if (element) {
            callAction(element, attribute, event);
        }
    });
});

document.addEventListener('click', (event) => {
    const toggle = event.target.closest?.('[data-toggle-target]');
    if (toggle) {
        const target = document.querySelector(toggle.dataset.toggleTarget);
        if (target) {
            target.style.display = target.style.display === 'none' ? 'block' : 'none';
        }
    }

    const submitter = event.target.closest?.('[data-submit-closest-form]');
    if (submitter) {
        event.preventDefault();
        submitter.closest('form')?.submit();
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (form instanceof HTMLFormElement && form.dataset.confirm !== undefined && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
}, true);

document.addEventListener('mouseover', (event) => {
    const element = event.target.closest?.('[data-hover-style]');
    if (element && !element.contains(event.relatedTarget)) {
        applyStyles(element, element.dataset.hoverStyle);
    }
});

document.addEventListener('mouseout', (event) => {
    const element = event.target.closest?.('[data-rest-style]');
    if (element && !element.contains(event.relatedTarget)) {
        applyStyles(element, element.dataset.restStyle);
    }
});
