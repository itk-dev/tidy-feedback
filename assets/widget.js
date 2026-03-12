// NOTE! THIS FILE IS A MESS AND NEEDS A CLEAN-UP!

import "./styles/widget.scss";
import { makeResizableDiv } from "./component/region";
import { makeDraggable } from "./component/draggable.js";

import { snapdom } from "@zumer/snapdom";
import unique from "@cypress/unique-selector";

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

const showMessage = (message, type = "default") => {
    const el = root.querySelector(".tidy-feedback-message");
    if (el) {
        el.classList.remove("success", "warning", "danger", "default");

        if (type) {
            el.classList.add(type);
        }

        el.innerHTML = config.messages[message] ?? message;
        el.hidden = !message;
    }
};

let start;
let cancel;
let region;
let form;
let dragCleanup;
let feedbackItems = [];
let selectedSelector = null;

const positionRegion = (rect) => {
    if (region) {
        region.parentNode.hidden = false;
        region.style.left = rect.left + "px";
        region.style.top = rect.top + "px";
        region.style.width = Math.max(rect.width, 20) + "px";
        region.style.height = Math.max(rect.height, 20) + "px";
        makeResizableDiv(region);
    }
};

const selectRegion = () => {
    positionRegion({ left: 300, top: 300, width: 300, height: 200 });
};

const hideRegion = () => {
    if (region) {
        region.parentNode.hidden = true;
    }
};

const makeFormDraggable = () => {
    if (dragCleanup) {
        return;
    }

    const tidyFeedbackDiv = getElement(".tidy-feedback-form");
    const dragHandle = getElement(".tidy-feedback-draggable-handle");

    if (tidyFeedbackDiv && dragHandle) {
        dragHandle.hidden = false;

        dragCleanup = makeDraggable(tidyFeedbackDiv, dragHandle, {
            constrainToViewport: true,
        });
    }
};

const hideFormDragHandle = () => {
    const tidyFeedbackDiv = getDocumentElement("#tidy-feedback");
    const dragHandle = tidyFeedbackDiv?.querySelector(
        ".tidy-feedback-draggable-handle",
    );

    if (dragHandle) {
        dragHandle.hidden = true;
    }
};

const renderItemsList = () => {
    if (feedbackItems.length === 0) {
        return;
    }

    // Remove existing list so we can re-render with updated data.
    form.querySelector(".tidy-feedback-items")?.remove();

    const container = document.createElement("div");
    container.className = "tidy-feedback-items";

    const toggle = document.createElement("button");
    toggle.type = "button";
    toggle.className = "tidy-feedback-items-toggle";
    const label = config.messages["Existing feedback"] ?? "Existing feedback";
    toggle.textContent = `${label} (${feedbackItems.length})`;
    toggle.addEventListener("click", () => {
        toggle.classList.toggle("open");
        list.hidden = !list.hidden;
    });

    const list = document.createElement("ul");
    list.className = "tidy-feedback-items-list";
    list.hidden = true;

    for (const item of feedbackItems) {
        const li = document.createElement("li");
        const a = document.createElement("a");
        a.href = item.url;
        a.target = "_blank";
        a.rel = "noopener noreferrer";
        a.textContent = item.description ?? "";
        li.appendChild(a);
        list.appendChild(li);
    }

    container.appendChild(toggle);
    container.appendChild(list);
    form.appendChild(container);
};

const refreshFeedbackData = () => {
    if (!config.checkUrl || !start) {
        return;
    }

    fetch(
        config.checkUrl + "?url=" + encodeURIComponent(document.location.href),
    )
        .then((response) => response.json())
        .then((json) => {
            const count = json?.data?.count;
            feedbackItems = json?.data?.items ?? [];

            // Update or create the badge on the start button.
            let badge = start.querySelector(".tidy-feedback-badge");
            if (count > 0) {
                if (!badge) {
                    badge = document.createElement("span");
                    badge.className = "tidy-feedback-badge";
                    start.appendChild(badge);
                }
                badge.textContent = count;
            } else if (badge) {
                badge.remove();
            }

            renderItemsList();
        })
        .catch(() => {
            // Silently ignore check errors.
        });
};

const showFormAfterSelection = () => {
    if (form) {
        form.hidden = false;
    }
    showMessage("");
    makeFormDraggable();
    renderItemsList();
};

const enterSelectMode = () => {
    // Inject crosshair cursor style into document head so it applies globally.
    const style = document.createElement("style");
    style.dataset.tidyFeedbackSelecting = "";
    style.textContent =
        "body.tidy-feedback-selecting, body.tidy-feedback-selecting * { cursor: crosshair !important; }";
    document.head.appendChild(style);
    document.body.classList.add("tidy-feedback-selecting");
    showMessage("Click an element to select");

    // Read the primary color from CSS custom properties for consistent styling.
    const primaryColor =
        getComputedStyle(document.documentElement)
            .getPropertyValue("--color-primary")
            .trim() || "hsla(252, 55%, 46%, 1)";
    let hoveredElement = null;

    const cleanup = () => {
        document.removeEventListener("click", onClick, true);
        document.removeEventListener("keydown", onKeydown);
        document.removeEventListener("mousemove", onMousemove);
        document.body.classList.remove("tidy-feedback-selecting");
        style.remove();
        if (hoveredElement) {
            hoveredElement.style.outline = "";
            hoveredElement = null;
        }
    };

    const onClick = (event) => {
        const target = event.target;

        // Let clicks on the widget itself pass through.
        if (
            target.closest("#tidy-feedback") ||
            target.closest("#tidy-feedback-region")
        ) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const rect = target.getBoundingClientRect();
        try {
            selectedSelector = unique(target);
        } catch {
            selectedSelector = null;
        }

        cleanup();
        positionRegion({
            left: rect.left + window.scrollX,
            top: rect.top + window.scrollY,
            width: rect.width,
            height: rect.height,
        });
        showFormAfterSelection();
    };

    const onKeydown = (event) => {
        if (event.key === "Escape") {
            cleanup();
            showMessage("");
            if (start) {
                start.hidden = false;
            }
        }
    };

    const onMousemove = (event) => {
        const target = event.target;

        if (
            target.closest("#tidy-feedback") ||
            target.closest("#tidy-feedback-region")
        ) {
            if (hoveredElement) {
                hoveredElement.style.outline = "";
                hoveredElement = null;
            }
            return;
        }

        if (hoveredElement && hoveredElement !== target) {
            hoveredElement.style.outline = "";
        }

        if (target !== hoveredElement) {
            target.style.outline = "2px dashed " + primaryColor;
            hoveredElement = target;
        }
    };

    document.addEventListener("click", onClick, true);
    document.addEventListener("keydown", onKeydown);
    document.addEventListener("mousemove", onMousemove);
};

const showForm = () => {
    if (start) {
        start.hidden = true;
    }
    selectedSelector = null;
    enterSelectMode();
};

const hideForm = (reset) => {
    hideRegion();
    hideFormDragHandle();
    if (form) {
        form.hidden = true;
        if (reset) {
            form.reset();
            selectedSelector = null;
        }
    }
    if (start) {
        start.hidden = false;
    }

    // Hide the drag handle when form is hidden
    const tidyFeedbackDiv = document.getElementById("tidy-feedback");
    const dragHandle = tidyFeedbackDiv?.querySelector(
        ".tidy-feedback-draggable-handle",
    );
    if (dragHandle) {
        dragHandle.hidden = true;
    }
};

addEventListener("load", () => {
    form = getElement(".tidy-feedback-form");
    start = getActionElement("start");
    cancel = getActionElement("cancel");
    region = getDocumentElement("#tidy-feedback-region > .resizable");

    if (form) {
        // Prefill email from localStorage if not already set.
        const emailInput = form.querySelector('[name="created_by"]');
        if (emailInput && !emailInput.value && !emailInput.readOnly) {
            const cachedEmail = localStorage.getItem("tidy-feedback-email");
            if (cachedEmail) {
                emailInput.value = cachedEmail;
            }
        }

        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            const data = {};

            showMessage("Taking screenshot …");

            try {
                const el = document.body;
                const result = await snapdom(el, { scale: 1 });

                let image = result.toRaw();
                // Check if some other image formats generate smaller payload
                for (const method of ["toWebp", "toPng", "toJpg"]) {
                    const img = await result[method]();
                    if (img.src.length < image.length) {
                        image = img.src;
                    }
                }

                data.image = image;
            } catch (error) {
                showMessage("Error taking screenshot", "danger");
            }

            showMessage("Sending feedback …");

            const formData = new FormData(form);
            // https://stackoverflow.com/a/46774073
            formData.forEach((value, key) => (data[key] = value));

            // Auto-generate subject from page title or URL path.
            data.subject = document.title || window.location.pathname;

            data.context = {
                url: document.location.href,
                referrer: document.referrer,
                document: document.documentElement.outerHTML,
                navigator: {
                    userAgent: navigator.userAgent,
                },
                window: {
                    innerWidth: window.innerWidth,
                    innerHeight: window.innerHeight,
                },
                region: {
                    left: region.style.left,
                    top: region.style.top,
                    width: region.style.width,
                    height: region.style.height,
                },
                selectedElement: selectedSelector,
            };

            fetch(form.action, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(data),
            })
                .then((response) => {
                    if (201 === response.status) {
                        // Cache email for next visit.
                        if (data.created_by) {
                            localStorage.setItem(
                                "tidy-feedback-email",
                                data.created_by,
                            );
                        }
                        if (region) {
                            region.hidden = true;
                        }
                        hideForm(true);
                        showMessage("Feedback created", "success");
                        refreshFeedbackData();
                    } else {
                        response
                            .json()
                            .then((data) =>
                                showMessage(
                                    "Error creating feedback: " +
                                        JSON.stringify(data),
                                    "danger",
                                ),
                            );
                    }
                })
                .catch((reason) => alert(reason));
        });
    }

    const s = getElement("[data-tidy-feedback-action='select-region']");
    if (s) {
        s.addEventListener("click", selectRegion);
    }

    if (start) {
        start.hidden = false;
        start.addEventListener("click", (event) => {
            showForm();
        });

        refreshFeedbackData();
    }

    if (cancel) {
        cancel.addEventListener("click", (event) => {
            showMessage("");
            // TODO Confirm if form not empty.
            hideForm(true);
        });
    }

    const params = new URLSearchParams(document.location.search);
    switch (params.get("tidy-feedback-show")) {
        case "form":
            showForm();
            break;
    }
});
