import "./styles/variables.css";
import "./styles/btn.css";
import "./styles/widget.css";
import { makeResizableDiv } from "./component/region";
import { showMessage } from "./component/messages";
import { enterSelectMode } from "./component/select-mode";
import {
    renderItemsList,
    refreshFeedbackData,
    showItemsPanel,
    hideItemsPanel,
} from "./component/items-panel";
import {
    makeFormDraggable,
    hideFormDragHandle,
    prefillEmail,
    showFormAfterSelection,
    showForm,
    hideForm,
    initFormSubmit,
} from "./component/form";
import { initKeyboardShortcuts } from "./component/keyboard";

try {
    CSS.registerProperty({
        name: "--border-angle",
        syntax: "<angle>",
        inherits: false,
        initialValue: "0deg",
    });
} catch {
    // Already registered or not supported
}

const root = document.querySelector("#tidy-feedback").shadowRoot;
const widget = root ?? document;

const getElement = (selector) => widget.querySelector(selector);
const getActionElement = (action) =>
    getElement(`[data-tidy-feedback-action="${action}"]`);
const getDocumentElement = (selector) => document.querySelector(selector);

const config = (() => {
    const el = getElement("[data-tidy-feedback-config]");
    if (el) {
        try {
            return JSON.parse(el.dataset.tidyFeedbackConfig);
        } catch (error) {
            // Ignore all errors.
        }
    }
    return {};
})();

// Shared context object passed to all component modules.
const ctx = {
    config,
    form: null,
    start: null,
    startCount: null,
    region: null,
    dragCleanup: null,
    feedbackItems: [],
    selectedSelector: null,
    itemsPanelMode: false,

    getElement,
    getDocumentElement,

    showMessage: (message, type) => showMessage(root, config, message, type),
    enterSelectMode: () => enterSelectMode(ctx),
    positionRegion: (rect) => {
        if (ctx.region) {
            ctx.region.parentNode.hidden = false;
            ctx.region.style.left = rect.left + "px";
            ctx.region.style.top = rect.top + "px";
            ctx.region.style.width = Math.max(rect.width, 20) + "px";
            ctx.region.style.height = Math.max(rect.height, 20) + "px";
            makeResizableDiv(ctx.region);
        }
    },
    hideRegion: () => {
        if (ctx.region) {
            ctx.region.parentNode.hidden = true;
        }
    },
    makeFormDraggable: () => makeFormDraggable(ctx),
    hideFormDragHandle: () => hideFormDragHandle(ctx),
    showFormAfterSelection: () => showFormAfterSelection(ctx),
    showForm: () => showForm(ctx),
    hideForm: (reset) => hideForm(ctx, reset),
    showItemsPanel: () => showItemsPanel(ctx),
    hideItemsPanel: () => hideItemsPanel(ctx),
    refreshFeedbackData: () => refreshFeedbackData(ctx),
    renderItemsList: (listOnly) => renderItemsList(ctx, listOnly),
};

addEventListener("load", () => {
    ctx.form = getElement(".tidy-feedback-form");
    ctx.start = getElement(".tidy-feedback-start");
    ctx.startCount = getElement(".tidy-feedback-start-count");
    ctx.region = getDocumentElement("#tidy-feedback-region > .resizable");

    const startAdd = getActionElement("start");
    const cancel = getActionElement("cancel");

    if (ctx.form) {
        prefillEmail(ctx.form);
        initFormSubmit(ctx);
    }

    const s = getElement("[data-tidy-feedback-action='select-region']");
    if (s) {
        s.addEventListener("click", () => {
            ctx.positionRegion({
                left: 300,
                top: 300,
                width: 300,
                height: 200,
            });
        });
    }

    if (ctx.start) {
        ctx.start.hidden = false;

        if (startAdd) {
            startAdd.addEventListener("click", () => {
                ctx.showForm();
            });
        }

        if (ctx.startCount) {
            ctx.startCount.addEventListener("click", () => {
                ctx.showItemsPanel();
            });
        }

        ctx.refreshFeedbackData();
    }

    if (cancel) {
        cancel.addEventListener("click", () => {
            ctx.showMessage("");
            ctx.hideForm(true);
        });
    }

    initKeyboardShortcuts(ctx);

    const params = new URLSearchParams(document.location.search);
    switch (params.get("tidy-feedback-show")) {
        case "form":
            ctx.showForm();
            break;
    }
});
